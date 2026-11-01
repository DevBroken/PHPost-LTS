<?php

declare(strict_types=1);

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
            $status  = 'warning';
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
        
        // Cabecera para JSON
        header('Content-Type: application/json; charset=utf-8');
        
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit; // Detiene la ejecución aquí, ideal para respuestas AJAX
    }
}
