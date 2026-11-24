<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Paiements en ligne (Jeko) : le panier et les coordonnées attendent ici que le paiement soit confirmé.
     * La précommande n'est créée qu'à la confirmation (aucune précommande sans paiement).
     */
    public function up(): void
    {
        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 12)->unique();       // TEF-XXXXX, reprise par la précommande
            $table->string('fournisseur', 20)->default('jeko');
            $table->string('moyen', 20);                      // orange, wave, mtn, moov, djamo
            $table->unsignedInteger('montant');               // FCFA
            $table->json('donnees');                          // panier + coordonnées du client
            $table->string('externe_id')->nullable()->index(); // id de la demande de paiement chez Jeko
            $table->string('statut', 20)->default('en_cours')->index(); // en_cours, paye, echec
            $table->foreignId('precommande_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('precommandes', function (Blueprint $table) {
            $table->string('paiement', 30)->nullable()->after('statut'); // ex. « jeko · orange » ; vide = capture d'écran
        });
    }

    public function down(): void
    {
        Schema::table('precommandes', fn (Blueprint $table) => $table->dropColumn('paiement'));
        Schema::dropIfExists('paiements');
    }
};
