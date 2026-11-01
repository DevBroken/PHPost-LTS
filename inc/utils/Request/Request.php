<?php

declare(strict_types=1);

/**
 * Petición HTTP — PHPost Risus LTS (reescrito)
 *
 * Cambios vs el legacy (inc/extras/QueryString.php):
 * - Sin magic_quotes (eliminado desde PHP 5.4; el código viejo aún lo chequeaba).
 * - NO muta $_GET/$_POST con htmlspecialchars: eso corrompía contraseñas
 *   ("a&b" llegaba como "a&amp;b") y rompía firmas binarias. Escapar se hace
 *   al usar el dato (Security::esc para SQL, Security::e al imprimir).
 * - Sin notices: todo acceso a $_SERVER con ??.
 * - El chequeo de referer solo aplica a páginas sensibles y tolera ausencia
 *   de referer (apps, fetch same-origin, CLI) en vez de morir siempre.
 */

if (!defined('TS_HEADER')) {
    exit('No se permite el acceso directo al script');
}

if (!function_exists('cleanRequest')) {
    function cleanRequest(): void
    {
        global $tsPage;

        // Estas claves nunca deben existir: permiten contaminar $GLOBALS.
        if (isset($_REQUEST['GLOBALS']) || isset($_COOKIE['GLOBALS'])) {
            http_response_code(400);
            exit('Invalid request variable.');
        }

        // Claves numéricas en GET/POST/FILES: signo clásico de inyección.
        foreach (array_merge(array_keys($_POST), array_keys($_GET), array_keys($_FILES)) as $key) {
            if (is_numeric($key)) {
                http_response_code(400);
                exit('Numeric request keys are invalid.');
            }
        }
        foreach ($_COOKIE as $key => $value) {
            if (is_numeric($key)) {
                unset($_COOKIE[$key]);
            }
        }

        // Smuggling: una URL completa como query string.
        $qs = $_SERVER['QUERY_STRING'] ?? getenv('QUERY_STRING') ?: '';
        if (str_starts_with($qs, 'http')) {
            http_response_code(400);
            exit;
        }

        // Reconstruye $_REQUEST sin $_COOKIE (sesiones van por cookie aparte).
        $_REQUEST = $_POST + $_GET;

        // Solo páginas sensibles exigen mismo origen, y solo si hay referer.
        $sensitive = ['admin', 'moderacion', 'cuenta'];
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if ($referer !== '' && in_array($tsPage ?? '', $sensitive, true) && !Request::isSameOrigin($referer)) {
            http_response_code(403);
            exit('Invalid request origin.');
        }
    }
}

if (!class_exists('Request')) {
    final class Request
    {
        /** ¿El referer pertenece a este mismo host? */
        public static function isSameOrigin(string $referer): bool
        {
            $refHost = strtolower(parse_url($referer, PHP_URL_HOST) ?? '');
            $host = strtolower($_SERVER['HTTP_HOST'] ?? '');
            $host = (string) preg_replace('/:\d+$/', '', $host);
            return $refHost !== '' && hash_equals($host, $refHost);
        }

        /** GET entero, sin excepciones. */
        public static function get(string $key, string $default = ''): string
        {
            $v = $_GET[$key] ?? $default;
            return is_string($v) ? $v : $default;
        }

        /** POST entero, sin excepciones. */
        public static function post(string $key, string $default = ''): string
        {
            $v = $_POST[$key] ?? $default;
            return is_string($v) ? $v : $default;
        }

        /** Entero de la petición (paginación, ids). */
        public static function int(string $key, int $default = 0): int
        {
            return (int) ($_REQUEST[$key] ?? $default);
        }
    }
}

// Alias legacy: el código viejo usa QueryString::...
if (!class_exists('QueryString')) {
    class_alias('Request', 'QueryString');
}
