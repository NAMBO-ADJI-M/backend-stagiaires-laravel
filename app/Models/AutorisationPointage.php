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
