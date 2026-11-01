<?php

declare(strict_types=1);

/**
 * @author PHPost Risus LTS
 * @copyright 2027
 */

class Logger {

    private string $logPath;

    public function __construct() {
        $this->logPath = defined('TS_STORAGE') ? TS_STORAGE . '/logs' : dirname(__DIR__, 3) . '/storage/logs';
    }

    /**
     * Helper específico para funciones obsoletas (Mantiene compatibilidad)
     */
    public function deprecated(string $oldFn, string $newFn): void {
        $message = sprintf("La función %s está obsoleta. Use el nuevo método %s en su lugar.", $oldFn, $newFn);
        $this->log('warning', $message, 'deprecations');
    }

    public function removed(string $fnName, string $version = 'próximas versiones'): void {
        $message = sprintf("La función %s está obsoleta y será ELIMINADA definitivamente en %s debido a que ya no se utiliza en el sistema.", $fnName, $version);
        $this->log('warning', $message, 'deprecations');
    }

    /**
     * Métodos directos y semánticos para uso general
     */
    public function info(string $message, string $category = 'system'): void {
        $this->log('info', $message, $category);
    }

    public function error(string $message, string $category = 'system'): void {
        $this->log('error', $message, $category);
    }

    public function critical(string $message, string $category = 'system'): void {
        $this->log('critical', $message, $category);
    }

    /**
     * Método base centralizado (Reemplaza al antiguo warning)
     */
    public function log(string $level, string $message, string $category = 'system'): void {
        global $config, $userLevel;
        if (($config['mostrar'] ?? 'visitantes') === 'ninguno') {
            return;
        }
        // Buscamos el archivo y línea exactos del código que originó el log
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5) ?? [];
        $file = 'Desconocido';
        $line = 0;
        foreach ($trace as $step) {
            if (isset($step['file']) && basename($step['file']) !== 'Logger.php') {
                $file = basename($step['file']);
                $line = $step['line'] ?? 0;
                // Si encontramos que la llamada proviene de un método de compatibilidad obsoleto,
                // hacemos un ciclo más para ver quién invocó a esa función obsoleta.
                if (isset($step['function']) && in_array($step['function'], ['deprecated', 'removed'])) {
                    continue;
                }
                break;
            }
        }
        #$file = isset($trace[1]['file']) ? basename($trace[1]['file']) : (isset($trace[0]['file']) ? basename($trace[0]['file']) : 'Desconocido');
        #$line = $trace[1]['line'] ?? ($trace[0]['line'] ?? 0);
        // Escribir siempre en su respectivo archivo .log
        $this->writeLog(strtoupper($level), $message, $category, $file, $line);
        // Mostrar solo si es desarrollador
        if ($userLevel === 'desarrollador') {
            $this->renderAlert($level, $message, $file, $line);
        }
    }

    /**
     * Escribe en el archivo específico por categoría y mes
     */
    private function writeLog(string $level, string $message, string $category, string $file, int $line): void {
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }

        $logFile = sprintf('%s/%s-%s.log', $this->logPath, strtolower($category), date('Y-m-d'));
        $timestamp = date('d/m/y - H:i');
        $logLine = sprintf("[%s] [%s] %s (Origen: %s en línea %d)\n", $timestamp, $level, $message, $file, $line);

        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    }

    /**
     * Renderiza el recuadro adaptando los colores según el nivel de error
     */
    private function renderAlert(string $level, string $message, string $file, int $line): void {
        $styles = match (strtolower($level)) {
            'error', 'critical' => ['bg' => '#f8d7da', 'text' => '#721c24', 'type' => 'Error Crítico'],
            'warning' => ['bg' => '#fff3cd', 'text' => '#856404', 'type' => 'Advertencia'],
            'info' => ['bg' => '#d1ecf1', 'text' => '#0c5460', 'type' => 'Info'],
            default => ['bg' => '#e2e3e5', 'text' => '#383d41', 'type' => 'Nota']
        };

        echo "
        <div style='background: {$styles['bg']}; color: {$styles['text']}; padding: 1.325rem;margin-bottom:.5rem;font-family: sans-serif; font-size: 15px; line-height:1.5;'>
            <strong>{$styles['type']}:</strong><br> " . htmlspecialchars($message) . "
            <span style='color: #6c757d;font-size:13px;display:block;'>Origen: En archivo <strong>{$file}</strong> línea <strong>{$line}</strong></span>
        </div>
        ";
    }

    /**
     * Captura los errores nativos de PHP (como E_USER_DEPRECATED)
     */
    public function handlePhpError(int $errno, string $errstr, string $errfile, int $errline): bool {
        global $config, $userLevel;

        // Si el error fue silenciado con @, respetamos el operador y no hacemos nada
        if (!(error_reporting() & $errno)) {
            return false;
        }

        // Filtrar si es un aviso de obsolescencia
        if ($errno === E_USER_DEPRECATED) {
            $file = basename($errfile);

            // 1. Escribir siempre en el archivo mensual
            $this->writeLog('DEPRECATED', $errstr, 'deprecations', $file, $errline);

            // 2. Mostrar alerta visual solo al desarrollador
            if ($userLevel === 'desarrollador') {
                $this->renderAlert('warning', $errstr, $file, $errline);
            }
            return true; // Indica a PHP que ya manejamos el error y no use el handler interno
        }

        return false; // Deja que otros errores sigan su curso normal
    }

}