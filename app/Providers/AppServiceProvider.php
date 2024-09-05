<?php

namespace App\Providers;

use Chewie\Art;
use Chewie\Renderer;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Renderer::setNamespace('App\\Lab\\Renderers\\');
        Art::setDirectory(storage_path('ascii'));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
