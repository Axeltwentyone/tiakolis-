<?php

namespace Tests\Feature;

use App\Enums\Statut;
use App\Mail\CaptureRecue;
use App\Mail\NouvellePrecommande;
use App\Mail\PrecommandeRecue;
use App\Models\Precommande;
use App\Models\Stock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PrecommandeTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function stock(string $slug, string $taille): int
    {
        return Stock::whereHas('produit', fn ($q) => $q->where('slug', $slug))->where('taille', $taille)->value('quantite');
    }

    private function client(array $plus = []): array
    {
        return $plus + ['nom' => 'Favor', 'telephone' => '0700235034', 'quartier' => 'Palmeraie', 'commune' => 'Cocody'];
    }

    private function commande(array $articles, array $client = []): TestResponse
    {
        return $this->postJson('/api/precommandes', $this->client($client) + ['articles' => $articles]);
    }

    public function test_le_catalogue_donne_stock_communes_et_numero_wave(): void
    {
        config(['services.precommandes.wave_numero' => '+225 07 88 11 72 61']);
        $this->getJson('/api/catalogue')->assertOk()
            ->assertJsonPath('pieces.0.stock.M', 40)
            ->assertJsonPath('communes.5', 'Cocody')
            ->assertJsonPath('paiement.wave', '+225 07 88 11 72 61');
    }

    public function test_une_commande_reserve_le_stock_et_previent_l_equipe(): void
    {
        Mail::fake();
        config(['services.precommandes.notification_email' => 'equipe@example.test']);

        $r = $this->commande([['piece' => 'melo-noir', 'taille' => 'M', 'quantite' => 2]])->assertCreated()
            ->assertJsonPath('total', 25000)
            ->assertJsonPath('adresse', 'Palmeraie, Cocody (Abidjan)');

        $this->assertMatchesRegularExpression('/^TEF-[A-Z]{5}$/', $r->json('reference'));
        $this->assertSame(38, $this->stock('melo-noir', 'M'));
        Mail::assertSent(NouvellePrecommande::class, fn ($m) => $m->hasTo('equipe@example.test'));
        Mail::assertNotSent(PrecommandeRecue::class); // pas d'e-mail client : il est facultatif
    }

    public function test_modifier_mes_coordonnees_met_a_jour_sans_dupliquer(): void
    {
        $r = $this->commande([['piece' => 'melo-noir', 'taille' => 'M', 'quantite' => 2]]);

        $this->putJson('/api/precommandes/'.$r->json('reference'), $this->client(['quartier' => 'Riviera 3']) + [
            'articles' => [['piece' => 'melo-noir', 'taille' => 'L', 'quantite' => 1]],
        ], ['X-Jeton' => $r->json('jeton')])->assertOk()->assertJsonPath('total', 12500);

        $this->assertSame(1, Precommande::count());
        $this->assertSame('Riviera 3', Precommande::first()->quartier);
        $this->assertSame(40, $this->stock('melo-noir', 'M')); // rendu
        $this->assertSame(39, $this->stock('melo-noir', 'L')); // repris
    }

    public function test_sans_le_jeton_on_ne_touche_pas_a_la_commande(): void
    {
        $r = $this->commande([['piece' => 'melo-noir', 'taille' => 'M', 'quantite' => 1]]);
        $ref = $r->json('reference');

        $this->putJson("/api/precommandes/{$ref}", $this->client() + ['articles' => [['piece' => 'melo-noir', 'taille' => 'M', 'quantite' => 1]]], ['X-Jeton' => 'faux'])->assertNotFound();
        $this->post("/api/precommandes/{$ref}/capture", ['capture' => UploadedFile::fake()->image('c.png')], ['X-Jeton' => 'faux', 'Accept' => 'application/json'])->assertNotFound();
    }

    public function test_la_capture_wave_arrive_au_back_office_et_par_mail(): void
    {
        Mail::fake();
        Storage::fake('local');
        config(['services.precommandes.notification_email' => 'equipe@example.test']);
        $r = $this->commande([['piece' => 'melo-noir', 'taille' => 'M', 'quantite' => 1]]);

        $this->post('/api/precommandes/'.$r->json('reference').'/capture', ['capture' => UploadedFile::fake()->image('wave.png', 600, 1200)], ['X-Jeton' => $r->json('jeton'), 'Accept' => 'application/json'])
            ->assertOk()->assertJsonPath('statut', 'a_verifier');

        $p = Precommande::first();
        $this->assertSame(Statut::AVerifier, $p->statut);
        Storage::disk('local')->assertExists($p->capture);
        Mail::assertSent(CaptureRecue::class, fn ($m) => $m->hasTo('equipe@example.test') && count($m->attachments()) === 1);

        // capture envoyée : la commande n'est plus modifiable par le client
        $this->putJson('/api/precommandes/'.$p->reference, $this->client() + ['articles' => [['piece' => 'melo-noir', 'taille' => 'M', 'quantite' => 1]]], ['X-Jeton' => $r->json('jeton')])->assertStatus(409);
    }

    public function test_on_ne_vend_pas_plus_que_le_stock(): void
    {
        Stock::whereHas('produit', fn ($q) => $q->where('slug', 'melo-noir'))->where('taille', 'M')->update(['quantite' => 1]);

        $this->commande([['piece' => 'melo-noir', 'taille' => 'M', 'quantite' => 2]])
            ->assertStatus(409)->assertJson(['erreur' => 'Plus que 1 en M pour Mélo Décalé · Noir.']);
        $this->assertSame(0, Precommande::count());
    }

    public function test_annuler_remet_en_stock_et_reactiver_le_reprend(): void
    {
        $this->commande([['piece' => 'melo-noir', 'taille' => 'M', 'quantite' => 3]])->assertCreated();
        $p = Precommande::first();

        $p->update(['statut' => Statut::Annulee]);
        $this->assertSame(40, $this->stock('melo-noir', 'M'));
        $p->update(['statut' => Statut::Payee]);
        $this->assertSame(37, $this->stock('melo-noir', 'M'));
    }

    public function test_formulaire_invalide(): void
    {
        $this->postJson('/api/precommandes', ['articles' => []])->assertStatus(400)->assertJson(['erreur' => 'Ton panier est vide.']);
        $this->commande([['piece' => 'melo-noir', 'taille' => 'M', 'quantite' => 1]], ['commune' => 'Bouaké'])
            ->assertStatus(400)->assertJson(['erreur' => 'Choisis ta commune à Abidjan.']);
    }

    public function test_valider_le_paiement_previent_le_client_et_toute_l_equipe(): void
    {
        Mail::fake();
        config(['services.precommandes.notification_email' => 'equipe@example.test']);
        \App\Models\User::factory()->create(['email' => 'admin2@example.test']);
        \App\Models\User::factory()->create(['email' => 'admin3@example.test', 'recoit_mails' => false]);

        $this->commande([['piece' => 'melo-noir', 'taille' => 'M', 'quantite' => 1]], ['email' => 'favor@example.test'])->assertCreated();
        $equipe = \App\Support\Courrier::equipe(); // NOTIFICATION_EMAIL + admins qui reçoivent les e-mails
        $this->assertContains('admin2@example.test', $equipe);
        $this->assertNotContains('admin3@example.test', $equipe); // a refusé
        Mail::assertSent(NouvellePrecommande::class, count($equipe));

        Precommande::first()->update(['statut' => Statut::Payee]);
        Mail::assertSent(\App\Mail\PaiementValide::class, fn ($m) => $m->hasTo('favor@example.test'));
        Mail::assertSent(\App\Mail\PaiementValideEquipe::class, count($equipe));
        Mail::assertNotSent(\App\Mail\PaiementValideEquipe::class, fn ($m) => $m->hasTo('admin3@example.test'));
    }
}
