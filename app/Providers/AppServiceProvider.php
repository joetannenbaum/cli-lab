<?php

namespace App\Providers;

use Chewie\Art;
use Chewie\Theme;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Theme::setNamespace('App\\Lab\\Renderers\\');
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
