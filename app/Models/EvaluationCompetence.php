<?php
 
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
 
class EvaluationCompetence extends Model
{
    use HasUuids;
 
    protected $table = 'evaluations_competence';
    public $timestamps = false;
 
    protected $fillable = ['carnet_id', 'entreprise_id', 'indicateur_assiduite_id', 'jugee_utile'];
 
    protected $casts = [
        'jugee_utile' => 'boolean',
    ];
 
    public function carnet()
    {
        return $this->belongsTo(CarnetDeStage::class, 'carnet_id');
    }
 
    public function entreprise()
    {
        return $this->belongsTo(Entreprise::class, 'entreprise_id');
    }
 
    public function indicateurAssiduite()
    {
        return $this->belongsTo(IndicateurAssiduite::class, 'indicateur_assiduite_id');
    }
 
    public function attestation()
    {
        return $this->hasOne(Attestation::class, 'evaluation_id');
    }
 
    public function carteAppuiStage()
    {
        return $this->hasOne(CarteAppuiStage::class, 'evaluation_id');
    }
}
 