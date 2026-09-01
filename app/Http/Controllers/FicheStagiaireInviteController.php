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
            'email' => 'required|email',
            'nom' => 'nullable|string|max:100',
            'prenom' => 'nullable|string|max:100',

            // Données du contrat (Projet de convention)
            'poste' => 'required|string|max:255',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
            'etablissement_nom' => 'nullable|string|max:255',

            // Tuteur (Maître de stage)
            'tuteur_designe' => 'required|string|max:255',
            'tuteur_nom' => 'nullable|string|max:255',
            'tuteur_prenom' => 'nullable|string|max:255',
            'tuteur_fonction' => 'nullable|string|max:255',
            'tuteur_email' => 'nullable|email|max:255',
            'tuteur_telephone' => 'nullable|string|max:20',

            // Infos Entreprise Document
            'raison_sociale_custom' => 'nullable|string|max:255',
            'adresse_custom' => 'nullable|string|max:255',
            'secteur_activite_custom' => 'nullable|string|max:255',
            'entreprise_email_document' => 'nullable|email|max:255',
            'entreprise_telephone_document' => 'nullable|string|max:20',

            // Représentant Légal
            'representant_legal_nom' => 'nullable|string|max:255',
            'representant_legal_fonction' => 'nullable|string|max:255',
            'representant_legal_contact' => 'nullable|string|max:255',

            'objet_stage' => 'nullable|string|max:500',
            'objet_stage_autre' => 'nullable|string|max:500',
            'cursus_rattachement' => 'nullable|string|in:BTS,Licence,Master,Ingénieur,Doctorat,Autre',
            'stagiaire_annee_academique' => 'nullable|string|max:50',

            'lieu_execution' => 'nullable|string|max:255',
            'lieu_execution_lat' => 'nullable|numeric|between:-90,90',
            'lieu_execution_lng' => 'nullable|numeric|between:-180,180',
            'nombre_mois_stage' => 'nullable|integer',
            'duree_hebdomadaire' => 'nullable|string|max:100',
            'jours_presence' => 'nullable|array',
            'jours_presence.*' => 'string|in:lundi,mardi,mercredi,jeudi,vendredi,samedi,dimanche',
            'teletravail_modalites' => 'nullable|string|max:255',

            'referent_pedagogique_nom' => 'nullable|string|max:255',
            'referent_pedagogique_contact' => 'nullable|string|max:255',

            // Gratification & Congés
            'gratification_prevue' => 'nullable|boolean',
            'gratification_montant' => 'nullable|numeric',
            'gratification_periodicite' => 'nullable|string|max:100',
            'conges_absences' => 'nullable|string',
            'conditions_stage' => 'nullable|string',
        ]);

        $code = strtoupper(Str::random(8)); // ex. "TC7X9K2P"

        $fiche = FicheStagiaireInvite::create(array_merge($data, [
            'entreprise_id' => $request->user()->entreprise->id,
            'code_invitation' => $code,
            'date_expiration' => now()->addDays(30),
        ]));

        $entrepriseNom = $request->user()->entreprise->raison_sociale ?? 'Votre entreprise';

        // 1. Envoi par e-mail (Brevo)
        try {
            Mail::to($data['email'])->send(new InvitationStagiaireMail(
                $data['prenom'] ?? '',
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
