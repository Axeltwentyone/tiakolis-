<?php

namespace Tests\Feature;

use App\Enums\Statut;
use App\Mail\CaptureRecue;
use App\Mail\PaiementValide;
use App\Mail\PaiementValideEquipe;
use App\Models\Precommande;
use App\Models\Stock;
use App\Models\User;
use App\Support\Courrier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrecommandeTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function stock(string $slug, string $taille): int
    {
        return Stock::whereHas('produit', fn ($q) => $q->where('slug', $slug))->where('taille', $taille)->value('quantite');
    }

    public function test_le_catalogue_donne_stock_communes_et_liens_de_paiement(): void
    {
        config(['services.precommandes.wave_numero' => '+225 07 88 11 72 61']);
        $this->getJson('/api/catalogue')->assertOk()
            ->assertJsonPath('pieces.0.stock.M', 40)
            ->assertJsonPath('communes.5', 'Cocody')
            ->assertJsonPath('paiement.wave', '+225 07 88 11 72 61')
            ->assertJsonPath('paiement.lien', 'https://pay.wave.com/m/M_ci_3gSXyQLySdf3/c/ci/?amount={montant}')
            ->assertJsonPath('paiement.om', fn ($om) => str_starts_with($om, 'https://multi.app.orange-money.com/') && str_ends_with($om, 'amount={montant}'));
    }

    public function test_la_verification_ne_cree_rien_et_donne_le_montant(): void
    {
        Mail::fake();
        $this->postJson('/api/precommandes/verifier', $this->client() + ['articles' => [['piece' => 'melo-noir', 'taille' => 'M', 'quantite' => 2]]])
            ->assertOk()->assertJsonPath('total', 25000)->assertJsonPath('adresse', 'Palmeraie, Cocody (Abidjan)');

        $this->assertSame(0, Precommande::count());      // aucune précommande sans paiement
        $this->assertSame(40, $this->stock('melo-noir', 'M')); // rien n'est réservé
        Mail::assertNothingSent();
    }

    public function test_la_verification_previent_si_le_stock_manque(): void
    {
        Stock::whereHas('produit', fn ($q) => $q->where('slug', 'melo-noir'))->where('taille', 'M')->update(['quantite' => 1]);
        $this->postJson('/api/precommandes/verifier', $this->client() + ['articles' => [['piece' => 'melo-noir', 'taille' => 'M', 'quantite' => 2]]])
            ->assertStatus(409)->assertJson(['erreur' => 'Plus que 1 en M pour Mélo Décalé · Noir.']);
    }

    public function test_sans_capture_de_paiement_pas_de_precommande(): void
    {
        $this->precommander([['piece' => 'melo-noir', 'taille' => 'M', 'quantite' => 1]], avecCapture: false)
            ->assertStatus(422)->assertJson(['erreur' => "Envoie la capture d'écran de ton paiement : sans paiement, la précommande n'est pas validée."]);
        $this->assertSame(0, Precommande::count());
        $this->assertSame(40, $this->stock('melo-noir', 'M'));
    }

    public function test_avec_la_capture_la_precommande_est_creee_et_l_equipe_prevenue(): void
    {
        Mail::fake();
        config(['services.precommandes.notification_email' => 'equipe@example.test']);

        $r = $this->precommander([['piece' => 'melo-noir', 'taille' => 'M', 'quantite' => 2]])->assertCreated()->assertJsonPath('total', 25000);

        $this->assertMatchesRegularExpression('/^TEF-[A-Z]{5}$/', $r->json('reference'));
        $p = Precommande::first();
        $this->assertSame(Statut::AVerifier, $p->statut); // directement « Capture à vérifier »
        Storage::disk('local')->assertExists($p->capture);
        $this->assertSame(38, $this->stock('melo-noir', 'M'));
        Mail::assertSent(CaptureRecue::class, fn ($m) => $m->hasTo('equipe@example.test') && count($m->attachments()) === 1);
        // rien au client tant que le paiement n'est pas validé
        Mail::assertNotSent(Mailable::class, fn ($m) => $m->hasTo('favor@example.test'));
    }

    public function test_on_ne_vend_pas_plus_que_le_stock(): void
    {
        Stock::whereHas('produit', fn ($q) => $q->where('slug', 'melo-noir'))->where('taille', 'M')->update(['quantite' => 1]);

        $this->precommander([['piece' => 'melo-noir', 'taille' => 'M', 'quantite' => 2]])
            ->assertStatus(409)->assertJson(['erreur' => 'Plus que 1 en M pour Mélo Décalé · Noir. Ton paiement est bien parti : écris-nous sur WhatsApp avec ta capture, on te propose une autre taille ou on te rembourse.']);
        $this->assertSame(0, Precommande::count());
        $this->assertSame([], Storage::disk('local')->allFiles('captures')); // capture non gardée
    }

    public function test_annuler_remet_en_stock_et_reactiver_le_reprend(): void
    {
        $this->precommander([['piece' => 'melo-noir', 'taille' => 'M', 'quantite' => 3]])->assertCreated();
        $p = Precommande::first();

        $p->update(['statut' => Statut::Annulee]);
        $this->assertSame(40, $this->stock('melo-noir', 'M'));
        $p->update(['statut' => Statut::Payee]);
        $this->assertSame(37, $this->stock('melo-noir', 'M'));
    }

    public function test_formulaire_invalide(): void
    {
        $this->postJson('/api/precommandes/verifier', ['articles' => []])->assertStatus(400)->assertJson(['erreur' => 'Ton panier est vide.']);
        $this->postJson('/api/precommandes/verifier', $this->client(['commune' => 'Bouaké']) + ['articles' => [['piece' => 'melo-noir', 'taille' => 'M', 'quantite' => 1]]])
            ->assertStatus(400)->assertJson(['erreur' => 'Choisis ta commune à Abidjan.']);
    }

    public function test_l_e_mail_est_obligatoire(): void
    {
        $client = $this->client();
        unset($client['email']);
        $this->postJson('/api/precommandes/verifier', $client + ['articles' => [['piece' => 'melo-noir', 'taille' => 'M', 'quantite' => 1]]])
            ->assertStatus(400)->assertJson(['erreur' => 'Indique ton e-mail : on y envoie la confirmation de ta précommande.']);
    }

    public function test_valider_le_paiement_previent_le_client_et_toute_l_equipe(): void
    {
        Mail::fake();
        config(['services.precommandes.notification_email' => 'equipe@example.test']);
        User::factory()->create(['email' => 'admin2@example.test']);
        User::factory()->create(['email' => 'admin3@example.test', 'recoit_mails' => false]);

        $this->precommander([['piece' => 'melo-noir', 'taille' => 'M', 'quantite' => 1]])->assertCreated();
        $equipe = Courrier::equipe(); // NOTIFICATION_EMAIL + admins qui reçoivent les e-mails
        $this->assertContains('admin2@example.test', $equipe);
        $this->assertNotContains('admin3@example.test', $equipe); // a refusé
        Mail::assertSent(CaptureRecue::class, count($equipe));

        Precommande::first()->update(['statut' => Statut::Payee]);
        Mail::assertSent(PaiementValide::class, fn ($m) => $m->hasTo('favor@example.test'));
        Mail::assertSent(PaiementValideEquipe::class, count($equipe));
    }
}
