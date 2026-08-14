<?php
 
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
 
class BilanReflexif extends Model
{
    use HasUuids;
 
    protected $table = 'bilans_reflexifs';
    public $timestamps = false;
 
    protected $fillable = ['carnet_id', 'periode', 'contenu'];
 
    public function carnet()
    {
        return $this->belongsTo(CarnetDeStage::class, 'carnet_id');
    }
}