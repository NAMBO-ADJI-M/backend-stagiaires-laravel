<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class DemandeRattachement extends Model
{
    use HasUuids;

    protected $table = 'demandes_rattachement';

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
