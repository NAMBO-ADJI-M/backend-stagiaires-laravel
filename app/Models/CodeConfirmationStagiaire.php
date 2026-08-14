<?php
 
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
 
class CodeConfirmationStagiaire extends Model
{
    use HasUuids;
 
    protected $table = 'code_confirmation_stagiaire';
    public $timestamps = false;
 
    protected $fillable = ['stagiaire_id', 'email', 'code', 'utilise', 'date_expiration'];
 
    protected $casts = [
        'utilise' => 'boolean',
        'date_expiration' => 'datetime',
    ];
 
    public function stagiaire()
    {
        return $this->belongsTo(Stagiaire::class, 'stagiaire_id');
    }
}
 
 