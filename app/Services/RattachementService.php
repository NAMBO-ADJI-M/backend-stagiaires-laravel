<?php

namespace App\Services;

use App\Models\CarnetDeStage;
use App\Models\Convention;
use App\Models\Stagiaire;
use App\Models\Entreprise;
use App\Models\AutorisationPointage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\ConventionSigneeMail;

class RattachementService
{
    /**
     * Effectue le rattachement complet d'un carnet à une entreprise
     * et génère la convention signée, puis envoie les emails.
     */
    public function rattacherEtSigner(CarnetDeStage $carnet, Entreprise $entreprise, AutorisationPointage $autorisation)
    {
        $stagiaire = $carnet->stagiaire;

        // 1. Mise à jour du carnet
        $carnet->update([
            'entreprise_id' => $entreprise->id,
            'statut' => 'EN_COURS',
            'autorisation_suivi' => true,
            'date_rattachement' => now(),
        ]);

        // 2. Création/Mise à jour de la Convention officielle
        $convention = Convention::updateOrCreate(
            ['carnet_id' => $carnet->id],
            [
                'raison_sociale' => $autorisation->raison_sociale_custom ?? $entreprise->raison_sociale,
                'adresse' => $autorisation->adresse_custom ?? $entreprise->adresse_libelle,
                'situation_geographique' => $autorisation->situation_geographique,
                'secteur_activite' => $autorisation->secteur_activite_custom ?? $entreprise->secteur,

                'representant_legal_nom' => $autorisation->representant_legal_nom,
                'representant_legal_fonction' => $autorisation->representant_legal_fonction,
                'representant_legal_contact' => $autorisation->representant_legal_contact,

                'date_debut' => $autorisation->date_debut,
                'date_fin' => $autorisation->date_fin,
                'duree_hebdomadaire' => $autorisation->duree_hebdomadaire,
                'jours_presence' => $autorisation->jours_presence,
                'lieu_execution' => $autorisation->lieu_execution,
                'modalites_suivi' => $autorisation->modalites_suivi_detail,

                'gratification_prevue' => $autorisation->gratification_prevue,
                'gratification_montant' => $autorisation->gratification_montant,
                'gratification_periodicite' => $autorisation->gratification_periodicite,
                'conges_absences' => $autorisation->conges_absences,

                'entreprise_email' => $autorisation->entreprise_email_document ?? $entreprise->email,
                'entreprise_telephone' => $autorisation->entreprise_telephone_document ?? $entreprise->telephone,

                'tuteur_nom' => $autorisation->tuteur_nom ?? $autorisation->tuteur_designe,
                'tuteur_prenom' => $autorisation->tuteur_prenom,
                'tuteur_fonction' => $autorisation->tuteur_fonction,
                'tuteur_email' => $autorisation->tuteur_email ?? $autorisation->referent_pedagogique_contact,
                'tuteur_telephone' => $autorisation->tuteur_telephone,

                'stagiaire_nom' => $stagiaire->nom,
                'stagiaire_prenom' => $stagiaire->prenom,
                'stagiaire_numero' => $stagiaire->telephone,
                'stagiaire_email' => $stagiaire->email,
                'stagiaire_telephone' => $stagiaire->telephone,
                'stagiaire_adresse' => $stagiaire->domicile_adresse,
                'stagiaire_date_naissance' => $stagiaire->date_naissance,
                'stagiaire_etablissement' => $autorisation->etablissement_nom,
                'stagiaire_annee_academique' => $autorisation->stagiaire_annee_academique ?? $autorisation->cursus_rattachement,

                'statut' => 'signee',
                'tuteur_valide_le' => $autorisation->created_at ?? now(),
                'stagiaire_valide_le' => now(),
            ]
        );

        // 3. Passage de l'autorisation à ACTIVE si ce n'est pas déjà fait
        if ($autorisation->statut !== 'ACTIVE' && $autorisation->statut !== 'CONVENTION_SIGNEE') {
            $autorisation->update(['statut' => 'ACTIVE']);
        }

        // 4. Envoi de la convention par mail
        $this->envoyerConventionParMail($autorisation);

        Log::info("Rattachement et signature convention effectués pour carnet {$carnet->id}");

        return $convention;
    }

    private function envoyerConventionParMail(AutorisationPointage $autorisation)
    {
        $autorisation->load(['entreprise', 'stagiaire']);

        try {
            $mail = new ConventionSigneeMail($autorisation);
            Mail::to($autorisation->stagiaire->email)->send($mail);
            Mail::to($autorisation->entreprise->email)->send($mail);
        } catch (\Exception $e) {
            Log::error("Erreur envoi convention PDF par mail : " . $e->getMessage());
        }
    }
}
