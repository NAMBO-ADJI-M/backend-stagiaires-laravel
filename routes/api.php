<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ReferentielController;
use App\Http\Controllers\CarnetController;
use App\Http\Controllers\FicheStagiaireInviteController;
use App\Http\Controllers\RattachementController;
use App\Http\Controllers\PointageController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\TrajetController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\SignalementController;
use App\Http\Controllers\CritereSavoirEtreController;
use App\Http\Controllers\EvaluationSavoirEtreController;
use App\Http\Controllers\BilanReflexifController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TestEmailController;

// ================================================
// 🔐 ROUTE LOGIN POUR SANCTUM (ÉVITE L'ERREUR)
// ================================================
Route::get('/login', function () {
    return response()->json([
        'message' => 'Non authentifié. Veuillez vous connecter.'
    ], 401);
})->name('login');

// ================================================
// 📧 ROUTE DE TEST EMAIL
// ================================================
Route::get('/test-email', [TestEmailController::class, 'sendTestEmail']);

// ================================================
// RÉFÉRENTIEL (PUBLIC - nécessaire pour les formulaires)
// ================================================
Route::prefix('referentiel')->group(function () {
    Route::get('domaines', [ReferentielController::class, 'domaines']);
    Route::get('metiers', [ReferentielController::class, 'metiers']);
    Route::get('niveaux-formation', [ReferentielController::class, 'niveauxFormation']);
    Route::get('competences', [ReferentielController::class, 'competences']);
});

// ================================================
// AUTHENTIFICATION UNIFIÉE - PUBLIC
// ================================================
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('verify', [AuthController::class, 'verify']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('resend-code', [AuthController::class, 'resendCode']);
});

// ================================================
// ROUTES PROTÉGÉES (AUTH + PROFIL)
// ================================================
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::post('auth/delete-account', [AuthController::class, 'deleteAccount']);
    Route::get('auth/profile', [AuthController::class, 'profile']);

    // Photo de profil (commune)
    Route::post('user/photo', [AuthController::class, 'updatePhotoProfil']);
    Route::delete('user/photo', [AuthController::class, 'supprimerPhotoProfil']);

    // Notifications
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::post('notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead']);

    // Autorisations de pointage (Handshake)
    Route::post('pointage/autorisation', [AutorisationPointageController::class, 'stagiaireToggle']);
    Route::post('pointage/repondre', [AutorisationPointageController::class, 'stagiaireRepond']);
    Route::post('pointage/verifier-code', [AutorisationPointageController::class, 'verifierCode']);
    Route::post('pointage/valider-liaison', [AutorisationPointageController::class, 'validerLiaison']);
    Route::get('pointage/{carnetId}/historique', [PointageController::class, 'historique']);
    Route::post('entreprise/demander-suivi', [AutorisationPointageController::class, 'entrepriseDemande']);

    // ============================================
    // PROFIL - Complétion
    // ============================================
    Route::middleware('profil:stagiaire')->group(function () {
        Route::post('stagiaire/profil', [AuthController::class, 'completeStagiaireProfile']);
    });

    Route::middleware('profil:entreprise')->group(function () {
        Route::post('entreprise/profil', [AuthController::class, 'completeEntrepriseProfile']);
    });

    // ============================================
    // CRITÈRES SAVOIR-ÊTRE (accessibles aux deux)
    // ============================================
    Route::get('criteres-savoir-etre', [CritereSavoirEtreController::class, 'index']);

    // ============================================
    // ROUTES STAGIAIRE UNIQUEMENT
    // ============================================
    Route::middleware('profil:stagiaire')->group(function () {

        // Carnet de stage
        Route::prefix('carnets')->group(function () {
            Route::post('/', [CarnetController::class, 'store']);
            Route::get('/', [CarnetController::class, 'index']);
            Route::get('{carnetId}/stats', [CarnetController::class, 'stats']);
            Route::post('{carnetId}/entrees', [CarnetController::class, 'storeEntree']);
        });

        // Journal & encouragements du carnet — le stagiaire doit pouvoir
        // consulter ses propres entrées/encouragements (onglets Journal
        // et Encouragements de LogbookScreen). Le contrôleur doit vérifier
        // que carnetId appartient bien au stagiaire authentifié.
        Route::get('carnets/{carnetId}/entrees', [CarnetController::class, 'entrees']);
        Route::get('carnets/{carnetId}/encouragements', [CarnetController::class, 'encouragements']);

        // Rattachement
        Route::post('rattacher-carnet', [RattachementController::class, 'rattacher']);

        // Pointage
        Route::prefix('pointage')->group(function () {
            Route::post('arrivee', [PointageController::class, 'arrivee']);
            Route::post('depart', [PointageController::class, 'depart']);
        });

        // Documents
        Route::get('mes-attestations', [DocumentController::class, 'mesAttestations']);
        Route::get('attestations/{attestationId}/telecharger', [DocumentController::class, 'telechargerAttestation']);

        // Bilan réflexif
        Route::prefix('bilans-reflexifs')->group(function () {
            Route::post('/', [BilanReflexifController::class, 'store']);
            Route::get('carnets/{carnetId}/bilans-reflexifs', [BilanReflexifController::class, 'index']);
        });

        // ==========================================
        // COVOITURAGE - Stagiaire
        // ==========================================
        Route::prefix('trajets')->group(function () {
            Route::post('/', [TrajetController::class, 'store']);
            Route::get('/', [TrajetController::class, 'index']);
            Route::get('mes-trajets', [TrajetController::class, 'mesTrajets']);
            Route::post('{id}/position', [TrajetController::class, 'updatePosition']);
        });

        Route::prefix('reservations')->group(function () {
            Route::post('{trajetId}/reserver', [ReservationController::class, 'store']);
            Route::post('{reservationId}/annuler', [ReservationController::class, 'annuler']);
            Route::get('mes-reservations', [ReservationController::class, 'mesReservations']);
        });

        // Messagerie globale et spécifique
        Route::get('messages', [MessageController::class, 'conversations']);
        Route::prefix('trajets/{trajetId}/messages')->group(function () {
            Route::post('/', [MessageController::class, 'store']);
            Route::get('/', [MessageController::class, 'index']);
        });

        Route::post('trajets/{trajetId}/signaler', [SignalementController::class, 'store']);
    });

    // ============================================
    // ROUTES ENTREPRISE UNIQUEMENT
    // ============================================
    Route::middleware('profil:entreprise')->group(function () {

        // Fiches d'invitation
        Route::prefix('fiches-invitation')->group(function () {
            Route::post('/', [FicheStagiaireInviteController::class, 'store']);
            Route::get('/', [FicheStagiaireInviteController::class, 'index']);
        });

        // Évaluations compétences
        Route::prefix('evaluations')->group(function () {
            Route::post('/', [EvaluationController::class, 'store']);
            Route::get('carnets/{carnetId}/evaluations', [EvaluationController::class, 'index']);
        });

        // Documents (entreprise)
        Route::prefix('documents')->group(function () {
            Route::get('liaison/{autorisationId}/convention-pdf', [DocumentController::class, 'genererConvention']);
            Route::post('evaluations/{evaluationId}/attestation', [DocumentController::class, 'genererAttestation']);
            Route::post('evaluations/{evaluationId}/carte-appui', [DocumentController::class, 'genererCarteAppui']);
        });

        // Critères savoir-être (entreprise)
        Route::prefix('criteres-savoir-etre')->group(function () {
            Route::post('/', [CritereSavoirEtreController::class, 'store']);
        });

        // Dashboard & Liste Stagiaires
        Route::get('stagiaires', [CarnetController::class, 'listeEntreprise']);
        Route::get('dashboard-stats', [CarnetController::class, 'statsEntreprise']);
        Route::post('carnets/{carnetId}/encourager', [CarnetController::class, 'encourager']);
        Route::patch('entrees-carnet/{id}/commentaire', [CarnetController::class, 'commenterEntree']);

        // Évaluations savoir-être
        Route::prefix('evaluations-savoir-etre')->group(function () {
            Route::post('/', [EvaluationSavoirEtreController::class, 'store']);
            Route::get('carnets/{carnetId}/evaluations-savoir-etre', [EvaluationSavoirEtreController::class, 'index']);
        });

        // Vue entreprise/tuteur sur les mêmes entrées/encouragements
        // (contrôleur : vérifier que le carnet appartient à un stagiaire
        // rattaché à cette entreprise).
        Route::get('carnets/{carnetId}/entrees', [CarnetController::class, 'entrees']);
        Route::get('carnets/{carnetId}/encouragements', [CarnetController::class, 'encouragements']);

        Route::get('attestations/{attestationId}/telecharger', [DocumentController::class, 'telechargerAttestation']);
    });
});

// ================================================
// PROFILE TEST (à garder pour débogage)
// ================================================
Route::middleware('auth:sanctum')->get('/profil/moi', function (Request $request) {
    $user = $request->user();
    return response()->json([
        'id' => $user->id,
        'email' => $user->email,
        'role' => $user->role,
        'type' => get_class($user),
    ]);
});
