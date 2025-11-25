<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Router principal del mini-framework.
 *
 * Características:
 *  - Rutas GET / POST / PUT.
 *  - Handlers tipo [Controlador::class, 'metodo'] o closures.
 *  - Middlewares registrables por nombre.
 *  - Sintaxis fluida:
 *
 *        $router->middleware('auth')->get('/dashboard', [...]);
 *
 *  - Grupos de rutas con prefijo y middlewares compartidos:
 *
 *        $router->group('/admin', function (Router $r) {
 *            $r->get('/dashboard', [...]);
 *        }, ['auth', 'admin']);
 */
class Router
{
    /**
     * Estructura de rutas registradas.
     *
     * Ejemplo:
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
     *  - 'auth'  => instancia de AuthMiddleware (invocable).
     *  - 'guest' => instancia de GuestMiddleware (invocable).
     *
     * @var array<string, callable>
     */
    private array $middlewareMap = [];

    /**
     * Lista de middlewares que se aplicarán sólo
     * a la siguiente ruta que se registre.
     *
     * Se usa para permitir la sintaxis fluida:
     *
     *   $router->middleware('auth')->get('/dashboard', ...);
     *
     * @var string[]
     */
    private array $currentMiddleware = [];

    /**
     * Prefijo actual de grupo de rutas.
     *
     * Se utiliza en $this->group() para componer URLs como:
     *  - '/admin' + '/dashboard' => '/admin/dashboard'
     */
    private string $currentPrefix = '';

    /**
     * Middlewares comunes a un grupo de rutas.
     *
     * Se acumulan cuando hay grupos anidados:
     *  group('/admin', [...])
     *    group('/users', [...])
     *
     * @var string[]
     */
    private array $currentGroupMiddlewares = [];

    /**
     * Registra un middleware en el router, asociándolo a un nombre.
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
     *      ->middleware('auth', 'verified')
     *      ->get('/perfil', [ProfileController::class, 'index']);
     */
    public function middleware(string|array ...$names): self
    {
        $all = [];

        foreach ($names as $name) {
            $all = array_merge($all, (array) $name);
        }

        $this->currentMiddleware = array_values(array_unique($all));
        return $this;
    }
    /**
     * Define un grupo de rutas con un prefijo y middlewares comunes.
     *
     * El prefijo y los middlewares se aplican a todas las rutas
     * definidas dentro del callback. Soporta grupos anidados.
     *
     * Ejemplo:
     *   $router->group('/admin', function (Router $r) {
     *       $r->get('/dashboard', [...]); // => /admin/dashboard
     *       $r->get('/users', [...]);     // => /admin/users
     *   }, ['auth', 'admin']);
     */
    public function group(string $prefix, callable $callback, array $middleware = []): void
    {
        // Guardar el contexto actual (para soportar grupos anidados)
        $parentPrefix      = $this->currentPrefix;
        $parentMiddlewares = $this->currentGroupMiddlewares;

        // Nuevo prefijo = prefijo actual + nuevo prefijo
        $this->currentPrefix = \rtrim($parentPrefix . $prefix, '/');

        // Middlewares acumulados (grupo padre + grupo actual)
        $this->currentGroupMiddlewares = \array_merge($parentMiddlewares, $middleware);

        // Ejecutar el callback de definición de rutas
        $callback($this);

        // Restaurar el contexto anterior
        $this->currentPrefix           = $parentPrefix;
        $this->currentGroupMiddlewares = $parentMiddlewares;
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
     * Método interno para registrar una ruta, independientemente del método HTTP.
     *
     * Aplica:
     *  - Prefijo de grupo (si existe).
     *  - Middlewares del grupo + middlewares específicos de la ruta.
     */
    private function register(string $method, string $path, callable|array $handler): void
    {
        // Componer la URL final con el prefijo del grupo (si lo hay)
        $fullPath = $this->currentPrefix . $path;

        $this->routes[$method][$fullPath] = [
            'handler'    => $handler,
            'middleware' => \array_merge($this->currentGroupMiddlewares, $this->currentMiddleware),
        ];

        // Limpiar los middlewares temporales para no afectar a futuras rutas
        $this->currentMiddleware = [];
    }

    /**
     * Resuelve la petición HTTP y ejecuta el handler asociado,
     * pasando antes por la cadena de middlewares definida para esa ruta.
     *
     * Flujo general:
     *   1) Normaliza la ruta (sin query string).
     *   2) Localiza la configuración en $this->routes.
     *   3) Construye un "pipeline" de middlewares alrededor del handler final.
     *   4) Ejecuta la cadena resultante.
     *
     * Si no encuentra la ruta: 404 + intento de cargar templates/notFound.php.
     */
    public function dispatch(string $uri, string $method): void
    {
        // 1. Normalizar la ruta pedida (ignorando query string)
        //   "/about?id=1"  -> "/about"
        //   "/contacto?x=1" -> "/contacto"
        $path = \parse_url($uri, PHP_URL_PATH) ?? '/';

        // 2. Buscar la ruta correspondiente a método + path
        $route = $this->routes[$method][$path] ?? null;

        // 3. Si no hay ruta, devolvemos un 404
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
         * 4. Handler central ("core handler"): ejecuta el controlador o callable.
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
         * 5. Construir la cadena de middlewares que envuelve al coreHandler.
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
                // Si no existe un middleware con ese nombre, simplemente
                // lo ignoramos. Podríamos lanzar una excepción si
                // queremos ser más estrictos.
                continue;
            }

            $next = $runner;

            // Cada nuevo "runner" envuelve al anterior
            $runner = function () use ($mw, $next) {
                // Llamamos al middleware pasándole el siguiente eslabón.
                // El middleware decidirá si continúa con $next() o no.
                $mw($next);
            };
        }

        // 6. Ejecutar la cadena completa de middlewares + handler final.
        $runner();
    }
}
