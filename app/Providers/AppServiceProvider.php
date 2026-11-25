<?php

namespace App\Providers;

use App\Models\PortalSetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
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
        View::composer(['layouts.*', 'portal', 'admin', 'login', 'wifi', 'components.*'], function ($view) {
            try {
                if (Schema::hasTable('portal_settings')) {
                    if (! $view->offsetExists('settings')) {
                        $view->with('settings', PortalSetting::current());
                    }
                }
            } catch (\Throwable $e) {
                // In early CLI migrations/scaffolding
            }
        });
    }
}
