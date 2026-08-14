<?php

namespace App\Http\Controllers;

use App\Models\CarnetDeStage;
use App\Models\EntreeCarnet;
use App\Models\IndicateurAssiduite;
use App\Models\ProgressionCompetence;
use App\Models\NotificationEncouragement;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CarnetController extends Controller
{
    /**
     * Crée un carnet de stage pour le stagiaire connecté.
     * Enregistre aussi le lieu de stage (adresse + GPS) sur son profil
     * Stagiaire, utilisé ensuite par le geofencing du pointage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            // ⚠️ Nom de table corrigé : domaines_formation (confirmé par la migration)
            'domaine_formation_id' => 'required|uuid|exists:domaines_formation,id',
            'metier_id' => 'required|uuid|exists:metiers,id',
            'niveau_formation_id' => 'required|uuid|exists:niveaux_formation,id',
            'poste' => 'required|string|max:255',
            'entreprise_nom' => 'required|string|max:255',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',

            'lieu_stage_adresse' => 'required|string|max:255',
            'lieu_stage_lat' => 'required|numeric|between:-90,90',
            'lieu_stage_lng' => 'required|numeric|between:-180,180',
            'rayon_geofence' => 'nullable|integer|min:20|max:1000',
        ]);

        $stagiaire = $request->user()->stagiaire;

        // Enregistre le lieu de stage sur le profil du stagiaire
        // (distinct de son domicile, déjà utilisé pour le covoiturage)
        $stagiaire->update([
            'lieu_stage_adresse' => $validated['lieu_stage_adresse'],
            'lieu_stage_lat' => $validated['lieu_stage_lat'],
            'lieu_stage_lng' => $validated['lieu_stage_lng'],
            'rayon_geofence' => $validated['rayon_geofence'] ?? 100,
            'carnet_creer' => true,
        ]);

        $carnet = CarnetDeStage::create([
            'stagiaire_id' => $stagiaire->id,
            'domaine_formation_id' => $validated['domaine_formation_id'],
            'metier_id' => $validated['metier_id'],
            'niveau_formation_id' => $validated['niveau_formation_id'],
            'poste' => $validated['poste'],
            'entreprise_nom' => $validated['entreprise_nom'],
            'statut' => 'EN_ATTENTE',
            'date_debut' => $validated['date_debut'],
            'date_fin' => $validated['date_fin'],
            'date_creation' => now(),
        ]);

        return response()->json([
            'message' => 'Carnet créé avec succès.',
            'carnet' => $carnet,
        ], 201);
    }

    /**
     * Liste les carnets du stagiaire connecté.
     * Chaque carnet inclut les coordonnées de geofencing à surveiller :
     * priorité au lieu de stage précis saisi par le stagiaire
     * (Stagiaire::lieu_stage_lat/lng), sinon repli sur l'adresse
     * de l'entreprise (Entreprise::adresse_lat/lng).
     */
    public function index(Request $request)
    {
        $stagiaire = $request->user()->stagiaire;

        $carnets = CarnetDeStage::where('stagiaire_id', $stagiaire->id)
            ->with('entreprise:id,adresse_lat,adresse_lng,rayon_detection_metres')
            ->orderByDesc('date_creation')
            ->get()
            ->map(function ($carnet) use ($stagiaire) {
                $carnet->geofence_lat = $stagiaire->lieu_stage_lat ?? $carnet->entreprise?->adresse_lat;
                $carnet->geofence_lng = $stagiaire->lieu_stage_lng ?? $carnet->entreprise?->adresse_lng;
                $carnet->geofence_rayon = $stagiaire->rayon_geofence
                    ?? $carnet->entreprise?->rayon_detection_metres
                    ?? 100;
                return $carnet;
            });

        return response()->json([
            'data' => $carnets,
        ]);
    }

    /**
     * Vérifie que l'utilisateur connecté a le droit de consulter ce carnet :
     * - stagiaire propriétaire du carnet, ou
     * - entreprise à laquelle le carnet est rattaché (entreprise_id renseigné
     *   après le flux de rattachement par code — un carnet non rattaché n'est
     *   donc consultable par aucune entreprise).
     * Lève un 403 explicite sinon (au lieu d'un plantage ou d'un 404 ambigu).
     */
    private function autoriserAccesCarnet(Request $request, CarnetDeStage $carnet): void
    {
        $user = $request->user();

        if ($user->role === 'stagiaire'
            && $user->stagiaire
            && $carnet->stagiaire_id === $user->stagiaire->id) {
            return;
        }

        if ($user->role === 'entreprise'
            && $user->entreprise
            && $carnet->entreprise_id !== null
            && $carnet->entreprise_id === $user->entreprise->id) {
            return;
        }

        abort(403, "Vous n'avez pas accès à ce carnet.");
    }

    /**
     * Stats agrégées pour le dashboard (stagiaire ou entreprise/tuteur rattaché).
     */
    public function stats(Request $request, string $carnetId)
    {
        $carnet = CarnetDeStage::findOrFail($carnetId);
        $this->autoriserAccesCarnet($request, $carnet);

        $indicateur = IndicateurAssiduite::where('carnet_id', $carnet->id)->first();
        $joursPresents = $indicateur->jours_presents ?? 0;
        $joursAttendus = $indicateur->jours_attendus ?? 0;

        $missionsTotales = EntreeCarnet::where('carnet_id', $carnet->id)
            ->where('type', 'MISSION')
            ->count();

        $missionsCompletees = EntreeCarnet::where('carnet_id', $carnet->id)
            ->where('type', 'MISSION')
            ->whereNotNull('date_fin')
            ->count();

        $competencesValidees = ProgressionCompetence::where('carnet_id', $carnet->id)
            ->where('niveau_tuteur', 'MAITRISEE')
            ->count();

        $progressionGlobale = $joursAttendus > 0
            ? (int) round(($joursPresents / $joursAttendus) * 100)
            : 0;

        return response()->json([
            'data' => [
                'progression_globale' => $progressionGlobale,
                'jours_presents' => $joursPresents,
                'jours_attendus' => $joursAttendus,
                'missions_completees' => $missionsCompletees,
                'missions_totales' => $missionsTotales,
                'competences_validees' => $competencesValidees,
                'activites_recentes' => $this->activitesRecentes($carnet->id),
            ],
        ]);
    }

    /**
     * Journal complet d'un carnet : toutes les entrées MISSION et
     * DIFFICULTE, sans limite (contrairement à activitesRecentes()
     * qui n'en montre que 5, mélangées aux encouragements et à la
     * présence). Alimente l'onglet "Journal" du carnet, côté
     * stagiaire comme côté entreprise/tuteur rattaché.
     */
    public function entrees(Request $request, string $carnetId)
    {
        $carnet = CarnetDeStage::findOrFail($carnetId);
        $this->autoriserAccesCarnet($request, $carnet);

        $entrees = EntreeCarnet::where('carnet_id', $carnet->id)
            ->whereIn('type', ['MISSION', 'DIFFICULTE'])
            ->orderByDesc('date_debut')
            ->get();

        return response()->json(['data' => $entrees]);
    }

    /**
     * Historique complet des encouragements du tuteur pour un carnet,
     * sans limite (contrairement à activitesRecentes() qui n'en
     * montre que 5, mélangés au journal). Alimente l'onglet
     * "Encouragements" du carnet, côté stagiaire comme côté
     * entreprise/tuteur rattaché.
     */
    public function encouragements(Request $request, string $carnetId)
    {
        $carnet = CarnetDeStage::findOrFail($carnetId);
        $this->autoriserAccesCarnet($request, $carnet);

        $notifications = NotificationEncouragement::where('carnet_id', $carnet->id)
            ->orderByDesc('date_envoi')
            ->get();

        return response()->json(['data' => $notifications]);
    }

    private function activitesRecentes(string $carnetId): array
    {
        $entrees = EntreeCarnet::where('carnet_id', $carnetId)
            ->whereNotNull('date_fin')
            ->orderByDesc('date_fin')
            ->limit(5)
            ->get()
            ->map(function ($e) {
                return [
                    'type' => strtolower($e->type),
                    'title' => match ($e->type) {
                        'MISSION' => 'Mission clôturée',
                        'PRESENCE' => 'Journée de présence enregistrée',
                        'DIFFICULTE' => 'Difficulté signalée',
                        default => 'Note ajoutée au carnet',
                    },
                    'subtitle' => $e->commentaire_tuteur ?? $e->commentaire_stagiaire ?? '',
                    'date' => optional($e->date_fin)->toIso8601String(),
                ];
            });

        $notifications = NotificationEncouragement::where('carnet_id', $carnetId)
            ->orderByDesc('date_envoi')
            ->limit(5)
            ->get()
            ->map(function ($n) {
                return [
                    'type' => strtolower($n->type),
                    'title' => $n->type === 'FELICITATION'
                        ? 'Félicitations de votre tuteur'
                        : 'Encouragement de votre tuteur',
                    'subtitle' => Str::limit($n->contenu, 80),
                    'date' => optional($n->date_envoi)->toIso8601String(),
                ];
            });

        return $entrees->concat($notifications)
            ->sortByDesc('date')
            ->take(5)
            ->values()
            ->toArray();
    }
}
