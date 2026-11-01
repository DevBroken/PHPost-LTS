<?php

declare(strict_types=1);

/**
 * Utilidad HTTP/IP — PHPost Risus LTS
 *
 * Las columnas *_ip son VARBINARY(16): se guardan con ipToBin()
 * (inet_pton, apto IPv4 e IPv6) y se leen con binToIp().
 * Guardar el string con puntos trunca IPv6 (16 bytes no alcanzan).
 */

if (!defined('TS_HEADER')) {
    exit('No se permite el acceso directo al script');
}

if (!class_exists('IP')) {
    require_once __DIR__ . '/../seguridad/ip.php';
}

final class Http
{
    /**
     * IP del cliente.
     * OJO: confía en cabeceras de proxy. Úsalo solo si estás detrás de un
     * proxy/CDN de confianza; si no, usa (new IP())->getIPHuman().
     */
    public static function clientIp(): string
    {
        $candidates = [
            $_SERVER['HTTP_CLIENT_IP'] ?? '',
            $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '',
            $_SERVER['REMOTE_ADDR'] ?? '',
        ];
        foreach ($candidates as $raw) {
            $ip = trim(explode(',', (string) $raw)[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
        return '0.0.0.0';
    }

    /** IP string -> binario de 16 bytes (delega en IP, la canónica). */
    public static function ipToBin(?string $ip): ?string
    {
        if ($ip === null || $ip === '') {
            return null;
        }
        return (new IP())->ipToBinary($ip);
    }

    /** Binario -> string legible (delega en IP, la canónica). */
    public static function binToIp(?string $bin): string
    {
        if ($bin === null || $bin === '') {
            return '';
        }
        return (new IP())->binaryToIp($bin) ?? '';
    }

    public static function isSecure(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? 0) == 443)
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    }

    public static function isLocal(): bool
    {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        return in_array($host, ['localhost', '127.0.0.1', '::1'], true)
            || str_ends_with($host, '.local')
            || str_ends_with($host, '.test');
    }

    public static function scheme(): string
    {
        return self::isSecure() ? 'https' : 'http';
    }

    public static function currentUrl(): string
    {
        $scheme = self::scheme();
        return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/');
    }

    public static function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}
