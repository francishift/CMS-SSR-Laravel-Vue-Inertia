<?php

use App\Services\SeoService;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Tareas Programadas del CMS
|--------------------------------------------------------------------------
|
| El cron de Plesk ejecuta `artisan schedule:run` cada minuto.
| Aquí definimos las tareas periódicas del CMS y el guardian SSR.
|
*/


// Generar sitemap XML diariamente a las 3:00 AM
Schedule::call(function () {
    app(SeoService::class)->generarSitemap();
})->daily()->at('03:00')->description('Generar sitemap.xml');

// Publicar artículos programados (status = 'scheduled' con published_at pasada)
Schedule::call(function () {
    \App\Models\Article::where('status', 'scheduled')
        ->whereNotNull('published_at')
        ->where('published_at', '<=', now())
        ->update(['status' => 'published']);
})->everyFiveMinutes()->description('Publicar artículos programados');

// Copias de seguridad automáticas diarias en Google Drive (08:00 AM) y limpieza de antiguas (09:00 AM)
Schedule::command('backup:run')->dailyAt('08:00')->description('Ejecutar copia de seguridad diaria enviada a Google Drive');
Schedule::command('backup:clean')->dailyAt('09:00')->description('Limpieza diaria de copias antiguas en Google Drive');

// Purgado diario automático de telemetría antigua de descargas (conservando el acumulado histórico en download_items y download_settings)
if (class_exists(\App\Modules\Downloads\Models\DownloadLog::class)) {
    Schedule::command('model:prune', [
        '--model' => [\App\Modules\Downloads\Models\DownloadLog::class],
    ])->dailyAt('03:30')->description('Purgar registros antiguos de download_logs según retención configurada');
}

// Limpieza diaria automática de registros antiguos de auditoría Spatie Activitylog
if (class_exists(\Spatie\Activitylog\Models\Activity::class)) {
    Schedule::command('activitylog:clean', ['--force' => true])
        ->dailyAt('03:45')
        ->description('Purgar registros antiguos de activity_log según clean_after_days');
}
