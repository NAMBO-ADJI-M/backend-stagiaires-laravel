<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class DomaineFormation extends Model
{
    use HasUuids;

    protected $table = 'domaines_formation';
    public $timestamps = false;

    protected $fillable = ['nom'];

    public function metiers()
    {
        return $this->hasMany(Metier::class, 'domaine_formation_id');
    }
}