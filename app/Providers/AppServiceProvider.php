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
        // 🛠️ AUTO-RÉPARATION SANS TERMINAL (Liaison & Convention)
        // Ce bloc force la mise à jour de la BDD et vide le cache des routes
        try {
            if (!file_exists(storage_path('framework/repaired.lock'))) {
                \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
                \Illuminate\Support\Facades\Artisan::call('route:clear');
                \Illuminate\Support\Facades\Artisan::call('config:clear');
                file_put_contents(storage_path('framework/repaired.lock'), 'done');
            }
        } catch (\Exception $e) {
            \Log::error("Erreur Auto-réparation : " . $e->getMessage());
        }

        EntreeCarnet::observe(EntreeCarnetObserver::class);
        CarnetDeStage::observe(CarnetDeStageObserver::class);
    }
}
