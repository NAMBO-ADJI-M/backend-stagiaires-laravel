<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use App\Models\User;

class Entreprise extends Model
{
    use HasFactory, HasUuids, HasApiTokens;

    protected $table = 'entreprises';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'email',
        'photo_profil',
        'raison_sociale',
        'secteur',
        'adresse_libelle',
        'adresse_lat',
        'adresse_lng',
        'rayon_detection_metres',
        'heure_debut_journee',
        'heure_fin_journee',
        'pause_heure_debut',
        'pause_heure_fin',
        'profil_complet',
        'telephone',
        'site_web',
        'derniere_connexion',
    ];

    protected $casts = [
        'profil_complet' => 'boolean',
        'rayon_detection_metres' => 'integer',
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

    public function stagiaires()
    {
        return $this->hasMany(Stagiaire::class);
    }

    public function carnets()
    {
        return $this->hasMany(CarnetDeStage::class, 'entreprise_id');
    }

    public function cartesAppui()
    {
        return $this->hasMany(CarteAppuiStage::class, 'entreprise_emettrice_id');
    }

    public function invitations()
    {
        return $this->hasMany(FicheStagiaireInvite::class, 'entreprise_id');
    }

    public function competencesAjoutees()
    {
        return $this->hasMany(Competence::class, 'entreprise_id');
    }

    public function criteresSavoirEtreAjoutes()
    {
        return $this->hasMany(CritereSavoirEtre::class, 'entreprise_id');
    }

    public function isComplete()
    {
        return $this->profil_complet;
    }
}
