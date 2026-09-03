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

    public function getApercuConvention(Request $request, string $id)
    {
        $user = $request->user();

        // 1. Chercher d'abord dans les autorisations réelles
        $auto = \App\Models\AutorisationPointage::where('id', $id)
            ->with(['entreprise', 'stagiaire'])
            ->first();

        if (!$auto) {
            // 2. Sinon chercher dans les invitations (Fiches)
            $invit = \App\Models\FicheStagiaireInvite::where('id', $id)
                ->with(['entreprise'])
                ->firstOrFail();

            return response()->json([
                'reference' => "PROJET-CONV-" . substr($invit->entreprise_id, 0, 8),
                'parties' => [
                    'entreprise' => $invit->raison_sociale_custom ?? $invit->entreprise->raison_sociale,
                    'stagiaire' => $user->stagiaire->prenom . ' ' . $user->stagiaire->nom,
                    'etablissement' => $invit->etablissement_nom ?? $user->stagiaire->ecole,
                ],
                'details_stage' => [
                    'objet' => $invit->objet_stage,
                    'cursus' => $invit->cursus_rattachement,
                    'periode' => "Du " . $invit->date_debut->format('d/m/Y') . " au " . $invit->date_fin->format('d/m/Y'),
                    'lieu' => $invit->lieu_execution ?? 'Locaux de l\'entreprise',
                ],
                'conditions' => [
                    'duree_hebdo' => $invit->duree_hebdomadaire,
                    'jours_presence' => is_array($invit->jours_presence) ? implode(', ', $invit->jours_presence) : $invit->jours_presence,
                    'teletravail' => $invit->teletravail_modalites ?? 'Non défini',
                    'gratification' => $invit->gratification_prevue
                        ? $invit->gratification_montant . " € (" . $invit->gratification_periodicite . ")"
                        : "Sans gratification",
                ],
                'encadrement' => [
                    'tuteur' => ($invit->tuteur_nom ?? $invit->tuteur_designe) . ' ' . $invit->tuteur_prenom,
                    'referent' => $invit->referent_pedagogique_nom,
                    'contact_referent' => $invit->referent_pedagogique_contact,
                ],
            ]);
        }

        return response()->json([
            'reference' => "CONV-" . substr($auto->entreprise_id, 0, 8),
            'parties' => [
                'entreprise' => $auto->raison_sociale_custom ?? $auto->entreprise->raison_sociale,
                'stagiaire' => $auto->stagiaire->prenom . ' ' . $auto->stagiaire->nom,
                'etablissement' => $auto->etablissement_nom ?? $auto->stagiaire->ecole,
            ],
            'details_stage' => [
                'objet' => $auto->objet_stage,
                'cursus' => $auto->cursus_rattachement,
                'periode' => "Du " . $auto->date_debut->format('d/m/Y') . " au " . $auto->date_fin->format('d/m/Y'),
                'lieu' => $auto->lieu_execution ?? 'Locaux de l\'entreprise',
            ],
            'conditions' => [
                'duree_hebdo' => $auto->duree_hebdomadaire,
                'jours_presence' => is_array($auto->jours_presence) ? implode(', ', $auto->jours_presence) : $auto->jours_presence,
                'teletravail' => $auto->teletravail_modalites ?? 'Non défini',
                'gratification' => $auto->gratification_prevue
                    ? $auto->gratification_montant . " € (" . $auto->gratification_periodicite . ")"
                    : "Sans gratification",
            ],
            'encadrement' => [
                'tuteur' => ($auto->tuteur_nom ?? $auto->tuteur_designe) . ' ' . $auto->tuteur_prenom,
                'referent' => $auto->referent_pedagogique_nom,
                'contact_referent' => $auto->referent_pedagogique_contact,
            ],
        ]);
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
            ->with(['carnet.stagiaire', 'carnet.entreprise', 'carnet.metier'])
            ->firstOrFail();

        $carte = CarteAppuiStage::create([
            'evaluation_id' => $evaluation->id,
            'carnet_id' => $evaluation->carnet_id,
            'entreprise_emettrice_id' => $request->user()->entreprise->id,
            'entreprise_destinataire_nom' => $data['entreprise_destinataire_nom'],
            'entreprise_destinataire_email' => $data['entreprise_destinataire_email'],
            'recommandation' => $data['recommandation'] ?? null,
            'document_genere' => null,
        ]);

        $pdf = Pdf::loadView('pdf.carte_appui', [
            'carte' => $carte,
            'evaluation' => $evaluation,
            'carnet' => $evaluation->carnet,
            'stagiaire' => $evaluation->carnet->stagiaire,
            'entreprise' => $evaluation->carnet->entreprise,
        ]);

        $filename = "cartes_appui/{$carte->id}.pdf";
        Storage::disk('public')->put($filename, $pdf->output());

        $carte->update(['document_genere' => $filename]);

        return response()->json($carte->fresh(), 201);
    }

    // Le stagiaire consulte ses attestations reçues
    public function mesAttestations(Request $request)
    {
        return Attestation::where('stagiaire_id', $request->user()->stagiaire->id)
            ->orderByDesc('date_generation')
            ->get();
    }

    // Le stagiaire consulte ses cartes d'appui stage
    public function mesCartesAppui(Request $request)
    {
        return CarteAppuiStage::where('carnet_id', function ($query) use ($request) {
            $query->select('id')
                ->from('carnets_de_stage')
                ->where('stagiaire_id', $request->user()->stagiaire->id);
        })
        ->with(['entrepriseEmettrice', 'evaluation'])
        ->orderByDesc('id')
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

    // Téléchargement du PDF d'une carte d'appui
    public function telechargerCarteAppui(Request $request, string $carteId)
    {
        $user = $request->user();
        $query = CarteAppuiStage::where('id', $carteId);

        if ($user->role === 'stagiaire') {
            $query->whereHas('evaluation.carnet', function ($q) use ($user) {
                $q->where('stagiaire_id', $user->stagiaire->id);
            });
        } else if ($user->role === 'entreprise') {
            $query->where('entreprise_emettrice_id', $user->entreprise->id);
        }

        $carte = $query->firstOrFail();

        if (!$carte->document_genere || !Storage::disk('public')->exists($carte->document_genere)) {
            return response()->json(['message' => 'Document non disponible.'], 404);
        }

        return Storage::disk('public')->download(
            $carte->document_genere,
            'carte-appui-stage.pdf'
        );
    }
}
