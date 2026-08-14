<?php
 
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
 
class Metier extends Model
{
    use HasUuids;
 
    protected $table = 'metiers';
    public $timestamps = false;
 
    protected $fillable = ['domaine_formation_id', 'nom'];
 
    public function domaineFormation()
    {
        return $this->belongsTo(DomaineFormation::class, 'domaine_formation_id');
    }
 
    public function competences()
    {
        return $this->hasMany(Competence::class, 'metier_id');
    }
}
 