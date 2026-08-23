<?php

namespace App\Http\Controllers;

use App\Models\EvaluationCompetence;
use App\Models\Attestation;
use App\Models\CarteAppuiStage;
use App\Models\ProgressionCompetence;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    // Génère la convention de stage au format PDF à partir des données de liaison
    public function genererConvention(Request $request, string $autorisationId)
    {
        $user = $request->user();

        $query = \App\Models\AutorisationPointage::where('id', $autorisationId);

        // Sécurité : soit c'est l'entreprise liée, soit c'est le stagiaire lié
        if ($user->role === 'entreprise') {
            $query->where('entreprise_id', $user->entreprise->id);
        } else {
            $query->where('stagiaire_id', $user->stagiaire->id);
        }

        $autorisation = $query->with(['entreprise', 'stagiaire'])->firstOrFail();

        // Récupérer l'URL complète du logo (si présent)
        $logoUrl = $autorisation->entreprise->photo_profil_url;

        $pdf = Pdf::loadView('pdf.convention', [
            'autorisation' => $autorisation,
            'entreprise' => $autorisation->entreprise,
            'stagiaire' => $autorisation->stagiaire,
            'logo_url' => $logoUrl,
        ]);

        return $pdf->download("convention-stage-{$autorisation->id}.pdf");
    }

    // Génère l'attestation seule (le tuteur peut s'arrêter là)
    public function genererAttestation(Request $request, string $evaluationId)
    {
        $evaluation = EvaluationCompetence::where('id', $evaluationId)
            ->where('entreprise_id', $request->user()->entreprise->id)
            ->with(['carnet.stagiaire', 'carnet.entreprise', 'carnet.metier'])
            ->firstOrFail();

        $attestation = Attestation::firstOrCreate(
            ['evaluation_id' => $evaluation->id],
            [
                'carnet_id' => $evaluation->carnet_id,
                'stagiaire_id' => $evaluation->carnet->stagiaire_id,
                'document_genere' => null,
            ]
        );

        // Le détail par compétence (niveau_tuteur), sans l'appréciation libre —
        // décision validée : le niveau final certifie l'acquis, le commentaire
        // reste un usage interne au suivi et n'apparaît pas sur le document officiel.
        $competences = ProgressionCompetence::where('carnet_id', $evaluation->carnet_id)
            ->whereNotNull('niveau_tuteur')
            ->with('competence:id,nom')
            ->get()
            ->map(fn ($p) => [
                'nom' => $p->competence->nom,
                'niveau' => $p->niveau_tuteur,
            ]);

        $pdf = Pdf::loadView('pdf.attestation', [
            'attestation' => $attestation,
            'evaluation' => $evaluation,
            'carnet' => $evaluation->carnet,
            'stagiaire' => $evaluation->carnet->stagiaire,
            'entreprise' => $evaluation->carnet->entreprise,
            'competences' => $competences,
        ]);

        $filename = "attestations/{$attestation->id}.pdf";
        Storage::disk('public')->put($filename, $pdf->output());

        $attestation->update(['document_genere' => $filename]);

        return response()->json([
            'attestation' => $attestation->fresh(),
            'proposition' => 'Souhaitez-vous aussi générer une carte d’appui stage pour ce stagiaire ?',
        ], 201);
    }

    // Génère la carte d'appui stage, séparément, sur décision du tuteur après l'attestation
    public function genererCarteAppui(Request $request, string $evaluationId)
    {
        $data = $request->validate([
            'entreprise_destinataire_nom' => 'required|string|max:150',
            'entreprise_destinataire_email' => 'required|email',
            'recommandation' => 'nullable|string',
        ]);

        $evaluation = EvaluationCompetence::where('id', $evaluationId)
            ->where('entreprise_id', $request->user()->entreprise->id)
            ->firstOrFail();

        $carte = CarteAppuiStage::create([
            'evaluation_id' => $evaluation->id,
            'carnet_id' => $evaluation->carnet_id,
            'entreprise_emettrice_id' => $request->user()->entreprise->id,
            'entreprise_destinataire_nom' => $data['entreprise_destinataire_nom'],
            'entreprise_destinataire_email' => $data['entreprise_destinataire_email'],
            'recommandation' => $data['recommandation'] ?? null,
            'document_genere' => null, // TODO : génération PDF réelle, plus tard
        ]);

        return response()->json($carte, 201);
    }

    // Le stagiaire consulte ses attestations reçues
    public function mesAttestations(Request $request)
    {
        return Attestation::where('stagiaire_id', $request->user()->stagiaire->id)
            ->orderByDesc('date_generation')
            ->get();
    }

    // Le stagiaire télécharge le PDF d'une attestation qui lui appartient
    public function telechargerAttestation(Request $request, string $attestationId)
    {
        $attestation = Attestation::where('id', $attestationId)
            ->where('stagiaire_id', $request->user()->stagiaire->id)
            ->firstOrFail();

        if (!$attestation->document_genere || !Storage::disk('public')->exists($attestation->document_genere)) {
            return response()->json(['message' => 'Document non disponible.'], 404);
        }

        return Storage::disk('public')->download(
            $attestation->document_genere,
            'attestation-stage.pdf'
        );
    }
}
