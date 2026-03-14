# AfterReload

Plataforma web de matchmaking para CS:GO con login vía Steam, lobbies en tiempo real y tienda de skins del servidor.

## Stack
- Laravel 12 (PHP 8.2+)
- MySQL (producción) / SQLite (local rápido)
- Steam OpenID (SocialiteProviders/Steam)
- Tailwind CSS (CDN)
- Real-time con Laravel Echo + Pusher (o fallback con polling)
- Nginx + PHP-FPM (deploy VPS)

## Features principales
- Login con Steam (nickname, avatar, SteamID)
- Lobbies de matchmaking con autorefresh y auto-leave al salir
- Estado online/offline por ping TCP al servidor
- Tienda de skins con filtros, búsqueda y paginación
- Perfil, inventario y contacto

## Configuración rápida (local)
```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed --class=ServerSeeder
```

## Variables de entorno clave
```env
APP_URL=http://localhost

DB_CONNECTION=sqlite
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_DATABASE=afterreload
# DB_USERNAME=afterreload
# DB_PASSWORD=secret

STEAM_CLIENT_ID=TU_API_KEY
STEAM_CLIENT_SECRET=any-string
STEAM_REDIRECT_URL=http://localhost/login/steam/callback

BROADCAST_CONNECTION=log
PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_APP_CLUSTER=eu
PUSHER_APP_HOST=
PUSHER_APP_PORT=
PUSHER_APP_SCHEME=https
```

## Realtime (Echo + Pusher)
El lobby escucha eventos en tiempo real con Echo + Pusher y tiene fallback con polling si no hay claves.

1. Instalar dependencia PHP:
```bash
composer require pusher/pusher-php-server
```

2. En `.env`:
```env
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=...
PUSHER_APP_KEY=...
PUSHER_APP_SECRET=...
PUSHER_APP_CLUSTER=...
```

3. Limpiar cache:
```bash
php artisan config:clear
php artisan config:cache
```

Si quieres empaquetar Echo en Vite (en lugar de CDN), instala:
```bash
npm install laravel-echo pusher-js
```

## Deploy (VPS)
```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan db:seed --class=ServerSeeder
php artisan config:cache
php artisan view:cache
```

## FastDL
Si usas Nginx, puedes servir FastDL en `/fastdl` con:
```
location /fastdl/ {
    alias /var/www/html/fastdl/;
    autoindex off;
    access_log off;
    add_header Cache-Control "public, max-age=31536000, immutable";
}
```

## Notas
- El listado de servidores se controla vía `database/seeders/ServerSeeder.php`.
- Para sincronizar servidores entre local y VPS, haz `git pull` y ejecuta el seeder.
