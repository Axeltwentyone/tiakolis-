<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_site_affiche_les_medias_de_la_base(): void
    {
        $this->get('/')->assertOk()
            ->assertSee(asset('assets/logotiako.png'), false)
            ->assertSee(asset('assets/shoot-6574.mp4'), false)
            ->assertSee('Dos · Warning');
    }

    public function test_changer_une_colonne_le_logo_et_les_teles(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());
        $this->get('/admin/site')->assertOk()->assertSee('Premier écran');

        // colonne 1 : une photo à la place de la vidéo, nouvelle légende
        $col = Media::where('zone', 'hero')->orderBy('ordre')->first();
        $this->put("/admin/site/{$col->id}", ['fichier' => UploadedFile::fake()->image('col.jpg', 540, 960), 'legende' => 'Nouvelle légende'])->assertRedirect();
        $col->refresh();
        Storage::disk('public')->assertExists($col->fichier);
        $this->assertNull($col->poster); // une photo n'a pas d'image d'attente
        $this->get('/')->assertSee(Storage::disk('public')->url($col->fichier), false)->assertSee('Nouvelle légende');

        // logo : une vidéo est refusée
        $logo = Media::where('zone', 'logo')->first();
        $this->put("/admin/site/{$logo->id}", ['fichier' => UploadedFile::fake()->create('x.mp4', 100, 'video/mp4')])->assertSessionHasErrors('fichier');

        // mur de télés : ajouter 2 images puis en retirer une
        $avant = Media::where('zone', 'tv_image')->count();
        $this->post('/admin/site/tv', ['images' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')]])->assertRedirect();
        $this->assertSame($avant + 2, Media::where('zone', 'tv_image')->count());
        $ajoutee = Media::where('zone', 'tv_image')->latest('id')->first();
        $this->delete("/admin/site/{$ajoutee->id}")->assertRedirect();
        Storage::disk('public')->assertMissing($ajoutee->fichier);
        $this->assertSame($avant + 1, Media::where('zone', 'tv_image')->count());

        // on ne supprime pas le logo
        $this->delete("/admin/site/{$logo->id}")->assertForbidden();
    }
}
