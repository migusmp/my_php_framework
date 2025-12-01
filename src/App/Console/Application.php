<?php

declare(strict_types=1);

namespace App\Console;

/**
 * Punto de entrada de la consola (tipo "Kernel" de artisan).
 *
 * - Registra comandos disponibles.
 * - Resuelve el comando pedido en $argv[1].
 * - Pasa los argumentos al comando.
 */
class Application
{
    /**
     * @var array<string, Command>
     */
    private array $commands = [];

    public function __construct()
    {
        // Registrar aquí todos tus comandos
        $this->register(new Commands\ListCommandsCommand());
        $this->register(new Commands\MakeControllerCommand());
        // Ej: $this->register(new Commands\RouteListCommand());
        // Ej: $this->register(new Commands\MigrateCommand());
    }

    /**
     * Registra un comando en el "catálogo".
     */
    public function register(Command $command): void
    {
        $this->commands[$command->getName()] = $command;
    }

    /**
     * Ejecuta el comando pedido.
     *
     * @param array<int,string> $argv
     */
    public function run(array $argv): int
    {
        $name = $argv[1] ?? 'list';

        if (!isset($this->commands[$name])) {
            $this->writeLine("Comando \"$name\" no encontrado.");
            $this->writeLine('Usa "php artisan list" para ver la lista de comandos.');
            return 1;
        }

        // Dejamos la instancia accesible globalmente para comandos que la necesiten (como "list")
        $GLOBALS['__app_console'] = $this;

        $command   = $this->commands[$name];
        $arguments = \array_slice($argv, 2);

        return $command->handle($arguments);
    }

    /**
     * Helper para escribir en consola.
     */
    private function writeLine(string $text): void
    {
        echo $text . PHP_EOL;
    }

    /**
     * Devuelve todos los comandos registrados (para el comando "list").
     *
     * @return array<string, Command>
     */
    public function getCommands(): array
    {
        return $this->commands;
    }
}
