<?php

declare(strict_types=1);

use Smarty\Compile\Tag\FunctionTag;

/**
 * @author PHPost Risus LTS
 * @copyright 2027
 */

if (!defined('TS_HEADER')) {
    exit('No se permite el acceso directo al script');
}

class Response {
    /**
     * Convierte el formato clásico de texto plano a una estructura de array.
     */
    private static function parse(string $responseText): array {
        if (strpos($responseText, ':') === false) {
            return [
                'status'  => 'error',
                'success' => false,
                'code'    => 500,
                'message' => 'Formato de respuesta interna inválido'
            ];
        }

        // Rompe la cadena solo en el primer ":"
        list($codigoRaw, $mensajeRaw) = explode(':', $responseText, 2);
        $codigo  = trim($codigoRaw);
        $message = trim($mensajeRaw);

        // Valores por defecto (Caso 0 o errores)
        $success = false;
        $status  = 'error';
        $code    = 400;

        if ($codigo === '1') {
            $success = true;
            $status  = 'ok';
            $code    = 200;
        } elseif ($codigo === '2') {
            $success = true; 
            $status  = 'info';
            $code    = 202;
        }

        return [
            'status'  => $status,
            'success' => $success,
            'code'    => $code,
            'message' => $message
        ];
    }

    /**
     * Convierte la cadena, envía las cabeceras HTTP correctas e imprime el JSON.
     */
    public static function json(string $responseText): void {
        $data = self::parse($responseText);
        // Define el código de estado HTTP dinámicamente (200, 400, 202, etc.)
        http_response_code($data['code']);
        header('Content-Type: application/json', true, $data['code']);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private static function only(int $code, string $status, string $message): void {
        header('Content-Type: application/json', true, $code);
        echo json_encode([
            'status'  => $status,
            'success' => ($status === 'ok'),
            'code'    => $code,
            'message' => $message
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function success(string $message): void {
        self::only(200, 'ok', $message);
    }

    public static function info(string $message): void {
        self::only(202, 'info', $message);
    }

    public static function error(string $message): void {
        self::only(400, 'error', $message);
    }
}
