<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trajets', function (Blueprint $table) {
            // Trajet ponctuel : une date/heure précise remplace l'horaire récurrent
            $table->dateTime('date_depart')->nullable()->after('conducteur_id');

            // Adresses lisibles, en complément des coordonnées GPS (conservées
            // pour l'affichage carte / calcul de distance)
            $table->string('lieu_depart')->nullable()->after('depart_lng');
            $table->string('lieu_arrivee')->nullable()->after('arrivee_lng');

            $table->decimal('tarif', 6, 2)->nullable()->after('places_disponibles');
            $table->text('description')->nullable()->after('tarif');

            $table->dropColumn(['heure_depart', 'jours_recurrence']);
        });
    }

    public function down(): void
    {
        Schema::table('trajets', function (Blueprint $table) {
            $table->time('heure_depart')->nullable();
            $table->json('jours_recurrence')->nullable();

            $table->dropColumn([
                'date_depart',
                'lieu_depart',
                'lieu_arrivee',
                'tarif',
                'description',
            ]);
        });
    }
};
