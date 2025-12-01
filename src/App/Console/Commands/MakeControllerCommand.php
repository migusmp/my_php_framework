<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Command;

/**
 * Crea un nuevo controlador en src/App/Controllers.
 *
 * Uso:
 *   php artisan make:controller CursoController
 */
class MakeControllerCommand extends Command
{
    public function getName(): string
    {
        return 'make:controller';
    }

    public function getDescription(): string
    {
        return 'Genera un nuevo controlador base en App\\Controllers.';
    }

    /**
     * @param array<int,string> $arguments
     */
    public function handle(array $arguments): int
    {
        $name = $arguments[0] ?? null;

        if ($name === null) {
            $this->error('Debes indicar el nombre del controlador.');
            $this->line('Ejemplo: php artisan make:controller CursoController');
            return 1;
        }

        // Asegurarnos de que termina en "Controller"
        if (!str_ends_with($name, 'Controller')) {
            $name .= 'Controller';
        }

        $controllerDir  = __DIR__ . '/../../Controllers';
        $controllerPath = $controllerDir . '/' . $name . '.php';

        if (!is_dir($controllerDir)) {
            if (!mkdir($controllerDir, 0777, true) && !is_dir($controllerDir)) {
                $this->error('No se pudo crear el directorio de controladores.');
                return 1;
            }
        }

        if (file_exists($controllerPath)) {
            $this->error("El controlador {$name} ya existe.");
            return 1;
        }

        $stub = $this->getStubContent($name);

        if (file_put_contents($controllerPath, $stub) === false) {
            $this->error('No se pudo escribir el archivo del controlador.');
            return 1;
        }

        $this->info("Controlador creado: src/App/Controllers/{$name}.php");

        return 0;
    }

    /**
     * Genera el contenido del controlador.
     */
    private function getStubContent(string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Http\Request;
use App\Http\Response;

/**
 * Controlador {$className}.
 *
 * Generado con "php artisan make:controller {$className}".
 */
class {$className}
{
    public function index(Request \$request, Response \$response): void
    {
        // TODO: Implementa la lógica de tu controlador
        View::render('{$this->toViewName($className)}', [
            'title' => '{$className}',
        ]);
    }
}

PHP;
    }

    /**
     * Convierte el nombre del controlador en un nombre de vista por defecto.
     *
     * Ej: CursoController -> 'curso/index'
     */
    private function toViewName(string $className): string
    {
        $name = preg_replace('/Controller$/', '', $className) ?? $className;
        $name = strtolower($name);

        return $name . '/index';
    }
}
