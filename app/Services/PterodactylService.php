<?php

namespace App\Services;

use App\Models\Server;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PterodactylService
{
    public function accountEmail(): string
    {
        return trim((string) config('services.pterodactyl.account_email', ''));
    }

    public function getPanelUrl(): string
    {
        return $this->panelUrl();
    }

    public function isConfigured(): bool
    {
        return $this->panelUrl() !== '' && $this->clientApiKey() !== '';
    }

    public function hasApplicationApi(): bool
    {
        return $this->panelUrl() !== '' && $this->applicationApiKey() !== '';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listServers(): array
    {
        if ($this->hasApplicationApi()) {
            return $this->listServersFromApplicationApi();
        }

        if ($this->isConfigured()) {
            return $this->listServersFromClientApi();
        }

        throw new RuntimeException('Faltan credenciales de Pterodactyl.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function listServersFromClientApi(): array
    {
        $response = $this->client()->get('/api/client');

        if ($response->failed()) {
            throw new RuntimeException('No se pudo obtener la lista de servidores desde Pterodactyl.');
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException('Respuesta inválida de Pterodactyl.');
        }

        $servers = Arr::get($payload, 'data', []);

        if (! is_array($servers)) {
            return [];
        }

        $mappedServers = array_map(function ($entry) {
            if (! is_array($entry)) {
                return null;
            }

            $attributes = Arr::get($entry, 'attributes', []);

            if (! is_array($attributes)) {
                return null;
            }

            $identifier = Arr::get($attributes, 'identifier');
            $uuid = Arr::get($attributes, 'uuid');
            $name = Arr::get($attributes, 'name');

            if (! is_string($identifier) || $identifier === '' || ! is_string($name) || $name === '') {
                return null;
            }

            $status = Arr::get($attributes, 'status');
            $description = Arr::get($attributes, 'description');
            $suspended = (bool) Arr::get($attributes, 'is_suspended', false);
            $limits = Arr::get($attributes, 'limits', []);

            return [
                'identifier' => $identifier,
                'uuid' => is_string($uuid) && $uuid !== '' ? $uuid : null,
                'name' => $name,
                'description' => is_string($description) && $description !== '' ? $description : null,
                'status' => is_string($status) && $status !== '' ? $status : ($suspended ? 'suspended' : 'unknown'),
                'suspended' => $suspended,
                'limits' => is_array($limits) ? $limits : [],
                'panel_link' => $this->panelUrl() . '/server/' . $identifier,
            ];
        }, $servers);

        return array_values(array_filter($mappedServers));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function listServersFromApplicationApi(): array
    {
        $response = $this->applicationClient()->get('/api/application/servers');

        if ($response->failed()) {
            throw new RuntimeException('No se pudo obtener la lista global de servidores desde Pterodactyl.');
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException('Respuesta inválida de Pterodactyl.');
        }

        $servers = Arr::get($payload, 'data', []);

        if (! is_array($servers)) {
            return [];
        }

        $mappedServers = array_map(function ($entry) {
            if (! is_array($entry)) {
                return null;
            }

            $attributes = Arr::get($entry, 'attributes', []);

            if (! is_array($attributes)) {
                return null;
            }

            $identifier = Arr::get($attributes, 'identifier');
            $uuid = Arr::get($attributes, 'uuid');
            $name = Arr::get($attributes, 'name');

            if (! is_string($identifier) || $identifier === '' || ! is_string($name) || $name === '') {
                return null;
            }

            $description = Arr::get($attributes, 'description');
            $suspended = (bool) Arr::get($attributes, 'suspended', false);
            $limits = Arr::get($attributes, 'limits', []);
            $allocation = Arr::get($attributes, 'relationships.allocations.data.0.attributes', []);

            $ip = Arr::get($allocation, 'ip_alias') ?: Arr::get($allocation, 'ip');
            $port = Arr::get($allocation, 'port');

            return [
                'identifier' => $identifier,
                'uuid' => is_string($uuid) && $uuid !== '' ? $uuid : null,
                'name' => $name,
                'description' => is_string($description) && $description !== '' ? $description : null,
                'status' => $suspended ? 'suspended' : 'unknown',
                'suspended' => $suspended,
                'limits' => is_array($limits) ? $limits : [],
                'ip' => is_string($ip) && $ip !== '' ? $ip : null,
                'port' => is_numeric($port) ? (int) $port : null,
                'panel_link' => $this->panelUrl() . '/server/' . $identifier,
            ];
        }, $servers);

        return array_values(array_filter($mappedServers));
    }

    public function resolveCurrentState(Server $server): ?string
    {
        if (! $server->hasPterodactylIntegration() || ! $this->isConfigured()) {
            return $server->pterodactyl_status ?: null;
        }

        try {
            $payload = $this->fetchResources($server);
            $state = $this->extractCurrentState($payload);

            if ($state === null) {
                return $server->pterodactyl_status ?: null;
            }

            $server->forceFill([
                'pterodactyl_status' => $state,
                'pterodactyl_last_synced_at' => now(),
            ])->save();

            return $state;
        } catch (\Throwable) {
            return $server->pterodactyl_status ?: null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function sync(Server $server): array
    {
        if (! $server->hasPterodactylIntegration()) {
            throw new RuntimeException('El servidor no tiene identificador de Pterodactyl.');
        }

        if (! $this->isConfigured()) {
            throw new RuntimeException('Faltan PTERODACTYL_URL o PTERODACTYL_CLIENT_API_KEY.');
        }

        $details = $this->fetchDetails($server);
        $resources = $this->fetchResources($server);
        $state = $this->extractCurrentState($resources);

        $server->update([
            'pterodactyl_uuid' => Arr::get($details, 'attributes.uuid', $server->pterodactyl_uuid),
            'pterodactyl_status' => $state ?? $server->pterodactyl_status,
            'pterodactyl_last_synced_at' => now(),
        ]);

        return [
            'details' => $details,
            'resources' => $resources,
        ];
    }

    public function sendPowerSignal(Server $server, string $signal): string
    {
        if (! in_array($signal, ['start', 'stop', 'restart', 'kill'], true)) {
            throw new RuntimeException('Señal de energía no válida.');
        }

        if (! $server->hasPterodactylIntegration()) {
            throw new RuntimeException('El servidor no tiene identificador de Pterodactyl.');
        }

        if (! $this->isConfigured()) {
            throw new RuntimeException('Faltan PTERODACTYL_URL o PTERODACTYL_CLIENT_API_KEY.');
        }

        $response = $this->client()
            ->post('/api/client/servers/' . $server->pterodactyl_identifier . '/power', [
                'signal' => $signal,
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Pterodactyl rechazó la acción de energía.');
        }

        return $signal;
    }

    public function panelLink(Server $server): ?string
    {
        if (! $server->hasPterodactylIntegration() || $this->panelUrl() === '') {
            return null;
        }

        return $this->panelUrl() . '/server/' . $server->pterodactyl_identifier;
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchDetails(Server $server): array
    {
        $response = $this->client()->get('/api/client/servers/' . $server->pterodactyl_identifier);

        if ($response->failed()) {
            throw new RuntimeException('No se pudo obtener el servidor desde Pterodactyl.');
        }

        $data = $response->json();
        if (! is_array($data)) {
            throw new RuntimeException('Respuesta inválida de Pterodactyl.');
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchResources(Server $server): array
    {
        $response = $this->client()->get('/api/client/servers/' . $server->pterodactyl_identifier . '/resources');

        if ($response->failed()) {
            throw new RuntimeException('No se pudo obtener el estado del servidor desde Pterodactyl.');
        }

        $data = $response->json();
        if (! is_array($data)) {
            throw new RuntimeException('Respuesta inválida de Pterodactyl.');
        }

        return $data;
    }

    private function extractCurrentState(array $payload): ?string
    {
        $state = Arr::get($payload, 'attributes.current_state');

        return is_string($state) && $state !== '' ? $state : null;
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl($this->panelUrl())
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('services.pterodactyl.timeout', 5))
            ->withToken($this->clientApiKey());
    }

    private function applicationClient(): PendingRequest
    {
        return Http::baseUrl($this->panelUrl())
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('services.pterodactyl.timeout', 5))
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->applicationApiKey(),
                'Accept' => 'Application/vnd.pterodactyl.v1+json',
            ]);
    }

    private function panelUrl(): string
    {
        return rtrim((string) config('services.pterodactyl.panel_url', ''), '/');
    }

    private function clientApiKey(): string
    {
        return trim((string) config('services.pterodactyl.client_api_key', ''));
    }

    private function applicationApiKey(): string
    {
        return trim((string) config('services.pterodactyl.application_api_key', ''));
    }
}
