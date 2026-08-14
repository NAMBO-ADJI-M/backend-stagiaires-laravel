<?php

namespace App\Http\Controllers;

use App\Models\Trajet;
use Illuminate\Http\Request;

class TrajetController extends Controller
{
    // Proposer un trajet en tant que conducteur
    public function store(Request $request)
    {
        $data = $request->validate([
            'depart_lat' => 'required|numeric',
            'depart_lng' => 'required|numeric',
            'arrivee_lat' => 'required|numeric',
            'arrivee_lng' => 'required|numeric',
            'heure_depart' => 'required|date_format:H:i',
            'jours_recurrence' => 'required|array|min:1',
            'jours_recurrence.*' => 'in:LUN,MAR,MER,JEU,VEN,SAM,DIM',
            'places_disponibles' => 'required|integer|min:1',
        ]);

        $trajet = Trajet::create([
            ...$data,
            'conducteur_id' => $request->user()->stagiaire->id,
        ]);

        return response()->json($trajet, 201);
    }

    // Liste des trajets disponibles (tous les trajets actifs, pour rechercher un covoitureur)
    public function index(Request $request)
    {
        $query = Trajet::where('statut', 'ACTIF')->with('conducteur:id,nom,prenom');

        // Recherche par proximité simple (rayon en km, formule de Haversine)
        if ($request->has(['lat', 'lng', 'rayon_km'])) {
            $lat = $request->query('lat');
            $lng = $request->query('lng');
            $rayon = $request->query('rayon_km');

            $query->selectRaw("*,
                (6371 * acos(cos(radians(?)) * cos(radians(depart_lat)) *
                cos(radians(depart_lng) - radians(?)) + sin(radians(?)) *
                sin(radians(depart_lat)))) AS distance_km", [$lat, $lng, $lat])
                ->having('distance_km', '<=', $rayon)
                ->orderBy('distance_km');
        }

        return $query->get();
    }

    // Mes trajets en tant que conducteur
    public function mesTrajets(Request $request)
    {
        return Trajet::where('conducteur_id', $request->user()->stagiaire->id)
            ->orderByDesc('date_creation')
            ->get();
    }
}
