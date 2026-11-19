# Reglas y Estándares de Desarrollo del Proyecto

## 1. Principios de Código Limpio y Mantenibilidad

- **Idioma Obligatorio**: Todo el código, comentarios, nombres de variables, métodos, mensajes de commit y documentación deben escribirse en **castellano**.
- **Clean Code & SOLID**: Respetar estrictamente los 5 principios SOLID. Prohibido el código espagueti o duplicar lógica de negocio en controladores o componentes Vue.
- **Arquitectura Hexagonal / Capas**: Separa la lógica de negocio (`Services`), validación (`Requests`), orquestación (`Controllers`), presentación DTO (`Resources`) e interfaz UI (`Vue Components`).

---

## 2. Reglas del Backend (Laravel)

- **Prevención de N+1**: Prohibido realizar consultas dentro de bucles o enviar modelos sin eager loading. Usar siempre `User::with('roles')` en listados.
- **Prohibido enviar Modelos Eloquent Crudos a Vue**: Todos los datos enviados a los componentes Vue a través de Inertia deben ser transformados mediante **API Resources** (`UserResource`), asegurando que jamás se envíen campos sensibles como contraseñas, tokens o atributos internos.
- **Validaciones en Form Requests**: Ningún controlador debe realizar validación manual `$request->validate()`. Usar siempre clases `FormRequest` aisladas.
- **Seguridad XSS y CSS en Frontend**: Todo contenido HTML renderizado con `v-html` en la web pública se sanitiza mediante el composable `useSanitize.js` (`isomorphic-dompurify`), y toda etiqueta `<style>` con CSS personalizado en el Page Builder se inyecta mediante `sanitizeInlineCss()` para preservar selectores CSS válidos en SSR sin permitir cierres `</style>`. Aplicado en todos los bloques del Builder, `Article.vue` y `Page.vue`. El bloque `ElementHtml` queda exento por ser contenido exclusivo de administradores.
- **Sanitización y Normalización de Texto**: El editor `WysiwygEditor.vue` y los controladores backend normalizan automáticamente entidades `&nbsp;` y caracteres `\u00A0` a espacios estándar ASCII, además de limpiar estilos inline, clases de Office y comentarios condicionales al pegar contenido externo.
- **Manejo de Errores Limpio**: Excepciones de negocio lanzadas como `RuntimeException` y capturadas en los controladores para devolver respuestas descriptivas al usuario vía `back()->with('error', $message)`.

---

## 3. Reglas del Frontend (Vue 3, Inertia, PrimeVue & Tailwind)

- **Componentes Reutilizables**: Usar el design system del proyecto basado en PrimeVue v4 (preset Aura) e iconos PrimeIcons.
- **Sincronización de Tema**: Todo componente nuevo debe incorporar estilos de modo claro y oscuro (`dark:` classes) y ser compatible con la variante `@custom-variant dark (&:where(.dark, .dark *))` definida en `app.css`.
- **Cero Fuentes o CDNs Externas**: Queda estrictamente prohibido enlazar fuentes, scripts o hojas de estilos desde servidores de terceros (Google Fonts, Bunny Fonts, CDNs). Toda dependencia debe servirse localmente o mediante la pila de fuentes nativas del sistema operativo para cumplir con RGPD y optimizar el rendimiento.
- **Identidad Visual**: Usar la paleta primaria Emerald (`#10b981`) y superficies Zinc (`#09090b`).
- **Selectores PrimeVue 4 (`<Select optionValue="value" />`)**: Nunca utilizar cadena vacía (`value: ''`) en opciones por defecto o nulas; emplear siempre valores explícitos no vacíos (`'none'`, `'transparent'`, `'inherit'`, `'all'`) junto con modelos computados desambiguados.
- **Discriminación de Enlaces SPA vs Nativos**: Utilizar siempre `esNavegacionInertia(url, target)` de `useUrlHelpers.js` para decidir entre `<Link>` de Inertia y `<a>` nativo, manteniendo las rutas excluidas (`contacto`, `descargas`, `storage`) en `RUTAS_NO_INERTIA`.

---

## 4. Calidad y Pruebas

- **Suite de Pruebas Automatizadas**: Cualquier modificación o nueva funcionalidad debe mantener el 100% de éxito en `php artisan test` (**248/248 tests pasados, 1.231 aserciones**).
- **Archivos Temporales**: Borrar inmediatamente cualquier script o archivo temporal creado durante tareas de depuración o desarrollo.
