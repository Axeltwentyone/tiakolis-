<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('precommandes', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 12)->unique(); // TEF-XXXXX, donnée au client
            $table->string('jeton', 64);                // empreinte du jeton qui autorise le client à modifier / envoyer sa capture
            $table->string('nom', 80);
            $table->string('telephone', 30)->index();   // WhatsApp
            $table->string('email', 120)->nullable();
            $table->string('commune', 40);
            $table->string('quartier', 120);            // quartier et repère pour le livreur
            $table->text('note_client')->nullable();    // infos pour le livreur
            $table->string('livraison', 20)->default('yango');
            $table->unsignedInteger('total');           // en FCFA, recalculé côté serveur (hors course Yango)
            $table->string('statut', 20)->default('en_attente')->index();
            $table->string('capture')->nullable();      // capture du paiement Wave (disque privé)
            $table->timestamp('capture_le')->nullable();
            $table->text('note')->nullable();           // note interne du back office
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('precommandes');
    }
};
