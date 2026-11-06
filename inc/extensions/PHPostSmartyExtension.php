<?php

use Smarty\Extension\Base;
use Smarty\Compile\CompilerInterface;

class PHPostSmartyExtension extends Base 
{
    private string $pluginsDir;

    public function __construct(string $pluginsDir)
    {
        $this->pluginsDir = rtrim($pluginsDir, '/') . '/';
    }

    /**
     * 1. Resuelve dinámicamente los modificadores: {$variable|hace}
     */
    public function getModifierCallback(string $modifierName): ?callable
    {
        $functionName = 'smarty_modifier_' . $modifierName;

        if (function_exists($functionName)) {
            return $functionName;
        }

        $pluginFile = $this->pluginsDir . 'modifier.' . $modifierName . '.php';

        if (file_exists($pluginFile)) {
            require_once $pluginFile;
            if (function_exists($functionName)) {
                return $functionName;
            }
        }

        return null;
    }

    /**
     * 2. Resuelve dinámicamente las funciones / custom tags: {mi_funcion}
     */
    public function getTags(): array
    {
        return [
            // El asterisco '*' captura CUALQUIER etiqueta desconocida (como 'lts')
            '*' => function(string $tagName, array $args, CompilerInterface $compiler) {

                // Intentamos cargar el archivo de la función si existe
                $pluginFile = $this->pluginsDir . 'function.' . $tagName . '.php';
                $functionName = 'smarty_function_' . $tagName;

                if (file_exists($pluginFile)) {
                    require_once $pluginFile;

                    if (function_exists($functionName)) {
                        // Registramos el callback sobre la marcha para que el compilador lo reconozca
                        $compiler->getSmarty()->registerPlugin(\Smarty\Smarty::PLUGIN_FUNCTION, $tagName, $functionName);

                        // Le ordenamos al compilador volver a intentar procesar la etiqueta, ahora que ya la conoce
                        return $compiler->compileTag($tagName, $args);
                    }
                }

                return null; // Si el archivo no existe, tira el "unknown tag" normal
            }
        ];
    }
}
