# AfterReload - Matchmaking & Public Server Management

Sistema optimizado para gestión de servidores CS:GO (Legacy).

## Guía de Implementación

### 1. Entorno
*   **PHP**: 8.3
*   **Servidor Web**: Nginx
*   **Base de Datos**: MySQL o SQLite
*   **Cache**: Redis

### 2. Instalación
1. Clonar el repositorio:
   ```bash
   git clone https://github.com/betanS/AfterReload.git
   cd AfterReload
   ```
2. Instalar dependencias:
   ```bash
   composer install
   npm install && npm run build
   ```
3. Configuración:
   - Copiar `.env.example` a `.env` y configurar las credenciales (Steam, Database).
   - Generar la clave de la app: `php artisan key:generate`
   - Migrar la base de datos: `php artisan migrate --force`

### 3. Servidores de Juego
Asegúrate de que los ejecutables de LinuxGSM estén correctamente ubicados en `/csgoserver` y que los servidores CS:GO (Legacy) tengan las dependencias de 32 bits (`lib32gcc-s1`, `lib32stdc++6`) instaladas.

## Gestión de Servidores
- **Panel Admin**: Acceso vía `/admin` para alta, edición y control de energía de servidores.
- **Limpieza**: Usa los botones en el Panel Admin para expulsar jugadores de servidores Matchmaking (MM) o Públicos en caso de bloqueos.
- **Lobbies Públicos**: Sistema de slots dinámicos. El usuario debe unirse para ver la IP de conexión. La salida es gestionada automáticamente al cerrar la página.
