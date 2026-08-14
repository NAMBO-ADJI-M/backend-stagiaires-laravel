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
        EntreeCarnet::observe(EntreeCarnetObserver::class);
        CarnetDeStage::observe(CarnetDeStageObserver::class);
    }
}