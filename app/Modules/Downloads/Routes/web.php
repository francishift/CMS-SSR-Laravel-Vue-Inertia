<?php

declare(strict_types=1);

use App\Modules\Downloads\Http\Controllers\AdminDownloadBuilderController;
use App\Modules\Downloads\Http\Controllers\AdminDownloadController;
use App\Modules\Downloads\Http\Controllers\DownloadFileDeliveryController;
use App\Modules\Downloads\Models\DownloadSetting;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas Autónomas del Módulo de Descargas
|--------------------------------------------------------------------------
|
| Todos los prefijos, roles y límites se leen desde `config('downloads.*')`
| para garantizar cero rutas hardcodeadas y portabilidad inmediata a otros portales.
|
*/

$adminPrefix    = trim((string) config('downloads.admin_prefix', 'panel/descargas'), '/');
$adminRole      = (string) config('downloads.admin_role', 'admin');
$deliveryPrefix = trim((string) config('downloads.delivery_prefix', 'download-vault'), '/');
$rateLimit      = max(1, (int) config('downloads.delivery.rate_limit_per_minute', 15));

// 1. Endpoints blindados de entrega binaria (excluidos de caché Redis e Inertia SPA)
Route::prefix($deliveryPrefix)
    ->name('downloads.delivery.')
    ->middleware("throttle:{$rateLimit},1")
    ->group(function () {
        Route::get('/{slug}/request', [DownloadFileDeliveryController::class, 'requestDownload'])
            ->where('slug', '[a-z0-9\-]+')
            ->name('request');

        Route::get('/stream/{downloadItem}', [DownloadFileDeliveryController::class, 'stream'])
            ->whereNumber('downloadItem')
            ->name('stream');
    });

// Ruta nombrada canónica de referencia para enlaces internos y sistema de Menús (compatible con route:cache)
Route::get("/{$deliveryPrefix}/catalogo", [DownloadFileDeliveryController::class, 'redirectToCatalog'])
    ->name('public.downloads.index');

// 2. Panel de Administración del Módulo de Descargas
Route::middleware(['auth', "role:{$adminRole}"])
    ->prefix($adminPrefix)
    ->name('downloads.admin.')
    ->group(function () {
        Route::get('/', [AdminDownloadController::class, 'index'])->name('index');
        Route::put('/configuracion', [AdminDownloadController::class, 'updateSettings'])->name('settings.update');
        Route::post('/reordenar', [AdminDownloadController::class, 'reorder'])->name('reorder');

        // Constructor Visual (Page Builder) para la Portada y la Plantilla de Ficha Individual de Descargas
        Route::get('/builder', [AdminDownloadBuilderController::class, 'edit'])->name('builder');
        Route::get('/builder/preview-frame', [AdminDownloadBuilderController::class, 'previewFrame'])->name('builder.preview-frame');
        Route::put('/builder', [AdminDownloadBuilderController::class, 'update'])->name('builder.update');

        Route::get('/item-builder', [AdminDownloadBuilderController::class, 'editItemTemplate'])->name('item-builder');
        Route::get('/item-builder/preview-frame', [AdminDownloadBuilderController::class, 'previewFrameItemTemplate'])->name('item-builder.preview-frame');
        Route::put('/item-builder', [AdminDownloadBuilderController::class, 'updateItemTemplate'])->name('item-builder.update');

        // Papelera (antes de /{downloadItem} para evitar colisiones de ruta)
        Route::get('/papelera', [AdminDownloadController::class, 'trashed'])->name('trashed');
        Route::delete('/papelera/vaciar', [AdminDownloadController::class, 'emptyTrash'])->name('empty-trash');
        Route::patch('/{id}/restaurar', [AdminDownloadController::class, 'restore'])->whereNumber('id')->name('restore');
        Route::delete('/{id}/definitivo', [AdminDownloadController::class, 'forceDelete'])->whereNumber('id')->name('force-delete');

        // CRUD de ítems descargables
        Route::get('/crear', [AdminDownloadController::class, 'create'])->name('create');
        Route::post('/', [AdminDownloadController::class, 'store'])->name('store');
        Route::get('/{downloadItem}/editar', [AdminDownloadController::class, 'edit'])->name('edit');
        Route::put('/{downloadItem}', [AdminDownloadController::class, 'update'])->name('update');
        Route::patch('/{downloadItem}/estado', [AdminDownloadController::class, 'changeStatus'])->name('change-status');
        Route::post('/{downloadItem}/duplicar', [AdminDownloadController::class, 'duplicate'])->name('duplicate');
        Route::delete('/{downloadItem}', [AdminDownloadController::class, 'destroy'])->name('destroy');
    });
