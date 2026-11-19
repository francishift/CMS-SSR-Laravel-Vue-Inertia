<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicFormController;
use App\Http\Controllers\PublicPageController;
use App\Http\Controllers\SiteSettingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas Públicas (SEO + SSR)
|--------------------------------------------------------------------------
|
| Estas rutas son accesibles sin autenticación y se renderizan
| con SSR para que los buscadores indexen el contenido.
| IMPORTANTE: Las rutas catch-all ({slug}) van al FINAL
| para no interceptar las rutas específicas del panel.
|
*/

Route::get('/', [PublicPageController::class, 'home'])->name('public.home');
Route::get('/buscar', [PublicPageController::class, 'search'])->name('public.search');

// Entrega estática ultrarrápida de variantes WebP sin bloqueo de sesión PHP ni sobrecarga de middlewares Inertia/Auth
Route::get('/media/render', [\App\Http\Controllers\ImageController::class, 'render'])
    ->withoutMiddleware('web')
    ->name('media.render');

// Canales de Contacto Directo Seguro (Protección Anti-Bot y Anti-Scraping)
// Rate-limit: máximo 10 peticiones por minuto por IP para dificultar el scraping masivo.
Route::middleware('throttle:10,1')->group(function () {
    Route::get('/whatsapp', [\App\Http\Controllers\PublicContactRedirectController::class, 'whatsapp'])->name('public.whatsapp');
    Route::get('/llamar', [\App\Http\Controllers\PublicContactRedirectController::class, 'phone'])->name('public.phone');
    Route::get('/telefono', [\App\Http\Controllers\PublicContactRedirectController::class, 'phone']);
    Route::get('/email', [\App\Http\Controllers\PublicContactRedirectController::class, 'email'])->name('public.email');
    Route::get('/mail', [\App\Http\Controllers\PublicContactRedirectController::class, 'email']);
});

// Envío público de formularios dinámicos (rate-limit: 5 envíos/min por IP)
Route::post('/formularios/{slug}/enviar', [PublicFormController::class, 'submit'])
    ->middleware('throttle:30,1')
    ->name('public.form.submit');

// Obtener esquema público de un formulario por slug (para renderizar en el Builder público)
Route::get('/api/formularios/{slug}', [PublicFormController::class, 'schema'])
    ->middleware('throttle:30,1')
    ->name('public.form.schema');

// Endpoint público para obtener contenido limpio de una página estática (para modales)
Route::get('/api/paginas/{slug}', [\App\Http\Controllers\PublicPageController::class, 'pageContent'])
    ->middleware('throttle:60,1')
    ->name('public.page.content')
    ->where('slug', '.*');

// Endpoint seguro para obtener la API key de Google Maps bajo demanda.
// No se incluye en siteSettings para evitar exponerla en el HTML de cada página.
Route::get('/api/cms/maps-key', \App\Http\Controllers\GoogleMapsKeyController::class)
    ->middleware('throttle:15,1')
    ->name('api.maps-key');


/*
|--------------------------------------------------------------------------
| Rutas del Panel de Administración — prefijo /panel
|--------------------------------------------------------------------------
*/

// Dashboard (todos los usuarios autenticados)
Route::get('/panel', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Perfil del usuario
Route::middleware('auth')->prefix('panel')->group(function () {
    Route::get('/perfil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/perfil', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/perfil', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Administración — solo admin
Route::middleware(['auth', 'role:admin'])->prefix('panel')->group(function () {

    // Biblioteca de Medios — Gestión centralizada de archivos multimedia
    Route::prefix('media')->name('media.')->group(function () {
        Route::get('/', [MediaController::class, 'index'])->name('index');
        Route::get('/list', [MediaController::class, 'list'])->name('list');
        Route::post('/upload', [MediaController::class, 'upload'])->name('upload');
        Route::put('/{mediaFile}', [MediaController::class, 'update'])->name('update');
        Route::delete('/{mediaFile}', [MediaController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-delete', [MediaController::class, 'bulkDelete'])->name('bulk-delete');
    });

    // Centro de Actividad, Telemetría de IPs de Descargas y Auditoría del CMS (Spatie)
    Route::prefix('actividad')->name('actividad.')->group(function () {
        Route::get('/', [\App\Http\Controllers\ActivityCenterController::class, 'index'])->name('index');
        Route::delete('/descargas-logs', [\App\Http\Controllers\ActivityCenterController::class, 'purgeDownloadLogs'])->name('purge-downloads');
        Route::delete('/auditoria-logs', [\App\Http\Controllers\ActivityCenterController::class, 'purgeActivityLogs'])->name('purge-activity');
    });

    // Gestión de usuarios
    Route::prefix('usuarios')->name('usuarios.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/papelera', [UserController::class, 'trashed'])->name('trashed');
        Route::get('/crear', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{user}/editar', [UserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::patch('/{user}/activar', [UserController::class, 'toggleActive'])->name('toggle-active');
        Route::patch('/{id}/restaurar', [UserController::class, 'restore'])->name('restore');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
        Route::delete('/{id}/definitivo', [UserController::class, 'forceDelete'])->name('force-delete');
    });

    // Configuración global del sitio
    Route::prefix('configuracion')->name('configuracion.')->group(function () {
        Route::get('/', [SiteSettingController::class, 'index'])->name('index');
        Route::put('/', [SiteSettingController::class, 'update'])->name('update');
        Route::get('/footer/builder', [SiteSettingController::class, 'editFooterBuilder'])->name('footer.builder');
        Route::put('/footer/builder', [SiteSettingController::class, 'updateFooterBuilder'])->name('footer.builder.update');
        Route::get('/footer/builder/preview-frame', [SiteSettingController::class, 'previewFrameFooterBuilder'])->name('footer.builder.preview-frame');
    });

    // Módulo de Formularios Dinámicos
    Route::prefix('formularios')->name('panel.formularios.')->group(function () {
        Route::get('/', [FormController::class, 'index'])->name('index');
        Route::get('/crear', [FormController::class, 'create'])->name('create');
        Route::post('/', [FormController::class, 'store'])->name('store');

        // Bandeja de envíos global (ANTES de /{form} para evitar colisiones de ruta)
        Route::get('/envios', [FormController::class, 'allSubmissions'])->name('submissions.all');
        Route::get('/envios/exportar-csv', [FormController::class, 'exportAllCsv'])->name('submissions.export-all-csv');
        Route::patch('/envios/{submission}/leer', [FormController::class, 'markSubmissionRead'])->name('submissions.mark-read-direct');
        Route::patch('/envios/{submission}/spam', [FormController::class, 'markSubmissionSpam'])->name('submissions.spam-direct');
        Route::delete('/envios/{submission}', [FormController::class, 'destroySubmissionDirect'])->name('submissions.destroy-direct');

        Route::get('/{form}/editar', [FormController::class, 'edit'])->name('edit');
        Route::put('/{form}', [FormController::class, 'update'])->name('update');
        Route::post('/{form}/duplicar', [FormController::class, 'duplicate'])->name('duplicate');
        Route::patch('/{form}/activar', [FormController::class, 'toggleActive'])->name('toggle-active');
        Route::delete('/{form}', [FormController::class, 'destroy'])->name('destroy');
        // Bandeja de envíos por formulario específico
        Route::get('/{form}/envios', [FormController::class, 'submissions'])->name('submissions');
        Route::get('/{form}/envios/{submission}', [FormController::class, 'showSubmission'])->name('submissions.show');
        Route::patch('/{form}/envios/{submission}/leer', [FormController::class, 'markRead'])->name('submissions.mark-read');
        Route::patch('/{form}/envios/{submission}/spam', [FormController::class, 'markSpam'])->name('submissions.spam');
        Route::delete('/{form}/envios/{submission}', [FormController::class, 'destroySubmission'])->name('submissions.destroy');
        Route::get('/{form}/exportar-csv', [FormController::class, 'exportCsv'])->name('export-csv');
    });

    // Gestión de menús
    Route::prefix('menus')->name('menus.')->group(function () {
        Route::get('/', [MenuController::class, 'index'])->name('index');
        Route::get('/papelera', [MenuController::class, 'trashed'])->name('trashed');
        Route::post('/', [MenuController::class, 'store'])->name('store');
        Route::get('/{menu}/editar', [MenuController::class, 'edit'])->name('edit');
        Route::put('/{menu}', [MenuController::class, 'update'])->name('update');
        Route::patch('/{id}/restaurar', [MenuController::class, 'restore'])->name('restore');
        Route::delete('/{menu}', [MenuController::class, 'destroy'])->name('destroy');
        Route::delete('/{id}/definitivo', [MenuController::class, 'forceDelete'])->name('force-delete');
        // Items de menú
        Route::post('/{menu}/items', [MenuController::class, 'storeItem'])->name('items.store');
        Route::put('/items/{item}', [MenuController::class, 'updateItem'])->name('items.update');
        Route::patch('/items/{id}/restaurar', [MenuController::class, 'restoreItem'])->name('items.restore');
        Route::delete('/items/{item}', [MenuController::class, 'destroyItem'])->name('items.destroy');
        Route::delete('/items/{id}/definitivo', [MenuController::class, 'forceDeleteItem'])->name('items.force-delete');
        Route::post('/{menu}/reordenar', [MenuController::class, 'reorderItems'])->name('items.reorder');
    });

    // Gestión de páginas estáticas
    Route::prefix('paginas')->name('paginas.')->group(function () {
        Route::get('/', [PageController::class, 'index'])->name('index');
        Route::get('/papelera', [PageController::class, 'trashed'])->name('trashed');
        Route::get('/crear', [PageController::class, 'create'])->name('create');
        Route::post('/', [PageController::class, 'store'])->name('store');
        Route::get('/{page}/editar', [PageController::class, 'edit'])->name('edit');
        Route::get('/{page}/builder', [\App\Http\Controllers\PageBuilderController::class, 'edit'])->name('builder');
        Route::get('/{page}/builder/preview-frame', [\App\Http\Controllers\PageBuilderController::class, 'previewFrame'])->name('builder.preview-frame');
        Route::put('/{page}/builder', [\App\Http\Controllers\PageBuilderController::class, 'update'])->name('builder.update');
        Route::post('/builder/preview', [\App\Http\Controllers\PageBuilderController::class, 'resolvePreview'])->name('builder.preview');
        Route::put('/{page}', [PageController::class, 'update'])->name('update');
        Route::patch('/{page}/activar', [PageController::class, 'toggleActive'])->name('toggle-active');
        Route::put('/{page}/homepage', [PageController::class, 'setHomepage'])->name('set-homepage');
        Route::patch('/{id}/restaurar', [PageController::class, 'restore'])->name('restore');
        Route::delete('/{page}', [PageController::class, 'destroy'])->name('destroy');
        Route::delete('/{id}/definitivo', [PageController::class, 'forceDelete'])->name('force-delete');
    });

    // Biblioteca de Presets del Page Builder
    Route::prefix('builder-library')->name('builder-library.')->group(function () {
        Route::get('/', [\App\Http\Controllers\BuilderLibraryController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\BuilderLibraryController::class, 'store'])->name('store');
        Route::put('/{builderLibraryItem}', [\App\Http\Controllers\BuilderLibraryController::class, 'update'])->name('update');
        Route::delete('/{builderLibraryItem}', [\App\Http\Controllers\BuilderLibraryController::class, 'destroy'])->name('destroy');
    });

    // Plantillas Dinámicas del Maquetador Visual (Theme Templates)
    Route::prefix('plantillas')->name('plantillas.')->group(function () {
        Route::get('/', [\App\Http\Controllers\BuilderTemplateController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\BuilderTemplateController::class, 'store'])->name('store');
        Route::put('/{template}', [\App\Http\Controllers\BuilderTemplateController::class, 'update'])->name('update');
        Route::delete('/{template}', [\App\Http\Controllers\BuilderTemplateController::class, 'destroy'])->name('destroy');
        Route::post('/{template}/duplicar', [\App\Http\Controllers\BuilderTemplateController::class, 'duplicate'])->name('duplicate');

        // Maquetador Visual para Plantillas
        Route::get('/{template}/builder', [\App\Http\Controllers\TemplateBuilderController::class, 'edit'])->name('builder');
        Route::get('/{template}/builder/preview-frame', [\App\Http\Controllers\TemplateBuilderController::class, 'previewFrame'])->name('builder.preview-frame');
        Route::put('/{template}/builder', [\App\Http\Controllers\TemplateBuilderController::class, 'update'])->name('builder.update');
        Route::post('/{template}/builder/preview', [\App\Http\Controllers\TemplateBuilderController::class, 'resolvePreview'])->name('builder.preview');
    });

    // Gestión de categorías
    Route::prefix('categorias')->name('categorias.')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('index');
        Route::get('/papelera', [CategoryController::class, 'trashed'])->name('trashed');
        Route::get('/crear', [CategoryController::class, 'create'])->name('create');
        Route::post('/', [CategoryController::class, 'store'])->name('store');
        Route::get('/{category}/editar', [CategoryController::class, 'edit'])->name('edit');
        Route::put('/{category}', [CategoryController::class, 'update'])->name('update');
        Route::patch('/{id}/restaurar', [CategoryController::class, 'restore'])->name('restore');
        Route::delete('/papelera/vaciar', [CategoryController::class, 'emptyTrash'])->name('empty-trash');
        Route::delete('/{category}', [CategoryController::class, 'destroy'])->name('destroy');
        Route::delete('/{id}/definitivo', [CategoryController::class, 'forceDelete'])->name('force-delete');
    });

    // Gestión de artículos
    Route::prefix('articulos')->name('articulos.')->group(function () {
        Route::get('/', [ArticleController::class, 'index'])->name('index');
        Route::get('/papelera', [ArticleController::class, 'trashed'])->name('trashed');
        Route::get('/crear', [ArticleController::class, 'create'])->name('create');
        Route::post('/', [ArticleController::class, 'store'])->name('store');
        Route::get('/{article}/editar', [ArticleController::class, 'edit'])->name('edit');
        Route::put('/{article}', [ArticleController::class, 'update'])->name('update');
        Route::patch('/{article}/estado', [ArticleController::class, 'changeStatus'])->name('change-status');
        Route::post('/{article}/duplicar', [ArticleController::class, 'duplicate'])->name('duplicate');
        Route::post('/reordenar', [ArticleController::class, 'reorder'])->name('reorder');
        Route::patch('/{id}/restaurar', [ArticleController::class, 'restore'])->name('restore');
        Route::delete('/papelera/vaciar', [ArticleController::class, 'emptyTrash'])->name('empty-trash');
        Route::delete('/{article}', [ArticleController::class, 'destroy'])->name('destroy');
        Route::delete('/{id}/definitivo', [ArticleController::class, 'forceDelete'])->name('force-delete');
    });
});

/*
|--------------------------------------------------------------------------
| Rutas de Módulos Autónomos (Extensiones en app/Modules/*)
|--------------------------------------------------------------------------
|
| Se cargan automáticamente antes de las rutas catch-all públicas para
| permitir que cada extensión independiente registre sus endpoints sin
| acoplar ni hardcodear rutas en el núcleo del CMS.
|
*/

foreach (glob(app_path('Modules/*/Routes/web.php')) ?: [] as $moduleRouteFile) {
    require $moduleRouteFile;
}

/*
|--------------------------------------------------------------------------
| Rutas de Autenticación
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| Rutas Públicas Catch-All (SIEMPRE al final)
|--------------------------------------------------------------------------
|
| Estas rutas capturan los slugs de artículos, categorías y páginas estáticas.
| Van AL FINAL para no interferir con /acceso, /panel, etc.
|
*/

// Paginación limpia SEO para la página de inicio (/pagina/{page})
Route::get('/pagina/{page}', [PublicPageController::class, 'homePaginated'])
    ->name('public.home.paginated')
    ->where('page', '[0-9]+');

Route::get('/articulos/{articleSlug}', [PublicPageController::class, 'articleBySlug'])
    ->name('public.article.single')
    ->where('articleSlug', '[a-z0-9\-]+');

// Paginación limpia SEO para categorías y páginas estáticas (/{slug}/pagina/{page})
Route::get('/{slug}/pagina/{page}', [PublicPageController::class, 'resolveSlugPaginated'])
    ->name('public.slug.paginated')
    ->where(['slug' => '[a-z0-9\-]+', 'page' => '[0-9]+']);

Route::get('/{categorySlug}/{articleSlug}', [PublicPageController::class, 'article'])
    ->name('public.article')
    ->where(['categorySlug' => '[a-z0-9\-]+', 'articleSlug' => '[a-z0-9\-]+']);

Route::get('/{slug}', [PublicPageController::class, 'resolveSlug'])
    ->name('public.slug')
    ->where('slug', '[a-z0-9\-]+');

