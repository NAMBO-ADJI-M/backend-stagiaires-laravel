<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entreprises', function (Blueprint $row) {
            $row->time('heure_fin_journee')->default('17:30:00')->after('adresse_libelle');
        });
    }

    public function down(): void
    {
        Schema::table('entreprises', function (Blueprint $row) {
            $row->dropColumn('heure_fin_journee');
        });
    }
};
