<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carnets_de_stage', function (Blueprint $table) {
            $table->string('poste')->nullable()->after('niveau_formation_id');
            $table->string('entreprise_nom')->nullable()->after('poste');
        });
    }

    public function down(): void
    {
        Schema::table('carnets_de_stage', function (Blueprint $table) {
            $table->dropColumn(['poste', 'entreprise_nom']);
        });
    }
};