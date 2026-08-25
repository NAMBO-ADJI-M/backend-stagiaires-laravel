<?php

namespace App\Http\Controllers;

use App\Models\DemandeRattachement;
use App\Models\Entreprise;
use Illuminate\Http\Request;

class DemandeRattachementController extends Controller
{
    /**
     * Recherche d'entreprises pour l'autocomplete.
     */
    public function recherche(Request $request)
    {
        $query = $request->query('q');

        if (empty($query)) {
            return response()->json([]);
        }

        $entreprises = Entreprise::where('raison_sociale', 'LIKE', "%{$query}%")
            ->take(10)
            ->get(['id', 'raison_sociale', 'secteur', 'adresse_libelle']);

        return response()->json($entreprises);
    }

    /**
     * Le stagiaire demande un rattachement à une entreprise.
     */
    public function demander(Request $request)
    {
        $request->validate([
            'entreprise_id' => 'required|uuid|exists:entreprises,id',
        ]);

        $stagiaire = $request->user()->stagiaire;

        if (!$stagiaire) {
            return response()->json(['message' => 'Profil stagiaire non trouvé.'], 404);
        }

        // Vérifier si une demande en attente existe déjà
        $demande = DemandeRattachement::where('stagiaire_id', $stagiaire->id)
            ->where('entreprise_id', $request->entreprise_id)
            ->where('statut', 'en_attente')
            ->first();

        if ($demande) {
            return response()->json([
                'message' => 'Une demande est déjà en attente pour cette entreprise.',
                'data' => $demande
            ]);
        }

        $demande = DemandeRattachement::create([
            'stagiaire_id' => $stagiaire->id,
            'entreprise_id' => $request->entreprise_id,
            'statut' => 'en_attente',
        ]);

        return response()->json([
            'message' => 'Demande de rattachement envoyée.',
            'data' => $demande
        ], 201);
    }

    /**
     * Liste des demandes de rattachement pour l'entreprise connectée.
     */
    public function indexEntreprise(Request $request)
    {
        $entreprise = $request->user()->entreprise;

        if (!$entreprise) {
            return response()->json(['message' => 'Profil entreprise non trouvé.'], 404);
        }

        $demandes = DemandeRattachement::where('entreprise_id', $entreprise->id)
            ->where('statut', 'en_attente')
            ->with(['stagiaire' => function($query) {
                // On charge user pour l'accesseur email par sécurité
                $query->select('id', 'user_id', 'email', 'nom', 'prenom', 'photo_profil', 'ecole', 'filiere')
                      ->with('user:id,email');
            }])
            ->orderByDesc('created_at')
            ->get();

        return response()->json($demandes);
    }
}
