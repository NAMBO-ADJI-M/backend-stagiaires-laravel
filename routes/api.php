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
use App\Http\Controllers\AutorisationPointageController;

// ================================================
// 🛠️ ROUTE DE SECOURS (PUBLIC)
// ================================================
Route::any('debug/clear-stagiaires', function() {
    try {
        // Nettoyer tout le cache en premier
        \Illuminate\Support\Facades\Artisan::call('optimize:clear');

        // Forcer la migration
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);

        // Nettoyage complet (Ordre respectant les contraintes)
        \App\Models\EntreeCarnet::query()->delete();
        \App\Models\IndicateurAssiduite::query()->delete();
        \App\Models\ProgressionCompetence::query()->delete();
        \App\Models\CarnetDeStage::query()->delete();
        \App\Models\Stagiaire::query()->delete();
        \App\Models\User::where('role', 'stagiaire')->delete();
        \App\Models\FicheStagiaireInvite::query()->delete();
        \App\Models\AutorisationPointage::query()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Système réparé : Cache vidé, BDD migrée et données nettoyées.'
        ]);
    } catch (\Exception $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
});

// ================================================
// 🔐 ROUTE LOGIN POUR SANCTUM
// ================================================
Route::get('/login', function () {
    return response()->json(['message' => 'Non authentifié.'], 401);
})->name('login');

Route::get('/test-email', [TestEmailController::class, 'sendTestEmail']);

Route::prefix('referentiel')->group(function () {
    Route::get('domaines', [ReferentielController::class, 'domaines']);
    Route::get('metiers', [ReferentielController::class, 'metiers']);
    Route::get('niveaux-formation', [ReferentielController::class, 'niveauxFormation']);
    Route::get('competences', [ReferentielController::class, 'competences']);
});

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('verify', [AuthController::class, 'verify']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('resend-code', [AuthController::class, 'resendCode']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::post('auth/delete-account', [AuthController::class, 'deleteAccount']);
    Route::get('auth/profile', [AuthController::class, 'profile']);
    Route::post('user/photo', [AuthController::class, 'updatePhotoProfil']);
    Route::delete('user/photo', [AuthController::class, 'supprimerPhotoProfil']);
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::post('notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead']);

    Route::post('pointage/autorisation', [AutorisationPointageController::class, 'stagiaireToggle']);
    Route::post('pointage/repondre', [AutorisationPointageController::class, 'stagiaireRepond']);
    Route::post('pointage/verifier-code', [AutorisationPointageController::class, 'verifierCode']);
    Route::post('pointage/valider-liaison', [AutorisationPointageController::class, 'validerLiaison']);
    Route::post('pointage/decliner-liaison', [AutorisationPointageController::class, 'declinerLiaison']);
    Route::get('pointage/{carnetId}/historique', [PointageController::class, 'historique']);
    Route::post('entreprise/demander-suivi', [AutorisationPointageController::class, 'entrepriseDemande']);

    Route::middleware('profil:stagiaire')->group(function () {
        Route::post('stagiaire/profil', [AuthController::class, 'completeStagiaireProfile']);
        Route::prefix('carnets')->group(function () {
            Route::post('/', [CarnetController::class, 'store']);
            Route::get('/', [CarnetController::class, 'index']);
            Route::get('{carnetId}/stats', [CarnetController::class, 'stats']);
            Route::post('{carnetId}/entrees', [CarnetController::class, 'storeEntree']);
        });
        Route::get('carnets/{carnetId}/entrees', [CarnetController::class, 'entrees']);
        Route::get('carnets/{carnetId}/encouragements', [CarnetController::class, 'encouragements']);
        Route::post('rattacher-carnet', [RattachementController::class, 'rattacher']);
        Route::prefix('pointage')->group(function () {
            Route::post('arrivee', [PointageController::class, 'arrivee']);
            Route::post('depart', [PointageController::class, 'depart']);
        });
        Route::get('mes-attestations', [DocumentController::class, 'mesAttestations']);
        Route::get('attestations/{attestationId}/telecharger', [DocumentController::class, 'telechargerAttestation']);
        Route::prefix('bilans-reflexifs')->group(function () {
            Route::post('/', [BilanReflexifController::class, 'store']);
            Route::get('carnets/{carnetId}/bilans-reflexifs', [BilanReflexifController::class, 'index']);
        });
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
        Route::get('messages', [MessageController::class, 'conversations']);
        Route::prefix('trajets/{trajetId}/messages')->group(function () {
            Route::post('/', [MessageController::class, 'store']);
            Route::get('/', [MessageController::class, 'index']);
        });
        Route::post('trajets/{trajetId}/signaler', [SignalementController::class, 'store']);
    });

    Route::middleware('profil:entreprise')->group(function () {
        Route::post('entreprise/profil', [AuthController::class, 'completeEntrepriseProfile']);
        Route::prefix('fiches-invitation')->group(function () {
            Route::post('/', [FicheStagiaireInviteController::class, 'store']);
            Route::get('/', [FicheStagiaireInviteController::class, 'index']);
        });
        Route::prefix('evaluations')->group(function () {
            Route::post('/', [EvaluationController::class, 'store']);
            Route::get('carnets/{carnetId}/evaluations', [EvaluationController::class, 'index']);
        });
        Route::prefix('documents')->group(function () {
            Route::get('liaison/{autorisationId}/convention-pdf', [DocumentController::class, 'genererConvention']);
            Route::post('evaluations/{evaluationId}/attestation', [DocumentController::class, 'genererAttestation']);
            Route::post('evaluations/{evaluationId}/carte-appui', [DocumentController::class, 'genererCarteAppui']);
        });
        Route::prefix('criteres-savoir-etre')->group(function () {
            Route::post('/', [CritereSavoirEtreController::class, 'store']);
        });
        Route::get('stagiaires', [CarnetController::class, 'listeEntreprise']);
        Route::get('dashboard-stats', [CarnetController::class, 'statsEntreprise']);
        Route::post('carnets/{carnetId}/encourager', [CarnetController::class, 'encourager']);
        Route::patch('entrees-carnet/{id}/commentaire', [CarnetController::class, 'commenterEntree']);
        Route::prefix('evaluations-savoir-etre')->group(function () {
            Route::post('/', [EvaluationSavoirEtreController::class, 'store']);
            Route::get('carnets/{carnetId}/evaluations-savoir-etre', [EvaluationSavoirEtreController::class, 'index']);
        });
        Route::get('carnets/{carnetId}/entrees', [CarnetController::class, 'entrees']);
        Route::get('carnets/{carnetId}/encouragements', [CarnetController::class, 'encouragements']);
        Route::get('attestations/{attestationId}/telecharger', [DocumentController::class, 'telechargerAttestation']);
    });

    Route::get('criteres-savoir-etre', [CritereSavoirEtreController::class, 'index']);
});

Route::middleware('auth:sanctum')->get('/profil/moi', function (Request $request) {
    $user = $request->user();
    return response()->json([
        'id' => $user->id,
        'email' => $user->email,
        'role' => $user->role,
        'type' => get_class($user),
    ]);
});
