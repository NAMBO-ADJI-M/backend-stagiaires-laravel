<?php
 
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
 
class NiveauFormation extends Model
{
    use HasUuids;
 
    protected $table = 'niveaux_formation';
    public $timestamps = false;
 
    protected $fillable = ['nom'];
}
 
