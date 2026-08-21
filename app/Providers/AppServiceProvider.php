<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\EntreeCarnet;
use App\Observers\EntreeCarnetObserver;
use App\Models\CarnetDeStage;
use App\Observers\CarnetDeStageObserver;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // 🛠️ AUTO-RÉPARATION SANS TERMINAL
        // On force le nettoyage du cache des routes à chaque déploiement/redémarrage
        try {
            if (!file_exists(storage_path('framework/cache_cleared.lock'))) {
                \Illuminate\Support\Facades\Artisan::call('optimize:clear');
                \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
                file_put_contents(storage_path('framework/cache_cleared.lock'), 'done');
            }
        } catch (\Exception $e) {
            \Log::error("Erreur Auto-réparation : " . $e->getMessage());
        }

        EntreeCarnet::observe(EntreeCarnetObserver::class);
        CarnetDeStage::observe(CarnetDeStageObserver::class);
    }
}
