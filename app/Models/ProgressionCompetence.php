<?php
 
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
 
class ProgressionCompetence extends Model
{
    use HasUuids;
 
    protected $table = 'progression_competences';
    public $timestamps = false;
 
    protected $fillable = [
        'carnet_id', 'competence_id', 'heures_cumulees',
        'niveau_auto', 'niveau_stagiaire', 'niveau_tuteur', 'appreciation_tuteur',
    ];
 
    public function carnet()
    {
        return $this->belongsTo(CarnetDeStage::class, 'carnet_id');
    }
 
    public function competence()
    {
        return $this->belongsTo(Competence::class, 'competence_id');
    }
}