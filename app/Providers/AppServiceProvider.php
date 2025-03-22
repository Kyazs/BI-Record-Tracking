<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

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
        $this->googleDriver();
        $this->gatesAuthorization();

        Inertia::share([
            'auth' => fn () => [
                'user' => Auth::user() ? [
                    'id' => Auth::user()->id,
                    'name' => Auth::user()->name,
                    'role' => Auth::user()->role, // Pass role to Vue
                ] : null,
            ],
        ]);
    }

    /**
     * function for google cloud storage
     */
    public function googleDriver(): void
    {
        try {
            \Storage::extend('google', function($app, $config) {
                $options = [];

                if (!empty($config['teamDriveId'] ?? null)) {
                    $options['teamDriveId'] = $config['teamDriveId'];
                }

                if (!empty($config['sharedFolderId'] ?? null)) {
                    $options['sharedFolderId'] = $config['sharedFolderId'];
                }

                $client = new \Google\Client();
                $client->setClientId($config['clientId']);
                $client->setClientSecret($config['clientSecret']);
                $client->refreshToken($config['refreshToken']);
                
                $service = new \Google\Service\Drive($client);
                $adapter = new \Masbug\Flysystem\GoogleDriveAdapter($service, $config['folder'] ?? '/', $options);
                $driver = new \League\Flysystem\Filesystem($adapter);

                return new \Illuminate\Filesystem\FilesystemAdapter($driver, $adapter);
            });
        } catch(\Exception $e) {
            // your exception handling logic
        }
    }

    public function gatesAuthorization(): void
    {
        // $this->registerPolicies(); // Removed or commented out as the method is undefined

        // Define an 'admin' gate
        Gate::define('admin', function (User $user) {
            return $user->role === 'admin';
        });
    
        // Define an 'officer' gate
        Gate::define('officer', function (User $user) {
            return $user->role === 'officer';
        });
    
        // Define a gate that allows both admin and officer roles
        Gate::define('manage-records', function (User $user) {
            return in_array($user->role, ['admin', 'officer']);
        });
    }
}
