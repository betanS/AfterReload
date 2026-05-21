# AfterReload

Plataforma web de matchmaking para comunidades de CS:GO Legacy. Centraliza la autenticación de jugadores via Steam, la gestión de lobbies, el control remoto de servidores mediante RCON, la integración con Get5 para partidas competitivas y el procesamiento automático de resultados en servidores públicos.

## Instalación en Linux

### 1. Instalar programas del sistema

Ejemplo para Ubuntu o Debian:

```bash
sudo apt update
sudo apt install -y git unzip curl nginx sqlite3 php8.2 php8.2-cli php8.2-fpm php8.2-mbstring php8.2-xml php8.2-curl php8.2-sqlite3 php8.2-mysql php8.2-zip php8.2-bcmath php8.2-intl php8.2-redis composer nodejs npm
```

Si vas a usar servidores de CS:GO Legacy en la misma máquina, instala también:

```bash
sudo apt install -y lib32gcc-s1 lib32stdc++6
```

### 2. Clonar el proyecto

```bash
git clone https://github.com/betanS/AfterReload afterreload
cd afterreload
```

### 3. Instalar dependencias

```bash
composer install
npm install
```

### 4. Crear el archivo de entorno

```bash
cp .env.example .env
php artisan key:generate
```

### 5. Configurar `.env`

Ajusta como mínimo estas variables:

```env
APP_NAME=AfterReload
APP_URL=http://TU_DOMINIO_O_IP
DB_CONNECTION=sqlite

STEAM_CLIENT_ID=
STEAM_CLIENT_SECRET=
STEAM_REDIRECT_URL=http://TU_DOMINIO_O_IP/login/steam/callback

ADMIN_STEAM_ID=
ADMIN_STEAM_IDS=

GET5_WEBHOOK_TOKEN=
GET5_DEFAULT_MAP=de_mirage

RCON_HOST=
RCON_PORT=
RCON_PASSWORD=

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME=AfterReload
```

Si usas MySQL o MariaDB, cambia `DB_CONNECTION` y completa `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` y `DB_PASSWORD`.

### 6. Preparar la base de datos

Si usas SQLite:

```bash
touch database/database.sqlite
```

Después ejecuta:

```bash
php artisan migrate --force
```

### 7. Compilar frontend

```bash
npm run build
```

### 8. Ejecutar en desarrollo

```bash
composer run dev
```

### 9. Publicar con Nginx

El `root` del sitio debe apuntar a:

```text
/var/www/afterreload/public
```

Reinicia los servicios:

```bash
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx
```

### 10. Integración con servidor de juego

Si vas a controlar servidores locales, deja disponible la carpeta `csgoserver` dentro del proyecto y configura correctamente RCON, Get5 y la ruta de logs del servidor.

## Instalación en Windows

Instala `Git for Windows`, `PHP 8.2+`, `Composer`, `Node.js` con `npm` y una base de datos compatible. Luego sigue la misma secuencia:

```bash
git clone https://github.com/betanS/AfterReload afterreload
cd afterreload
composer install
npm install
copy .env.example .env
php artisan key:generate
type nul > database\database.sqlite
php artisan migrate --force
npm run build
php artisan serve
```

Antes de usar login con Steam o integración con servidores, completa las variables necesarias en `.env`.
