<?php

namespace Database\Seeders;

use App\Models\DomaineFormation;
use App\Models\Metier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MetierSeeder extends Seeder
{
    /**
     * Métiers par domaine de formation.
     * Ajuste librement les libellés selon les besoins réels de la plateforme.
     */
    protected array $metiersParDomaine = [
        'Agronomie' => [
            'Technicien agricole',
            'Agent de développement rural',
            'Technicien en production végétale',
            'Technicien en élevage',
        ],
        'Commerce et Vente' => [
            'Vendeur conseil',
            'Attaché commercial',
            'Chargé de clientèle',
            'Responsable de rayon',
        ],
        'Droit' => [
            'Assistant juridique',
            'Clerc de notaire',
            'Juriste d\'entreprise junior',
        ],
        'Électrotechnique' => [
            'Technicien électricien',
            'Technicien de maintenance industrielle',
            'Installateur électrique',
        ],
        'Génie Civil et BTP' => [
            'Conducteur de travaux',
            'Technicien géomètre',
            'Dessinateur BTP',
            'Chef de chantier',
        ],
        'Gestion et Comptabilité' => [
            'Assistant comptable',
            'Aide-comptable',
            'Gestionnaire de paie',
            'Contrôleur de gestion junior',
        ],
        'Informatique' => [
            'Développeur logiciel',
            'Administrateur systèmes et réseaux',
            'Technicien support informatique',
            'Développeur mobile',
            'Analyste-développeur',
        ],
        'Logistique et Transport' => [
            'Agent logistique',
            'Gestionnaire de stock',
            'Agent d\'exploitation transport',
            'Responsable approvisionnement',
        ],
        'Marketing et Communication' => [
            'Chargé de communication',
            'Assistant marketing',
            'Community manager',
            'Chargé d\'études marketing',
        ],
        'Ressources Humaines' => [
            'Assistant RH',
            'Chargé de recrutement',
            'Gestionnaire administratif du personnel',
        ],
        'Santé et Social' => [
            'Aide-soignant',
            'Assistant social',
            'Technicien de laboratoire médical',
            'Agent de santé communautaire',
        ],
        'Tourisme et Hôtellerie' => [
            'Réceptionniste hôtelier',
            'Agent de voyages',
            'Serveur en restauration',
            'Guide touristique',
        ],
    ];

    public function run(): void
    {
        foreach ($this->metiersParDomaine as $nomDomaine => $metiers) {
            $domaine = DomaineFormation::where('nom', $nomDomaine)->first();

            if (! $domaine) {
                $this->command?->warn("Domaine introuvable, ignoré : {$nomDomaine}");
                continue;
            }

            foreach ($metiers as $nomMetier) {
                Metier::firstOrCreate(
                    [
                        'domaine_formation_id' => $domaine->id,
                        'nom' => $nomMetier,
                    ],
                    [
                        'id' => (string) Str::uuid(),
                    ]
                );
            }
        }
    }
}
