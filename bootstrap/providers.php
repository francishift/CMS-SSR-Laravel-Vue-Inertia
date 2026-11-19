<?php

use App\Providers\AppServiceProvider;

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\GoogleDriveServiceProvider::class,
    App\Providers\SeoServiceProvider::class,
    App\Modules\Downloads\Providers\DownloadsServiceProvider::class,
];
