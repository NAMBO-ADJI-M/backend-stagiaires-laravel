<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class FicheStagiaireInvite extends Model
{
    use HasUuids;

    protected $table = 'fiches_stagiaire_invite';
    public $timestamps = false;

    protected $fillable = [
        'entreprise_id', 'nom', 'prenom', 'email',
        'code_invitation', 'utilise', 'carnet_id', 'date_expiration',
        'poste', 'date_debut', 'date_fin', 'etablissement_nom', 'tuteur_designe',
        'objet_stage', 'objet_stage_autre', 'cursus_rattachement', 'lieu_execution',
        'lieu_execution_lat', 'lieu_execution_lng', 'nombre_mois_stage', 'duree_hebdomadaire',
        'jours_presence', 'teletravail_modalites', 'referent_pedagogique_nom',
        'referent_pedagogique_contact', 'conditions_stage',
        'raison_sociale_custom', 'adresse_custom', 'secteur_activite_custom',
        'entreprise_email_document', 'entreprise_telephone_document',
        'representant_legal_nom', 'representant_legal_fonction', 'representant_legal_contact',
        'gratification_prevue', 'gratification_montant', 'gratification_periodicite', 'conges_absences',
        'stagiaire_annee_academique',
    ];

    protected $casts = [
        'utilise' => 'boolean',
        'jours_presence' => 'array',
        'date_expiration' => 'datetime',
        'lieu_execution_lat' => 'float',
        'lieu_execution_lng' => 'float',
        'nombre_mois_stage' => 'integer',
        'gratification_prevue' => 'boolean',
        'gratification_montant' => 'float',
    ];

    public function entreprise()
    {
        return $this->belongsTo(Entreprise::class, 'entreprise_id');
    }

    public function carnet()
    {
        return $this->belongsTo(CarnetDeStage::class, 'carnet_id');
    }
}
