# Lista de Verificación: Migración Manual AfterReload

Como vas a usar GitHub para el código, esto es lo que **SÍ O SÍ** tienes que mover tú a mano para que la web funcione:

## 1. El archivo de Identidad (.env)
**Ruta:** `/var/www/afterreload/.env`
**Por qué:** Contiene las claves de Steam y la conexión a la DB. Sin esto, el Login de Steam dará error y la web no podrá funcionar correctamente.

## 2. Los Servidores de Juego (CS:GO Legacy)
**Ruta:** `/var/www/afterreload/csgoserver/`
**Por qué:** Estos archivos pesan gigas y **no están en GitHub**. 
**Qué copiar:**
- Toda la carpeta `serverfiles/` (especialmente `csgo/addons`, `csgo/cfg` y `csgo/maps`).
- Los ejecutables de LinuxGSM (`linuxgsm.sh`, `csgoserver`, etc.).

## 3. Logos y Branding (Solo si no están en Git)
He visto carpetas como `logos/` y `public/branding/`. Asegúrate de que se vean en el nuevo servidor. Si faltan imágenes, cópialas a mano.

---

## Pasos en el nuevo VPS:
1. Instalar el entorno (PHP 8.3, Nginx, Redis).
2. `git clone https://github.com/betanS/AfterReload.git`.
3. **Copiar tu archivo `.env` viejo a la carpeta del nuevo.**
4. Ejecutar `composer install` y `npm install`.
5. Ejecutar `php artisan key:generate` (Solo si no copiaste el .env).
6. Configurar Nginx y Certbot para el SSL.

*Nota: Al ser CS:GO Legacy, asegúrate de que el nuevo VPS tenga instaladas las librerías de 32 bits necesarias (`lib32gcc-s1`, `lib32stdc++6`, etc.), de lo contrario el servidor de CSGO no arrancará.*
