<?php

declare(strict_types=1);

namespace App\Console;

/**
 * Clase base simple para comandos de consola.
 *
 * Cada comando definirá:
 *  - getName(): nombre del comando ("make:controller")
 *  - getDescription(): descripción para el "list"
 *  - handle(): lógica principal
 */
abstract class Command
{
    /**
     * Nombre del comando, ej: "make:controller"
     */
    abstract public function getName(): string;

    /**
     * Descripción corta del comando.
     */
    abstract public function getDescription(): string;

    /**
     * Lógica principal del comando.
     *
     * @param array<int,string> $arguments Argumentos de CLI (sin el nombre de comando)
     */
    abstract public function handle(array $arguments): int;

    // Helpers de salida
    protected function line(string $message): void
    {
        echo $message . PHP_EOL;
    }

    protected function info(string $message): void
    {
        echo "\033[32m" . $message . "\033[0m" . PHP_EOL; // verde
    }

    protected function error(string $message): void
    {
        echo "\033[31m" . $message . "\033[0m" . PHP_EOL; // rojo
    }
}
