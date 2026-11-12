<?php

declare(strict_types=1);

namespace DevBroken\Utils\Http;

use DevBroken\Utils\Seguridad\IP;

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

final class Http
{

    private static ?IP $ipInstance = null;

    private static function extractIP(): IP
    {
        if (self::$ipInstance === null) {
            self::$ipInstance = new IP();
        }
        return self::$ipInstance;
    }

    /**
     * IP del cliente.
     * OJO: confía en cabeceras de proxy. Úsalo solo si estás detrás de un
     * proxy/CDN de confianza; si no, usa self::extractIP()->getIPHuman().
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
        return self::extractIP()->ipToBinary($ip);
    }

    /** Binario -> string legible (delega en IP, la canónica). */
    public static function binToIp(?string $bin): string
    {
        if ($bin === null || $bin === '') {
            return '';
        }
        return self::extractIP()->binaryToIp($bin) ?? '';
    }

    public static function isSecure(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? 0) == 443)
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    }

    public static function isLocal(): bool
    {
        /*$host = $_SERVER['HTTP_HOST'] ?? '';*/
        $host = strtolower(explode(':', $_SERVER['HTTP_HOST'] ?? '')[0]);
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

    public static function redirect(string $url, int $statusCode = 302): never
    {
        $clean = str_replace(["\r", "\n"], '', $url);
        header('Location: ' . $clean, true, $statusCode);
        exit;
    }
}
