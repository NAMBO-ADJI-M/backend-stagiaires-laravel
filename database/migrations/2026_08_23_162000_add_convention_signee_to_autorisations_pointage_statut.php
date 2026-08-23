<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // On modifie l'enum pour ajouter CONVENTION_SIGNEE
        // Note: SQLite ne supporte pas bien la modification d'enum,
        // mais sur MySQL/PostgreSQL c'est OK. Pour SQLite, Laravel recrée la table.
        Schema::table('autorisations_pointage', function (Blueprint $table) {
            $table->enum('statut', ['ACTIVE', 'INACTIVE', 'EN_ATTENTE', 'REFUSEE', 'CONVENTION_SIGNEE'])
                  ->default('INACTIVE')
                  ->change();
        });
    }

    public function down(): void
    {
        Schema::table('autorisations_pointage', function (Blueprint $table) {
            $table->enum('statut', ['ACTIVE', 'INACTIVE', 'EN_ATTENTE', 'REFUSEE'])
                  ->default('INACTIVE')
                  ->change();
        });
    }
};
