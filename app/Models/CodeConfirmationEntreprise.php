<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class CodeConfirmationEntreprise extends Model
{
    use HasUuids;

    protected $table = 'code_confirmation_entreprise';
    public $timestamps = false;

    protected $fillable = ['entreprise_id', 'code', 'utilise', 'date_expiration'];

    protected $casts = [
        'utilise' => 'boolean',
        'date_expiration' => 'datetime',
    ];

    public function entreprise()
    {
        return $this->belongsTo(Entreprise::class, 'entreprise_id');
    }
}