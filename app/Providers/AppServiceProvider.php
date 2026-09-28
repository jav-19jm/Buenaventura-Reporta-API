<?php

namespace App\Providers;

use Illuminate\Http\Resources\Json\JsonResource;
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
        // Los recursos se devuelven sin el envoltorio {"data": ...}: el frontend
        // consume directamente el objeto o el arreglo.
        JsonResource::withoutWrapping();
    }
}
