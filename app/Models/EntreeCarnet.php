<?php
 
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
 
class EntreeCarnet extends Model
{
    use HasUuids;
 
    protected $table = 'entrees_carnet';
    public $timestamps = false;
 
    protected $fillable = [
        'carnet_id', 'type', 'date_debut', 'date_fin',
        'position_lat', 'position_lng', 'source_validation',
        'commentaire_stagiaire', 'commentaire_tuteur',
        'session_id', 'statut_cloture',
    ];
 
    protected $casts = [
        'date_debut' => 'datetime',
        'date_fin' => 'datetime',
    ];
 
    public function carnet()
    {
        return $this->belongsTo(CarnetDeStage::class, 'carnet_id');
    }
}
