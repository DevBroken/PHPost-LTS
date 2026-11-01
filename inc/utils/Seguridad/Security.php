<?php

declare(strict_types=1);

/**
 * Utilidad de seguridad — PHPost Risus LTS
 *
 * Reglas:
 * - Escapar para SQL SOLO vía db_exec('real_escape_string') (nunca addslashes).
 * - Escapar para HTML SOLO al imprimir (e()), nunca mutando $_POST/$_GET.
 * - Validaciones de nick/email centralizadas (mismas que installer y registro).
 */

if (!defined('TS_HEADER')) {
    exit('No se permite el acceso directo al script');
}

final class Security
{
    /** Escape para SQL usando la conexión real. */
    public static function esc(string $value): string
    {
        return (string) db_exec('real_escape_string', stripslashes($value));
    }

    /** Escape para HTML al imprimir. */
    public static function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** Nick válido: alfanumérico 4-16 (igual que registro). */
    public static function validNick(string $nick): bool
    {
        return (bool) preg_match('/^[a-zA-Z0-9_-]{4,16}$/', $nick);
    }

    /** Nick corto del installer/admin (mínimo 4). */
    public static function validAdminNick(string $nick): bool
    {
        return ctype_alnum($nick) && strlen($nick) >= 4;
    }

    public static function validEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /** Clave fuerte mínima del installer: 8 + mayúscula + número. */
    public static function validPassword(string $password): bool
    {
        return strlen($password) >= 8
            && preg_match('/[A-Z]/', $password) === 1
            && preg_match('/[0-9]/', $password) === 1;
    }

    /** Token CSRF: créalo en el form, verifícalo en el POST. */
    public static function csrfToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    public static function checkCsrf(?string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return is_string($token)
            && isset($_SESSION['csrf'])
            && hash_equals($_SESSION['csrf'], $token);
    }
}
