<?php

namespace TitasGailius\Terminal;

class Terminal
{
    /**
     * Dynamically instantiate a new ProcessBuilder instance.
     *
     * @param  string $method
     * @param  array $parameters
     * @return ProcessBuilder
     */
    public static function __callStatic(string $method, array $parameters = [])
    {
        if (method_exists(Builder::class, $method)) {
            return (new Builder)->{$method}(...$parameters);
        }

        throw new BadMethodCallException(sprintf(
            'Call to undefined method %s::%s()', static::class, $method
        ));
    }
}