<?php

namespace Tests\Feature;

use App\Models\Precommande;
use App\Models\Produit;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_le_back_office_demande_une_connexion(): void
    {
        $this->get('/admin')->assertRedirect('/admin/connexion');
        $this->get('/admin/connexion')->assertOk()->assertSee('Back office');
    }

    public function test_connexion_et_pages(): void
    {
        $user = User::factory()->create(['password' => 'secret-de-test']);
        $this->post('/admin/connexion', ['email' => $user->email, 'password' => 'secret-de-test'])->assertRedirect('/admin');

        $this->postJson('/api/precommandes', [
            'articles' => [['piece' => 'melo-noir', 'taille' => 'S', 'quantite' => 1]],
            'nom' => 'Awa', 'telephone' => '0700000001', 'quartier' => 'Riviera', 'commune' => 'Cocody',
        ])->assertCreated();
        $ref = Precommande::first()->reference;

        $this->get('/admin')->assertOk()->assertSee($ref);
        $this->get('/admin/precommandes?statut=en_attente&q=awa')->assertOk()->assertSee('Awa');
        $this->get('/admin/precommandes?q='.$ref)->assertOk()->assertSee($ref);
        $this->get('/admin/precommandes/1')->assertOk()->assertSee('Mélo Décalé · Noir')->assertSee('Pas encore de capture');
        $this->get('/admin/precommandes/1/capture')->assertNotFound();
        $this->get('/admin/precommandes/export')->assertOk()->assertDownload();
        $this->get('/admin/pieces')->assertOk()->assertSee('Tiakolisé');
        $this->get('/admin/pieces/1/edit')->assertOk();
    }

    public function test_le_stock_modifie_tient_compte_des_precommandes_passees_entre_temps(): void
    {
        $this->actingAs(User::factory()->create());
        $p = Produit::where('slug', 'melo-noir')->first();
        // l'admin ouvre la fiche (M = 40) ; pendant ce temps un client prend 2 M (→ 38)
        Stock::where('produit_id', $p->id)->where('taille', 'M')->update(['quantite' => 38]);

        $this->put("/admin/pieces/{$p->id}", [
            'nom' => $p->nom, 'couleur' => $p->couleur, 'type' => $p->type, 'prix' => 15000, 'slug' => $p->slug, 'actif' => 1,
            'stock' => ['S' => 20, 'M' => 50, 'L' => 40, 'XL' => 20],
            'stock_initial' => ['S' => 20, 'M' => 40, 'L' => 40, 'XL' => 20],
        ])->assertRedirect('/admin/pieces');

        // +10 ajoutés par l'admin, les 2 du client restent vendus ; la photo d'origine est gardée
        $this->assertSame(48, Stock::where('produit_id', $p->id)->where('taille', 'M')->value('quantite'));
        $this->assertSame(15000, $p->fresh()->prix);
        $this->assertSame('assets/noir-melo-front.webp', $p->fresh()->image_face);
    }

    public function test_nouvelle_piece_avec_photos(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());

        $this->post('/admin/pieces', [
            'nom' => 'Mélo Décalé', 'couleur' => 'Rouge', 'type' => 'T-shirt oversize', 'prix' => 12500, 'slug' => 'melo-rouge', 'actif' => 1,
            'stock' => ['S' => 5, 'M' => 10, 'L' => 10, 'XL' => 5],
            'image_face' => UploadedFile::fake()->image('face.png', 800, 800),
            'image_dos' => UploadedFile::fake()->image('dos.png', 800, 800),
        ])->assertRedirect('/admin/pieces');

        $piece = Produit::where('slug', 'melo-rouge')->first();
        Storage::disk('public')->assertExists($piece->image_face);
        $this->assertSame(30, $piece->stocks()->sum('quantite'));
        $this->getJson('/api/catalogue')->assertJsonPath('pieces.4.id', 'melo-rouge');
    }

    public function test_une_piece_deja_precommandee_ne_se_supprime_pas(): void
    {
        $this->actingAs(User::factory()->create());
        $this->postJson('/api/precommandes', [
            'articles' => [['piece' => 'melo-noir', 'taille' => 'S', 'quantite' => 1]],
            'nom' => 'Awa', 'telephone' => '0700000001', 'quartier' => 'Riviera', 'commune' => 'Cocody',
        ]);

        $this->delete('/admin/pieces/1');
        $this->assertNotNull(Produit::find(1));
        $this->assertSame(1, Precommande::count());
    }
}
