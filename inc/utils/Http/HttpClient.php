<?php

declare(strict_types=1);

/**
 * @author PHPost Risus LTS
 * @copyright 2027
 */

if (!defined('TS_HEADER')) {
    exit('No se permite el acceso directo al script');
}

class HttpClient {

    /**
     * Obtiene el contenido de una URL remota mediante una petición GET
     *
     * @param string $url Dirección web a consultar
     * @param int $timeout Tiempo límite de espera en segundos
     * @return string Contenido de la respuesta
     */
    public static function get(string $url, int $timeout = 60): string {
        return self::request('GET', $url, [], $timeout);
    }

    /**
     * Realiza una petición POST HTTP segura
     *
     * @param string $url Dirección web a consultar
     * @param array $data Parámetros a enviar en el cuerpo de la petición
     * @param int $timeout Tiempo límite de espera en segundos
     * @return string Contenido de la respuesta
     */
    public static function post(string $url, array $data = [], int $timeout = 60): string {
        return self::request('POST', $url, $data, $timeout);
    }

    /**
     * Motor interno unificado para la gestión de transferencias HTTP (DRY)
     */
    private static function request(string $method, string $url, array $data = [], int $timeout = 60): string {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new InvalidArgumentException("La URL provista no es válida: {$url}");
        }

        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'PHPost-HttpClient/2.0';
        $payload   = !empty($data) ? http_build_query($data) : '';

        // Opción primaria: cURL
        if (function_exists('curl_init')) {
            $ch = curl_init();
            $options = [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_USERAGENT      => $userAgent,
                CURLOPT_TIMEOUT        => $timeout,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 5,
                CURLOPT_SSL_VERIFYPEER => true,
            ];
            if ($method === 'POST') {
                $options[CURLOPT_POST] = true;
                $options[CURLOPT_POSTFIELDS] = $payload;
            }
            curl_setopt_array($ch, $options);
            $result   = curl_exec($ch);
            $error    = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($result === false) {
                throw new RuntimeException("Error en cURL ({$method}): {$error}");
            }
            if ($httpCode >= 400) {
                throw new RuntimeException("El servidor remoto respondió con un código de error HTTP: {$httpCode}");
            }
            return $result;
        }
        // Opción secundaria: Stream Wrappers (file_get_contents)
        if (ini_get('allow_url_fopen')) {
            $httpHeaders = "User-Agent: {$userAgent}\r\n";
            $httpOptions = [
                'method'          => $method,
                'timeout'         => $timeout,
                'follow_location' => 1,
                'max_redirects'   => 5,
                'ignore_errors'   => true
            ];
            if ($method === 'POST') {
                $httpHeaders .= "Content-Type: application/x-www-form-urlencoded\r\n" .
                                "Content-Length: " . strlen($payload) . "\r\n";
                $httpOptions['content'] = $payload;
            }
            $httpOptions['header'] = $httpHeaders;
            $context = stream_context_create([
                'http' => $httpOptions,
                'ssl'  => [
                    'verify_peer'      => true,
                    'verify_peer_name' => true,
                ]
            ]);
            $result = @file_get_contents($url, false, $context);
            if ($result === false) {
                throw new RuntimeException("Error en file_get_contents ({$method}) al conectar con la URL.");
            }
            // Uso de http_get_last_response_headers() sin romper versiones anteriores
            $headers = function_exists('http_get_last_response_headers') ?
            http_get_last_response_headers() :
            ($http_response_header ?? null);

            if (!empty($headers) && isset($headers[0]) && preg_match('{HTTP/\d\.\d\s+(\d+)}i', $headers[0], $matches)) {
                $httpCode = (int)$matches[1];
                if ($httpCode >= 400) {
                    throw new RuntimeException("El servidor remoto respondió con un código de error HTTP: {$httpCode}");
                }
            }
            return $result;
        }
        throw new RuntimeException("No hay entornos de transferencia HTTP disponibles (cURL o allow_url_fopen deshabilitados).");
    }
}
