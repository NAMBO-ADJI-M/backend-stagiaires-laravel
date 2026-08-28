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
     * Ajoute une entrée dans le journal de bord (MISSION ou DIFFICULTE).
     */
    public function storeEntree(Request $request, string $carnetId)
    {
        $carnet = CarnetDeStage::findOrFail($carnetId);
        $this->autoriserAccesCarnet($request, $carnet);

        $validated = $request->validate([
            'type' => 'required|in:MISSION,DIFFICULTE',
            'titre' => 'required|string|max:255',
            'commentaire_stagiaire' => 'nullable|string',
            'date_debut' => 'required|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
        ]);

        $entree = EntreeCarnet::create([
            'carnet_id' => $carnet->id,
            'type' => $validated['type'],
            'titre' => $validated['titre'],
            'commentaire_stagiaire' => $validated['commentaire_stagiaire'],
            'date_debut' => $validated['date_debut'],
            'date_fin' => $validated['date_fin'],
        ]);

        return response()->json($entree, 201);
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

        if (!$stagiaire) {
            return response()->json([
                'data' => [],
            ]);
        }

        $carnets = CarnetDeStage::where('stagiaire_id', $stagiaire->id)
            ->with(['entreprise:id,adresse_lat,adresse_lng,rayon_detection_metres', 'autorisation'])
            ->orderByDesc('date_creation')
            ->get()
            ->map(function ($carnet) use ($stagiaire) {
                // Priorité 1 : Lieu d'exécution saisi dans la convention
                // Priorité 2 : Lieu de stage précis saisi par le stagiaire
                // Priorité 3 : Adresse du siège de l'entreprise
                $carnet->geofence_lat = $carnet->autorisation?->lieu_execution_lat
                    ?? $stagiaire->lieu_stage_lat
                    ?? $carnet->entreprise?->adresse_lat;

                $carnet->geofence_lng = $carnet->autorisation?->lieu_execution_lng
                    ?? $stagiaire->lieu_stage_lng
                    ?? $carnet->entreprise?->adresse_lng;

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
     * LOGIQUE DE CONFIDENTIALITÉ : Le tuteur ne voit QUE les stats de présence.
     */
    public function stats(Request $request, string $carnetId)
    {
        $carnet = CarnetDeStage::findOrFail($carnetId);
        $this->autoriserAccesCarnet($request, $carnet);

        // Si la convention est signée, le tuteur n'accède plus aux stats du carnet
        if ($request->user()->role === 'entreprise' && $carnet->convention && $carnet->convention->statut === 'signee') {
            abort(403, "L'accès au carnet est bloqué car la convention est signée.");
        }

        $indicateur = $carnet->indicateurAssiduite;
        $joursPresents = $indicateur->jours_presents ?? 0;
        $joursAttendus = $indicateur->jours_attendus ?? 0;

        $progressionGlobale = $joursAttendus > 0
            ? (int) round(($joursPresents / $joursAttendus) * 100)
            : 0;

        $stats = [
            'progression_globale' => $progressionGlobale,
            'jours_presents' => $joursPresents,
            'jours_attendus' => $joursAttendus,
            'activites_recentes' => $this->activitesRecentes($carnet->id, $request->user()->role),
        ];

        // LOGIQUE PRIVÉE : Seul le stagiaire voit le détail des missions et compétences
        if ($request->user()->role === 'stagiaire') {
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

            $stats['missions_completees'] = $missionsCompletees;
            $stats['missions_totales'] = $missionsTotales;
            $stats['competences_validees'] = $competencesValidees;
        }

        return response()->json(['data' => $stats]);
    }

    /**
     * Journal complet d'un carnet.
     * LOGIQUE DE CONFIDENTIALITÉ : Le tuteur n'a pas accès au journal des missions.
     */
    public function entrees(Request $request, string $carnetId)
    {
        $carnet = CarnetDeStage::findOrFail($carnetId);
        $this->autoriserAccesCarnet($request, $carnet);

        // Les missions et difficultés sont PRIVÉES
        if ($request->user()->role === 'entreprise') {
            return response()->json(['data' => [], 'message' => 'Le journal des missions est privé.'], 403);
        }

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

        // Le tuteur ne peut voir les encouragements que si la convention est signée
        if ($request->user()->role === 'entreprise' && (!$carnet->convention || $carnet->convention->statut !== 'signee')) {
            abort(403, "La convention doit être signée pour accéder aux encouragements.");
        }

        $notifications = NotificationEncouragement::where('carnet_id', $carnet->id)
            ->orderByDesc('date_envoi')
            ->get();

        return response()->json(['data' => $notifications]);
    }

    /**
     * Envoie un encouragement ou une félicitation à un stagiaire.
     */
    public function encourager(Request $request, string $carnetId)
    {
        $carnet = CarnetDeStage::findOrFail($carnetId);
        $this->autoriserAccesCarnet($request, $carnet);

        // Le tuteur ne peut encourager que si la convention est signée
        if (!$carnet->convention || $carnet->convention->statut !== 'signee') {
            return response()->json(['message' => 'Action impossible. La convention doit être signée par les deux parties.'], 403);
        }

        $validated = $request->validate([
            'type' => 'required|in:ENCOURAGEMENT,FELICITATION',
            'contenu' => 'required|string|max:500',
        ]);

        $notification = NotificationEncouragement::create([
            'carnet_id' => $carnet->id,
            'type' => $validated['type'],
            'contenu' => $validated['contenu'],
            'date_envoi' => now(),
            'lu' => false,
        ]);

        return response()->json([
            'message' => 'Encouragement envoyé avec succès.',
            'data' => $notification
        ], 201);
    }

    /**
     * Liste tous les stagiaires (carnets) rattachés à l'entreprise connectée.
     * PLUS : Liste les stagiaires disponibles (non rattachés) pour la découverte.
     */
    public function listeEntreprise(Request $request)
    {
        $entrepriseId = $request->user()->entreprise->id;

        // 1. Stagiaires déjà rattachés
        $rattaches = CarnetDeStage::where('entreprise_id', $entrepriseId)
            ->with(['stagiaire:id,nom,prenom,photo_profil', 'convention'])
            ->orderByDesc('date_rattachement')
            ->get()
            ->map(function($carnet) use ($entrepriseId) {
                $autorisation = \App\Models\AutorisationPointage::where('stagiaire_id', $carnet->stagiaire_id)
                    ->where('entreprise_id', $entrepriseId)
                    ->first();

                $indicateur = $carnet->indicateurAssiduite;
                $joursPresents = $indicateur->jours_presents ?? 0;
                $joursAttendus = $indicateur->jours_attendus ?? 0;
                $progression = $joursAttendus > 0 ? ($joursPresents / $joursAttendus) : 0;

                $statut = $autorisation ? $autorisation->statut : 'INACTIVE';
                if ($carnet->convention && $carnet->convention->statut === 'signee') {
                    $statut = 'CONVENTION_SIGNEE';
                }

                $carnet->autorisation_pointage_statut = $statut;
                $carnet->presence_progress = $progression;
                $carnet->is_linked = true;
                return $carnet;
            });

        // 2. Stagiaires disponibles (tous ceux qui ont un compte mais pas encore de stage actif)
        $disponibles = \App\Models\Stagiaire::whereDoesntHave('carnets', function ($q) {
                $q->where('statut', 'EN_COURS')->whereNotNull('entreprise_id');
            })
            ->where('profil_complet', true)
            ->whereNotNull('nom')
            ->whereNotNull('prenom')
            ->whereNotNull('ecole')
            ->whereNotNull('filiere')
            ->select('id', 'email', 'nom', 'prenom', 'photo_profil', 'ecole', 'filiere', 'created_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(function($stagiaire) {
                return [
                    'stagiaire' => $stagiaire,
                    'autorisation_pointage_statut' => 'DISPONIBLE',
                    'is_linked' => false
                ];
            });

        return response()->json([
            'rattaches' => $rattaches,
            'disponibles' => $disponibles
        ]);
    }

    /**
     * Statistiques globales pour le dashboard entreprise.
     */
    public function statsEntreprise(Request $request)
    {
        $entrepriseId = $request->user()->entreprise->id;

        $carnetsIds = CarnetDeStage::where('entreprise_id', $entrepriseId)->pluck('id');

        $nbActifs = CarnetDeStage::where('entreprise_id', $entrepriseId)
            ->where('statut', 'EN_COURS')
            ->count();

        // Calcul de l'assiduité moyenne pour l'ensemble des stagiaires rattachés
        $progressions = IndicateurAssiduite::whereHas('autorisation', function($q) use ($entrepriseId) {
            $q->where('entreprise_id', $entrepriseId);
        })->get();
        $moyenne = 0;
        if ($progressions->isNotEmpty()) {
            $somme = $progressions->sum(function ($p) {
                return $p->jours_attendus > 0 ? ($p->jours_presents / $p->jours_attendus) * 100 : 0;
            });
            $moyenne = (int) round($somme / $progressions->count());
        }

        return response()->json([
            'data' => [
                'stagiaires_actifs' => $nbActifs,
                'progression_moyenne' => $moyenne,
                'alertes' => $this->detecterInactivite($entrepriseId),
            ]
        ]);
    }

    /**
     * Détecte les stagiaires qui n'ont pas pointé depuis plus de 3 jours.
     * Basé uniquement sur le pointage (PRESENCE) par respect pour la vie privée.
     */
    private function detecterInactivite(string $entrepriseId): array
    {
        $troisJours = now()->subDays(3);

        return CarnetDeStage::where('entreprise_id', $entrepriseId)
            ->where('statut', 'EN_COURS')
            ->whereDoesntHave('entrees', function ($query) use ($troisJours) {
                $query->where('type', 'PRESENCE')->where('date_debut', '>', $troisJours);
            })
            ->with('stagiaire:id,nom,prenom')
            ->get()
            ->map(function ($carnet) {
                $dernierePresence = EntreeCarnet::where('carnet_id', $carnet->id)
                    ->where('type', 'PRESENCE')
                    ->orderByDesc('date_debut')
                    ->first();

                return [
                    'carnet_id' => $carnet->id,
                    'stagiaire_nom' => $carnet->stagiaire ? "{$carnet->stagiaire->prenom} {$carnet->stagiaire->nom}" : "Stagiaire inconnu",
                    'derniere_activite' => $dernierePresence ? $dernierePresence->date_debut->diffForHumans() : 'Jamais',
                ];
            })
            ->toArray();
    }

    /**
     * Permet au tuteur de laisser un commentaire sur une entrée spécifique.
     * LOGIQUE DE CONFIDENTIALITÉ : Interdit si l'entrée est de type MISSION ou DIFFICULTE.
     */
    public function commenterEntree(Request $request, string $entreeId)
    {
        $validated = $request->validate([
            'commentaire_tuteur' => 'required|string|max:1000',
        ]);

        $entree = EntreeCarnet::findOrFail($entreeId);
        $carnet = $entree->carnet;

        // Le tuteur ne peut commenter que si la convention est signée
        if (!$carnet->convention || $carnet->convention->statut !== 'signee') {
            return response()->json(['message' => 'Action impossible. La convention doit être signée par les deux parties.'], 403);
        }

        // Sécurité : Un tuteur ne peut commenter que ce qu'il voit (donc uniquement les pointages)
        if ($entree->type !== 'PRESENCE') {
            return response()->json(['message' => 'Action non autorisée sur ce type d\'entrée.'], 403);
        }

        $entree->update([
            'commentaire_tuteur' => $validated['commentaire_tuteur'],
        ]);

        return response()->json(['message' => 'Commentaire ajouté.', 'data' => $entree]);
    }

    private function activitesRecentes(string $carnetId, string $role = 'stagiaire'): array
    {
        $query = EntreeCarnet::where('carnet_id', $carnetId)->whereNotNull('date_fin');

        // CONFIDENTIALITÉ : Le tuteur ne voit que les activités de type PRESENCE
        if ($role === 'entreprise') {
            $query->where('type', 'PRESENCE');
        }

        $entrees = $query->orderByDesc('date_fin')
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

        // Les encouragements sont visibles par les deux
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
