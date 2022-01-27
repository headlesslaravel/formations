<?php

namespace HeadlessLaravel\Formations;

use HeadlessLaravel\Formations\Commands\FormationMakeCommand;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class FormationProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                FormationMakeCommand::class,
            ]);
        }

        $this->app->singleton(Manager::class, function () {
            return new Manager();
        });

        $this->mergeConfigFrom(__DIR__.'/../config/headless-formations.php', 'headless-formations');
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/formations.php' => config_path('formations.php'),
        ], 'formations-config');

        $this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/formations'),
        ], 'formations-lang');

        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'formations');

        Route::macro('formation', function ($formation) {
            return app(Routes::class)->formation($formation);
        });

        Route::macro('seeker', function ($formations = [], $key = 'main') {
            app(Manager::class)->seeker($formations, $key);
        });
    }
}
