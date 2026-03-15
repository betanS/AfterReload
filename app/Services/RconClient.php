<?php

namespace App\Services;

class RconClient
{
    /**
     * @param  string  $host
     * @param  int  $port
     * @param  string  $password
     * @param  string  $command
     * @param  float  $timeoutSeconds
     * @return string|null
     */
    public function send(string $host, int $port, string $password, string $command, float $timeoutSeconds = 1.5): ?string
    {
        if ($host === '' || $port <= 0 || $password === '') {
            return null;
        }

        $socket = @fsockopen($host, $port, $errno, $errstr, $timeoutSeconds);
        if (! $socket) {
            return null;
        }

        stream_set_timeout($socket, (int) ceil($timeoutSeconds));

        $authId = 1;
        $this->writePacket($socket, $authId, 3, $password);

        // Auth response usually comes in two packets; read a couple safely.
        $authOk = false;
        for ($i = 0; $i < 2; $i++) {
            $packet = $this->readPacket($socket);
            if ($packet === null) {
                break;
            }

            if ($packet['id'] === $authId) {
                $authOk = true;
                break;
            }
        }

        if (! $authOk) {
            fclose($socket);
            return null;
        }

        $cmdId = 2;
        $this->writePacket($socket, $cmdId, 2, $command);

        $response = '';
        // Read until timeout or another packet with same id
        while (true) {
            $packet = $this->readPacket($socket);
            if ($packet === null) {
                break;
            }

            if ($packet['id'] === $cmdId) {
                $response .= $packet['body'];
            }
        }

        fclose($socket);

        return $response !== '' ? $response : null;
    }

    /**
     * @param  resource  $socket
     */
    private function writePacket($socket, int $id, int $type, string $body): void
    {
        $payload = pack('V', $id)
            . pack('V', $type)
            . $body
            . "\x00\x00";

        $packet = pack('V', strlen($payload)) . $payload;

        fwrite($socket, $packet);
    }

    /**
     * @param  resource  $socket
     * @return array{id:int, type:int, body:string}|null
     */
    private function readPacket($socket): ?array
    {
        $sizeData = fread($socket, 4);
        if ($sizeData === false || strlen($sizeData) < 4) {
            return null;
        }

        $size = unpack('Vsize', $sizeData)['size'] ?? 0;
        if ($size <= 0) {
            return null;
        }

        $packetData = '';
        while (strlen($packetData) < $size) {
            $chunk = fread($socket, $size - strlen($packetData));
            if ($chunk === false || $chunk === '') {
                break;
            }
            $packetData .= $chunk;
        }

        if (strlen($packetData) < 8) {
            return null;
        }

        $id = unpack('Vid', substr($packetData, 0, 4))['id'] ?? -1;
        $type = unpack('Vtype', substr($packetData, 4, 4))['type'] ?? 0;
        $body = substr($packetData, 8);
        $body = rtrim($body, "\x00");

        return [
            'id' => (int) $id,
            'type' => (int) $type,
            'body' => $body,
        ];
    }
}