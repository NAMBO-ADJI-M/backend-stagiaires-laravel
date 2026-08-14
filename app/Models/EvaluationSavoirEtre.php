<?php
 
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
 
class EvaluationSavoirEtre extends Model
{
    use HasUuids;
 
    protected $table = 'evaluations_savoir_etre';
    public $timestamps = false;
 
    protected $fillable = ['carnet_id', 'critere_id', 'niveau', 'commentaire'];
 
    public function carnet()
    {
        return $this->belongsTo(CarnetDeStage::class, 'carnet_id');
    }
 
    public function critere()
    {
        return $this->belongsTo(CritereSavoirEtre::class, 'critere_id');
    }
}