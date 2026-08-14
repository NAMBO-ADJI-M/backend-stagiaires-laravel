<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Signalement extends Model
{
    use HasUuids;

    protected $table = 'signalements';
    public $timestamps = false;

    protected $fillable = ['auteur_id', 'trajet_id', 'motif', 'description', 'statut'];

    public function auteur()
    {
        return $this->belongsTo(Stagiaire::class, 'auteur_id');
    }

    public function trajet()
    {
        return $this->belongsTo(Trajet::class, 'trajet_id');
    }
}