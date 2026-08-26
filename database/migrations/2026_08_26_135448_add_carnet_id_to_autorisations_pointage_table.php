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
        Schema::table('autorisations_pointage', function (Blueprint $table) {
            $table->uuid('carnet_id')->nullable()->after('entreprise_id');
            $table->foreign('carnet_id')->references('id')->on('carnets_de_stage')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('autorisations_pointage', function (Blueprint $table) {
            $table->dropForeign(['carnet_id']);
            $table->dropColumn('carnet_id');
        });
    }
};
