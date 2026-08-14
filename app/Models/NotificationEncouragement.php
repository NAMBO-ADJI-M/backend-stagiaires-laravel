<?php
 
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
 
class NotificationEncouragement extends Model
{
    use HasUuids;
 
    protected $table = 'notifications_encouragement';
    public $timestamps = false;
 
    protected $fillable = ['carnet_id', 'entreprise_id', 'type', 'origine', 'contenu', 'lu'];
 
    protected $casts = [
        'lu' => 'boolean',
    ];
 
    public function carnet()
    {
        return $this->belongsTo(CarnetDeStage::class, 'carnet_id');
    }
 
    public function entreprise()
    {
        return $this->belongsTo(Entreprise::class, 'entreprise_id');
    }
}