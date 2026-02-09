<?php

namespace App\Providers;

use App\Models\SystemSetting;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

   
    public function boot(): void
    {
        view()->composer('*', function ($view) {
            $setting = SystemSetting::first(); // or where('id',1)->first()
            $view->with('setting', $setting);
        });

        Relation::morphMap([
        'portfolio' => \App\Models\Portfolio::class,
    ]);
    }
}
