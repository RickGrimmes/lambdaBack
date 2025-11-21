<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging;

class FirebaseServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('firebase.messaging', function ($app) {
            $factory = (new Factory)->withServiceAccount(storage_path(env('FIREBASE_CREDENTIALS')));
            return $factory->createMessaging();
        });
    }

    public function provides()
    {
        return ['firebase.messaging'];
    }
}