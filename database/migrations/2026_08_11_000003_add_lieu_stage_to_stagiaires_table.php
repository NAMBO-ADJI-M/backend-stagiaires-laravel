<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stagiaires', function (Blueprint $table) {
            // Distinct de domicile_adresse/domicile_lat/domicile_lng
            // (qui servent au covoiturage) : ici c'est le lieu de stage,
            // utilisé pour le geofencing du pointage.
            $table->string('lieu_stage_adresse')->nullable()->after('domicile_lng');
            $table->decimal('lieu_stage_lat', 10, 7)->nullable()->after('lieu_stage_adresse');
            $table->decimal('lieu_stage_lng', 10, 7)->nullable()->after('lieu_stage_lat');
            $table->unsignedInteger('rayon_geofence')->default(100)->after('lieu_stage_lng');
        });
    }

    public function down(): void
    {
        Schema::table('stagiaires', function (Blueprint $table) {
            $table->dropColumn(['lieu_stage_adresse', 'lieu_stage_lat', 'lieu_stage_lng', 'rayon_geofence']);
        });
    }
};
