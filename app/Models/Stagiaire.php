<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;


class Stagiaire extends Model
{
    use HasFactory, HasUuids;

    /**
     * Synchronisation automatique de l'email avec le compte User associé.
     */
    protected static function booted()
    {
        static::saving(function ($stagiaire) {
            if ($stagiaire->user_id) {
                // On s'assure que le profil métier a toujours le même email que le compte d'auth
                $user = $stagiaire->user ?? \App\Models\User::find($stagiaire->user_id);
                if ($user) {
                    $stagiaire->email = $user->email;
                }
            }
        });
    }

    /**
     * Accesseur de sécurité : garantit que l'email retourné est celui de l'utilisateur
     * si l'email local est vide.
     */
    public function getEmailAttribute($value)
    {
        if (empty($value) && ($this->user_id || $this->relationLoaded('user'))) {
            return $this->user ? $this->user->email : $value;
        }
        return $value;
    }

    protected $fillable = [
        'user_id',
        'email',
        'nom',
        'prenom',
        'photo_profil',
        'domicile_adresse',
        'domicile_lat',
        'domicile_lng',
        'lieu_stage_adresse',
        'lieu_stage_lat',
        'lieu_stage_lng',
        'rayon_geofence',
        'autorisation_entraide',
        'profil_complet',
        'carnet_creer',
        'date_naissance',
        'telephone',
        'ecole',
        'filiere',
        'niveau',
        'date_premiere_connexion',
        'derniere_connexion',
    ];

    protected $casts = [
        'autorisation_entraide' => 'boolean',
        'profil_complet' => 'boolean',
        'carnet_creer' => 'boolean',
        'date_naissance' => 'date',
        'date_premiere_connexion' => 'datetime',
        'derniere_connexion' => 'datetime',
    ];

    protected $appends = ['photo_profil_url'];

    public function getPhotoProfilUrlAttribute()
    {
        if (!$this->photo_profil) {
            return null;
        }
        if (filter_var($this->photo_profil, FILTER_VALIDATE_URL)) {
            return $this->photo_profil;
        }
        return url('storage/' . $this->photo_profil);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function carnets()
    {
        return $this->hasMany(CarnetDeStage::class);
    }

    public function trajets()
    {
        return $this->hasMany(Trajet::class, 'conducteur_id');
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'passager_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'auteur_id');
    }

    public function signalements()
    {
        return $this->hasMany(Signalement::class, 'auteur_id');
    }

    public function getFullNameAttribute()
    {
        return $this->prenom . ' ' . $this->nom;
    }

    public function isComplete()
    {
        return $this->profil_complet && $this->carnet_creer;
    }
}
