<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class AutorisationPointage extends Model
{
    use HasUuids;

    protected $table = 'autorisations_pointage';

    protected $fillable = [
        'stagiaire_id',
        'stagiaire_adresse',
        'stagiaire_telephone',
        'entreprise_id',
        'carnet_id',
        'statut',
        'code_validation',
        'poste',
        'date_debut',
        'date_fin',
        'conditions_stage',
        'etablissement_nom',
        'tuteur_designe',
        'objet_stage',
        'objet_stage_autre',
        'cursus_rattachement',
        'lieu_execution',
        'lieu_execution_lat',
        'lieu_execution_lng',
        'nombre_mois_stage',
        'duree_hebdomadaire',
        'jours_presence',
        'teletravail_modalites',
        'referent_pedagogique_nom',
        'referent_pedagogique_contact',
        'tuteur_valide_le',
        'stagiaire_valide_le',
        'raison_sociale_custom',
        'adresse_custom',
        'secteur_activite_custom',
        'entreprise_email_document',
        'entreprise_telephone_document',
        'representant_legal_nom',
        'representant_legal_fonction',
        'representant_legal_contact',
        'gratification_prevue',
        'gratification_montant',
        'gratification_periodicite',
        'conges_absences',
        'stagiaire_annee_academique',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'jours_presence' => 'array',
        'tuteur_valide_le' => 'datetime',
        'stagiaire_valide_le' => 'datetime',
        'lieu_execution_lat' => 'float',
        'lieu_execution_lng' => 'float',
        'nombre_mois_stage' => 'integer',
        'gratification_prevue' => 'boolean',
        'gratification_montant' => 'float',
    ];

    public function stagiaire()
    {
        return $this->belongsTo(Stagiaire::class);
    }

    public function entreprise()
    {
        return $this->belongsTo(Entreprise::class);
    }

    public function convention()
    {
        return $this->hasOne(Convention::class, 'autorisation_pointage_id');
    }

    public function carnet()
    {
        return $this->belongsTo(CarnetDeStage::class, 'carnet_id');
    }
}
