<?php

declare(strict_types=1);

/**
 * Utilidad de contraseñas — PHPost Risus LTS
 *
 * Esquema moderno: password_hash() solo sobre la contraseña.
 * Esquema legacy:  md5(md5(pass) . strtolower(nick)).
 *
 * MD5 es irreversible: no se "desencripta". La migración es transparente:
 * verify() acepta ambos; si el hash era legacy y la clave es correcta,
 * se genera el hash moderno para guardarlo (UPDATE) en el login.
 */

if (!defined('TS_HEADER')) {
    exit('No se permite el acceso directo al script');
}

final class Password
{
    /** Costo bcrypt (no usar ARGON2I por defecto: hashes >72 rompen columnas viejas). */
    public const OPTIONS = ['cost' => 10];

    /** Crea un hash moderno solo con la contraseña. */
    public static function hash(string $password): string
    {
        $hash = password_hash($password, PASSWORD_DEFAULT, self::OPTIONS);
        if (!is_string($hash) || $hash === '') {
            throw new RuntimeException('No se pudo generar el hash de la contraseña.');
        }
        return $hash;
    }

    /** Hash legacy para comparar contra cuentas viejas. */
    public static function legacyHash(string $password, string $nick): string
    {
        return md5(md5($password) . strtolower($nick));
    }

    /** ¿Es un hash legacy md5(32 hex) y no uno moderno? */
    public static function isLegacy(string $hash): bool
    {
        return strlen($hash) === 32 && ctype_xdigit($hash);
    }

    /**
     * Verifica aceptando ambos esquemas.
     * Primero el moderno (timing-safe), luego el legacy.
     */
    public static function verify(string $password, string $nick, string $hash): bool
    {
        if ($hash !== '' && !self::isLegacy($hash) && password_verify($password, $hash)) {
            return true;
        }
        if (self::isLegacy($hash)) {
            return hash_equals($hash, self::legacyHash($password, $nick));
        }
        return false;
    }

    /**
     * Verifica y dice si hay que migrar.
     * @return array{ok: bool, migrate: bool} migrate=true solo si era legacy válido.
     */
    public static function verifyWithMigration(string $password, string $nick, string $hash): array
    {
        if ($hash !== '' && !self::isLegacy($hash)) {
            $ok = password_verify($password, $hash);
            return [
                'ok'      => $ok,
                'migrate' => $ok && password_needs_rehash($hash, PASSWORD_DEFAULT, self::OPTIONS),
            ];
        }
        $ok = self::isLegacy($hash) && hash_equals($hash, self::legacyHash($password, $nick));
        return ['ok' => $ok, 'migrate' => $ok];
    }
}
