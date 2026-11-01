<?php

declare(strict_types=1);

/**
 * @author PHPost Risus LTS
 * @copyright 2027
 */

if (!defined('TS_HEADER')) {
    exit('No se permite el acceso directo al script');
}

class Container
{
    // Almacena instancias ya creadas (Singletons)
    private array $instances = [];
    
    // Almacena el "contexto" (parámetros manuales por nombre de variable o clase)
    private array $context = [];

    // Método para registrar contexto manual (ej: 'apiToken' => 'secret_123')
    public function setContext(string $key, mixed $value): void 
    {
        $this->context[$key] = $value;
    }

    public function get(string $className): object 
    {
        if (isset($this->instances[$className])) {
            return $this->instances[$className];
        }

        $reflector = new ReflectionClass($className);

        $constructor = $reflector->getConstructor();
        if (null === $constructor) {
            return $this->instances[$className] = new $className();
        }

        $parameters = $constructor->getParameters();
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $paramName = $parameter->getName();
            $paramType = $parameter->getType();

            if (array_key_exists($paramName, $this->context)) {
                $dependencies[] = $this->context[$paramName];
                continue;
            }

            if ($paramType && !$paramType->isBuiltin() && array_key_exists($paramType->getName(), $this->context)) {
                $dependencies[] = $this->context[$paramType->getName()];
                continue;
            }

            if ($paramType && !$paramType->isBuiltin()) {
                $dependencies[] = $this->get($paramType->getName());
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
                continue;
            }

            throw new Exception("No se pudo resolver el parámetro '\${$paramName}' para la clase {$className}");
        }

        // Instanciar la clase con las dependencias resueltas
        return $this->instances[$className] = $reflector->newInstanceArgs($dependencies);
    }
}
