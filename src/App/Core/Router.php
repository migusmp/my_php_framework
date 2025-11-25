<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Router principal del mini-framework.
 *
 * Características:
 *  - Rutas GET / POST / PUT.
 *  - Handlers tipo:
 *      • [Controlador::class, 'metodo']
 *      • closures
 *      • "HomeController@index"
 *  - Soporte de parámetros en la ruta:
 *      • /users/{id}
 *      • /posts/{slug}/comments/{commentId}
 *  - Middlewares registrables por nombre.
 *  - Sintaxis fluida:
 *
 *        $router->middleware('auth')->get('/dashboard', [...]);
 *
 *  - Prefijos y grupos de rutas:
 *
 *        $router->prefix('admin')->group('/panel', function (Router $r) {
 *            $r->get('/dashboard', 'AdminController@dashboard');
 *        }, ['auth', 'admin']);
 */
class Router
{
    /**
     * Estructura de rutas registradas por método HTTP.
     *
     * Cada ruta guarda:
     *  - handler:    callable o [Controller::class, 'metodo']
     *  - middleware: lista de nombres de middlewares
     *  - pattern:    patrón regex compilado si la ruta tiene parámetros ({id}, {slug}, etc.)
     *  - params:     nombres de los parámetros en orden
     *
     * Ejemplo de estructura interna:
     *
     * [
     *   'GET' => [
     *      '/about' => [
     *          'handler'    => [AboutController::class, 'index'],
     *          'middleware' => ['auth'],
     *          'pattern'    => null,
     *          'params'     => [],
     *      ],
     *      '/users/{id}' => [
     *          'handler'    => [UserController::class, 'show'],
     *          'middleware' => [],
     *          'pattern'    => '#^/users/(?P<id>[^/]+)$#',
     *          'params'     => ['id'],
     *      ],
     *   ],
     * ]
     *
     * @var array<string, array<string, array{
     *     handler: callable|array,
     *     middleware: string[],
     *     pattern: string|null,
     *     params: string[]
     * }>>
     */
    private array $routes = [];

    /**
     * Namespace base de los controladores para usar "Controller@method".
     *
     * Por defecto: App\Controllers\HomeController, etc.
     */
    private string $controllerNamespace = 'App\\Controllers\\';

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
     * Se utiliza en prefix()/group() para componer URLs como:
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
     * Cambia el namespace base donde se buscan controladores
     * cuando usas handlers tipo "HomeController@index".
     *
     * Por defecto: "App\\Controllers\\".
     */
    public function setControllerNamespace(string $namespace): self
    {
        $this->controllerNamespace = rtrim($namespace, '\\') . '\\';
        return $this;
    }

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
     * Define un prefijo base para las rutas siguientes.
     *
     * Se acumula con el prefijo actual y se usa junto con group().
     *
     * Ejemplo:
     *   $router->prefix('admin')->group('/panel', function (Router $r) {
     *       $r->get('/dashboard', ...); // => /admin/panel/dashboard
     *   });
     */
    public function prefix(string $prefix): self
    {
        // Normaliza y acumula el prefijo actual:
        //  ''      + 'admin' -> '/admin'
        //  '/api'  + 'v1'    -> '/api/v1'
        $this->currentPrefix = \rtrim(
            ($this->currentPrefix === '' ? '' : $this->currentPrefix . '/') . \ltrim($prefix, '/'),
            '/'
        );

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
     *
     * $handler puede ser:
     *  - callable
     *  - [Controller::class, 'method']
     *  - "HomeController@index"
     */
    public function get(string $path, callable|array|string $handler): void
    {
        $this->register('GET', $path, $handler);
    }

    /**
     * Registra una ruta POST.
     */
    public function post(string $path, callable|array|string $handler): void
    {
        $this->register('POST', $path, $handler);
    }

    /**
     * Registra una ruta PUT.
     */
    public function put(string $path, callable|array|string $handler): void
    {
        $this->register('PUT', $path, $handler);
    }

    /**
     * Método interno para registrar una ruta, independientemente del método HTTP.
     *
     * Aplica:
     *  - Prefijo de grupo (si existe).
     *  - Middlewares del grupo + middlewares específicos de la ruta.
     *  - Normalización del handler ("Controller@method").
     *  - Compilación de patrón regex si la ruta tiene parámetros {param}.
     */
    private function register(string $method, string $path, callable|array|string $handler): void
    {
        // Componer la URL final con el prefijo del grupo (si lo hay)
        $fullPath = $this->currentPrefix . $path;

        // Normalizar handler (soporta "HomeController@index")
        $handler = $this->normalizeHandler($handler);

        $pattern   = null;
        $paramNames = [];

        // Si la ruta contiene llaves, asumimos parámetros {id}, {slug}, etc.
        if (\str_contains($fullPath, '{')) {
            [$pattern, $paramNames] = $this->compilePathToRegex($fullPath);
        }

        $this->routes[$method][$fullPath] = [
            'handler'    => $handler,
            'middleware' => \array_merge($this->currentGroupMiddlewares, $this->currentMiddleware),
            'pattern'    => $pattern,
            'params'     => $paramNames,
        ];

        // Limpiar los middlewares temporales para no afectar a futuras rutas
        $this->currentMiddleware = [];
    }

    /**
     * Normaliza un handler:
     *
     * - "HomeController@index" → [App\Controllers\HomeController, 'index']
     * - [Controller::class, 'method'] → se deja igual
     * - callable → se deja igual
     */
    private function normalizeHandler(callable|array|string $handler): callable|array
    {
        // Caso estilo Laravel: "Controller@method"
        if (\is_string($handler)) {
            if (!\str_contains($handler, '@')) {
                throw new \InvalidArgumentException(
                    "El handler debe tener formato 'Controller@method'. Dado: {$handler}"
                );
            }

            [$controller, $method] = \explode('@', $handler, 2);

            // Si ya trae namespace, lo respetamos; si no, usamos el namespace base
            if (\str_contains($controller, '\\')) {
                $fqcn = $controller;
            } else {
                $fqcn = $this->controllerNamespace . $controller;
            }

            if (!\class_exists($fqcn)) {
                throw new \RuntimeException("Controlador {$fqcn} no existe");
            }

            return [$fqcn, $method];
        }

        // Caso [Controller::class, 'method'] o callable
        if (\is_array($handler) || \is_callable($handler)) {
            return $handler;
        }

        throw new \InvalidArgumentException('Handler de ruta no válido.');
    }

    /**
     * Compila una ruta con parámetros a un patrón regex.
     *
     * Ejemplo:
     *   /users/{id}/posts/{slug}
     *   =>
     *   '#^/users/(?P<id>[^/]+)/posts/(?P<slug>[^/]+)$#'
     *   y ['id', 'slug']
     *
     * @return array{0: string, 1: string[]}
     */
    private function compilePathToRegex(string $path): array
    {
        $paramNames = [];

        // Reemplazamos cada {param} por un grupo con nombre (?P<param>[^/]+)
        $regex = \preg_replace_callback(
            '#\{([a-zA-Z_][a-zA-Z0-9_-]*)\}#',
            function (array $matches) use (&$paramNames): string {
                $paramNames[] = $matches[1];
                return '(?P<' . $matches[1] . '>[^/]+)';
            },
            $path
        );

        // Delimitadores de regex + anclaje inicio/fin
        $regex = '#^' . $regex . '$#';

        return [$regex, $paramNames];
    }

    /**
     * Resuelve la petición HTTP y ejecuta el handler asociado,
     * pasando antes por la cadena de middlewares definida para esa ruta.
     *
     * Soporta:
     *  - Rutas estáticas: /about
     *  - Rutas con parámetros: /users/{id}
     */
    public function dispatch(string $uri, string $method): void
    {
        // Normalizar la ruta pedida (ignorando query string)
        $path = \parse_url($uri, PHP_URL_PATH) ?? '/';

        $method = \strtoupper($method);
        $routesForMethod = $this->routes[$method] ?? [];

        $matchedRoute = null;
        $routeParams  = [];

        // 1) Intento rápido: buscar ruta estática exacta
        if (isset($routesForMethod[$path]) && $routesForMethod[$path]['pattern'] === null) {
            $matchedRoute = $routesForMethod[$path];
            $routeParams  = [];
        } else {
            // 2) Buscar entre rutas (estáticas y dinámicas) comparando patrones
            foreach ($routesForMethod as $routePath => $route) {
                $pattern = $route['pattern'] ?? null;

                // Ruta estática sin patrón: comparar path literal
                if ($pattern === null) {
                    if ($routePath === $path) {
                        $matchedRoute = $route;
                        $routeParams  = [];
                        break;
                    }
                    continue;
                }

                // Ruta dinámica: probar regex
                if (\preg_match($pattern, $path, $matches)) {
                    $params = [];
                    foreach ($route['params'] as $name) {
                        if (isset($matches[$name])) {
                            $params[] = $matches[$name];
                        }
                    }

                    $matchedRoute = $route;
                    $routeParams  = $params;
                    break;
                }
            }
        }

        // 3. Si no hay ruta, devolvemos un 404
        if (!$matchedRoute) {
            \http_response_code(404);

            $viewFile = \dirname(__DIR__, 3) . '/templates/notFound.php';

            if (\file_exists($viewFile)) {
                require $viewFile;
            } else {
                echo '404 Not Found';
            }

            return;
        }

        $handler     = $matchedRoute['handler']    ?? null;
        $middlewares = $matchedRoute['middleware'] ?? [];

        if (!$handler) {
            throw new \RuntimeException('Handler de ruta no definido');
        }

        /**
         * 4. Handler central ("core handler"): ejecuta el controlador o callable.
         *    Si la ruta tenía parámetros, se pasan como argumentos en orden.
         */
        $coreHandler = function () use ($handler, $routeParams) {
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

                // Pasamos los parámetros capturados como argumentos
                $controller->{$action}(...$routeParams);
                return;
            }

            // Caso 2: closure / callable normal
            if (\is_callable($handler)) {
                \call_user_func_array($handler, $routeParams);
                return;
            }

            throw new \RuntimeException('Handler de ruta no válido');
        };

        /**
         * 5. Construir la cadena de middlewares que envuelve al coreHandler.
         */
        $runner = $coreHandler;

        // Recorremos los middlewares en orden inverso para que el primero
        // configurado sea el más externo en la cadena.
        foreach (\array_reverse($middlewares) as $name) {
            $mw = $this->middlewareMap[$name] ?? null;

            if (!$mw) {
                continue;
            }

            $next = $runner;

            // Cada nuevo "runner" envuelve al anterior
            $runner = function () use ($mw, $next) {
                $mw($next);
            };
        }

        // 6. Ejecutar la cadena completa de middlewares + handler final.
        $runner();
    }
}
