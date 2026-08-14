<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids, Notifiable, SoftDeletes;

    protected $fillable = [
        'email',
        'password',
        'role',
        'email_verified_at',
        'last_login_at',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Relations
    public function stagiaire()
    {
        return $this->hasOne(Stagiaire::class);
    }

    public function entreprise()
    {
        return $this->hasOne(Entreprise::class);
    }

    // Méthodes utilitaires
    public function isStagiaire()
    {
        return $this->role === 'stagiaire';
    }

    public function isEntreprise()
    {
        return $this->role === 'entreprise';
    }

    public function isVerified()
    {
        return $this->email_verified_at !== null;
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->whereNotNull('email_verified_at');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}