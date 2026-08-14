<?php
 
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
 
class Attestation extends Model
{
    use HasUuids;
 
    protected $table = 'attestations';
    public $timestamps = false;
 
    protected $fillable = ['evaluation_id', 'carnet_id', 'stagiaire_id', 'document_genere'];
 
    public function evaluation()
    {
        return $this->belongsTo(EvaluationCompetence::class, 'evaluation_id');
    }
 
    public function stagiaire()
    {
        return $this->belongsTo(Stagiaire::class, 'stagiaire_id');
    }
}