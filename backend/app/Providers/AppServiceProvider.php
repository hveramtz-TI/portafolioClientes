<?php

namespace App\Providers;

use App\Models\Categoria;
use App\Models\Rubro;
use App\Models\Service;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::morphMap([
            'rubro' => Rubro::class,
            'categoria' => Categoria::class,
            'service' => Service::class,
        ]);
    }
}
