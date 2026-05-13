# Mater-Natura 3.0

[![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php&logoColor=white)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

> **Donde los versos encuentran su hogar** — Plataforma de poesía minimalista con sistema de gestión de contenido propio.

Mater-Natura es un clon de WordPress con alma poética: un CMS escrito desde cero en PHP 8.1+ con un diseño **monocromo en escala de grises**, donde el color se reserva exclusivamente para las imágenes. Construido para ser ligero, extensible con plugins, y con una experiencia de escritura limpia gracias a Quill.js.

---

## 📋 Tabla de contenidos

- [Stack tecnológico](#-stack-tecnológico)
- [Arquitectura](#-arquitectura)
- [Primeros pasos (local)](#-primeros-pasos-local)
- [Despliegue en producción](#-despliegue-en-producción)
- [Uso](#-uso)
- [Visibilidad de posts](#-visibilidad-de-posts)
- [Sistema de plugins](#-sistema-de-plugins)
- [Estructura del proyecto](#-estructura-del-proyecto)
- [Diseño y temática](#-diseño-y-temática)
- [Especificaciones](#-especificaciones)
- [Solución de problemas](#-solución-de-problemas)
- [Contribuir](#-contribuir)
- [Licencia](#-licencia)

---

## 🥞 Stack tecnológico

| Capa | Tecnología |
|------|-----------|
| **Backend** | PHP 8.1+ (MVC propio, sin frameworks) |
| **Base de datos** | MySQL 8+ / MariaDB 10.6+ |
| **Frontend público** | Tailwind CSS 3.4 + Alpine.js 3.14 |
| **Admin** | CSS plano + Alpine.js + Quill.js 2.0 (WYSIWYG) |
| **Autenticación** | Sesiones HTTP-only + Argon2id + rate limiting |
| **Plugins** | Sistema de hooks propio con migraciones y templates |
| **SEO** | Sitemap XML dinámico, Open Graph, Schema.org |
| **Testing** | PHPUnit 11, Behat 3, phpspec 7 |

### Dependencias del sistema

- PHP 8.1+ con extensiones: `pdo_mysql`, `mbstring`, `fileinfo`, `gd`
- Composer 2.x
- Node.js 18+ (solo para compilar Tailwind)
- MySQL o MariaDB

---

## 🏗️ Arquitectura

### MVC sin framework

El proyecto implementa su propio patrón MVC sin framework externo:

```
public/index.php  →  App.php  →  Router.php  →  Controlador  →  Template::render()
                        ↓
                   Database.php, Auth.php, Security.php...
```

### Flujo de petición

1. **Front controller** (`public/index.php`) — carga autoloader, constantes, helpers, inicia sesión
2. **App** (`src/Core/App.php`) — inicializa Database → Security → Auth → PluginManager → Router
3. **Router** (`src/Core/Router.php`) — hace matching de ruta y ejecuta el handler
4. **Controlador** — lógica de negocio (CRUD, validación, etc.)
5. **Template** (`src/Core/Template.php`) — renderiza la vista dentro de un layout (dark/light)

### Enrutamiento

Las rutas se definen con closures en `Router.php`:

```php
$this->get('/post', function () {
    $controller = new PostController($this->db, $this->security);
    echo $controller->index();
});
```

Para rutas con slug dinámico, el Router primero busca en la tabla `posts`, luego en `pages`, y devuelve 404 si no encuentra ninguna coincidencia.

### Seguridad por capas

- **SQL injection**: 100% prepared statements con PDO
- **XSS**: `htmlspecialchars(ENT_QUOTES|ENT_HTML5, UTF-8)` en todas las salidas
- **CSRF**: tokens persistentes por sesión (generados una vez, no single-use)
- **Rate limiting**: 5 intentos por IP+usuario en ventana de 15 minutos
- **Contraseñas**: Argon2id con rehash automático
- **Subidas**: validación MIME con `finfo`, límite 5MB/1200px, sin path traversal
- **Headers de seguridad**: CSP (`self` + `unsafe-inline` + `unsafe-eval`), XFO: DENY, HSTS, etc.

---

## 🚀 Primeros pasos (local)

### 1. Clonar e instalar dependencias

```bash
git clone https://github.com/JuananRodriguez/Mater-Natura-3.0.git
cd Mater-Natura-3.0
composer install
```

### 2. Configurar el entorno

```bash
cp config/database.example.php config/database.php
cp config/app.example.php config/app.php
```

Edita `config/database.php` con tus credenciales:

```php
return [
    'host' => '127.0.0.1',
    'port' => 3306,
    'dbname' => 'mater_natura',
    'username' => 'mn_user',
    'password' => 'tu_contraseña',
    'charset' => 'utf8mb4',
];
```

### 3. Crear la base de datos

```bash
# Con MariaDB/MySQL
mysql -e "CREATE DATABASE IF NOT EXISTS mater_natura CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "GRANT ALL PRIVILEGES ON mater_natura.* TO 'mn_user'@'localhost' IDENTIFIED BY 'tu_contraseña';"
mysql -e "FLUSH PRIVILEGES;"
```

### 4. Ejecutar migraciones

```bash
php public/index.php migrate
```

Esto creará las tablas: `users`, `posts`, `pages`, `migrations`, `failed_logins`, `plugins`, `plugin_hooks`, `sitemap_queue`, y las del plugin de comentarios.

### 5. Crear usuario administrador

```bash
PHP_HASH=$(php -r "echo password_hash('admin123', PASSWORD_ARGON2ID);")
mysql mater_natura -e "INSERT INTO users (username, email, password_hash, role) VALUES ('admin', 'admin@ejemplo.com', '${PHP_HASH}', 'admin');"
```

### 6. Contenido inicial

```bash
# Página de inicio
mysql mater_natura -e "
INSERT INTO pages (user_id, title, slug, content, template, status, is_home, published_at)
VALUES (1, 'Mater-Natura', 'home', '<p>Bienvenido a Mater-Natura, donde los versos encuentran su hogar.</p>', 'dark', 'published', 1, NOW());"

# Post de ejemplo
mysql mater_natura -e "
INSERT INTO posts (user_id, title, slug, description, template, status, published_at)
VALUES (1, 'El primer verso', 'el-primer-verso', '<p>En el principio era la palabra...</p>', 'dark', 'published', NOW());"
```

### 7. Compilar Tailwind (opcional, para el frontend público)

```bash
npx tailwindcss -i resources/css/app.css -o public/assets/css/tailwind.css --minify
```

### 8. Iniciar servidor de desarrollo

```bash
php -S localhost:8081 router.php
```

### 9. ¡A escribir!

Abre `http://localhost:8081` y accede al admin en `http://localhost:8081/login` con `admin` / `admin123`.

---

## 🌍 Despliegue en producción

### Requisitos del servidor

- **PHP**: 8.1+ con extensiones `pdo_mysql`, `mbstring`, `fileinfo`, `gd`
- **Servidor web**: Apache 2.4+ (con `mod_rewrite`) o Nginx
- **Base de datos**: MySQL 8+ o MariaDB 10.6+
- **SSL**: Certificado HTTPS (recomendado Let's Encrypt)

### Apache (`virtualhost`)

```apache
<VirtualHost *:80>
    ServerName maternatura.com
    DocumentRoot /var/www/mater-natura/public

    <Directory /var/www/mater-natura/public>
        AllowOverride All
        Require all granted

        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^ index.php [L]
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/mater-natura-error.log
    CustomLog ${APACHE_LOG_DIR}/mater-natura-access.log combined
</VirtualHost>
```

> **Nota**: El `.htaccess` ya está incluido en `public/.htaccess`. Solo asegúrate de que `mod_rewrite` esté activo.

### Nginx

```nginx
server {
    listen 80;
    server_name maternatura.com;
    root /var/www/mater-natura/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~* \.(jpg|jpeg|png|gif|webp|svg|ico|css|js)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    location ~ /\.ht {
        deny all;
    }
}
```

### Configuración para producción

Edita `config/app.php`:

```php
return [
    'base_url' => 'https://maternatura.com',
    'debug' => false,
    'env' => 'production',
    'session_lifetime' => 1800,
    // ...
];
```

### Migraciones en producción

```bash
php public/index.php migrate
```

Las migraciones son **idempotentes**: cada archivo `.sql` en `migrations/` solo se ejecuta una vez (trackeado en la tabla `migrations`).

### Seguridad adicional en producción

1. **HTTPS obligatorio**: redirige HTTP a HTTPS en el servidor web
2. **CSP estricto**: revisa las directivas en `Security.php::sendSecurityHeaders()`
3. **logs**: rota los logs de `storage/logs/` con logrotate
4. **subidas**: limita el tamaño de subida en `php.ini` (`upload_max_filesize`, `post_max_size`)
5. **sesiones**: configura `session.cookie_secure = 1` y `session.cookie_samesite = Strict`
6. **Firewall**: limita el acceso a `/admin` por IP si es posible

### Despliegue automatizado (ejemplo con Deployer)

```bash
composer require --dev deployer/deployer ^7.0
```

Crea `deploy.php` en la raíz y configura tu servidor remoto. Ver [documentación de Deployer](https://deployer.org/).

---

## ✍️ Uso

### Admin

| Ruta | Descripción |
|------|-------------|
| `/login` | Inicio de sesión |
| `/admin` | Dashboard con estadísticas |
| `/admin/posts` | Listado de posts con columna de visibilidad |
| `/admin/posts/editar` | Crear/editar post (editor visual con Quill.js) |
| `/admin/posts/eliminar` | Eliminar post |
| `/admin/pages` | Listado de páginas |
| `/admin/pages/editar` | Crear/editar página |
| `/admin/comments` | Moderación de comentarios (plugin) |
| `/admin/usuarios` | Gestión de usuarios (solo admin) |
| `/admin/plugins` | Gestión de plugins (solo admin) |
| `/admin/ajustes` | Configuración (selector página de inicio, solo admin) |

### Frontend público

| Ruta | Descripción |
|------|-------------|
| `/` | Página de inicio (configurable desde admin) |
| `/post` | Listado paginado de posts públicos |
| `/{slug}` | Post o página individual |
| `/sitemap.xml` | Sitemap dinámico para SEO |

### Roles de usuario

| Rol | Acceso |
|-----|--------|
| **admin** | Acceso completo (posts, páginas, comentarios, usuarios, plugins, ajustes) |
| **editor** | Solo contenido (posts, páginas, comentarios) |

---

## 🔒 Visibilidad de posts

Cada post puede tener uno de tres niveles de visibilidad, controlable desde el panel de edición:

| Visibilidad | Comportamiento |
|-------------|----------------|
| **Público** (por defecto) | Accesible para cualquier visitante. Aparece en listados y sitemap. |
| **Privado** | Solo usuarios autenticados pueden verlo. Los visitantes ven una página 403 con enlace a iniciar sesión. No aparece en listados públicos ni sitemap. |
| **Protegido con contraseña** | Visible solo tras introducir una contraseña correcta. Se verifica por sesión. No aparece en listados públicos ni sitemap. |

### Cómo funciona

- **Privado**: El controlador verifica `Auth::isAuthenticated()`. Si es `false`, devuelve la vista `403.php`.
- **Protegido**: Al visitar el post, se muestra un formulario (`post-password.php`). Al enviar la contraseña, se verifica con `password_verify()` contra el hash almacenado en `visibility_password`. Si es correcta, se guarda `$_SESSION['post_password_' . $post['id']] = true` y se redirige al post. La sesión mantiene la verificación hasta que el usuario cierra el navegador.

### Columnas en base de datos

```sql
visibility ENUM('public','private','password') NOT NULL DEFAULT 'public',
visibility_password VARCHAR(255) DEFAULT NULL,
```

---

## 🔌 Sistema de plugins

Mater-Natura tiene un sistema de plugins propio basado en hooks, con dependencias inyectadas automáticamente.

### Plugins incluidos

| Plugin | Descripción |
|--------|-------------|
| **Comments** | Sistema de comentarios tipo WordPress con moderación |

### Hooks disponibles

| Hook | Disparo |
|------|---------|
| `entry.render.before` | Antes de renderizar un post/página |
| `entry.render.after` | Después de renderizar un post/página |
| `comment.submit` | Al enviar un comentario (POST /comment) |
| `admin.menu.add` | Para añadir enlaces al sidebar del admin |
| `admin.entry.form` | Campos adicionales en formularios |
| `admin.dashboard.widgets` | Widgets en el dashboard |

### Crear un plugin

```bash
src/Plugins/MiPlugin/
├── Plugin.php              ← Clase principal
├── migrations/             ← Migraciones SQL propias
│   └── 001_init.sql
└── templates/              ← Templates específicos
    └── admin-view.php
```

### Estructura de un Plugin

```php
<?php
namespace MaterNatura\Plugins\MiPlugin;

use MaterNatura\Core\Database;
use MaterNatura\Core\Security;
use MaterNatura\Core\Auth;

class Plugin implements \MaterNatura\Plugins\PluginInterface
{
    private ?Database $db = null;
    private ?Security $security = null;
    private ?Auth $auth = null;

    // Inyección automática por method_exists
    public function setDatabase(Database $db): void { $this->db = $db; }
    public function setSecurity(Security $security): void { $this->security = $security; }
    public function setAuth(Auth $auth): void { $this->auth = $auth; }

    public function getMeta(): array { /* nombre, versión, descripción */ }
    public function onActivate(): void { /* ejecutar migraciones */ }
    public function onDeactivate(): void { /* limpiar */ }
    public function registerHooks(): array { /* hook → handler */ }
}
```

---

## 📁 Estructura del proyecto

```
mater-natura/
├── composer.json                   ← Dependencias PHP
├── package.json                    ← Dependencias Node (Tailwind)
├── tailwind.config.js              ← Configuración de Tailwind
├── router.php                      ← Router para servidor de desarrollo
│
├── config/
│   ├── app.php                     ← Configuración de la app (pública)
│   ├── app.example.php             ← Plantilla de app.php
│   ├── database.example.php        ← Plantilla de database.php
│   └── database.php                ← ⚠️ Credenciales BD (NO SUBIR)
│
├── public/
│   ├── .htaccess                   ← Rewrite rules para Apache
│   ├── index.php                   ← Front controller
│   ├── sitemap.xml                 ← Sitemap auto-generado
│   ├── assets/
│   │   ├── css/
│   │   │   ├── admin.css           ← Estilos del panel de admin
│   │   │   ├── base.css            ← Estilos base + contenido renderizado
│   │   │   ├── dark.css            ← Tema oscuro
│   │   │   ├── light.css           ← Tema claro
│   │   │   └── tailwind.css        ← Tailwind compilado
│   │   ├── js/
│   │   │   ├── admin.js            ← Quill init, upload AJAX
│   │   │   ├── alpine.min.js       ← Alpine.js v3.14
│   │   │   └── app.js              ← JS del frontend público
│   │   ├── icons/                  ← SVG iconos CoreUI Free (18 iconos)
│   │   ├── img/                    ← Logo, iconos sociales, menú
│   │   └── quill/                  ← Quill.js v2.0 (editor WYSIWYG)
│
├── src/
│   ├── Controllers/
│   │   ├── AdminController.php     ← CRUD posts, páginas, usuarios, upload
│   │   ├── AuthController.php      ← Login/logout
│   │   ├── HomeController.php      ← Página de inicio
│   │   ├── MediaController.php     ← Servir imágenes (proxy PHP)
│   │   ├── PageController.php      ← Páginas públicas
│   │   ├── PostController.php      ← Posts públicos con visibilidad
│   │   └── SitemapController.php   ← Sitemap XML dinámico
│   │
│   ├── Core/
│   │   ├── App.php                 ← Bootstrap de la app
│   │   ├── Auth.php                ← Autenticación (Argon2id, sesiones, 2FA)
│   │   ├── Database.php            ← PDO wrapper + migraciones
│   │   ├── PluginManager.php       ← Sistema de plugins + hooks
│   │   ├── Router.php              ← Enrutador con slug resolver
│   │   ├── Security.php            ← CSRF, rate limiting, CSP, sanitización
│   │   ├── Template.php            ← Motor de plantillas con layouts
│   │   ├── UploadHandler.php       ← Subida de imágenes con validación
│   │   └── helpers.php             ← svg_icon(), renderHtml()
│   │
│   ├── Models/
│   │   ├── Page.php                ← Modelo de página (PHP 8 promoted props)
│   │   ├── Plugin.php              ← Modelo de plugin
│   │   ├── Post.php                ← Modelo de post (con visibilidad)
│   │   └── User.php                ← Modelo de usuario
│   │
│   ├── Plugins/
│   │   └── Comments/               ← Plugin de comentarios
│   │       ├── Plugin.php
│   │       ├── migrations/001_comments.sql
│   │       └── templates/
│   │           ├── admin-comments.php
│   │           └── comments-section.php
│   │
│   └── Templates/
│       ├── 403.php                 ← Acceso restringido
│       ├── 404.php                 ← Página no encontrada
│       ├── home.php                ← Página de inicio
│       ├── page.php                ← Página estática
│       ├── post-list.php           ← Listado de posts
│       ├── post-password.php       ← Formulario contraseña
│       ├── post-single.php         ← Post individual
│       ├── admin/
│       │   ├── layout.php          ← Layout del admin (sidebar, toolbar)
│       │   ├── login.php           ← Login standalone
│       │   ├── dashboard.php
│       │   ├── post-form.php       ← Editor brutalista dos columnas
│       │   ├── post-list.php
│       │   ├── page-form.php
│       │   ├── page-list.php
│       │   ├── plugins.php
│       │   ├── settings.php
│       │   ├── user-form.php
│       │   └── user-list.php
│       ├── layouts/
│       │   ├── dark.php            ← Tema oscuro
│       │   └── light.php           ← Tema claro
│       └── partials/
│           ├── head.php            ← Meta tags, CSS, Open Graph
│           ├── header.php          ← Navegación (Alpine + overlay)
│           └── footer.php          ← Footer con enlaces legales
│
├── migrations/
│   └── 001_initial.sql             ← Migración inicial (tablas del core)
│
├── specs/                          ← Especificaciones (Gherkin + contratos)
│   ├── features/                   ← 7 features (.feature)
│   ├── modules/                    ← 10 módulos de especificación
│   └── contracts/                  ← 8 contratos de interfaz
│
├── resources/
│   └── css/app.css                 ← Input de Tailwind (@tailwind directives)
│
├── storage/
│   └── logs/
│       └── .gitkeep               ← Directorio de logs
│
└── uploads/
    └── .gitkeep                    ← Archivos subidos (imágenes)
```

---

## 🎨 Diseño y temática

### Paleta de colores

El diseño es **monocromo en escala de grises**. Sin colores — ni azules, ni verdes, ni marrones. El color se reserva exclusivamente para las imágenes.

| Elemento | Color |
|----------|-------|
| Fondos | `#f9f9f9`, `#ffffff`, `#1a1a2e` (dark) |
| Texto | `#1a1a1a`, `#e8e0d4` (dark) |
| Bordes | `#000000`, `#e0e0e0` |
| Acento | `#000000` (hover: `#333333`) |
| Botones primarios | `#000000` con texto blanco |

### Temas

- **Dark** (`layouts/dark.php`): Fondo `#1a1a2e` (noche, luna, tinta), texto `#e8e0d4`
- **Light** (`layouts/light.php`): Fondo `#faf6f0` (amanecer, papel), texto `#2c2416`

Cada post y página puede elegir su tema individualmente desde el editor.

### Iconos

Prohibido el uso de emojis. Todos los iconos son SVGs inline de **CoreUI Free** renderizados con la función global `svg_icon()`:

```php
<?= svg_icon('speedometer') ?> Dashboard
<?= svg_icon('pencil') ?> Posts
```

Ver `public/assets/icons/` para los 18 iconos disponibles.

### Editor de posts

El editor de posts sigue un diseño **brutalista de dos columnas** inspirado en Noir CMS:

- **Columna izquierda (canvas)**: Título (48px bold) + editor Quill.js con toolbar completo (headings, bold, lists, quotes, code, links, imágenes) + zona de drop de imagen destacada
- **Columna derecha (settings)**: Panel de 320px sticky con ajustes de publicación (template, visibilidad, fecha), URL/Slug, y vista previa SEO (meta title, descripción)

En móvil (≤767px) las columnas se apilan verticalmente.

---

## 📋 Especificaciones

El proyecto incluye especificaciones formales en `specs/`:

- **Features** (Gherkin/Behat): 7 archivos `.feature` cubriendo autenticación, posts, páginas, plugins, media, sitemap y home
- **Módulos**: 10 documentos de especificación detallada (`auth-spec.md`, `posts-spec.md`, etc.)
- **Contratos**: 8 interfaces de contrato para los servicios core

---

## 🔧 Solución de problemas

### Error interno del servidor

```bash
# Iniciar con debug para ver la traza completa
MATER_DEBUG=true php -S localhost:8081 router.php
```

### Puerto ocupado

```bash
lsof -ti :8081 | xargs kill -9
# O usa otro puerto
php -S localhost:8082 router.php
```

### Apache ocupando el puerto 8080 en macOS

```bash
lsof -ti :8080 | xargs kill -9
# O mejor: usa el puerto 8081
```

### CSRF token no válido

Los tokens CSRF son persistentes por sesión. Si ves errores de CSRF, reinicia la sesión (cierra sesión y vuelve a entrar).

### "Property cannot have type ?callable"

Si estás en PHP 8.2+, revisa `Router.php` — la propiedad `$slugHandler` no puede tener tipo `?callable`. Usa `mixed` o ningún type hint.

### Los cambios en CSS no se ven

El servidor de desarrollo tiene caché de 24h para archivos estáticos. Añade `?v=N` al `<link>`:
```html
<link rel="stylesheet" href="/assets/css/admin.css?v=2">
```

### Alpine.js no funciona

Nuestra versión de Alpine (3.14.8) tiene un bug conocido al evaluar `x-data` con funciones nombradas como `postForm()`. Para formularios críticos usamos JS nativo como fallback.

---

## 🤝 Contribuir

1. Haz fork del proyecto
2. Crea una rama (`git checkout -b feature/nueva-funcionalidad`)
3. Haz commit de tus cambios (`git commit -m 'Añade nueva funcionalidad'`)
4. Haz push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Abre un Pull Request

### Convenciones de código

- PHP 8.1+ con type hints estrictos
- Nombres de tablas en snake_case, columnas en snake_case
- Propiedades de modelo en camelCase (PHP 8 promoted properties)
- Sin emojis en la interfaz (usar iconos CoreUI SVG)
- Paleta B/N/gris en diseño
- Pruebas antes de nuevas funcionalidades

---

## 📄 Licencia

MIT — Libre para usar, modificar y distribuir.

---

<p align="center">
  Hecho con ❤️ y versos por Juanan Rodríguez<br>
  <em>— Donde los versos encuentran su hogar —</em>
</p>
