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
use App\Models\DemandeRattachement;

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
        ]);

        $entreprise = $request->user()->entreprise;

        $code = strtoupper(\Illuminate\Support\Str::random(8));

        // Note : Le rattachement concerne l'autorisation de présence et le suivi du stagiaire (et non son carnet personnel)
        $autorisation = AutorisationPointage::updateOrCreate(
            ['stagiaire_id' => $validated['stagiaire_id'], 'entreprise_id' => $entreprise->id],
            array_merge($validated, [
                'statut' => 'EN_ATTENTE',
                'code_validation' => $code,
                'entreprise_id' => $entreprise->id,
                'tuteur_valide_le' => now(), // 1. Signature / validation initiale par le tuteur
            ])
        );

        // Si une demande de rattachement stagiaire existait, on la marque comme traitée
        DemandeRattachement::where('stagiaire_id', $validated['stagiaire_id'])
            ->where('entreprise_id', $entreprise->id)
            ->where('statut', 'en_attente')
            ->update(['statut' => 'traitee']);

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
                'objet_stage_autre' => $invit->objet_stage_autre,
                'cursus_rattachement' => $invit->cursus_rattachement,
                'lieu_execution' => $invit->lieu_execution,
                'lieu_execution_lat' => $invit->lieu_execution_lat,
                'lieu_execution_lng' => $invit->lieu_execution_lng,
                'nombre_mois_stage' => $invit->nombre_mois_stage,
                'duree_hebdomadaire' => $invit->duree_hebdomadaire,
                'jours_presence' => $invit->jours_presence,
                'teletravail_modalites' => $invit->teletravail_modalites,
                'referent_pedagogique_nom' => $invit->referent_pedagogique_nom,
                'referent_pedagogique_contact' => $invit->referent_pedagogique_contact,
                'conditions_stage' => $invit->conditions_stage,
                'stagiaire_nom' => $stagiaire->nom,
                'stagiaire_prenom' => $stagiaire->prenom,
                'stagiaire_email' => $user->email,
                'invitation_id' => $invit->id,
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
                'objet_stage_autre' => $autorisation->objet_stage_autre,
                'cursus_rattachement' => $autorisation->cursus_rattachement,
                'lieu_execution' => $autorisation->lieu_execution,
                'lieu_execution_lat' => $autorisation->lieu_execution_lat,
                'lieu_execution_lng' => $autorisation->lieu_execution_lng,
                'nombre_mois_stage' => $autorisation->nombre_mois_stage,
                'duree_hebdomadaire' => $autorisation->duree_hebdomadaire,
                'jours_presence' => $autorisation->jours_presence,
                'teletravail_modalites' => $autorisation->teletravail_modalites,
                'referent_pedagogique_nom' => $autorisation->referent_pedagogique_nom,
                'referent_pedagogique_contact' => $autorisation->referent_pedagogique_contact,
                'stagiaire_nom' => $stagiaire->nom,
                'stagiaire_prenom' => $stagiaire->prenom,
                'stagiaire_email' => $user->email,
                'autorisation_id' => $autorisation->id,
            ]);
        }

        return response()->json(['message' => 'Code incorrect, déjà utilisé ou expiré.'], 422);
    }

    /**
     * Sauvegarde un brouillon des informations du stagiaire pendant la saisie.
     * Pas de signature ni de changement de statut ici.
     */
    public function sauvegarderBrouillonLiaison(Request $request)
    {
        $request->validate([
            'entreprise_id' => 'required|uuid|exists:entreprises,id',
            'nom' => 'sometimes|string|max:100',
            'prenom' => 'sometimes|string|max:100',
            'stagiaire_adresse' => 'sometimes|string|max:255',
            'stagiaire_telephone' => 'sometimes|string|max:20',
        ]);

        $stagiaire = $request->user()->stagiaire;

        $stagiaire->update(array_filter([
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'domicile_adresse' => $request->stagiaire_adresse,
            'telephone' => $request->stagiaire_telephone,
        ]));

        // On peut aussi mettre à jour les champs sur l'autorisation elle-même si elle existe déjà
        $autorisation = AutorisationPointage::where('stagiaire_id', $stagiaire->id)
            ->where('entreprise_id', $request->entreprise_id)
            ->first();

        if ($autorisation) {
            $autorisation->update(array_filter([
                'stagiaire_adresse' => $request->stagiaire_adresse,
                'stagiaire_telephone' => $request->stagiaire_telephone,
            ]));
        }

        return response()->json(['message' => 'Brouillon sauvegardé.']);
    }

    /**
     * Étape 2 Stagiaire : Valide définitivement la liaison.
     */
    public function validerLiaison(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'entreprise_id' => 'required|uuid|exists:entreprises,id',
            'nom' => 'sometimes|required|string|max:100',
            'prenom' => 'sometimes|required|string|max:100',
            'stagiaire_adresse' => 'sometimes|required|string|max:255',
            'stagiaire_telephone' => 'sometimes|required|string|max:20',
            'stagiaire_annee_academique' => 'nullable|string|max:50',
            'carnet_id' => 'nullable|uuid|exists:carnets_de_stage,id',
        ]);

        $code = strtoupper(trim($request->code));
        $stagiaire = $request->user()->stagiaire;
        $entreprise = Entreprise::findOrFail($request->entreprise_id);

        // Mise à jour du profil stagiaire (coordonnées récoltées lors de la signature)
        $stagiaire->update(array_filter([
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'domicile_adresse' => $request->stagiaire_adresse,
            'telephone' => $request->stagiaire_telephone,
            'profil_complet' => ($request->has(['nom', 'prenom', 'stagiaire_adresse', 'stagiaire_telephone']))
        ]));

        // Rechercher si c'est une invitation
        $invit = \App\Models\FicheStagiaireInvite::where('code_invitation', $code)->first();
        $autorisation = null;

        if ($invit) {
            // Créer l'autorisation réelle de suivi à partir du brouillon d'invitation
            $autorisation = AutorisationPointage::updateOrCreate(
                ['stagiaire_id' => $stagiaire->id, 'entreprise_id' => $request->entreprise_id],
                [
                    'statut' => 'CONVENTION_SIGNEE',
                    'carnet_id' => $request->carnet_id,
                    'poste' => $invit->poste,
                    'date_debut' => $invit->date_debut,
                    'date_fin' => $invit->date_fin,
                    'etablissement_nom' => $invit->etablissement_nom,

                    'tuteur_designe' => $invit->tuteur_designe,
                    'tuteur_nom' => $invit->tuteur_nom,
                    'tuteur_prenom' => $invit->tuteur_prenom,
                    'tuteur_fonction' => $invit->tuteur_fonction,
                    'tuteur_email' => $invit->tuteur_email,
                    'tuteur_telephone' => $invit->tuteur_telephone,

                    'raison_sociale_custom' => $invit->raison_sociale_custom,
                    'adresse_custom' => $invit->adresse_custom,
                    'secteur_activite_custom' => $invit->secteur_activite_custom,
                    'entreprise_email_document' => $invit->entreprise_email_document,
                    'entreprise_telephone_document' => $invit->entreprise_telephone_document,

                    'representant_legal_nom' => $invit->representant_legal_nom,
                    'representant_legal_fonction' => $invit->representant_legal_fonction,
                    'representant_legal_contact' => $invit->representant_legal_contact,

                    'gratification_prevue' => $invit->gratification_prevue,
                    'gratification_montant' => $invit->gratification_montant,
                    'gratification_periodicite' => $invit->gratification_periodicite,
                    'conges_absences' => $invit->conges_absences,

                    'objet_stage' => $invit->objet_stage,
                    'objet_stage_autre' => $invit->objet_stage_autre,
                    'cursus_rattachement' => $invit->cursus_rattachement,
                    'stagiaire_annee_academique' => $request->stagiaire_annee_academique ?? $invit->stagiaire_annee_academique,

                    'lieu_execution' => $invit->lieu_execution,
                    'lieu_execution_lat' => $invit->lieu_execution_lat,
                    'lieu_execution_lng' => $invit->lieu_execution_lng,
                    'nombre_mois_stage' => $invit->nombre_mois_stage,
                    'duree_hebdomadaire' => $invit->duree_hebdomadaire,
                    'jours_presence' => $invit->jours_presence,
                    'teletravail_modalites' => $invit->teletravail_modalites,
                    'referent_pedagogique_nom' => $invit->referent_pedagogique_nom,
                    'referent_pedagogique_contact' => $invit->referent_pedagogique_contact,
                    'conditions_stage' => $invit->conditions_stage,
                    'stagiaire_adresse' => $request->stagiaire_adresse,
                    'stagiaire_telephone' => $request->stagiaire_telephone,

                    // 2. Signatures numériques horodatées
                    'tuteur_valide_le' => $invit->created_at ?? now(),
                    'stagiaire_valide_le' => now(),
                ]
            );

            // Signature de la convention et rattachement éventuel du carnet
            $carnet = $request->carnet_id ? CarnetDeStage::find($request->carnet_id) : null;
            app(\App\Services\RattachementService::class)->rattacherEtSigner($autorisation, $entreprise, $carnet);

            $invit->update(['utilise' => true]);
        } else {
            // Sinon chercher liaison directe
            $autorisation = AutorisationPointage::where('code_validation', $code)
                ->where('stagiaire_id', $stagiaire->id)
                ->first();

            if ($autorisation) {
                $autorisation->update([
                    'carnet_id' => $request->carnet_id,
                    'stagiaire_adresse' => $request->stagiaire_adresse,
                    'stagiaire_telephone' => $request->stagiaire_telephone,
                    'stagiaire_valide_le' => now(),
                    'tuteur_valide_le' => $autorisation->tuteur_valide_le ?? $autorisation->created_at ?? now(),
                    'statut' => 'CONVENTION_SIGNEE',
                    'code_validation' => null
                ]);

                // Signature de la convention et rattachement éventuel du carnet
                $carnet = $request->carnet_id ? CarnetDeStage::find($request->carnet_id) : null;
                app(\App\Services\RattachementService::class)->rattacherEtSigner($autorisation, $entreprise, $carnet);
            }
        }

        if ($autorisation) {
            return response()->json([
                'message' => 'Convention signée et liaison établie !',
                'statut' => 'CONVENTION_SIGNEE',
                'autorisation_id' => $autorisation->id,
                'carnet_id' => $autorisation->carnet_id,
            ]);
        }

        return response()->json(['message' => 'Validation impossible.'], 422);
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

        $stagiaire = $request->user()->stagiaire;
        $autorisation = AutorisationPointage::where('id', $request->autorisation_id)
            ->where('stagiaire_id', $stagiaire->id)
            ->firstOrFail();

        $autorisation->update([
            'statut' => $request->accepter ? 'ACTIVE' : 'REFUSEE'
        ]);

        // Si acceptation, on tente de rattacher le carnet en attente le plus récent
        if ($request->accepter) {
            $carnet = CarnetDeStage::where('stagiaire_id', $stagiaire->id)
                ->whereNull('entreprise_id')
                ->where('statut', 'EN_ATTENTE')
                ->orderByDesc('date_creation')
                ->first();

            if ($carnet) {
                app(\App\Services\RattachementService::class)->rattacherEtSigner(
                    $autorisation,
                    $autorisation->entreprise,
                    $carnet
                );
            }
        }

        return response()->json([
            'message' => $request->accepter ? 'Demande acceptée.' : 'Demande refusée.',
            'statut' => $autorisation->statut
        ]);
    }
}
