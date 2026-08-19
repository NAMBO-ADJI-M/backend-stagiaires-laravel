<?php

namespace App\Http\Controllers;

use App\Models\Trajet;
use App\Models\Reservation;
use App\Models\Stagiaire;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class ReservationController extends Controller
{
    // Réserver une ou plusieurs places sur un trajet (en tant que passager)
    public function store(Request $request, string $trajetId)
    {
        try {
            $data = $request->validate([
                'nombre_places' => 'nullable|integer|min:1',
            ]);
            $placesDemandees = $data['nombre_places'] ?? 1;

            $trajet = Trajet::where('id', $trajetId)->where('statut', 'ACTIF')->first();

            if (!$trajet) {
                return response()->json(['message' => 'Ce trajet n\'est plus disponible ou est introuvable.'], 404);
            }

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

            if ($trajet->conducteur_id === $stagiaire->id) {
                return response()->json(['message' => 'Vous ne pouvez pas réserver votre propre trajet.'], 422);
            }

            $placesReservees = Reservation::where('trajet_id', $trajet.id)
                ->where('statut', 'CONFIRMEE')
                ->where('passager_id', '!=', $stagiaire->id)
                ->sum('places');

            if ($placesReservees + $placesDemandees > $trajet->places_disponibles) {
                return response()->json(['message' => 'Plus assez de places disponibles sur ce trajet.'], 422);
            }

            $reservation = Reservation::updateOrCreate(
                ['trajet_id' => $trajet.id, 'passager_id' => $stagiaire->id],
                ['places' => $placesDemandees, 'statut' => 'CONFIRMEE']
            );

            Log::info("✅ Réservation confirmée pour stagiaire {$stagiaire->id} sur trajet {$trajet->id}");

            return response()->json($reservation, 201);
        } catch (\Exception $e) {
            Log::error("❌ Erreur réservation : " . $e->getMessage());
            return response()->json([
                'message' => 'Impossible de finaliser la réservation.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Annuler sa réservation
    public function annuler(Request $request, string $reservationId)
    {
        $reservation = Reservation::where('id', $reservationId)
            ->where('passager_id', $request->user()->stagiaire->id)
            ->firstOrFail();

        $reservation->update(['statut' => 'ANNULEE']);

        return response()->json($reservation);
    }

    // Mes réservations en tant que passager, formatées pour le frontend
    public function mesReservations(Request $request)
    {
        $reservations = Reservation::where('passager_id', $request->user()->stagiaire->id)
            ->where('statut', '!=', 'ANNULEE')
            ->with(['trajet.conducteur:id,nom,prenom,photo_profil'])
            ->orderByDesc('date_creation')
            ->get();

        return response()->json($reservations->map(function ($r) {
            $trajet = $r->trajet;
            $prixTotal = $trajet && $trajet->tarif ? $trajet->tarif * $r->places : 0;

            return [
                'id' => $r->id,
                'trajet_id' => $r->trajet_id,
                'places' => $r->places,
                'prix_total' => round($prixTotal, 2),
                'statut' => $r->statut,
                'trajet' => $trajet ? [
                    'id' => $trajet->id,
                    'lieu_depart' => $trajet->lieu_depart,
                    'lieu_arrivee' => $trajet->lieu_arrivee,
                    'date_depart' => $trajet->date_depart?->toIso8601String(),
                    'places_disponibles' => $trajet->places_disponibles,
                    'tarif' => $trajet->tarif,
                    'chauffeur' => $trajet->conducteur ? [
                        'id' => $trajet->conducteur->id,
                        'nom' => trim($trajet->conducteur->prenom.' '.$trajet->conducteur->nom),
                        'photo_profil' => $trajet->conducteur->photo_profil,
                    ] : null,
                ] : null,
            ];
        }));
    }
}
