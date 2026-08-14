<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class CritereSavoirEtre extends Model
{
    use HasUuids;

    protected $table = 'criteres_savoir_etre';
    public $timestamps = false;

    protected $fillable = ['entreprise_id', 'nom', 'description'];

    public function entreprise()
    {
        return $this->belongsTo(Entreprise::class, 'entreprise_id');
    }

    public function evaluations()
    {
        return $this->hasMany(EvaluationSavoirEtre::class, 'critere_id');
    }
}