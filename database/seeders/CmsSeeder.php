<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

/**
 * Seeder por defecto del CMS.
 *
 * Crea la configuración global inicial, la página estática de inicio,
 * una categoría de ejemplo y el menú principal de la cabecera.
 */
class CmsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Configuración global
        SiteSetting::firstOrCreate(
            ['id' => 1],
            [
                'site_name'                => 'CMS SSR',
                'site_description'         => 'CMS con SSR y SEO optimizado',
                'favicon_path'             => '/favicon.ico',
                'primary_color'            => '#10b981',
                'secondary_color'          => '#3b82f6',
                'heading_font'             => 'Hanken Grotesk',
                'body_font'                => 'Hanken Grotesk',
                'seo_default_title'        => 'CMS SSR — Sitio Oficial',
                'seo_default_description'  => 'Sitio web profesional con SSR y optimización SEO.',
                'footer_text'              => '© ' . date('Y') . ' CMS SSR. Todos los derechos reservados.',
            ]
        );

        // 2. Página estática por defecto (Inicio / Portada)
        $paginaInicio = Page::firstOrCreate(
            ['slug' => 'inicio'],
            [
                'title'           => 'Página de Inicio',
                'body'            => '<h1>Bienvenido a CMS SSR</h1><p>Este es tu nuevo sitio web CMS optimizado para velocidad, SEO y experiencia de usuario.</p>',
                'is_active'       => true,
                'is_homepage'     => true,
                'order'           => 0,
                'seo_title'       => 'Inicio — CMS SSR',
                'seo_description' => 'Página principal del sitio web.',
            ]
        );

        // 3. Categoría de ejemplo
        $categoriaGeneral = Category::firstOrCreate(
            ['slug' => 'general'],
            [
                'name'        => 'General',
                'description' => 'Publicaciones generales y noticias del sitio',
                'template'    => 'grid',
                'color'       => '#10b981',
                'icon'        => 'pi pi-folder',
                'is_active'   => true,
                'order'       => 0,
            ]
        );

        // 4. Menú principal de la cabecera
        $menuHeader = Menu::firstOrCreate(
            ['location' => 'header'],
            [
                'name'      => 'Menú Principal',
                'slug'      => 'menu-principal',
                'is_active' => true,
            ]
        );

        // 5. Item de Menú 1: Inicio (apunta a página estática / home)
        MenuItem::firstOrCreate(
            [
                'menu_id' => $menuHeader->id,
                'title'   => 'Inicio',
            ],
            [
                'url'       => '/',
                'page_id'   => $paginaInicio->id,
                'target'    => '_self',
                'icon'      => 'pi pi-home',
                'order'     => 0,
                'is_active' => true,
            ]
        );

        // 6. Item de Menú 2: Categoría General
        MenuItem::firstOrCreate(
            [
                'menu_id'     => $menuHeader->id,
                'category_id' => $categoriaGeneral->id,
            ],
            [
                'title'     => 'General',
                'target'    => '_self',
                'icon'      => 'pi pi-folder',
                'order'     => 1,
                'is_active' => true,
            ]
        );
    }
}
