<?php

namespace App\Support;

class WsToken
{
    public static function issue(array $claims, string $secret): string
    {
        $json = json_encode($claims, JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new \RuntimeException('Could not encode websocket claims');
        }

        $payload = self::base64UrlEncode($json);
        $signature = self::base64UrlEncode(hash_hmac('sha256', $payload, $secret, true));

        return $payload . '.' . $signature;
    }

    public static function decode(string $token, string $secret): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return null;
        }

        [$payload, $signature] = $parts;
        $expected = self::base64UrlEncode(hash_hmac('sha256', $payload, $secret, true));

        if (!hash_equals($expected, $signature)) {
            return null;
        }

        $json = self::base64UrlDecode($payload);
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

    private static function base64UrlEncode(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $encoded): ?string
    {
        $padding = strlen($encoded) % 4;
        if ($padding > 0) {
            $encoded .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode(strtr($encoded, '-_', '+/'), true);
        return $decoded === false ? null : $decoded;
    }
}
