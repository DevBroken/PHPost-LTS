<?php

declare(strict_types=1);

/**
 * IP del cliente — PHPost Risus LTS
 * Portado de PHPost-subversion-v3 (src/Utils/Security/IP.php), sin cambios
 * funcionales. Solo confía en X-Forwarded-For si el proxy es confiable;
 * si no, usa REMOTE_ADDR (evita spoofing).
 *
 * Las columnas *_ip son VARBINARY(16): guarda con ipToBinary()
 * (inet_pton, vale IPv4 e IPv6) y lee con binaryToIp().
 */

if (!defined('TS_HEADER')) {
    exit('No se permite el acceso directo al script');
}

final class IP
{
    /**
     * Proxies confiables (si existen).
     * Ej: ['127.0.0.1', '10.0.0.1']
     */
    private array $trustedProxies = [];

    public function __construct(array $trustedProxies = [])
    {
        $this->trustedProxies = $trustedProxies;
    }

    private function isValidIP(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6) !== false;
    }

    /**
     * Convierte una IP a binario para BD (columna VARBINARY(16)).
     */
    public function ipToBinary(string $ip): ?string
    {
        if (!$this->isValidIP($ip)) {
            return null;
        }
        $binary = inet_pton($ip);
        return $binary === false ? null : $binary;
    }

    /**
     * Convierte IP binaria a legible.
     */
    public function binaryToIp(?string $binary): ?string
    {
        if ($binary === null || $binary === '') {
            return null;
        }
        $ip = inet_ntop($binary);
        return $ip === false ? null : $ip;
    }

    /**
     * IP real en binario (ideal para guardar directo en BD).
     */
    public function getIPBinary(): ?string
    {
        $ip = $this->executeIP();
        if ($ip === 'unknown') {
            return null;
        }
        return $this->ipToBinary($ip);
    }

    /**
     * IP real legible (método principal).
     */
    public function getIPHuman(): string
    {
        return $this->executeIP();
    }

    /**
     * Compatibilidad legacy.
     */
    public function getIP(): string
    {
        return $this->executeIP();
    }

    /**
     * IP real del cliente de forma segura.
     */
    public function executeIP(): string
    {
        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
        if (!$this->isValidIP($remoteAddr)) {
            return 'unknown';
        }
        // Si NO estamos detrás de un proxy confiable → REMOTE_ADDR
        if (!$this->isTrustedProxy($remoteAddr)) {
            return $remoteAddr;
        }
        // Proxy confiable → intentamos X-Forwarded-For
        $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
        if ($forwarded) {
            foreach (explode(',', $forwarded) as $ip) {
                $ip = trim($ip);
                if ($this->isValidIP($ip)) {
                    return $ip;
                }
            }
        }
        return $remoteAddr;
    }

    private function isTrustedProxy(string $ip): bool
    {
        return in_array($ip, $this->trustedProxies, true);
    }
}
