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
        Schema::create('produits', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique(); // identifiant utilisé par le site (ex. melo-noir)
            $table->string('type')->default('T-shirt oversize');
            $table->string('nom');
            $table->string('couleur');
            $table->unsignedInteger('prix'); // en FCFA
            $table->string('image_face');
            $table->string('image_dos');
            $table->boolean('actif')->default(true);
            $table->unsignedSmallInteger('ordre')->default(0); // ordre d'apparition au scroll
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produits');
    }
};
