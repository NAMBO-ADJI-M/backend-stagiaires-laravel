<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class VerificationCode extends Model
{
     use HasUuids;

    // ✅ IMPORTANT : Désactiver l'auto-incrémentation
    public $incrementing = false;
    
    // ✅ IMPORTANT : Définir le type de la clé primaire
    protected $keyType = 'string';

    protected $fillable = [
        'id',        // ✅ Ajouter 'id' dans les champs fillable
        'email',
        'code',
        'type',
        'used',
        'expires_at',
    ];

    protected $casts = [
        'used' => 'boolean',
        'expires_at' => 'datetime',
    ];

    // ✅ Générer automatiquement un UUID lors de la création
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function isValid(): bool
    {
        return !$this->used && $this->expires_at->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}