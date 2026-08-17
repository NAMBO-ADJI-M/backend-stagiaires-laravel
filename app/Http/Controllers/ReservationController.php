<?php

namespace App\Http\Controllers;

use App\Models\Trajet;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReservationController extends Controller
{
    // Réserver une ou plusieurs places sur un trajet (en tant que passager)
    public function store(Request $request, string $trajetId)
    {
        $data = $request->validate([
            'nombre_places' => 'nullable|integer|min:1',
        ]);
        $placesDemandees = $data['nombre_places'] ?? 1;

        $trajet = Trajet::where('id', $trajetId)->where('statut', 'ACTIF')->firstOrFail();

        if ($trajet->conducteur_id === $request->user()->stagiaire->id) {
            throw ValidationException::withMessages([
                'trajet_id' => 'Vous ne pouvez pas réserver votre propre trajet.',
            ]);
        }

        $placesReservees = Reservation::where('trajet_id', $trajet->id)
            ->where('statut', 'CONFIRMEE')
            ->sum('places');

        if ($placesReservees + $placesDemandees > $trajet->places_disponibles) {
            throw ValidationException::withMessages([
                'trajet_id' => 'Plus assez de places disponibles sur ce trajet.',
            ]);
        }

        $reservation = Reservation::updateOrCreate(
            ['trajet_id' => $trajet->id, 'passager_id' => $request->user()->stagiaire->id],
            ['places' => $placesDemandees, 'statut' => 'CONFIRMEE']
        );

        return response()->json($reservation, 201);
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