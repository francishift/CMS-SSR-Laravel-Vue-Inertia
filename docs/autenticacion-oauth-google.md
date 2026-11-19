# Autenticación OAuth con Google

Documentación del sistema de autenticación mediante Google OAuth 2.0 integrado en el panel de administración.

---

## 1. Descripción General

El CMS permite a los usuarios **ya registrados** acceder al panel mediante su cuenta de Google, como alternativa al login tradicional con email y contraseña. Este flujo **no crea cuentas nuevas** — si el email de Google no existe en la base de datos, se rechaza el acceso.

### Flujo de Autenticación

```
Usuario → Click "Acceder con Google"
       → Redirección a Google (consentimiento)
       → Google devuelve code al callback
       → Laravel busca usuario por email en la BD
       → Si existe y está activo → Login + redirect a /panel
       → Si no existe / inactivo → Redirect a /acceso con error
```

---

## 2. Archivos Principales

| Archivo | Responsabilidad |
|---|---|
| `app/Http/Controllers/Auth/GoogleOAuthController.php` | Controlador OAuth: redirect a Google y procesamiento del callback |
| `routes/auth.php` | Rutas `/auth/google/redirect` y `/auth/google/callback` |
| `config/services.php` | Configuración de credenciales OAuth (`services.google`) |
| `resources/js/Pages/Auth/Login.vue` | Botón "Acceder con Google" + mensajes flash de error |
| `database/migrations/xxxx_add_google_id_to_users_table.php` | Campo `google_id` nullable en la tabla `users` |
| `tests/Feature/Auth/GoogleOAuthTest.php` | 6 tests del flujo OAuth completo |

---

## 3. Configuración (`.env`)

```env
# Google OAuth (panel de administración)
GOOGLE_CLIENT_ID=tu-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-tu-secret
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
```

### Google Cloud Console

1. Acceder a [console.cloud.google.com](https://console.cloud.google.com) → **APIs y servicios** → **Credenciales**.
2. Crear o editar un **ID de cliente OAuth 2.0** de tipo "Aplicación web".
3. En **URIs de redirección autorizados** añadir: `https://tudominio.com/auth/google/callback`.
4. En **Orígenes autorizados de JavaScript** añadir: `https://tudominio.com`.
5. Verificar que la **Pantalla de consentimiento OAuth** esté **publicada** (no en modo Testing).

---

## 4. Reglas de Seguridad

### 4.1 Solo usuarios existentes
El controlador busca el email de Google en la tabla `users`. Si no existe, rechaza con mensaje de error. No se crean cuentas nuevas vía OAuth.

### 4.2 Usuarios inactivos
Si el usuario tiene `active = false`, se rechaza el acceso con mensaje específico.

### 4.3 Vinculación automática de `google_id`
En el primer login OAuth exitoso, se almacena el `google_id` de Google en la tabla `users`. En logins posteriores, si ya tiene un `google_id` asignado, no se sobreescribe.

### 4.4 Stateless OAuth
El callback usa `Socialite::driver('google')->stateless()->user()` para evitar problemas de `InvalidStateException` cuando la cookie de sesión no persiste correctamente entre la redirección a Google y el callback.

### 4.5 No-indexación
Las rutas `/auth/google/*` están protegidas contra indexación con las mismas 3 capas que el resto del panel:
- `robots.txt` → `Disallow: /auth/`
- Meta tag `<meta name="robots" content="noindex, nofollow">`
- Header HTTP `X-Robots-Tag: noindex, nofollow`

### 4.6 Rate limiting
Las rutas OAuth están protegidas con `throttle:10,1` (máximo 10 peticiones por minuto por IP).

---

## 5. Compatibilidad con ModSecurity (COMODO WAF)

### Problema conocido: Regla 210580

En servidores con **COMODO WAF** (ModSecurity), la regla `210580` bloquea el callback de Google porque el parámetro `scope` de la URL contiene la cadena `.profile` (de `userinfo.profile`), que ModSecurity interpreta como un intento de acceso al archivo del sistema `.profile`.

### Solución aplicada

El controlador usa `setScopes(['openid', 'email'])` para **reemplazar** los scopes por defecto de Socialite (que incluyen `profile`) y solicitar únicamente `openid` y `email`. Esto es suficiente para nuestro caso de uso, ya que solo necesitamos el email del usuario para buscarlo en la base de datos.

```php
// GoogleOAuthController::redirect()
return Socialite::driver('google')
    ->setScopes(['openid', 'email'])
    ->redirect();
```

> **Importante**: No usar `->scopes()` (que añade scopes a los por defecto), sino `->setScopes()` (que los reemplaza por completo).

---

## 6. Tests

El archivo `tests/Feature/Auth/GoogleOAuthTest.php` valida:

| Test | Validación |
|---|---|
| `test_redirige_a_google_correctamente` | El redirect devuelve 302 a `accounts.google.com` |
| `test_login_exitoso_con_usuario_existente_y_activo` | Login exitoso + vinculación de `google_id` |
| `test_no_sobreescribe_google_id_si_ya_existe` | `google_id` original no se sobreescribe |
| `test_rechaza_email_no_registrado` | Email inexistente → redirect a login con error |
| `test_rechaza_usuario_inactivo` | Usuario con `active = false` → redirect a login con error |
| `test_rutas_oauth_tienen_header_noindex` | Header `X-Robots-Tag: noindex, nofollow` presente |

```bash
php artisan test tests/Feature/Auth/GoogleOAuthTest.php
```
