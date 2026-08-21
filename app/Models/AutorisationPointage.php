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
        'entreprise_id',
        'statut',
        'code_validation',
        'poste',
        'date_debut',
        'date_fin',
        'conditions_stage',
        'etablissement_nom',
        'tuteur_designe',
        'objet_stage',
        'cursus_rattachement',
        'lieu_execution',
        'lieu_execution_lat',
        'lieu_execution_lng',
        'duree_hebdomadaire',
        'jours_presence',
        'teletravail_modalites',
        'referent_pedagogique_nom',
        'referent_pedagogique_contact',
        'modalites_suivi_detail',
    ];

    public function stagiaire()
    {
        return $this->belongsTo(Stagiaire::class);
    }

    public function entreprise()
    {
        return $this->belongsTo(Entreprise::class);
    }
}
