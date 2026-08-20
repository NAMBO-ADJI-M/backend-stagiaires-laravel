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
