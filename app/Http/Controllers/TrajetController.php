<?php

namespace App\Http\Controllers;

use App\Models\Trajet;
use App\Models\Stagiaire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class TrajetController extends Controller
{
    // Proposer un trajet ponctuel en tant que conducteur
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
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

            $user = $request->user();
            $stagiaire = $user->stagiaire;

            if (!$stagiaire) {
                // Création de secours du profil stagiaire si manquant
                $stagiaire = Stagiaire::create([
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'nom' => 'Utilisateur',
                    'prenom' => 'StageLink',
                    'profil_complet' => false,
                ]);
            }

            $trajet = Trajet::create([
                'conducteur_id' => $stagiaire->id,
                'lieu_depart' => $validated['lieu_depart'],
                'lieu_arrivee' => $validated['lieu_arrivee'],
                'depart_lat' => $validated['depart_lat'] ?? 0.0,
                'depart_lng' => $validated['depart_lng'] ?? 0.0,
                'arrivee_lat' => $validated['arrivee_lat'] ?? 0.0,
                'arrivee_lng' => $validated['arrivee_lng'] ?? 0.0,
                'date_depart' => $validated['date_depart'],
                'places_disponibles' => $validated['places_disponibles'],
                'tarif' => $validated['tarif'] ?? 0.0,
                'description' => $validated['description'] ?? '',
                'statut' => 'ACTIF',
            ]);

            Log::info("✅ Nouveau trajet créé par stagiaire {$stagiaire->id}");

            return response()->json($trajet, 201);
        } catch (\Exception $e) {
            Log::error("❌ Erreur création trajet : " . $e->getMessage());
            return response()->json([
                'message' => 'Une erreur est survenue sur le serveur.',
                'error' => $e->getMessage() // On affiche l'erreur pour le debug
            ], 500);
        }
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
