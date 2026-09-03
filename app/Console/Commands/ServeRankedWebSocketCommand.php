<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\RankedRealtimeConnectionState;
use App\Support\RankedRealtimeEventStreamPublisher;
use App\Support\RankedWebSocketTicketService;
use Illuminate\Console\Command;

class ServeRankedWebSocketCommand extends Command
{
    protected $signature = 'ranked:websocket-serve
        {--host= : Host do nasluchiwania}
        {--port= : Port do nasluchiwania}
        {--path= : Sciezka WebSocket}
        {--publish-interval-ms= : Interwal publikacji eventow live}';

    protected $description = 'Serve the ranked 1v1 realtime feed over a plain WebSocket transport.';

    /**
     * @var array<int, array{
     *     stream: resource,
     *     id: int,
     *     read_buffer: string,
     *     handshake_complete: bool,
     *     user: User|null,
     *     state: RankedRealtimeConnectionState,
     *     next_publish_at: float
     * }>
     */
    protected array $clients = [];

    public function handle(
        RankedWebSocketTicketService $ticketService,
        RankedRealtimeEventStreamPublisher $publisher,
    ): int {
        $host = (string) ($this->option('host') ?: config('ranked.websocket_host', '0.0.0.0'));
        $port = (int) ($this->option('port') ?: config('ranked.websocket_port', 8080));
        $path = $this->normalizePath((string) ($this->option('path') ?: config('ranked.websocket_path', '/ranked')));
        $publishIntervalMs = max(
            100,
            (int) ($this->option('publish-interval-ms') ?: config('ranked.websocket_publish_interval_ms', 1000)),
        );

        $server = @stream_socket_server(
            sprintf('tcp://%s:%d', $host, $port),
            $errorCode,
            $errorMessage,
        );

        if (! is_resource($server)) {
            $this->error(sprintf('Nie udalo sie uruchomic ranked WebSocket servera: [%d] %s', $errorCode, $errorMessage));

            return self::FAILURE;
        }

        stream_set_blocking($server, false);

        $this->info(sprintf(
            'Ranked WebSocket server listening on ws://%s:%d%s',
            $host,
            $port,
            $path,
        ));

        while (true) {
            $readStreams = [$server];

            foreach ($this->clients as $clientState) {
                $readStreams[] = $clientState['stream'];
            }

            $writeStreams = null;
            $exceptStreams = null;
            @stream_select($readStreams, $writeStreams, $exceptStreams, 0, 200000);

            foreach ($readStreams as $stream) {
                if ($stream === $server) {
                    $this->acceptClient($server);

                    continue;
                }

                $this->readClient($stream, $ticketService, $publisher, $path, $publishIntervalMs);
            }

            $this->publishDueMessages($publisher, $publishIntervalMs);
        }
    }

    /**
     * @param  resource  $server
     */
    protected function acceptClient($server): void
    {
        $client = @stream_socket_accept($server, 0);

        if (! is_resource($client)) {
            return;
        }

        stream_set_blocking($client, false);

        $this->clients[get_resource_id($client)] = [
            'stream' => $client,
            'id' => get_resource_id($client),
            'read_buffer' => '',
            'handshake_complete' => false,
            'user' => null,
            'state' => new RankedRealtimeConnectionState,
            'next_publish_at' => microtime(true),
        ];
    }

    /**
     * @param  resource  $stream
     */
    protected function readClient(
        $stream,
        RankedWebSocketTicketService $ticketService,
        RankedRealtimeEventStreamPublisher $publisher,
        string $path,
        int $publishIntervalMs,
    ): void {
        $clientId = get_resource_id($stream);
        $clientState = $this->clients[$clientId] ?? null;

        if (! $clientState) {
            return;
        }

        $chunk = @fread($stream, 8192);

        if (($chunk === false || $chunk === '') && feof($stream)) {
            $this->disconnectClient($clientId);

            return;
        }

        if (! is_string($chunk) || $chunk === '') {
            return;
        }

        $clientState['read_buffer'] .= $chunk;

        if ($clientState['handshake_complete']) {
            $this->clients[$clientId] = $clientState;

            return;
        }

        if (! str_contains($clientState['read_buffer'], "\r\n\r\n")) {
            $this->clients[$clientId] = $clientState;

            return;
        }

        $headers = $this->parseHandshakeHeaders($clientState['read_buffer']);

        if (
            ($headers['method'] ?? null) !== 'GET'
            || ! isset($headers['sec-websocket-key'])
            || ($headers['path'] ?? null) !== $path
        ) {
            $this->disconnectClient($clientId);

            return;
        }

        parse_str((string) ($headers['query'] ?? ''), $query);
        $user = $ticketService->resolveUserForTicket(is_string($query['ticket'] ?? null) ? $query['ticket'] : null);

        if (! $user) {
            $this->disconnectClient($clientId);

            return;
        }

        $this->writeHandshakeResponse($stream, $headers['sec-websocket-key']);

        $clientState['handshake_complete'] = true;
        $clientState['user'] = $user;
        $clientState['read_buffer'] = '';
        $clientState['next_publish_at'] = microtime(true) + ($publishIntervalMs / 1000);
        $this->clients[$clientId] = $clientState;

        $this->sendEventPayload(
            $stream,
            'stream.ready',
            $publisher->streamReadyPayload(),
            $publisher,
        );

        foreach ($publisher->publish($user, $clientState['state']) as $message) {
            $this->sendEventPayload(
                $stream,
                $message['event'],
                $message['payload'],
                $publisher,
            );
        }

        $this->clients[$clientId] = $clientState;
    }

    protected function publishDueMessages(
        RankedRealtimeEventStreamPublisher $publisher,
        int $publishIntervalMs,
    ): void {
        $now = microtime(true);

        foreach (array_keys($this->clients) as $clientId) {
            $clientState = $this->clients[$clientId] ?? null;

            if (
                ! $clientState
                || ! $clientState['handshake_complete']
                || ! $clientState['user']
                || $clientState['next_publish_at'] > $now
            ) {
                continue;
            }

            foreach ($publisher->publish($clientState['user'], $clientState['state']) as $message) {
                $success = $this->sendEventPayload(
                    $clientState['stream'],
                    $message['event'],
                    $message['payload'],
                    $publisher,
                );

                if (! $success) {
                    $this->disconnectClient($clientId);

                    continue 2;
                }
            }

            $clientState['next_publish_at'] = $now + ($publishIntervalMs / 1000);
            $this->clients[$clientId] = $clientState;
        }
    }

    /**
     * @param  resource  $stream
     * @param  array<string, mixed>  $payload
     */
    protected function sendEventPayload(
        $stream,
        string $eventName,
        array $payload,
        RankedRealtimeEventStreamPublisher $publisher,
    ): bool {
        $message = json_encode(
            $publisher->websocketPayload($eventName, $payload),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );

        if (! is_string($message)) {
            return false;
        }

        return $this->writeTextFrame($stream, $message);
    }

    /**
     * @param  resource  $stream
     */
    protected function writeTextFrame($stream, string $payload): bool
    {
        $length = strlen($payload);
        $header = chr(0x81);

        if ($length <= 125) {
            $header .= chr($length);
        } elseif ($length <= 65535) {
            $header .= chr(126).pack('n', $length);
        } else {
            $header .= chr(127).pack('NN', 0, $length);
        }

        $frame = $header.$payload;
        $written = 0;
        $frameLength = strlen($frame);

        while ($written < $frameLength) {
            $result = @fwrite($stream, substr($frame, $written));

            if (! is_int($result) || $result <= 0) {
                return false;
            }

            $written += $result;
        }

        return true;
    }

    /**
     * @param  resource  $stream
     */
    protected function writeHandshakeResponse($stream, string $key): void
    {
        $accept = base64_encode(sha1(trim($key).'258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));

        $response = implode("\r\n", [
            'HTTP/1.1 101 Switching Protocols',
            'Upgrade: websocket',
            'Connection: Upgrade',
            'Sec-WebSocket-Accept: '.$accept,
            '',
            '',
        ]);

        @fwrite($stream, $response);
    }

    /**
     * @return array<string, string>
     */
    protected function parseHandshakeHeaders(string $rawRequest): array
    {
        [$headerBlock] = explode("\r\n\r\n", $rawRequest, 2);
        $lines = preg_split("/\r\n/", $headerBlock) ?: [];
        $requestLine = array_shift($lines);
        $headers = [];

        if (is_string($requestLine)) {
            [$method, $target] = array_pad(explode(' ', $requestLine, 3), 2, null);
            $headers['method'] = strtoupper((string) $method);

            $parsedTarget = parse_url((string) $target);
            $headers['path'] = $this->normalizePath((string) ($parsedTarget['path'] ?? '/'));
            $headers['query'] = (string) ($parsedTarget['query'] ?? '');
        }

        foreach ($lines as $line) {
            if (! is_string($line) || ! str_contains($line, ':')) {
                continue;
            }

            [$name, $value] = explode(':', $line, 2);
            $headers[strtolower(trim($name))] = trim($value);
        }

        return $headers;
    }

    protected function normalizePath(string $path): string
    {
        $trimmed = trim($path);

        if ($trimmed === '' || $trimmed === '/') {
            return '/';
        }

        return '/'.trim($trimmed, '/');
    }

    protected function disconnectClient(int $clientId): void
    {
        $clientState = $this->clients[$clientId] ?? null;

        if (! $clientState) {
            return;
        }

        if (is_resource($clientState['stream'])) {
            @fclose($clientState['stream']);
        }

        unset($this->clients[$clientId]);
    }
}
