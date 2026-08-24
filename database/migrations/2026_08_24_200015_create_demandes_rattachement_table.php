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
        Schema::create('demandes_rattachement', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('stagiaire_id');
            $table->uuid('entreprise_id');
            $table->enum('statut', ['en_attente', 'traitee', 'refusee'])->default('en_attente');
            $table->timestamps();

            $table->foreign('stagiaire_id')->references('id')->on('stagiaires')->onDelete('cascade');
            $table->foreign('entreprise_id')->references('id')->on('entreprises')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demandes_rattachement');
    }
};
