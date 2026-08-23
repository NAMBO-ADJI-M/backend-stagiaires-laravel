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
        'carnet_id',
        'raison_sociale',
        'adresse',
        'situation_geographique',
        'secteur_activite',
        'representant_legal_nom',
        'representant_legal_fonction',
        'representant_legal_contact',
        'date_debut',
        'date_fin',
        'duree_hebdomadaire',
        'jours_presence',
        'lieu_execution',
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
        'stagiaire_etablissement',
        'stagiaire_annee_academique',
        'statut',
        'tuteur_valide_le',
        'stagiaire_valide_le',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'gratification_prevue' => 'boolean',
        'tuteur_valide_le' => 'datetime',
        'stagiaire_valide_le' => 'datetime',
    ];

    public function carnet()
    {
        return $this->belongsTo(CarnetDeStage::class, 'carnet_id');
    }

    /**
     * Retourne true si les deux dates de validation sont renseignées.
     */
    public function estSignee(): bool
    {
        return $this->tuteur_valide_le !== null && $this->stagiaire_valide_le !== null;
    }
}
