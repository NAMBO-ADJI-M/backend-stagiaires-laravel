<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Models\Entreprise;

class CarteAppuiStage extends Model
{
    use HasUuids;

    protected $table = 'cartes_appui_stage';
    public $timestamps = false;

    protected $fillable = [
        'evaluation_id', 'carnet_id', 'entreprise_emettrice_id',
        'entreprise_destinataire_nom', 'entreprise_destinataire_email',
        'recommandation', 'document_genere',
    ];

    public function evaluation()
    {
        return $this->belongsTo(EvaluationCompetence::class, 'evaluation_id');
    }

    public function entrepriseEmettrice()
    {
        return $this->belongsTo(Entreprise::class, 'entreprise_emettrice_id');
    }

    public function carnet()
    {
        return $this->belongsTo(CarnetDeStage::class, 'carnet_id');
    }
}
