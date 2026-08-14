<?php
 
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
 
class FicheStagiaireInvite extends Model
{
    use HasUuids;
 
    protected $table = 'fiches_stagiaire_invite';
    public $timestamps = false;
 
    protected $fillable = [
        'entreprise_id', 'nom', 'prenom', 'email',
        'code_invitation', 'utilise', 'carnet_id', 'date_expiration',
    ];
 
    protected $casts = [
        'utilise' => 'boolean',
        'date_expiration' => 'datetime',
    ];
 
    public function entreprise()
    {
        return $this->belongsTo(Entreprise::class, 'entreprise_id');
    }
 
    public function carnet()
    {
        return $this->belongsTo(CarnetDeStage::class, 'carnet_id');
    }
}