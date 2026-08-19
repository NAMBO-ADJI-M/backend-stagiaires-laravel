<?php

namespace App\Http\Controllers;

use App\Models\FicheStagiaireInvite;
use App\Models\User;
use App\Mail\InvitationStagiaireMail;
use App\Notifications\InvitationRattachementNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;

class FicheStagiaireInviteController extends Controller
{
    // L'entreprise ajoute un nouveau stagiaire et génère son code d'invitation
    public function store(Request $request)
    {
        $data = $request->validate([
            'nom' => 'required|string|max:100',
            'prenom' => 'required|string|max:100',
            'email' => 'required|email',
        ]);

        $code = strtoupper(Str::random(8)); // ex. "TC7X9K2P"

        $fiche = FicheStagiaireInvite::create([
            ...$data,
            'entreprise_id' => $request->user()->entreprise->id,
            'code_invitation' => $code,
            'date_expiration' => now()->addDays(30),
        ]);

        $entrepriseNom = $request->user()->entreprise->raison_sociale ?? 'Votre entreprise';

        // 1. Envoi par e-mail (Brevo)
        try {
            Mail::to($data['email'])->send(new InvitationStagiaireMail(
                $data['prenom'],
                $entrepriseNom,
                $code
            ));
        } catch (\Exception $e) {
            \Log::error("Erreur envoi invitation email : " . $e->getMessage());
        }

        // 2. Notification in-app si le stagiaire existe déjà
        $existingUser = User::where('email', $data['email'])->first();
        if ($existingUser) {
            $existingUser->notify(new InvitationRattachementNotification($entrepriseNom, $code));
        }

        return response()->json($fiche, 201);
    }

    // Liste des invitations envoyées par l'entreprise connectée
    public function index(Request $request)
    {
        return FicheStagiaireInvite::where('entreprise_id', $request->user()->entreprise->id)
            ->orderByDesc('date_generation')
            ->get();
    }
}
