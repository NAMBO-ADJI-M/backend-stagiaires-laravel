<?php
 
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
 
class IndicateurAssiduite extends Model
{
    use HasUuids;
 
    protected $table = 'indicateurs_assiduite';
    public $timestamps = false;
 
    protected $fillable = ['carnet_id', 'jours_presents', 'jours_attendus', 'heures_totales_realisees'];
 
    public function carnet()
    {
        return $this->belongsTo(CarnetDeStage::class, 'carnet_id');
    }
}