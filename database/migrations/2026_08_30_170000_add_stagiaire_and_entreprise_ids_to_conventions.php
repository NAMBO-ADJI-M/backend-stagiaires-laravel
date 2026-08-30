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
        Schema::table('conventions', function (Blueprint $table) {
            $table->uuid('stagiaire_id')->nullable()->after('autorisation_pointage_id');
            $table->uuid('entreprise_id')->nullable()->after('stagiaire_id');

            $table->foreign('stagiaire_id')->references('id')->on('stagiaires')->onDelete('cascade');
            $table->foreign('entreprise_id')->references('id')->on('entreprises')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conventions', function (Blueprint $table) {
            $table->dropForeign(['stagiaire_id']);
            $table->dropForeign(['entreprise_id']);
            $table->dropColumn(['stagiaire_id', 'entreprise_id']);
        });
    }
};
