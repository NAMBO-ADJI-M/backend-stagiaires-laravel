<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DomainesNiveauxFormationSeeder extends Seeder
{
    public function run(): void
    {
        // ==================== DOMAINES DE FORMATION ====================
        $domaines = [
            'Informatique',
            'Gestion et Comptabilité',
            'Marketing et Communication',
            'Génie Civil et BTP',
            'Électrotechnique',
            'Ressources Humaines',
            'Commerce et Vente',
            'Logistique et Transport',
            'Santé et Social',
            'Agronomie',
            'Droit',
            'Tourisme et Hôtellerie',
        ];

        foreach ($domaines as $nom) {
            DB::table('domaines_formation')->updateOrInsert(
                ['nom' => $nom],
                ['id' => (string) Str::uuid()]
            );
        }

        // ==================== NIVEAUX DE FORMATION ====================
        $niveaux = [
            'Bac',
            'Bac+1',
            'Bac+2',
            'Licence 1',
            'Licence 2',
            'Licence 3',
            'Master 1',
            'Master 2',
            'Doctorat',
        ];

        foreach ($niveaux as $nom) {
            DB::table('niveaux_formation')->updateOrInsert(
                ['nom' => $nom],
                ['id' => (string) Str::uuid()]
            );
        }

        $this->command->info('Domaines de formation et niveaux de formation seedés avec succès.');
    }
}
