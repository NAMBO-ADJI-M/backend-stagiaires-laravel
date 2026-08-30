<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use App\Models\CarnetDeStage;

class Convention extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'autorisation_pointage_id',
        'carnet_id',
        'stagiaire_id',
        'entreprise_id',
        'raison_sociale',
        'adresse',
        'secteur_activite',
        'representant_legal_nom',
        'representant_legal_fonction',
        'representant_legal_contact',
        'objet_stage',
        'objet_stage_autre',
        'cursus_rattachement',
        'date_debut',
        'date_fin',
        'duree_hebdomadaire',
        'jours_presence',
        'teletravail_modalites',
        'nombre_mois_stage',
        'lieu_execution',
        'lieu_execution_lat',
        'lieu_execution_lng',
        'modalites_suivi',
        'gratification_prevue',
        'gratification_montant',
        'gratification_periodicite',
        'conges_absences',
        'entreprise_email',
        'entreprise_telephone',

        'stagiaire_nom',
        'stagiaire_prenom',
        'stagiaire_numero',
        'stagiaire_email',
        'stagiaire_telephone',
        'stagiaire_adresse',
        'stagiaire_etablissement',
        'stagiaire_annee_academique',
        'statut',
        'tuteur_valide_le',
        'stagiaire_valide_le',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'jours_presence' => 'array',
        'gratification_prevue' => 'boolean',
        'tuteur_valide_le' => 'datetime',
        'stagiaire_valide_le' => 'datetime',
        'lieu_execution_lat' => 'float',
        'lieu_execution_lng' => 'float',
        'nombre_mois_stage' => 'integer',
    ];

    public function carnet()
    {
        return $this->belongsTo(CarnetDeStage::class, 'carnet_id');
    }

    public function autorisation()
    {
        return $this->belongsTo(AutorisationPointage::class, 'autorisation_pointage_id');
    }

    public function stagiaire()
    {
        return $this->belongsTo(Stagiaire::class, 'stagiaire_id');
    }

    public function entreprise()
    {
        return $this->belongsTo(Entreprise::class, 'entreprise_id');
    }

    /**
     * Retourne true si les deux dates de validation sont renseignées.
     */
    public function estSignee(): bool
    {
        return $this->tuteur_valide_le !== null && $this->stagiaire_valide_le !== null;
    }
}
