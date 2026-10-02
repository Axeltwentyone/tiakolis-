<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Les admins reçoivent les e-mails de commandes et de paiements (désactivable par compte). */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('recoit_mails')->default(true)->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('recoit_mails');
        });
    }
};
