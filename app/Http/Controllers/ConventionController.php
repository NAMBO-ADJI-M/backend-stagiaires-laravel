<?php

namespace App\Http\Controllers;

use App\Models\Convention;
use App\Models\CarnetDeStage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConventionController extends Controller
{
    /**
     * Crée ou met à jour la convention associée à un carnet.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'carnet_id' => 'required|uuid|exists:carnets_de_stage,id',
            // Champs Entreprise / Tuteur
            'raison_sociale' => 'nullable|string|max:255',
            'adresse' => 'nullable|string|max:255',
            'situation_geographique' => 'nullable|string|max:255',
            'secteur_activite' => 'nullable|string|max:255',
            'representant_legal_nom' => 'nullable|string|max:255',
            'representant_legal_fonction' => 'nullable|string|max:255',
            'representant_legal_contact' => 'nullable|string|max:255',
            // Nouveaux champs Tuteur
            'tuteur_nom' => 'nullable|string|max:255',
            'tuteur_prenom' => 'nullable|string|max:255',
            'tuteur_fonction' => 'nullable|string|max:255',
            'tuteur_email' => 'nullable|email|max:255',
            'tuteur_telephone' => 'nullable|string|max:20',

            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'duree_hebdomadaire' => 'nullable|string|max:100',
            'jours_presence' => 'nullable|string|max:255',
            'lieu_execution' => 'nullable|string|max:255',
            'modalites_suivi' => 'nullable|string',
            'gratification_prevue' => 'nullable|boolean',
            'gratification_montant' => 'nullable|numeric',
            'gratification_periodicite' => 'nullable|string|max:100',
            'conges_absences' => 'nullable|string',
            'entreprise_email' => 'nullable|email|max:255',
            'entreprise_telephone' => 'nullable|string|max:20',
            // Champs Stagiaire
            'stagiaire_nom' => 'nullable|string|max:100',
            'stagiaire_prenom' => 'nullable|string|max:100',
            'stagiaire_numero' => 'nullable|string|max:50',
            'stagiaire_email' => 'nullable|email|max:255',
            'stagiaire_telephone' => 'nullable|string|max:20',
            'stagiaire_adresse' => 'nullable|string|max:255',
            'stagiaire_date_naissance' => 'nullable|date',
            'stagiaire_etablissement' => 'nullable|string|max:255',
            'stagiaire_annee_academique' => 'nullable|string|max:50',
        ]);

        $carnet = CarnetDeStage::findOrFail($validated['carnet_id']);
        $this->autoriserAccesCarnet($request, $carnet);

        $convention = Convention::updateOrCreate(
            ['carnet_id' => $carnet->id],
            $validated
        );

        // Synchronisation avec le profil Stagiaire
        if ($carnet->stagiaire) {
            $carnet->stagiaire->update([
                'nom' => $validated['stagiaire_nom'] ?? $carnet->stagiaire->nom,
                'prenom' => $validated['stagiaire_prenom'] ?? $carnet->stagiaire->prenom,
                'telephone' => $validated['stagiaire_telephone'] ?? $carnet->stagiaire->telephone,
                'domicile_adresse' => $validated['stagiaire_adresse'] ?? $carnet->stagiaire->domicile_adresse,
                'date_naissance' => $validated['stagiaire_date_naissance'] ?? $carnet->stagiaire->date_naissance,
                'ecole' => $validated['stagiaire_etablissement'] ?? $carnet->stagiaire->ecole,
            ]);
        }

        // Synchronisation avec le profil Entreprise
        if ($carnet->entreprise) {
            $carnet->entreprise->update([
                'raison_sociale' => $validated['raison_sociale'] ?? $carnet->entreprise->raison_sociale,
                'secteur' => $validated['secteur_activite'] ?? $carnet->entreprise->secteur,
                'adresse_libelle' => $validated['adresse'] ?? $carnet->entreprise->adresse_libelle,
                'telephone' => $validated['entreprise_telephone'] ?? $carnet->entreprise->telephone,
            ]);
        }

        // Mise à jour du statut si tout est rempli
        if ($convention->statut === 'brouillon' && $this->estComplet($convention)) {
            $convention->update(['statut' => 'en_attente']);
        }

        return response()->json([
            'message' => 'Convention enregistrée et profils mis à jour.',
            'data' => $convention
        ]);
    }

    /**
     * Validation par le tuteur.
     */
    public function validerParTuteur(Request $request, $id)
    {
        $convention = Convention::findOrFail($id);
        $carnet = $convention->carnet;

        // Vérification des droits : doit être l'entreprise rattachée au carnet
        $user = $request->user();
        if ($user->role !== 'entreprise' || $carnet->entreprise_id !== $user->entreprise->id) {
            abort(403, "Seul le tuteur rattaché peut valider cette convention.");
        }

        // Validation métier : tous les champs obligatoires du tuteur doivent être remplis
        $champsManquants = $this->verifierChampsTuteur($convention);
        if (!empty($champsManquants)) {
            return response()->json([
                'message' => 'Le formulaire tuteur est incomplet.',
                'champs_manquants' => $champsManquants
            ], 422);
        }

        $convention->tuteur_valide_le = now();

        if ($convention->stagiaire_valide_le !== null) {
            $convention->statut = 'signee';
            // Sync avec l'autorisation de pointage
            $autorisation = \App\Models\AutorisationPointage::where('stagiaire_id', $carnet->stagiaire_id)
                ->where('entreprise_id', $carnet->entreprise_id)
                ->first();
            if ($autorisation) {
                $autorisation->update(['statut' => 'CONVENTION_SIGNEE']);
            }
        } else {
            $convention->statut = 'en_attente';
        }

        $convention->save();

        return response()->json([
            'message' => 'Convention validée par le tuteur.',
            'data' => $convention
        ]);
    }

    /**
     * Validation par le stagiaire.
     */
    public function validerParStagiaire(Request $request, $id)
    {
        $convention = Convention::findOrFail($id);
        $carnet = $convention->carnet;

        // Vérification des droits : doit être le stagiaire propriétaire du carnet
        $user = $request->user();
        if ($user->role !== 'stagiaire' || $carnet->stagiaire_id !== $user->stagiaire->id) {
            abort(403, "Seul le stagiaire peut valider sa propre convention.");
        }

        // Validation métier : tous les champs obligatoires du stagiaire doivent être remplis
        $champsManquants = $this->verifierChampsStagiaire($convention);
        if (!empty($champsManquants)) {
            return response()->json([
                'message' => 'Le formulaire stagiaire est incomplet.',
                'champs_manquants' => $champsManquants
            ], 422);
        }

        $convention->stagiaire_valide_le = now();

        if ($convention->tuteur_valide_le !== null) {
            $convention->statut = 'signee';
            // Sync avec l'autorisation de pointage
            $autorisation = \App\Models\AutorisationPointage::where('stagiaire_id', $carnet->stagiaire_id)
                ->where('entreprise_id', $carnet->entreprise_id)
                ->first();
            if ($autorisation) {
                $autorisation->update(['statut' => 'CONVENTION_SIGNEE']);
            }
        } else {
            $convention->statut = 'en_attente';
        }

        $convention->save();

        return response()->json([
            'message' => 'Convention validée par le stagiaire.',
            'data' => $convention
        ]);
    }

    /**
     * Vérifie si tous les champs (tuteur et stagiaire) sont remplis.
     */
    private function estComplet(Convention $convention): bool
    {
        return empty($this->verifierChampsTuteur($convention))
            && empty($this->verifierChampsStagiaire($convention));
    }

    private function verifierChampsTuteur(Convention $convention): array
    {
        $obligatoires = [
            'raison_sociale', 'adresse', 'situation_geographique', 'secteur_activite',
            'representant_legal_nom', 'representant_legal_fonction', 'representant_legal_contact',
            'tuteur_nom', 'tuteur_prenom', 'tuteur_fonction', 'tuteur_email', 'tuteur_telephone',
            'date_debut', 'date_fin', 'duree_hebdomadaire', 'jours_presence',
            'lieu_execution', 'modalites_suivi', 'entreprise_email', 'entreprise_telephone'
        ];

        $manquants = [];
        foreach ($obligatoires as $champ) {
            if (empty($convention->$champ)) {
                $manquants[] = $champ;
            }
        }

        if ($convention->gratification_prevue && empty($convention->gratification_montant)) {
            $manquants[] = 'gratification_montant';
        }

        return $manquants;
    }

    private function verifierChampsStagiaire(Convention $convention): array
    {
        $obligatoires = [
            'stagiaire_nom', 'stagiaire_prenom', 'stagiaire_numero',
            'stagiaire_email', 'stagiaire_telephone', 'stagiaire_adresse', 'stagiaire_date_naissance',
            'stagiaire_etablissement', 'stagiaire_annee_academique'
        ];

        $manquants = [];
        foreach ($obligatoires as $champ) {
            if (empty($convention->$champ)) {
                $manquants[] = $champ;
            }
        }

        return $manquants;
    }

    private function autoriserAccesCarnet(Request $request, CarnetDeStage $carnet): void
    {
        $user = $request->user();

        if ($user->role === 'stagiaire' && $carnet->stagiaire_id === $user->stagiaire->id) {
            return;
        }

        if ($user->role === 'entreprise' && $carnet->entreprise_id === $user->entreprise->id) {
            return;
        }

        abort(403, "Vous n'avez pas accès à ce carnet.");
    }
}
