<?php

namespace App\Core;

/**
 * Router principal del "mini framework".
 *
 * Soporta:
 *  - Rutas GET/POST/PUT
 *  - Handlers tipo [Controlador::class, 'metodo'] o closures
 *  - Middlewares registrables por nombre
 *  - Sintaxis fluida tipo:
 *
 *      $router->middleware('auth')->get('/dashboard', [...]);
 */
class Router
{
    /**
     * Estructura de rutas:
     *
     * [
     *   'GET' => [
     *      '/about' => [
     *          'handler'    => [AboutController::class, 'index'],
     *          'middleware' => ['auth', 'otraCosa'],
     *      ],
     *   ],
     *   'POST' => [
     *      '/login' => [
     *          'handler'    => [AuthController::class, 'post_login'],
     *          'middleware' => ['guest'],
     *      ],
     *   ],
     * ]
     *
     * @var array<string, array<string, array{handler: callable|array, middleware: string[]}>>
     */
    private array $routes = [];

    /**
     * Mapa de middlewares registrados por nombre.
     *
     * Ejemplo:
     *   'auth'  => instancia de AuthMiddleware (invocable)
     *   'guest' => instancia de GuestMiddleware (invocable)
     *
     * @var array<string, callable>
     */
    private array $middlewareMap = [];

    /**
     * Lista de middlewares a aplicar a la SIGUIENTE ruta registrada.
     *
     * Se usa para permitir sintaxis fluida:
     *
     *   $router->middleware('auth')->get('/dashboard', ...);
     *
     * @var string[]
     */
    private array $currentMiddleware = [];

    /**
     * Asocia un nombre de middleware a un callable/instancia invocable.
     *
     * Ejemplo de uso:
     *   $router->registerMiddleware('auth', new AuthMiddleware());
     */
    public function registerMiddleware(string $name, callable $middleware): void
    {
        $this->middlewareMap[$name] = $middleware;
    }

    /**
     * Define uno o varios middlewares para la siguiente ruta que se registre.
     *
     * Permite encadenar llamadas:
     *
     *   $router
     *      ->middleware(['auth', 'verified'])
     *      ->get('/perfil', [ProfileController::class, 'index']);
     */
    public function middleware(string|array $names): self
    {
        $this->currentMiddleware = (array) $names;
        return $this; // Fluent interface (permite ->middleware()->get())
    }

    /**
     * Método interno para registrar una ruta, independientemente del método HTTP.
     */
    private function register(string $method, string $path, callable|array $handler): void
    {
        $this->routes[$method][$path] = [
            'handler'    => $handler,
            'middleware' => $this->currentMiddleware,
        ];

        // Limpia los middlewares temporales para no afectar a futuras rutas.
        $this->currentMiddleware = [];
    }

    /**
     * Registra una ruta GET.
     */
    public function get(string $path, callable|array $handler): void
    {
        $this->register('GET', $path, $handler);
    }

    /**
     * Registra una ruta POST.
     */
    public function post(string $path, callable|array $handler): void
    {
        $this->register('POST', $path, $handler);
    }

    /**
     * Registra una ruta PUT.
     */
    public function put(string $path, callable|array $handler): void
    {
        $this->register('PUT', $path, $handler);
    }

    /**
     * Resuelve la petición HTTP actual y ejecuta el handler asociado,
     * pasando antes por la cadena de middlewares definida para esa ruta.
     *
     * Ejemplo de flujo:
     *   - Petición:   GET /about
     *   - $uri:       "/about"
     *   - $method:    "GET"
     *   - $routes['GET']['/about'] = [
     *         'handler'    => [AboutController::class, 'index'],
     *         'middleware' => ['auth']
     *     ]
     *
     * El router:
     *   1) Normaliza la ruta (sin query string).
     *   2) Localiza el array de ruta correspondiente en $this->routes.
     *   3) Construye un "pipeline" de middlewares que rodea al handler final.
     *   4) Ejecuta la cadena resultante.
     *
     * Si no encuentra la ruta, responde con 404 y, si existe,
     * carga templates/notFound.php.
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
         * 2. Buscar la configuración registrada para este método y ruta
         */
        $route = $this->routes[$method][$path] ?? null;

        /**
         * 3. Si no hay ruta asociada a esa combinación método + path,
         *    devolvemos un 404 estándar intentando usar una vista notFound.php.
         */
        if (!$route) {
            \http_response_code(404);

            // __DIR__ = /ruta/al/proyecto/src/App/Core
            // dirname(__DIR__, 3) = /ruta/al/proyecto
            $viewFile = \dirname(__DIR__, 3) . '/templates/notFound.php';

            if (\file_exists($viewFile)) {
                require $viewFile;
            } else {
                echo '404 Not Found';
            }
            return;
        }

        $handler     = $route['handler']    ?? null;
        $middlewares = $route['middleware'] ?? [];

        if (!$handler) {
            throw new \RuntimeException('Handler de ruta no definido');
        }

        /**
         * 4. Definimos el "core handler": la ejecución real del handler.
         *
         * Aquí simplemente aplicamos la lógica que ya tenías:
         *   - Si es [Controlador::class, 'metodo'] → instanciamos y llamamos al método.
         *   - Si es callable/closure → call_user_func.
         */
        $coreHandler = function () use ($handler) {
            // Caso 1: [Controlador::class, 'metodo']
            if (\is_array($handler) && \count($handler) === 2) {
                [$class, $action] = $handler;

                if (!\class_exists($class)) {
                    throw new \RuntimeException("Controller $class no existe");
                }

                $controller = new $class();

                if (!\method_exists($controller, $action)) {
                    throw new \RuntimeException("Método $action no existe en $class");
                }

                $controller->{$action}();
                return;
            }

            // Caso 2: closure / callable normal
            if (\is_callable($handler)) {
                \call_user_func($handler);
                return;
            }

            throw new \RuntimeException('Handler de ruta no válido');
        };

        /**
         * 5. Construir la cadena de middlewares que rodea al coreHandler.
         *
         * Cada middleware es un callable que recibe $next (el siguiente
         * elemento de la cadena) y decide si lo ejecuta o no.
         *
         * Ejemplo conceptual:
         *   authMiddleware -> guestMiddleware -> coreHandler
         */
        $runner = $coreHandler;

        // Recorremos los middlewares en orden inverso para que el primero
        // configurado sea el más externo en la cadena.
        foreach (\array_reverse($middlewares) as $name) {
            $mw = $this->middlewareMap[$name] ?? null;

            if (!$mw) {
                // Si no existe un middleware con ese nombre, lo ignoramos o
                // podríamos lanzar una excepción si queremos ser estrictos.
                continue;
            }

            $next = $runner;

            // Cada nuevo "runner" envuelve al anterior
            $runner = function () use ($mw, $next) {
                // Llamamos al middleware pasándole el siguiente eslabón.
                // El middleware decide si llama o no a $next().
                $mw($next);
            };
        }

        /**
         * 6. Ejecutar la cadena completa de middlewares + handler final.
         */
        $runner();
    }
}
