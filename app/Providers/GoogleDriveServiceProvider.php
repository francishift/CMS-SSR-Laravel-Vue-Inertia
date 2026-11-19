<?php

namespace App\Providers;

use Google\Client;
use Google\Service\Drive;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use Masbug\Flysystem\GoogleDriveAdapter;

/**
 * Service Provider para registrar el driver de Google Drive en el sistema de archivos de Laravel.
 */
class GoogleDriveServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Storage::extend('google', function ($app, $config) {
            $client = new Client();
            $client->setClientId($config['clientId']);
            $client->setClientSecret($config['clientSecret']);
            $client->refreshToken($config['refreshToken']);

            $service = new Drive($client);

            $options = [
                'useHasDir'         => true,
                'supportsAllDrives' => true,
                'sharedFolderId'    => $config['folderId'] ?? null,
            ];
            $adapter = new GoogleDriveAdapter($service, null, $options);
            $driver = new Filesystem($adapter);

            return new FilesystemAdapter($driver, $adapter, $config);
        });
    }
}
