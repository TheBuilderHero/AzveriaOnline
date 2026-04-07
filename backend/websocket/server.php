<?php

declare(strict_types=1);

// Ratchet 0.4 uses dynamic properties internally, which triggers PHP 8.2+
// deprecation notices that can corrupt the WebSocket handshake if displayed.
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require __DIR__ . '/vendor/autoload.php';

use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServer;
use Ratchet\MessageComponentInterface;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;

final class AzveriaSocket implements MessageComponentInterface
{
    /** @var SplObjectStorage<ConnectionInterface, array{channel?: string, user_id?: int, role?: string}> */
    private SplObjectStorage $clients;

    /** @var array<string, SplObjectStorage<ConnectionInterface, null>> */
    private array $channels = [];

    public function __construct()
    {
        $this->clients = new SplObjectStorage();
    }

    public function onOpen(ConnectionInterface $conn): void
    {
        $this->clients[$conn] = [];
    }

    public function onMessage(ConnectionInterface $from, $msg): void
    {
        $payload = json_decode((string) $msg, true);
        if (!is_array($payload) || !isset($payload['type'])) {
            $from->send(json_encode(['type' => 'error', 'message' => 'Invalid payload']));
            return;
        }

        if ($payload['type'] === 'subscribe') {
            $channel = (string) ($payload['channel'] ?? '');
            $token = (string) ($payload['token'] ?? '');
            if ($channel === '') {
                $from->send(json_encode(['type' => 'error', 'message' => 'Missing channel']));
                return;
            }

            $claims = $this->decodeToken($token);
            if ($claims === null) {
                $from->send(json_encode(['type' => 'error', 'message' => 'Invalid auth token']));
                return;
            }

            if (!$this->canSubscribe($claims, $channel)) {
                $from->send(json_encode(['type' => 'error', 'message' => 'Not authorized for channel']));
                return;
            }

            if (!isset($this->channels[$channel])) {
                $this->channels[$channel] = new SplObjectStorage();
            }

            $this->channels[$channel]->attach($from);
            $meta = $this->clients[$from];
            $meta['channel'] = $channel;
            $meta['user_id'] = (int) ($claims['sub'] ?? 0);
            $meta['role'] = (string) ($claims['role'] ?? 'player');
            $this->clients[$from] = $meta;

            $from->send(json_encode(['type' => 'subscribed', 'channel' => $channel]));
            return;
        }

        if ($payload['type'] === 'message') {
            $channel = (string) ($payload['channel'] ?? '');
            $text = trim((string) ($payload['text'] ?? ''));
            if ($channel === '' || $text === '') {
                $from->send(json_encode(['type' => 'error', 'message' => 'Missing channel or text']));
                return;
            }

            if (!isset($this->clients[$from]['user_id'])) {
                $from->send(json_encode(['type' => 'error', 'message' => 'Authenticate before messaging']));
                return;
            }

            $event = [
                'type' => 'message',
                'channel' => $channel,
                'text' => $text,
                'user_id' => $this->clients[$from]['user_id'],
                'sent_at' => gmdate('c'),
            ];

            $this->broadcast($channel, $event);
            return;
        }

        $from->send(json_encode(['type' => 'error', 'message' => 'Unknown event type']));
    }

    public function onClose(ConnectionInterface $conn): void
    {
        if (isset($this->clients[$conn]['channel'])) {
            $channel = $this->clients[$conn]['channel'];
            if (isset($this->channels[$channel])) {
                $this->channels[$channel]->detach($conn);
            }
        }

        $this->clients->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e): void
    {
        $conn->close();
    }

    /** @param array<string, mixed> $event */
    private function broadcast(string $channel, array $event): void
    {
        if (!isset($this->channels[$channel])) {
            return;
        }

        $encoded = json_encode($event);
        if ($encoded === false) {
            return;
        }

        foreach ($this->channels[$channel] as $client) {
            $client->send($encoded);
        }
    }

    /** @return array<string, mixed>|null */
    private function decodeToken(string $token): ?array
    {
        if ($token === '') {
            return null;
        }

        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return null;
        }

        [$payload, $signature] = $parts;
        $secret = (string) (getenv('JWT_SHARED_SECRET') ?: 'change_me');
        $expected = $this->base64UrlEncode(hash_hmac('sha256', $payload, $secret, true));

        if (!hash_equals($expected, $signature)) {
            return null;
        }

        $json = $this->base64UrlDecode($payload);
        if ($json === null) {
            return null;
        }

        $claims = json_decode($json, true);
        if (!is_array($claims)) {
            return null;
        }

        $exp = (int) ($claims['exp'] ?? 0);
        if ($exp <= time()) {
            return null;
        }

        return $claims;
    }

    /** @param array<string, mixed> $claims */
    private function canSubscribe(array $claims, string $channel): bool
    {
        if ($channel === 'announcements.global') {
            return true;
        }

        if (strpos($channel, 'chat.') === 0) {
            $chatId = (int) substr($channel, 5);
            $allowed = $claims['chat_ids'] ?? [];
            if (!is_array($allowed)) {
                return false;
            }

            return in_array($chatId, array_map('intval', $allowed), true);
        }

        return false;
    }

    private function base64UrlEncode(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $encoded): ?string
    {
        $padding = strlen($encoded) % 4;
        if ($padding > 0) {
            $encoded .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode(strtr($encoded, '-_', '+/'), true);
        return $decoded === false ? null : $decoded;
    }
}

$server = IoServer::factory(
    new HttpServer(new WsServer(new AzveriaSocket())),
    8081
);

$server->run();
