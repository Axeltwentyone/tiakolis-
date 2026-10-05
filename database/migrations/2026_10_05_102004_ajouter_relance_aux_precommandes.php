<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Relances des clients en attente de paiement (bouton du tableau de bord). */
    public function up(): void
    {
        Schema::table('precommandes', function (Blueprint $table) {
            $table->timestamp('relance_le')->nullable()->after('capture_le');
            $table->unsignedSmallInteger('relances')->default(0)->after('relance_le');
        });
    }

    public function down(): void
    {
        Schema::table('precommandes', function (Blueprint $table) {
            $table->dropColumn(['relance_le', 'relances']);
        });
    }
};
