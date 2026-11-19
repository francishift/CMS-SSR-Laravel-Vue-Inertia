# Guía de Instalación y Despliegue en Servidor

Esta guía detalla los pasos completos para instalar, configurar y desplegar la aplicación **CMS SSR** en entornos de desarrollo local o servidores de producción.

---

## 1. Requisitos del Sistema

- **PHP**: v8.4+ con extensiones `pdo_mysql`, `mbstring`, `openssl`, `bcmath`, `curl`, `redis`, `gd`, `zip`.
- **Composer**: v2.6+
- **Node.js**: v18+ o v20+ con `npm`
- **Base de Datos**: MySQL 8.0+, MariaDB 10.5+ o SQLite
- **Servidor Redis**: v6.x+ corriendo en `127.0.0.1:6379`
- **Servidor Web**: Nginx (Proxy Inverso) + Apache (con módulo `mod_rewrite` activo)

---

## 2. Proceso Paso a Paso

### 2.1 Clonación e Instalación de Dependencias PHP
```bash
# Clonar el repositorio
git clone https://github.com/francishift/CMS-SSR-Laravel-Vue-Inertia.git
cd CMS-SSR-Laravel-Vue-Inertia

# Instalar dependencias de Composer optimizadas para producción
composer install --no-interaction --prefer-dist --optimize-autoloader
```

### 2.2 Configuración del Archivo de Entorno (`.env`)
```bash
# Copiar el archivo de plantilla
cp .env.example .env

# Generar la clave de encriptación de la aplicación
php artisan key:generate
```

Editar `.env` verificando los siguientes parámetros de base de datos y Redis:
```env
APP_NAME="CMS SSR"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://midominio.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tu_base_de_datos
DB_USERNAME=tu_usuario
DB_PASSWORD="tu_password"

SESSION_DRIVER=redis
CACHE_STORE=redis
CACHE_PREFIX=cms_ssr_

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=4
REDIS_CACHE_DB=5
REDIS_PREFIX=cms_ssr_
```

### 2.3 Migraciones y Sembrado de Datos
```bash
# Ejecutar migraciones de base de datos
php artisan migrate --force

# Sembrar los roles iniciales y el usuario administrador
php artisan db:seed --class=RolesSeeder
```

### 2.4 Compilación de Assets Frontend
```bash
# Instalar paquetes de npm
npm install

# Compilar los bundles estáticos de producción con Vite
npm run build
```

### 2.5 Limpieza y Optimización de Caché
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 2.6 Auditoría de Seguridad de Dependencias
Para verificar que los árboles de dependencias de PHP y Node.js se mantienen libres de vulnerabilidades conocidas (CVEs / GHSAs):
```bash
# Auditoría de paquetes Backend (objetivo: 0 avisos)
composer audit

# Auditoría de paquetes Frontend y SSR
npm audit
```
> **Nota sobre `npm audit fix`**: Ejecutar siempre `npm audit fix` (sin `--force`). El flag `--force` intentaría degradar `quill@2.0.3` a `2.0.2` (cambio disruptivo innecesario, dado que toda salida HTML pública pasa por sanitización estricta con `DOMPurify` en `useSanitize.js`).

---

## 3. Credenciales por Defecto

Tras ejecutar `RolesSeeder` o `DatabaseSeeder`, el acceso inicial al panel de administración es:
- **URL**: `https://midominio.com/acceso` (o `/login`)
- **Email**: `admin@example.com`
- **Contraseña**: `Admin1234!`
- **Rol**: `admin`

---

## 4. Google OAuth (Opcional)

Para habilitar el botón "Acceder con Google" en la pantalla de login, configurar en `.env`:
```env
GOOGLE_CLIENT_ID=tu-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-tu-secret
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
```

Requiere configurar las URIs de redirección autorizadas en Google Cloud Console. Consultar la documentación completa en [`docs/autenticacion-oauth-google.md`](autenticacion-oauth-google.md).

> **Nota**: Solo permite login a usuarios ya registrados en la BD. No crea cuentas nuevas.

