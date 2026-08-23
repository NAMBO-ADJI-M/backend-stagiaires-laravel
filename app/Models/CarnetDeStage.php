<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use App\Models\Stagiaire;

class CarnetDeStage extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'carnets_de_stage';

    public $timestamps = false; // notre table n'a pas created_at/updated_at, juste date_creation

    protected $fillable = [
        'stagiaire_id',
        'entreprise_id',
        'domaine_formation_id',
        'metier_id',
        'niveau_formation_id',
        'poste',
        'entreprise_nom',
        'statut',
        'code_rattachement_utilise',
        'date_rattachement',
        'autorisation_suivi',
        'date_debut',
        'date_fin',
        'date_creation',
    ];

    protected $casts = [
        'autorisation_suivi' => 'boolean',
        'date_debut' => 'date',
        'date_fin' => 'date',
        'date_rattachement' => 'datetime',
        'date_creation' => 'datetime',
    ];

    public function stagiaire()
    {
        return $this->belongsTo(Stagiaire::class);
    }

    public function entreprise()
    {
        return $this->belongsTo(Entreprise::class);
    }

    public function domaineFormation()
    {
        return $this->belongsTo(DomaineFormation::class);
    }

    public function metier()
    {
        return $this->belongsTo(Metier::class);
    }

    public function niveauFormation()
    {
        return $this->belongsTo(NiveauFormation::class);
    }

    public function entrees()
    {
        return $this->hasMany(EntreeCarnet::class, 'carnet_id');
    }

    public function progressions()
    {
        return $this->hasMany(ProgressionCompetence::class);
    }

    public function indicateurAssiduite()
    {
        return $this->hasOne(IndicateurAssiduite::class);
    }

    public function convention()
    {
        return $this->hasOne(Convention::class, 'carnet_id');
    }

    public function autorisation()
    {
        return $this->hasOne(AutorisationPointage::class, 'stagiaire_id', 'stagiaire_id')
                    ->where('entreprise_id', $this->entreprise_id);
    }

    public function estRattache()
    {
        return $this->entreprise_id !== null;
    }

    public function estEnCours()
    {
        return $this->statut === 'EN_COURS';
    }
}
