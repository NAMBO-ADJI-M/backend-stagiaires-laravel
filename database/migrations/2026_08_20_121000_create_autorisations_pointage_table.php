<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('autorisations_pointage', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('stagiaire_id');
            $table->uuid('entreprise_id');
            $table->enum('statut', ['ACTIVE', 'INACTIVE', 'EN_ATTENTE', 'REFUSEE'])->default('INACTIVE');
            $table->timestamps();

            $table->foreign('stagiaire_id')->references('id')->on('stagiaires')->onDelete('cascade');
            $table->foreign('entreprise_id')->references('id')->on('entreprises')->onDelete('cascade');

            // Un seul lien d'autorisation par binôme stagiaire/entreprise
            $table->unique(['stagiaire_id', 'entreprise_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('autorisations_pointage');
    }
};
