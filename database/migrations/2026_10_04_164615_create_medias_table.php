<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Photos et vidéos du site, modifiables depuis le back office (« Photos du site »). */
    public function up(): void
    {
        Schema::create('medias', function (Blueprint $table) {
            $table->id();
            $table->string('zone', 20)->index();    // logo, hero, tv_image, tv_video, partage
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->string('fichier');              // image ou vidéo : assets/… (livré avec le site) ou site/… (envoyé depuis le back office)
            $table->string('poster')->nullable();   // image affichée pendant le chargement d'une vidéo
            $table->string('legende', 60)->nullable();
            $table->timestamps();
        });

        // les médias actuels du site, pour que rien ne change tant qu'on ne les remplace pas
        $l = fn ($zone, $ordre, $fichier, $poster = null, $legende = null) => compact('zone', 'ordre', 'fichier', 'poster', 'legende') + ['created_at' => now(), 'updated_at' => now()];
        $lignes = [
            $l('logo', 0, 'assets/logotiako.png'),
            $l('hero', 0, 'assets/shoot-6574.mp4', 'assets/shoot-6574.jpg', 'Dos · Warning'),
            $l('hero', 1, 'assets/shoot-6571.mp4', 'assets/shoot-6571.jpg', 'Face · Mélo Décalé'),
            $l('hero', 2, 'assets/shoot-6572.mp4', 'assets/shoot-6572.jpg', 'Blanc · Rouge'),
            $l('hero', 3, 'assets/shoot-6573.mp4', 'assets/shoot-6573.jpg', 'Abidjan'),
            $l('tv_video', 0, 'assets/shoot-6569.mp4', 'assets/shoot-6569.jpg'),
            $l('tv_video', 1, 'assets/shoot-6570.mp4', 'assets/shoot-6570.jpg'),
            $l('partage', 0, 'og-image.jpg'),
        ];
        foreach (range(1, 14) as $i) {
            $lignes[] = $l('tv_image', $i, sprintf('assets/tv/still-%02d.jpg', $i));
        }
        DB::table('medias')->insert($lignes);
    }

    public function down(): void
    {
        Schema::dropIfExists('medias');
    }
};
