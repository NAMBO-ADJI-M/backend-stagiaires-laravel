<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('autorisations_pointage', function (Blueprint $table) {
            $table->date('stagiaire_date_naissance')->nullable()->after('stagiaire_id');
            $table->string('stagiaire_adresse')->nullable()->after('stagiaire_date_naissance');
            $table->string('stagiaire_telephone')->nullable()->after('stagiaire_adresse');
        });
    }

    public function down(): void
    {
        Schema::table('autorisations_pointage', function (Blueprint $table) {
            $table->dropColumn(['stagiaire_date_naissance', 'stagiaire_adresse', 'stagiaire_telephone']);
        });
    }
};
