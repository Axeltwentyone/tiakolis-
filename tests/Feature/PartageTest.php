<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartageTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_page_donne_l_apercu_de_partage_et_les_icones(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('<meta property="og:image" content="'.url('/og-image.jpg').'">', false)
            ->assertSee('<meta property="og:title" content="Tiakolisé et fière × Mélo Décalé">', false)
            ->assertSee('<meta name="twitter:card" content="summary_large_image">', false)
            ->assertSee('<link rel="apple-touch-icon" href="/apple-touch-icon.png">', false);

        foreach (['og-image.jpg', 'favicon.ico', 'favicon-32.png', 'apple-touch-icon.png', 'icon-512.png', 'site.webmanifest'] as $f) {
            $this->assertFileExists(public_path($f));
        }
        [$l, $h] = getimagesize(public_path('og-image.jpg'));
        $this->assertSame([1200, 630], [$l, $h]);
    }

    public function test_les_pages_du_back_office_ont_l_icone(): void
    {
        $this->get('/admin/connexion')->assertOk()->assertSee('/favicon-32.png', false);
    }
}
