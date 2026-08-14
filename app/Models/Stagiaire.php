<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;


class Stagiaire extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'email',
        'nom',
        'prenom',
        'photo_profil',
        'domicile_adresse',
        'domicile_lat',
        'domicile_lng',
        'lieu_stage_adresse',
        'lieu_stage_lat',
        'lieu_stage_lng',
        'rayon_geofence',
        'autorisation_entraide',
        'profil_complet',
        'carnet_creer',
        'date_naissance',
        'telephone',
        'ecole',
        'filiere',
        'niveau',
        'date_premiere_connexion',
        'derniere_connexion',
    ];

    protected $casts = [
        'autorisation_entraide' => 'boolean',
        'profil_complet' => 'boolean',
        'carnet_creer' => 'boolean',
        'date_naissance' => 'date',
        'date_premiere_connexion' => 'datetime',
        'derniere_connexion' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function carnets()
    {
        return $this->hasMany(CarnetDeStage::class);
    }

    public function trajets()
    {
        return $this->hasMany(Trajet::class, 'conducteur_id');
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'passager_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'auteur_id');
    }

    public function signalements()
    {
        return $this->hasMany(Signalement::class, 'auteur_id');
    }

    public function getFullNameAttribute()
    {
        return $this->prenom . ' ' . $this->nom;
    }

    public function isComplete()
    {
        return $this->profil_complet && $this->carnet_creer;
    }
}