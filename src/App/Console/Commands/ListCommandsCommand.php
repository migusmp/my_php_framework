<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Application;
use App\Console\Command;

/**
 * Muestra el listado de comandos registrados.
 */
class ListCommandsCommand extends Command
{
    public function getName(): string
    {
        return 'list';
    }

    public function getDescription(): string
    {
        return 'Lista todos los comandos disponibles.';
    }

    /**
     * @param array<int,string> $arguments
     */
    public function handle(array $arguments): int
    {
        // Pequeño truco: necesitamos acceso a Application para ver todos los comandos.
        // Lo más fácil es que Application pase la instancia por un setter/propiedad estática
        // o usar un mini contenedor. Para no complicarlo, haremos algo estático simple.

        if (!isset($GLOBALS['__app_console'])) {
            $this->error('No se pudo acceder a la aplicación de consola.');
            return 1;
        }

        /** @var Application $app */
        $app = $GLOBALS['__app_console'];

        $this->line('Comandos disponibles:');
        $this->line('');

        foreach ($app->getCommands() as $name => $command) {
            $this->line(sprintf("  %-20s %s", $name, $command->getDescription()));
        }

        return 0;
    }
}
