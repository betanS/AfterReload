# AfterReload

AfterReload es una aplicacion web de matchmaking para Counter-Strike orientada a comunidades privadas. El nucleo del proyecto es la parte web: autenticacion, gestion de usuarios, lobbies, ranking, panel administrativo, tienda y API JSON. Sobre esa base se anaden integraciones opcionales con servicios y servidores del juego.

## Stack actual
- Laravel 12
- PHP 8.2+
- MySQL en despliegue / SQLite en desarrollo local
- Blade + JavaScript
- Tailwind CSS cargado por CDN en la interfaz actual
- Laravel Broadcasting con Pusher como opcion de tiempo real
- Fallback por polling cuando no hay broadcaster configurado
- Vite disponible en el proyecto para assets y flujo frontend

## Modulos principales
- Inicio de sesion con Steam mediante OpenID
- Gestion de lobbies con seleccion de equipo, estado listo/no listo y salida automatica
- Ranking de usuarios por puntos
- Panel de administracion para roles y bloqueos
- Tienda con filtros, busqueda y paginacion
- API JSON para integraciones externas

## Integraciones opcionales
Estas partes amplian el proyecto, pero no son necesarias para ejecutar ni evaluar el nucleo web:
- Steam OpenID
- Broadcasting en tiempo real con Pusher
- Webhooks de resultados `get5`
- Comandos RCON contra el servidor del juego
- Sincronizacion con datos externos del ecosistema de Counter-Strike

## Puesta en marcha local
```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed --class=ServerSeeder
npm install
```

Si quieres levantar el entorno de desarrollo completo:
```bash
composer run dev
```

El proyecto puede ejecutarse tambien sin tiempo real configurado. En ese caso el lobby utiliza polling como mecanismo de actualizacion.

## Variables de entorno clave
```env
APP_URL=http://localhost

DB_CONNECTION=sqlite
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=afterreload
# DB_USERNAME=afterreload
# DB_PASSWORD=secret

STEAM_CLIENT_ID=
STEAM_CLIENT_SECRET=
STEAM_REDIRECT_URL=http://localhost/login/steam/callback

BROADCAST_CONNECTION=log
PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_APP_CLUSTER=eu
PUSHER_APP_HOST=
PUSHER_APP_PORT=
PUSHER_APP_SCHEME=https

GET5_WEBHOOK_TOKEN=
RCON_HOST=
RCON_PORT=
RCON_PASSWORD=
```

## Tiempo real
El lobby puede funcionar de dos formas:
- Con broadcasting y Pusher, cuando las credenciales estan configuradas.
- Con polling, como fallback cuando no hay proveedor realtime disponible.

Para habilitar broadcasting con Pusher:
1. Instala la dependencia PHP necesaria:
```bash
composer require pusher/pusher-php-server
```
2. Configura las variables `PUSHER_*` y `BROADCAST_CONNECTION=pusher`.
3. Limpia la cache de configuracion:
```bash
php artisan config:clear
php artisan config:cache
```

## Despliegue
```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan db:seed --class=ServerSeeder
php artisan config:cache
php artisan view:cache
```

## Notas
- El listado base de servidores se controla desde `database/seeders/ServerSeeder.php`.
- La parte web puede demostrarse de forma independiente aunque no haya servidores de juego operativos.
- Las integraciones con `get5`, RCON o sistemas externos deben considerarse extensiones del proyecto, no un requisito para usar el nucleo web.
