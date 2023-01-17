<?php

namespace JagdishJP\BilldeskHmac;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use JagdishJP\BilldeskHmac\Console\Commands\BilldeskPublish;
use JagdishJP\BilldeskHmac\Console\Commands\TransactionStatus;

class BilldeskHmacServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'billdesk');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->createBladeDirectives();

        $this->configureRoutes();

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'billdesk-hmac');

        $this->configurePublish();

    }

    public function createBladeDirectives() {
        
        Blade::directive('billdesksdk', function () {
            $js_sdk = app()->isLocal() ? Config::get('billdesk.urls.uat.js_sdk') : Config::get('billdesk.urls.production.js_sdk');
            return "<script type='module' src='{$js_sdk}billdesksdk/billdesksdk.esm.js'></script>
            <script nomodule='' src='{$js_sdk}billdesksdk.js'></script>
            <link href='{$js_sdk}billdesksdk/billdesksdk.css' rel='stylesheet'>";
        });
    }
    public function configureRoutes()
    {
        Route::group([
            'middleware' => Config::get('billdesk.middleware'),
        ], function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        });
    }

    public function configurePublish()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/config.php' => config_path('billdesk.php'),
            ], 'billdesk-config');

            $this->publishes([
                __DIR__ . '/../stubs/controller.php' => app_path('Http/Controllers/BilldeskHmac/Controller.php'),
            ], 'billdesk-controller');

            $this->publishes([
                __DIR__ . '/../public/assets' => public_path('assets/vendor/billdesk-hmac'),
            ], 'billdesk-assets');

            $this->publishes([
                __DIR__ . '/../resources/views/payment.blade.php' => resource_path('views/vendor/billdesk-hmac/payment.blade.php'),
            ], 'billdesk-views');

            $this->commands([
                BilldeskPublish::class,
                TransactionStatus::class,
            ]);
        }
    }
}
