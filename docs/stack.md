# Especificación Detallada del Stack Tecnológico

El proyecto **CMS SSR** combina una infraestructura backend robusta basada en Laravel con un frontend reactivo mediante Inertia.js, Vue 3, PrimeVue y Tailwind CSS v4.

---

## 1. Stack Backend (PHP & Laravel)

- **PHP Runtime**: PHP 8.4+ (`"php": "^8.4"` en `composer.json`, con soporte para JIT, tipos estrictos y atributos de clase).
- **Framework Principal**: Laravel 13.x (`v13.42+`, sobre componentes Symfony `v8.1+`).
- **Gestión de Permisos**: `spatie/laravel-permission` v8.3.
- **Auditoría de Actividad**: `spatie/laravel-activitylog` v5.1 (integrado con telemetría IP y exclusión automática de atributos sensibles/pesados).
- **Caché y Almacenamiento en Memoria**: Redis Server con cliente `phpredis`.
- **Manejador de Base de Datos**: MySQL 8.0+ / MariaDB 10.5+ / SQLite.
- **Autenticación**: Laravel Sanctum / Inertia Session Authentication con middleware de expiración y logout automático por inactividad (`VerificarUsuarioActivo`).
- **Sistema de Correo**: Laravel Mail (SMTP / Log Driver) utilizando notificaciones Blade (`BienvenidaUsuario`).

---

## 2. Stack Frontend (Vue 3, Inertia & Styling)

- **Capas de Presentación / Framework Reactivo**: Vue 3.5+ (`vue` y `@vue/server-renderer` `3.5.45+`) utilizando Composition API y sintaxis `<script setup>`.
- **Adaptador de Comunicación SPA**: Inertia.js v2.0 (navegación fluida SPA sin renderizado recargado de página).
- **Librería de Componentes UI**: PrimeVue v4.5 (Preset `Aura` extendido con paleta semántica `Emerald` para primarios y `Zinc` para superficies; cargado bajo demanda mediante `import()` dinámico exclusivamente en rutas de administración, autenticación y vista previa del Builder para mantener ligero el bundle público).
- **Librería de Iconos**: PrimeIcons (cargado de forma local empaquetado por Vite, sin fuentes o CDNs de terceros).
- **Framework CSS de Utilidades**: Tailwind CSS v4.0 integrado mediante el plugin oficial `@tailwindcss/vite` y `@tailwindcss/postcss`.
- **Tipografía**: Fuente `Hanken Grotesk` 100% local empaquetada vía `@fontsource/hanken-grotesk` con fallback a `system-ui`. Cero llamadas de red externas a Google Fonts o Bunny Fonts.
- **Sanitización XSS (DOMPurify)**: `isomorphic-dompurify` (`dompurify` `3.4.17+`) para sanitizar todo contenido HTML renderizado con `v-html` en el frontend público. Compatible con SSR (Node.js) y cliente (navegador).
- **Motores de Mapas e Interactividad Geoespacial**: Soporte dual dinámico para Google Maps JavaScript API (con API Key cifrada en base de datos) y OpenStreetMap (Leaflet 1.9.4 con soporte de colorimetría CSS). Ambos cargados en diferido (*Lazy Loading*) mediante `IntersectionObserver` y singleton loaders para garantizar cero impacto en el peso del bundle inicial.
- **Cookie Consent y GDPR**: `vanilla-cookieconsent` v3.1 (Orest Bida) con carga diferida client-only, integración con Google Consent Mode v2 y tema visual dinámico adaptado a los colores del sitio. Composable `useCookieConsent.js` 100% SSR-safe con shortcode `{{cookies_button}}` para insertar botones de preferencias en cualquier contenido.

---

## 3. Entorno de Compilación y Herramientas de Desarrollo

- **Empaquetador de Assets**: Vite 8.x con plugins `laravel-vite-plugin` y `@vitejs/plugin-vue`.
- **Concurrencia de Desarrollo**: `npx concurrently` para ejecutar servidor PHP, escrutador de colas, Pail y servidor Vite.
- **Formateador y Linter de Código**: Laravel Pint (`laravel/pint`).
- **Framework de Pruebas Unitarias y de Integración**: PHPUnit 12.x.
- **Herramientas de CLI y Depuración**: Laravel Tinker y Laravel Pail.
