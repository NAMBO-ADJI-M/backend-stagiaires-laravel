<?php

namespace App\Http\Controllers;

use App\Models\Trajet;
use Illuminate\Http\Request;

class TrajetController extends Controller
{
    // Proposer un trajet ponctuel en tant que conducteur
    public function store(Request $request)
    {
        $data = $request->validate([
            'lieu_depart' => 'required|string|max:255',
            'lieu_arrivee' => 'required|string|max:255',
            'depart_lat' => 'nullable|numeric',
            'depart_lng' => 'nullable|numeric',
            'arrivee_lat' => 'nullable|numeric',
            'arrivee_lng' => 'nullable|numeric',
            'date_depart' => 'required|date',
            'places_disponibles' => 'required|integer|min:1',
            'tarif' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $trajet = Trajet::create([
            ...$data,
            'conducteur_id' => $request->user()->stagiaire->id,
            'statut' => 'ACTIF',
        ]);

        return response()->json($trajet, 201);
    }

    // Liste des trajets disponibles (tous les trajets actifs à venir)
    public function index(Request $request)
    {
        $query = Trajet::where('statut', 'ACTIF')
            ->where('date_depart', '>=', now())
            ->with(['conducteur:id,nom,prenom,photo_profil'])
            ->orderBy('date_depart');

        // Recherche par proximité simple (rayon en km, formule de Haversine)
        if ($request->has(['lat', 'lng', 'rayon_km'])) {
            $lat = $request->query('lat');
            $lng = $request->query('lng');
            $rayon = $request->query('rayon_km');

            $query->selectRaw("trajets.*,
                (6371 * acos(cos(radians(?)) * cos(radians(depart_lat)) *
                cos(radians(depart_lng) - radians(?)) + sin(radians(?)) *
                sin(radians(depart_lat)))) AS distance_km", [$lat, $lng, $lat])
                ->having('distance_km', '<=', $rayon)
                ->orderBy('distance_km');
        }

        $trajets = $query->get();

        return response()->json(
            $trajets->map(fn (Trajet $t) => $this->formatTrajet($t))
        );
    }

    // Détail d'un trajet, avec conducteur et passagers confirmés
    public function show(string $id)
    {
        $trajet = Trajet::with(['conducteur:id,nom,prenom,photo_profil', 'passagers'])
            ->findOrFail($id);

        return response()->json($this->formatTrajet($trajet, avecPassagers: true));
    }

    // Mes trajets en tant que conducteur
    public function mesTrajets(Request $request)
    {
        $trajets = Trajet::where('conducteur_id', $request->user()->stagiaire->id)
            ->orderByDesc('date_depart')
            ->get();

        return response()->json(
            $trajets->map(fn (Trajet $t) => $this->formatTrajet($t))
        );
    }

    /**
     * Formate un trajet selon la structure attendue par le frontend
     * (clé "chauffeur" plutôt que "conducteur", passagers en tableau
     * simple si demandé).
     */
    private function formatTrajet(Trajet $trajet, bool $avecPassagers = false): array
    {
        $data = [
            'id' => $trajet->id,
            'lieu_depart' => $trajet->lieu_depart,
            'lieu_arrivee' => $trajet->lieu_arrivee,
            'depart_lat' => $trajet->depart_lat,
            'depart_lng' => $trajet->depart_lng,
            'arrivee_lat' => $trajet->arrivee_lat,
            'arrivee_lng' => $trajet->arrivee_lng,
            'date_depart' => $trajet->date_depart?->toIso8601String(),
            'places_disponibles' => $trajet->places_disponibles,
            'tarif' => $trajet->tarif,
            'description' => $trajet->description,
            'statut' => $trajet->statut,
            'chauffeur' => $trajet->conducteur ? [
                'id' => $trajet->conducteur->id,
                'nom' => trim($trajet->conducteur->prenom.' '.$trajet->conducteur->nom),
                'photo_profil' => $trajet->conducteur->photo_profil,
            ] : null,
        ];

        if (isset($trajet->distance_km)) {
            $data['distance'] = round($trajet->distance_km, 1).' km';
        }

        if ($avecPassagers) {
            $data['passagers'] = $trajet->passagers->map(fn ($p) => [
                'nom' => trim($p->prenom.' '.$p->nom),
                'places' => 1,
            ])->values();
        }

        return $data;
    }
}