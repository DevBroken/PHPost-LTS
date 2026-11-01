<?php

declare(strict_types=1);

/**
 * reCAPTCHA clásico (public key + secret key) — PHPost Risus LTS
 *
 * Solo Google reCAPTCHA v2/v3: envía el token al siteverify y listo.
 * Sin enterprise, sin hCaptcha.
 *
 * @example
 * $rc = ReCaptcha::fromCore($tsCore); // pkey/skey de w_configuracion
 * if ($rc->verify($_POST['response'] ?? '') !== true) {
 *     die('Validación incorrecta');
 * }
 * // en el form: <?= $rc->widgetHtml() ?>
 */

if (!defined('TS_HEADER')) {
    exit('No se permite el acceso directo al script');
}

final class ReCaptcha
{
    private const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    private string $secretKey;
    private string $siteKey;
    private string $userIp;

    public function __construct(string $secretKey, string $siteKey = '')
    {
        $this->secretKey = $secretKey;
        $this->siteKey = $siteKey;
        $this->userIp = $this->detectIp();
    }

    /** Atajo con las claves de este proyecto (w_configuracion pkey/skey). */
    public static function fromCore(tsCore $core): self
    {
        return new self(
            (string) ($core->settings['skey'] ?? ''),
            (string) ($core->settings['pkey'] ?? '')
        );
    }

    private function detectIp(): string
    {
        if (class_exists('IP')) {
            return (new IP())->getIP();
        }
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'unknown';
    }

    /**
     * Verifica el token contra Google. Devuelve true o mensaje de error.
     */
    public function verify(?string $token): bool|string
    {
        if ($token === null || $token === '') {
            return 'No hemos podido validar tu humanidad';
        }
        if ($this->secretKey === '') {
            return 'reCAPTCHA no configurado (falta secret key)';
        }

        $fields = http_build_query([
            'secret'   => $this->secretKey,
            'response' => $token,
            'remoteip' => $this->userIp,
        ]);

        $raw = $this->post($fields);
        if ($raw === null) {
            return 'No se pudo contactar con Google, intenta de nuevo';
        }

        $response = json_decode($raw, true);
        return !empty($response['success']) ? true : 'Validación incorrecta';
    }

    /**
     * HTML del checkbox v2 (requiere scriptTag() una vez por página).
     */
    public function widgetHtml(string $theme = 'light'): string
    {
        $key = htmlspecialchars($this->siteKey, ENT_QUOTES, 'UTF-8');
        $theme = $theme === 'dark' ? 'dark' : 'light';
        return "<div class=\"g-recaptcha\" data-sitekey=\"{$key}\" data-theme=\"{$theme}\"></div>";
    }

    public static function scriptTag(string $lang = 'es'): string
    {
        $lang = preg_replace('/[^a-z-]/i', '', $lang) ?: 'es';
        return "<script src=\"https://www.google.com/recaptcha/api.js?hl={$lang}\" async defer></script>";
    }

    private function post(string $fields): ?string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init(self::VERIFY_URL);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $fields,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
            ]);
            $out = curl_exec($ch);
            curl_close($ch);
            return is_string($out) ? $out : null;
        }

        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => $fields,
                'timeout' => 10,
            ],
        ]);
        $out = @file_get_contents(self::VERIFY_URL, false, $ctx);
        return is_string($out) ? $out : null;
    }
}
