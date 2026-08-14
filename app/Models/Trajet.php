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
        'conducteur_id', 'depart_lat', 'depart_lng', 'arrivee_lat', 'arrivee_lng',
        'heure_depart', 'jours_recurrence', 'places_disponibles', 'statut',
    ];
 
    protected $casts = [
        'jours_recurrence' => 'array',
    ];
 
    public function conducteur()
    {
        return $this->belongsTo(Stagiaire::class, 'conducteur_id');
    }
 
    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'trajet_id');
    }
 
    public function messages()
    {
        return $this->hasMany(Message::class, 'trajet_id');
    }
 
    public function signalements()
    {
        return $this->hasMany(Signalement::class, 'trajet_id');
    }
}