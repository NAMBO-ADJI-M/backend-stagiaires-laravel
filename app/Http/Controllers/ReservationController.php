<?php

namespace App\Http\Controllers;

use App\Models\Trajet;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReservationController extends Controller
{
    // Réserver une place sur un trajet (en tant que passager)
    public function store(Request $request, string $trajetId)
    {
        $trajet = Trajet::where('id', $trajetId)->where('statut', 'ACTIF')->firstOrFail();

        if ($trajet->conducteur_id === $request->user()->stagiaire->id) {
            throw ValidationException::withMessages([
                'trajet_id' => 'Vous ne pouvez pas réserver votre propre trajet.',
            ]);
        }

        $placesReservees = Reservation::where('trajet_id', $trajet->id)
            ->where('statut', 'CONFIRMEE')
            ->count();

        if ($placesReservees >= $trajet->places_disponibles) {
            throw ValidationException::withMessages([
                'trajet_id' => 'Plus de place disponible sur ce trajet.',
            ]);
        }

        $reservation = Reservation::firstOrCreate(
            ['trajet_id' => $trajet->id, 'passager_id' => $request->user()->stagiaire->id],
            ['statut' => 'CONFIRMEE']
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

    // Mes réservations en tant que passager
    public function mesReservations(Request $request)
    {
        return Reservation::where('passager_id', $request->user()->stagiaire->id)
            ->with('trajet.conducteur:id,nom,prenom')
            ->orderByDesc('date_creation')
            ->get();
    }
}
