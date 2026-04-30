<?php

namespace App\Services;

use App\Models\Server;

class LocalCsgoserverRuntimeService
{
    public function isRunning(Server $server): ?bool
    {
        $lockFile = $this->resolveLockFile($server);

        if ($lockFile === null) {
            return null;
        }

        $lockDir = rtrim((string) config('services.csgoserver.lock_dir', ''), '/');
        if ($lockDir === '') {
            return null;
        }

        return is_file($lockDir . '/' . $lockFile);
    }

    private function resolveLockFile(Server $server): ?string
    {
        $portMap = config('services.csgoserver.port_map', []);

        if (! is_array($portMap) || ! is_numeric($server->port)) {
            return null;
        }

        $lockFile = $portMap[(int) $server->port] ?? null;

        return is_string($lockFile) && $lockFile !== '' ? $lockFile : null;
    }
}

