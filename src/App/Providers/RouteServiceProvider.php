<?php

namespace App\Providers;

use App\Core\Router;

class RouteServiceProvider
{
    public static function loadRoutes(Router $router): void
    {
        // 📁 Ruta correcta a la carpeta /routes (subimos 3 niveles desde src/App/Providers)
        $routesPath = dirname(__DIR__, 3) . '/routes';

        if (!\is_dir($routesPath)) {
            throw new \RuntimeException("Carpeta de rutas no encontrada: {$routesPath}");
        }

        // --------------------------------------------
        // 1) Archivos con prioridad, si existen
        // --------------------------------------------
        $ordered = [
            $routesPath . '/web.php',
            $routesPath . '/api.php',
            $routesPath . '/auth.php',
            $routesPath . '/admin.php',
        ];

        $loadedRealPaths = [];

        foreach ($ordered as $file) {
            if (\is_file($file)) {
                require $file;
                $real = \realpath($file);
                if ($real !== false) {
                    $loadedRealPaths[] = $real;
                }
            }
        }

        // --------------------------------------------
        // 2) Cualquier otro *.php directamente en /routes
        // --------------------------------------------
        foreach (\glob($routesPath . '/*.php') ?: [] as $file) {
            $real = \realpath($file);

            if ($real === false) {
                continue;
            }

            if (\in_array($real, $loadedRealPaths, true)) {
                continue; // ya cargado en $ordered
            }

            require $file;
            $loadedRealPaths[] = $real;
        }

        // --------------------------------------------
        // 3) Archivos en subcarpetas de /routes (routes/admin/*.php, etc.)
        // --------------------------------------------
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($routesPath, \FilesystemIterator::SKIP_DOTS)
        );

        $rootReal = \realpath($routesPath);

        foreach ($iterator as $fileInfo) {
            /** @var \SplFileInfo $fileInfo */
            if (!$fileInfo->isFile() || $fileInfo->getExtension() !== 'php') {
                continue;
            }

            $real = $fileInfo->getRealPath();
            if ($real === false) {
                continue;
            }

            // Evitar volver a requerir archivos ya cargados
            if (\in_array($real, $loadedRealPaths, true)) {
                continue;
            }

            // Opcional: si quieres excluir los que están justo en /routes (ya tratados),
            // comprobamos que estén en subcarpetas:
            if (\dirname($real) === $rootReal) {
                continue;
            }

            require $real;
            $loadedRealPaths[] = $real;
        }
    }
}
