<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging;

class FirebaseServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('firebase.messaging', function ($app) {
            try {
                $credentialsPath = storage_path('app/' . env('FIREBASE_CREDENTIALS'));
                
                if (!file_exists($credentialsPath)) {
                    throw new \Exception("Archivo de credenciales no encontrado en: " . $credentialsPath);
                }

                $factory = (new Factory)->withServiceAccount($credentialsPath);
                $messaging = $factory->createMessaging();
                
                return $messaging;
                
            } catch (\Exception $e) {
                throw $e;
            }
        });
    }

    public function boot()
    {
        //
    }
}
