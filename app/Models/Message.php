<?php
 
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
 
class Message extends Model
{
    use HasUuids;
 
    protected $table = 'messages';
    public $timestamps = false;
 
    protected $fillable = ['trajet_id', 'auteur_id', 'contenu', 'lu'];
 
    protected $casts = [
    'lu' => 'boolean',
    'date_envoi' => 'datetime',
];
 
    public function trajet()
    {
        return $this->belongsTo(Trajet::class, 'trajet_id');
    }
 
    public function auteur()
    {
        return $this->belongsTo(Stagiaire::class, 'auteur_id');
    }
}