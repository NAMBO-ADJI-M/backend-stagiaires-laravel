<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Trajet extends Model
{
    use HasUuids;

    protected $table = 'trajets';
    public $timestamps = false;

    protected $fillable = [
        'conducteur_id',
        'depart_lat',
        'depart_lng',
        'arrivee_lat',
        'arrivee_lng',
        'current_lat',
        'current_lng',
        'last_position_update',
        'lieu_depart',
        'lieu_arrivee',
        'date_depart',
        'places_disponibles',
        'tarif',
        'description',
        'statut',
    ];

    protected $casts = [
        'date_depart' => 'datetime',
        'last_position_update' => 'datetime',
        'depart_lat' => 'float',
        'depart_lng' => 'float',
        'arrivee_lat' => 'float',
        'arrivee_lng' => 'float',
        'current_lat' => 'float',
        'current_lng' => 'float',
        'tarif' => 'float',
    ];

    public function conducteur()
    {
        return $this->belongsTo(Stagiaire::class, 'conducteur_id');
    }

    /**
     * Réservations confirmées sur ce trajet — sert de base à la liste
     * des passagers affichée dans TrajetDetailsScreen.
     */
    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'trajet_id');
    }

    public function passagers()
    {
        return $this->hasManyThrough(
            Stagiaire::class,
            Reservation::class,
            'trajet_id',   // clé étrangère sur reservations
            'id',          // clé locale sur stagiaires
            'id',          // clé locale sur trajets
            'passager_id'  // clé étrangère sur reservations vers stagiaires
        )->where('reservations.statut', 'CONFIRMEE');
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'trajet_id');
    }
}
