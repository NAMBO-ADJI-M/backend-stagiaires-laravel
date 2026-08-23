<?php

namespace App\Http\Controllers;

use App\Models\Convention;
use App\Models\AutorisationPointage;
use App\Models\Stagiaire;
use App\Models\Entreprise;
use App\Models\CarnetDeStage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use App\Notifications\DemandeSuiviNotification;

class AutorisationPointageController extends Controller
{
    /**
     * Le stagiaire active/désactive lui-même le suivi.
     * Passage immédiat à ACTIVE ou INACTIVE.
     */
    public function stagiaireToggle(Request $request)
    {
        $request->validate([
            'entreprise_id' => 'required|uuid|exists:entreprises,id',
            'autorise' => 'required|boolean'
        ]);

        $stagiaire = $request->user()->stagiaire;
        $statut = $request->autorise ? 'ACTIVE' : 'INACTIVE';

        $autorisation = AutorisationPointage::updateOrCreate(
            ['stagiaire_id' => $stagiaire->id, 'entreprise_id' => $request->entreprise_id],
            ['statut' => $statut]
        );

        return response()->json([
            'message' => $request->autorise ? 'Suivi activé.' : 'Suivi désactivé.',
            'statut' => $autorisation->statut
        ]);
    }

    /**
     * L'entreprise demande l'accès au suivi du stagiaire.
     * Statut passe à EN_ATTENTE. Envoie une notification au stagiaire.
     */
    public function entrepriseDemande(Request $request)
    {
        $validated = $request->validate([
            'stagiaire_id' => 'required|uuid|exists:stagiaires,id',
            'poste' => 'required|string|max:255',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
            'conditions_stage' => 'nullable|string',

            'etablissement_nom' => 'nullable|string|max:255',
            'tuteur_designe' => 'required|string|max:255',
            'objet_stage' => 'nullable|string|max:500',
            'cursus_rattachement' => 'nullable|string|max:255',
            'lieu_execution' => 'nullable|string|max:255',
            'lieu_execution_lat' => 'nullable|numeric|between:-90,90',
            'lieu_execution_lng' => 'nullable|numeric|between:-180,180',
            'duree_hebdomadaire' => 'nullable|string|max:100',
            'jours_presence' => 'nullable|string|max:255',
            'teletravail_modalites' => 'nullable|string|max:255',
            'referent_pedagogique_nom' => 'nullable|string|max:255',
            'referent_pedagogique_contact' => 'nullable|string|max:255',
            'modalites_suivi_detail' => 'nullable|string',
        ]);

        $entreprise = $request->user()->entreprise;

        $code = strtoupper(\Illuminate\Support\Str::random(8));

        $autorisation = AutorisationPointage::updateOrCreate(
            ['stagiaire_id' => $validated['stagiaire_id'], 'entreprise_id' => $entreprise->id],
            array_merge($validated, [
                'statut' => 'EN_ATTENTE',
                'code_validation' => $code,
                'entreprise_id' => $entreprise->id,
            ])
        );

        // Envoyer la notification au stagiaire avec le CODE
        $stagiaire = Stagiaire::find($validated['stagiaire_id']);
        $stagiaire->user->notify(new \App\Notifications\GenericNotification([
            'type' => 'DEMANDE_SUIVI',
            'title' => '🔒 Demande de liaison',
            'message' => "L'entreprise {$entreprise->raison_sociale} souhaite activer votre suivi de pointage. Utilisez le code {$code} sur votre écran d'accueil.",
            'code' => $code,
            'entreprise_id' => $entreprise->id,
            'autorisation_id' => $autorisation->id
        ]));

        return response()->json([
            'message' => 'Demande de suivi envoyée avec code et contrat détaillé.',
            'code' => $code,
            'statut' => $autorisation->statut
        ]);
    }

    public function verifierCode(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        $code = strtoupper(trim($request->code));
        $user = $request->user();
        $stagiaire = $user->stagiaire;
        $userEmail = strtolower($user->email);

        // 1. Chercher dans les invitations (Nouveau Stagiaire ou Première Liaison)
        $invit = \App\Models\FicheStagiaireInvite::where('code_invitation', $code)
            ->where('utilise', false)
            ->with('entreprise:id,raison_sociale')
            ->first();

        if ($invit) {
            // Sécurité : Vérifier que l'email de l'invitation correspond à l'utilisateur connecté
            if (strtolower($invit->email) !== $userEmail) {
                return response()->json(['message' => 'Ce code a été généré pour une autre adresse email.'], 422);
            }

            return response()->json([
                'entreprise_nom' => $invit->entreprise->raison_sociale,
                'entreprise_id' => $invit->entreprise_id,
                'poste' => $invit->poste,
                'date_debut' => $invit->date_debut,
                'date_fin' => $invit->date_fin,
                'etablissement_nom' => $invit->etablissement_nom,
                'tuteur_designe' => $invit->tuteur_designe,
                'objet_stage' => $invit->objet_stage,
                'cursus_rattachement' => $invit->cursus_rattachement,
                'lieu_execution' => $invit->lieu_execution,
                'lieu_execution_lat' => $invit->lieu_execution_lat,
                'lieu_execution_lng' => $invit->lieu_execution_lng,
                'duree_hebdomadaire' => $invit->duree_hebdomadaire,
                'jours_presence' => $invit->jours_presence,
                'teletravail_modalites' => $invit->teletravail_modalites,
                'referent_pedagogique_nom' => $invit->referent_pedagogique_nom,
                'referent_pedagogique_contact' => $invit->referent_pedagogique_contact,
                'modalites_suivi_detail' => $invit->modalites_suivi_detail,
                'conditions_stage' => $invit->conditions_stage,
                'stagiaire_nom' => $stagiaire->nom,
                'stagiaire_prenom' => $stagiaire->prenom,
                'stagiaire_email' => $user->email,
            ]);
        }

        // 2. Chercher dans les liaisons directes (Ancien flux ou code manuel)
        $autorisation = AutorisationPointage::where('code_validation', $code)
            ->where('stagiaire_id', $stagiaire->id)
            ->with('entreprise:id,raison_sociale')
            ->first();

        if ($autorisation) {
            return response()->json([
                'entreprise_nom' => $autorisation->entreprise->raison_sociale,
                'entreprise_id' => $autorisation->entreprise_id,
                'poste' => $autorisation->poste,
                'date_debut' => $autorisation->date_debut,
                'date_fin' => $autorisation->date_fin,
                'conditions_stage' => $autorisation->conditions_stage,
                'etablissement_nom' => $autorisation->etablissement_nom,
                'tuteur_designe' => $autorisation->tuteur_designe,
                'objet_stage' => $autorisation->objet_stage,
                'cursus_rattachement' => $autorisation->cursus_rattachement,
                'lieu_execution' => $autorisation->lieu_execution,
                'lieu_execution_lat' => $autorisation->lieu_execution_lat,
                'lieu_execution_lng' => $autorisation->lieu_execution_lng,
                'duree_hebdomadaire' => $autorisation->duree_hebdomadaire,
                'jours_presence' => $autorisation->jours_presence,
                'teletravail_modalites' => $autorisation->teletravail_modalites,
                'referent_pedagogique_nom' => $autorisation->referent_pedagogique_nom,
                'referent_pedagogique_contact' => $autorisation->referent_pedagogique_contact,
                'modalites_suivi_detail' => $autorisation->modalites_suivi_detail,
                'stagiaire_nom' => $stagiaire->nom,
                'stagiaire_prenom' => $stagiaire->prenom,
                'stagiaire_email' => $user->email,
            ]);
        }

        return response()->json(['message' => 'Code incorrect, déjà utilisé ou expiré.'], 422);
    }

    /**
     * Étape 2 Stagiaire : Valide définitivement la liaison.
     */
    public function validerLiaison(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'entreprise_id' => 'required|uuid|exists:entreprises,id',
            'stagiaire_date_naissance' => 'required|date',
            'stagiaire_adresse' => 'required|string|max:255',
            'stagiaire_telephone' => 'required|string|max:20',
            'carnet_id' => 'nullable|uuid|exists:carnets_de_stage,id',
        ]);

        $code = strtoupper(trim($request->code));
        $stagiaire = $request->user()->stagiaire;
        $entreprise = Entreprise::findOrFail($request->entreprise_id);

        // Mise à jour du profil stagiaire (coordonnées récoltées lors de la signature)
        $stagiaire->update([
            'date_naissance' => $request->stagiaire_date_naissance,
            'domicile_adresse' => $request->stagiaire_adresse,
            'telephone' => $request->stagiaire_telephone,
            'profil_complet' => true
        ]);

        // Rechercher si c'est une invitation
        $invit = \App\Models\FicheStagiaireInvite::where('code_invitation', $code)->first();
        $autorisation = null;

        if ($invit) {
            // Créer l'autorisation réelle à partir du brouillon d'invitation
            $autorisation = AutorisationPointage::updateOrCreate(
                ['stagiaire_id' => $stagiaire->id, 'entreprise_id' => $request->entreprise_id],
                [
                    'statut' => 'ACTIVE',
                    'poste' => $invit->poste,
                    'date_debut' => $invit->date_debut,
                    'date_fin' => $invit->date_fin,
                    'etablissement_nom' => $invit->etablissement_nom,
                    'tuteur_designe' => $invit->tuteur_designe,
                    'objet_stage' => $invit->objet_stage,
                    'cursus_rattachement' => $invit->cursus_rattachement,
                    'lieu_execution' => $invit->lieu_execution,
                    'lieu_execution_lat' => $invit->lieu_execution_lat,
                    'lieu_execution_lng' => $invit->lieu_execution_lng,
                    'duree_hebdomadaire' => $invit->duree_hebdomadaire,
                    'jours_presence' => $invit->jours_presence,
                    'teletravail_modalites' => $invit->teletravail_modalites,
                    'referent_pedagogique_nom' => $invit->referent_pedagogique_nom,
                    'referent_pedagogique_contact' => $invit->referent_pedagogique_contact,
                    'modalites_suivi_detail' => $invit->modalites_suivi_detail,
                    'conditions_stage' => $invit->conditions_stage,
                    'stagiaire_date_naissance' => $request->stagiaire_date_naissance,
                    'stagiaire_adresse' => $request->stagiaire_adresse,
                    'stagiaire_telephone' => $request->stagiaire_telephone,
                ]
            );

            // Rattachement du carnet si fourni
            if ($request->carnet_id) {
                $this->rattacherCarnetEtCreerConvention($request->carnet_id, $stagiaire, $entreprise, $autorisation, $request);
            }

            $invit->update(['utilise' => true]);
        } else {
            // Sinon chercher liaison directe
            $autorisation = AutorisationPointage::where('code_validation', $code)
                ->where('stagiaire_id', $stagiaire->id)
                ->first();

            if ($autorisation) {
                $autorisation->update([
                    'stagiaire_date_naissance' => $request->stagiaire_date_naissance,
                    'stagiaire_adresse' => $request->stagiaire_adresse,
                    'stagiaire_telephone' => $request->stagiaire_telephone,
                    'statut' => 'ACTIVE',
                    'code_validation' => null
                ]);

                // Rattachement du carnet si fourni
                if ($request->carnet_id) {
                    $this->rattacherCarnetEtCreerConvention($request->carnet_id, $stagiaire, $entreprise, $autorisation, $request);
                }
            }
        }

        if ($autorisation) {
            // ENVOI DE LA CONVENTION PAR MAIL
            $this->envoyerConventionParMail($autorisation);
            return response()->json(['message' => 'Convention signée et liaison établie !', 'statut' => 'ACTIVE']);
        }

        return response()->json(['message' => 'Validation impossible.'], 422);
    }

    /**
     * Helper pour rattacher le carnet et créer l'enregistrement officiel de la convention.
     */
    private function rattacherCarnetEtCreerConvention($carnetId, $stagiaire, $entreprise, $autorisation, $request)
    {
        $carnet = CarnetDeStage::where('id', $carnetId)
            ->where('stagiaire_id', $stagiaire->id)
            ->first();

        if ($carnet) {
            $carnet->update([
                'entreprise_id' => $entreprise->id,
                'statut' => 'EN_COURS',
                'autorisation_suivi' => true,
                'date_rattachement' => now(),
            ]);

            // Création automatique de la Convention officielle
            Convention::updateOrCreate(
                ['carnet_id' => $carnet->id],
                [
                    'raison_sociale' => $entreprise->raison_sociale,
                    'adresse' => $entreprise->adresse_libelle,
                    'secteur_activite' => $entreprise->secteur,
                    'entreprise_email' => $entreprise->email,
                    'entreprise_telephone' => $entreprise->telephone,

                    'date_debut' => $autorisation->date_debut,
                    'date_fin' => $autorisation->date_fin,
                    'duree_hebdomadaire' => $autorisation->duree_hebdomadaire,
                    'jours_presence' => $autorisation->jours_presence,
                    'lieu_execution' => $autorisation->lieu_execution,
                    'modalites_suivi' => $autorisation->modalites_suivi_detail,

                    'stagiaire_nom' => $stagiaire->nom,
                    'stagiaire_prenom' => $stagiaire->prenom,
                    'stagiaire_email' => $stagiaire->email,
                    'stagiaire_telephone' => $request->stagiaire_telephone,
                    'stagiaire_adresse' => $request->stagiaire_adresse,
                    'stagiaire_date_naissance' => $request->stagiaire_date_naissance,
                    'stagiaire_etablissement' => $autorisation->etablissement_nom,
                    'stagiaire_annee_academique' => $autorisation->cursus_rattachement,

                    'statut' => 'signee',
                    'tuteur_valide_le' => now(), // Considéré validé car le tuteur a généré le code
                    'stagiaire_valide_le' => now(),
                ]
            );
        }
    }

    /**
     * Génère et envoie la convention PDF au stagiaire et à l'entreprise.
     */
    private function envoyerConventionParMail($autorisation)
    {
        $autorisation->load(['entreprise', 'stagiaire']);

        try {
            $mail = new \App\Mail\ConventionSigneeMail($autorisation);

            // 1. Au stagiaire
            \Illuminate\Support\Facades\Mail::to($autorisation->stagiaire->email)->send($mail);

            // 2. À l'entreprise (tuteur)
            \Illuminate\Support\Facades\Mail::to($autorisation->entreprise->email)->send($mail);

        } catch (\Exception $e) {
            \Log::error("Erreur envoi convention PDF par mail : " . $e->getMessage());
        }
    }

    /**
     * Étape 2 Stagiaire (Alternative) : Décline l'offre de stage.
     */
    public function declinerLiaison(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'entreprise_id' => 'required|uuid|exists:entreprises,id'
        ]);

        $stagiaire = $request->user()->stagiaire;

        $autorisation = AutorisationPointage::where('stagiaire_id', $stagiaire->id)
            ->where('entreprise_id', $request->entreprise_id)
            ->where('code_validation', $request->code)
            ->first();

        if (!$autorisation) {
            // Chercher dans les invitations si c'est un nouveau stagiaire
            $invit = \App\Models\FicheStagiaireInvite::where('code_invitation', $request->code)->first();
            if ($invit) {
                $invit->update(['utilise' => true]); // Marqué comme utilisé pour annuler
                return response()->json(['message' => 'Offre déclinée.']);
            }
            return response()->json(['message' => 'Liaison introuvable.'], 404);
        }

        $autorisation->update([
            'statut' => 'REFUSEE',
            'code_validation' => null
        ]);

        return response()->json(['message' => 'Offre déclinée.']);
    }

    /**
     * Le stagiaire accepte ou refuse via la notification.
     */
    public function stagiaireRepond(Request $request)
    {
        $request->validate([
            'autorisation_id' => 'required|uuid|exists:autorisations_pointage,id',
            'accepter' => 'required|boolean'
        ]);

        $autorisation = AutorisationPointage::where('id', $request->autorisation_id)
            ->where('stagiaire_id', $request->user()->stagiaire->id)
            ->firstOrFail();

        $autorisation->update([
            'statut' => $request->accepter ? 'ACTIVE' : 'REFUSEE'
        ]);

        return response()->json([
            'message' => $request->accepter ? 'Demande acceptée.' : 'Demande refusée.',
            'statut' => $autorisation->statut
        ]);
    }
}
