<?php
 
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
 
class Competence extends Model
{
    use HasUuids;
 
    protected $table = 'competences';
    public $timestamps = false;
 
    protected $fillable = [
        'metier_id', 'entreprise_id', 'nom', 'description',
        'seuil_decouverte', 'seuil_maitrise', 'mots_cles_detection',
    ];
 
    protected $casts = [
        'mots_cles_detection' => 'array',
    ];
 
    public function metier()
    {
        return $this->belongsTo(Metier::class, 'metier_id');
    }
 
    public function entreprise()
    {
        return $this->belongsTo(Entreprise::class, 'entreprise_id');
    }
 
    public function progressions()
    {
        return $this->hasMany(ProgressionCompetence::class, 'competence_id');
    }
}
 