<?php

namespace App\Core;

class Router
{
    private array $routes = [];

    /* Esto es lo que se guarda en el $routes
        $this->routes = [
                'GET' => [
                '/' => [HomeController::class, 'index']
            ]
        ];
    */

    public function get(string $path, callable|array $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function put(string $path, callable|array $handler): void
    {
        $this->routes['PUT'][$path] = $handler;
    }

    /**
     * Resuelve la petición HTTP actual y ejecuta el handler asociado.
     *
     * Ejemplo de flujo:
     *   - Petición:   GET /about
     *   - $uri:       "/about"
     *   - $method:    "GET"
     *   - $routes['GET']['/about'] = [AboutController::class, 'index']
     *
     * El router:
     *   1) Localiza el handler correspondiente en $this->routes.
     *   2) Si es [Controlador::class, 'metodo'], instancia el controlador y llama al método.
     *   3) Si es una función/closure, la ejecuta tal cual.
     *   4) Si no encuentra nada, responde con 404.
     */
    public function dispatch(string $uri, string $method): void
    {
        /**
         * 1. Normalizar la ruta pedida
         *
         * parse_url extrae solo la parte de la ruta, ignorando query strings.
         *   "/about?id=1"  -> "/about"
         *   "/contacto?x=1&y=2" -> "/contacto"
         *
         * Si por alguna razón parse_url devolviera null, usamos "/" como valor por defecto.
         */
        $path = \parse_url($uri, PHP_URL_PATH) ?? '/';

        /**
         * 2. Buscar el handler registrado para este método y ruta
         *
         * $this->routes tiene una estructura similar a:
         *
         *   [
         *     'GET' => [
         *        '/'       => [HomeController::class, 'index'],
         *        '/about'  => [AboutController::class, 'index'],
         *     ],
         *     'POST' => [
         *        '/login' => [AuthController::class, 'login'],
         *     ],
         *   ]
         *
         * Así que aquí estamos haciendo algo como:
         *   $handler = $this->routes['GET']['/about'] ?? null;
         */
        $handler = $this->routes[$method][$path] ?? null;

        /**
         * 3. Si no hay handler asociado a esa combinación método + ruta,
         *    devolvemos un 404 estándar.
         */
        if (!$handler) {
            \http_response_code(404);

            // __DIR__ = /ruta/al/proyecto/src/Core
            // dirname(__DIR__, 2) = /ruta/al/proyecto
            $viewFile = \dirname(__DIR__, 3) . '/templates/notFound.php';

            if (\file_exists($viewFile)) {
                require $viewFile;
            } else {
                echo '404 Not Found';
            }
            return;
        }

        /**
         * 4. Caso 1: el handler es un array => asumimos que es [Controlador::class, 'metodo']
         *
         *   Ejemplo:
         *     $router->get('/', [HomeController::class, 'index']);
         *
         *   Internamente:
         *     $handler = ['App\Controllers\HomeController', 'index'];
         */
        if (\is_array($handler) && \count($handler) === 2) {
            // Desempaquetamos el array:
            //   $class  = "App\Controllers\HomeController"
            //   $action = "index"
            [$class, $action] = $handler;

            /**
             * Verificamos que la clase realmente exista. Esto, además,
             * dispara el autoload si aún no se ha cargado el archivo
             * correspondiente.
             */
            if (!\class_exists($class)) {
                throw new \RuntimeException("Controller $class no existe");
            }

            /**
             * Instanciamos el controlador dinámicamente.
             *
             * Es equivalente a:
             *   $controller = new App\Controllers\HomeController();
             */
            $controller = new $class();

            /**
             * Antes de llamar al método, comprobamos que exista en el controlador.
             *
             * Esto evita errores fatales tipo:
             *   "Call to undefined method HomeController::indez()"
             *
             * Si en las rutas te equivocas y pones:
             *   [HomeController::class, 'indez']
             * en vez de 'index', atrapamos el fallo aquí con un mensaje claro.
             */
            if (!\method_exists($controller, $action)) {
                throw new \RuntimeException("Método $action no existe en $class");
            }

            /**
             * Llamada dinámica al método del controlador.
             *
             *   $controller->{$action}();
             *
             * Si $action = 'index', equivale a:
             *   $controller->index();
             */
            $controller->{$action}();
            return;
        }

        /**
         * 5. Caso 2: el handler es algo ejecutable (callable), normalmente un closure.
         *
         *   Ejemplo:
         *     $router->get('/ping', function () {
         *         echo 'pong';
         *     });
         *
         *   En este caso, simplemente ejecutamos la función.
         */
        if (\is_callable($handler)) {
            \call_user_func($handler);
            return;
        }

        /**
         * 6. Si llegamos hasta aquí, significa que el handler tiene un formato
         *    que no reconocemos ni como [Controller::class, 'metodo']
         *    ni como callable, así que lanzamos una excepción.
         */
        throw new \RuntimeException('Handler de ruta no válido');
    }
}
