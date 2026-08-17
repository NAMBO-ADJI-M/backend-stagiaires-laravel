<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Reservation extends Model
{
    use HasUuids;

    protected $table = 'reservations';
    public $timestamps = false;

    protected $fillable = ['trajet_id', 'passager_id', 'places', 'statut'];

    public function trajet()
    {
        return $this->belongsTo(Trajet::class, 'trajet_id');
    }

    public function passager()
    {
        return $this->belongsTo(Stagiaire::class, 'passager_id');
    }
}