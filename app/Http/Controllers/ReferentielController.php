<?php

namespace App\Http\Controllers;

use App\Models\DomaineFormation;
use App\Models\Metier;
use App\Models\NiveauFormation;
use App\Models\Competence;
use App\Models\Stagiaire;
use Illuminate\Http\Request;

class ReferentielController extends Controller
{
    /**
     * Récupère les positions GPS de tous les stagiaires ayant accepté l'entraide.
     * Utilisé pour la carte globale des stages.
     */
    public function carteStages(Request $request)
    {
        return Stagiaire::where('autorisation_entraide', true)
            ->whereNotNull('lieu_stage_lat')
            ->whereNotNull('lieu_stage_lng')
            ->get([
                'id',
                'prenom',
                'nom',
                'ecole',
                'filiere',
                'lieu_stage_adresse',
                'lieu_stage_lat',
                'lieu_stage_lng',
                'photo_profil'
            ])
            ->map(function($s) {
                // On ajoute l'URL de la photo et un titre de poste par défaut
                $s->photo_url = $s->photo_profil_url;
                $s->poste = "Stagiaire " . ($s->filiere ?? "");
                return $s;
            });
    }

    public function domaines()
    {
        return DomaineFormation::orderBy('nom')->get(['id', 'nom']);
    }

    public function metiers(Request $request)
    {
        $query = Metier::orderBy('nom');

        if ($request->has('domaineId')) {
            $query->where('domaine_formation_id', $request->query('domaineId'));
        }

        return $query->get(['id', 'nom', 'domaine_formation_id']);
    }

    public function niveauxFormation()
    {
        return NiveauFormation::orderBy('nom')->get(['id', 'nom']);
    }

    public function competences(Request $request)
    {
        $request->validate(['metierId' => 'required|uuid']);

        return Competence::where('metier_id', $request->query('metierId'))
            ->whereNull('entreprise_id') // socle standard uniquement
            ->orderBy('nom')
            ->get(['id', 'nom', 'description', 'seuil_decouverte', 'seuil_maitrise']);
    }
}
