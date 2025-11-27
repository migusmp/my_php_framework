<?php

declare(strict_types=1);

namespace App\Core;

use App\Http\Request;
use App\Http\Response;

/**
 * Router principal del mini-framework.
 */
class Router
{
    /**
     * Estructura de rutas registradas por método HTTP.
     *
     * @var array<string, array<string, array{
     *     handler: callable|array,
     *     middleware: string[],
     *     pattern: string|null,
     *     params: string[],
     *     name: string|null
     * }>>
     */
    private array $routes = [];

    /**
     * Namespace base de los controladores para usar "Controller@method".
     */
    private string $controllerNamespace = 'App\\Controllers\\';

    /**
     * Mapa de middlewares registrados por nombre.
     *
     * @var array<string, callable>
     */
    private array $middlewareMap = [];

    /**
     * Middlewares que se aplicarán a la siguiente ruta registrada
     * (cuando se usa $router->middleware()->get()).
     *
     * @var string[]
     */
    private array $currentMiddleware = [];

    /**
     * Prefijo actual de grupo de rutas.
     */
    private string $currentPrefix = '';

    /**
     * Middlewares comunes a un grupo de rutas.
     *
     * @var string[]
     */
    private array $currentGroupMiddlewares = [];

    /**
     * Última ruta registrada (para soportar ->name()).
     *
     * @var array{method: string, path: string}|null
     */
    private ?array $lastRoute = null;

    /**
     * Mapa de nombres de ruta a [method, path].
     *
     * @var array<string, array{method: string, path: string}>
     */
    private array $namedRoutes = [];

    /**
     * Instancia global del Router para helpers como url().
     */
    private static ?self $instance = null;

    // ================================================================
    //                       CONFIGURACIÓN BÁSICA
    // ================================================================

    public function setControllerNamespace(string $namespace): self
    {
        $this->controllerNamespace = rtrim($namespace, '\\') . '\\';
        return $this;
    }

    public function registerMiddleware(string $name, callable $middleware): void
    {
        $this->middlewareMap[$name] = $middleware;
    }

    /**
     * Define middlewares "por defecto" para la siguiente ruta
     * cuando se usa: $router->middleware('auth')->get(...).
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
     * Añadir middlewares a una ruta ya registrada (usado por RouteDefinition).
     */
    public function appendMiddlewareToRoute(string $method, string $path, array $names): void
    {
        if (!isset($this->routes[$method][$path])) {
            throw new \RuntimeException("Ruta {$method} {$path} no encontrada para añadir middleware.");
        }

        $all = [];
        foreach ($names as $name) {
            $all = array_merge($all, (array) $name);
        }

        $this->routes[$method][$path]['middleware'] = array_unique(array_merge(
            $this->routes[$method][$path]['middleware'],
            $all
        ));
    }

    /**
     * Asignar nombre a ruta ya registrada (usado por RouteDefinition).
     */
    public function nameRoute(string $method, string $path, string $name): void
    {
        if (!isset($this->routes[$method][$path])) {
            throw new \RuntimeException("Ruta {$method} {$path} no encontrada para nombrar.");
        }

        $this->routes[$method][$path]['name'] = $name;

        $this->namedRoutes[$name] = [
            'method' => $method,
            'path'   => $path,
        ];
    }

    /**
     * Define un prefijo "fluido" para las siguientes rutas,
     * por ejemplo:
     *
     *   $router->prefix('admin')->get('/dashboard', ...);
     *
     * Se puede anidar con grupos, pero para cosas complejas
     * es mejor usar group() con array de opciones.
     */
    public function prefix(string $prefix): self
    {
        $normalized = trim($prefix, '/');

        if ($normalized === '') {
            $this->currentPrefix = '';
        } else {
            if ($this->currentPrefix === '') {
                $this->currentPrefix = $normalized;
            } else {
                $this->currentPrefix = trim($this->currentPrefix . '/' . $normalized, '/');
            }
        }

        return $this;
    }
    /**
     * Agrupa rutas con un prefijo y/o middlewares comunes.
     *
     * Formato "nuevo" tipo Laravel:
     *
     *   $router->group([
     *       'prefix'     => 'admin',
     *       'middleware' => ['auth', 'is_admin'],
     *   ], function (Router $router) {
     *       $router->get('/dashboard', 'AdminController@index');
     *       $router->get('/users', 'AdminController@users');
     *   });
     *
     * Mantiene compatibilidad con el formato antiguo:
     *
     *   $router->group('/admin', function (Router $r) {
     *       $r->get('/dashboard', 'AdminController@index');
     *   }, ['auth']);
     */
    public function group(string|array $prefixOrOptions, callable $callback, array $middleware = []): void
    {
        // Guardamos el contexto anterior para restaurarlo al salir
        $parentPrefix      = $this->currentPrefix;
        $parentMiddlewares = $this->currentGroupMiddlewares;

        // -------------------------
        // Normalizar parámetros
        // -------------------------
        $prefix          = '';
        $extraMiddleware = $middleware;

        if (\is_array($prefixOrOptions)) {
            // Nuevo formato: ['prefix' => '...', 'middleware' => [...]]
            $prefix = $prefixOrOptions['prefix'] ?? '';
            if (isset($prefixOrOptions['middleware'])) {
                $extraMiddleware = (array) $prefixOrOptions['middleware'];
            }
        } else {
            // Formato antiguo: group('/admin', callback, ['auth'])
            $prefix = $prefixOrOptions;
        }

        // -------------------------
        // Calcular nuevo prefijo
        // -------------------------
        $normalizedPrefix = trim($prefix, '/');

        if ($normalizedPrefix === '') {
            // Si no hay prefijo nuevo, mantenemos el del padre
            $this->currentPrefix = $parentPrefix;
        } else {
            if ($parentPrefix === '') {
                $this->currentPrefix = $normalizedPrefix;
            } else {
                $this->currentPrefix = trim($parentPrefix . '/' . $normalizedPrefix, '/');
            }
        }

        // Middlewares del grupo = middlewares del padre + extra de este grupo
        $this->currentGroupMiddlewares = \array_values(\array_unique(\array_merge(
            $parentMiddlewares,
            (array) $extraMiddleware
        )));

        // Ejecutamos el callback dentro del contexto del grupo
        $callback($this);

        // Restauramos el contexto anterior (para que el grupo no “contamine” fuera)
        $this->currentPrefix           = $parentPrefix;
        $this->currentGroupMiddlewares = $parentMiddlewares;
    }

    public function groupFile(string|array $options, string $file): void
    {
        $this->group($options, function () use ($file) {
            require $file;
        });
    }

    // ================================================================
    //                        REGISTRO DE RUTAS
    // ================================================================

    public function get(string $path, callable|array|string $handler): RouteDefinition
    {
        $fullPath = $this->register('GET', $path, $handler);
        return new RouteDefinition($this, 'GET', $fullPath);
    }

    public function post(string $path, callable|array|string $handler): RouteDefinition
    {
        $fullPath = $this->register('POST', $path, $handler);
        return new RouteDefinition($this, 'POST', $fullPath);
    }

    public function put(string $path, callable|array|string $handler): RouteDefinition
    {
        $fullPath = $this->register('PUT', $path, $handler);
        return new RouteDefinition($this, 'PUT', $fullPath);
    }

    public function patch(string $path, callable|array|string $handler): RouteDefinition
    {
        $fullPath = $this->register('PATCH', $path, $handler);
        return new RouteDefinition($this, 'PATCH', $fullPath);
    }

    public function delete(string $path, callable|array|string $handler): RouteDefinition
    {
        $fullPath = $this->register('DELETE', $path, $handler);
        return new RouteDefinition($this, 'DELETE', $fullPath);
    }

    public function name(string $name): self
    {
        if ($this->lastRoute === null) {
            throw new \LogicException("No hay ninguna ruta reciente para asignarle el nombre '{$name}'.");
        }

        $method = $this->lastRoute['method'];
        $path   = $this->lastRoute['path'];

        if (!isset($this->routes[$method][$path])) {
            throw new \RuntimeException("Ruta {$method} {$path} no encontrada para nombrar.");
        }

        $this->routes[$method][$path]['name'] = $name;
        $this->namedRoutes[$name] = [
            'method' => $method,
            'path'   => $path,
        ];

        return $this;
    }

    public function route(string $name, array $params = []): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new \InvalidArgumentException("No existe ninguna ruta con nombre '{$name}'.");
        }

        $path = $this->namedRoutes[$name]['path'];

        $url = \preg_replace_callback(
            '#\{([a-zA-Z_][a-zA-Z0-9_-]*)\}#',
            function (array $matches) use (&$params, $name): string {
                $key = $matches[1];

                if (!\array_key_exists($key, $params)) {
                    throw new \InvalidArgumentException(
                        "Falta el parámetro '{$key}' para construir la URL de la ruta '{$name}'."
                    );
                }

                $value = (string) $params[$key];
                unset($params[$key]);

                return $value;
            },
            $path
        );

        if ($params !== []) {
            $url .= '?' . \http_build_query($params);
        }

        return $url;
    }

    /**
     * Registro interno de rutas.
     *
     * Devuelve el path final normalizado ("/login", "/dashboard", etc.)
     * para que lo use RouteDefinition.
     */
    private function register(string $method, string $path, callable|array|string $handler): string
    {
        $base = $this->currentPrefix !== ''
            ? trim($this->currentPrefix, '/') . '/'
            : '';

        $normalizedPath = ltrim($path, '/');

        $fullPath = '/' . ltrim($base . $normalizedPath, '/');

        $handler = $this->normalizeHandler($handler);

        $pattern    = null;
        $paramNames = [];

        if (\str_contains($fullPath, '{')) {
            [$pattern, $paramNames] = $this->compilePathToRegex($fullPath);
        }

        $this->routes[$method][$fullPath] = [
            'handler'    => $handler,
            'middleware' => \array_merge(
                $this->currentGroupMiddlewares,
                $this->currentMiddleware
            ),
            'pattern'    => $pattern,
            'params'     => $paramNames,
            'name'       => null,
        ];

        $this->lastRoute = [
            'method' => $method,
            'path'   => $fullPath,
        ];

        $this->currentMiddleware = [];

        // Auto-naming de ruta si no tiene nombre explícito
        if ($this->routes[$method][$fullPath]['name'] === null) {
            // /admin/users/create -> admin.users.create
            $auto = \str_replace('/', '.', \trim($fullPath, '/'));

            if ($auto === '') {
                // Para la raíz "/" algo tipo "get.root" o lo que prefieras
                $auto = \strtolower($method) . '.root';
            }

            // No pisar nombres ya existentes
            if (!isset($this->namedRoutes[$auto])) {
                $this->routes[$method][$fullPath]['name'] = $auto;
                $this->namedRoutes[$auto] = [
                    'method' => $method,
                    'path'   => $fullPath,
                ];
            }
        }


        return $fullPath;
    }

    private function normalizeHandler(callable|array|string $handler): callable|array
    {
        if (\is_string($handler)) {
            if (!\str_contains($handler, '@')) {
                throw new \InvalidArgumentException(
                    "El handler debe tener formato 'Controller@method'. Dado: {$handler}"
                );
            }

            [$controller, $method] = \explode('@', $handler, 2);

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

        if (\is_array($handler) || \is_callable($handler)) {
            return $handler;
        }

        throw new \InvalidArgumentException('Handler de ruta no válido.');
    }

    private function compilePathToRegex(string $path): array
    {
        $paramNames = [];

        $regex = \preg_replace_callback(
            '#\{([a-zA-Z_][a-zA-Z0-9_-]*)\}#',
            function (array $matches) use (&$paramNames): string {
                $paramNames[] = $matches[1];
                return '(?P<' . $matches[1] . '>[^/]+)';
            },
            $path
        );

        $regex = '#^' . $regex . '$#';

        return [$regex, $paramNames];
    }

    // ================================================================
    //                 INSTANCIA GLOBAL (url(), Route::...)
    // ================================================================

    public function setAsGlobal(): void
    {
        self::$instance = $this;
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            throw new \RuntimeException('Router global no inicializado.');
        }

        return self::$instance;
    }

    // ================================================================
    //                          DISPATCH
    // ================================================================

    public function dispatch(string $uri, string $method): void
    {
        $originalMethod  = \strtoupper($method);
        $effectiveMethod = $originalMethod;

        if ($originalMethod === 'POST') {
            $override = $_POST['_method'] ?? null;

            if (\is_string($override)) {
                $override = \strtoupper($override);

                if (\in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
                    $effectiveMethod = $override;
                }
            }
        }

        $request  = Request::fromGlobals($effectiveMethod);
        $response = new Response();

        $path = rtrim($request->path(), '/');
        if ($path === '') {
            $path = '/';
        }

        $routesForMethod = $this->routes[$effectiveMethod] ?? [];

        $matchedRoute = null;
        $routeParams  = [];

        if (isset($routesForMethod[$path]) && $routesForMethod[$path]['pattern'] === null) {
            $matchedRoute = $routesForMethod[$path];
            $routeParams  = [];
        } else {
            foreach ($routesForMethod as $routePath => $route) {
                $pattern = $route['pattern'] ?? null;

                if ($pattern === null) {
                    if ($routePath === $path) {
                        $matchedRoute = $route;
                        $routeParams  = [];
                        break;
                    }
                    continue;
                }

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

        $coreHandler = function () use ($handler, $request, $response, $routeParams) {
            $buildArgs = function (\ReflectionFunctionAbstract $ref) use ($request, $response, $routeParams): array {
                $args          = [];
                $dynamicParams = array_values($routeParams);

                foreach ($ref->getParameters() as $param) {
                    $type = $param->getType();

                    if ($type instanceof \ReflectionNamedType) {
                        $tname = $type->getName();

                        if ($tname === Request::class) {
                            $args[] = $request;
                            continue;
                        }

                        if ($tname === Response::class) {
                            $args[] = $response;
                            continue;
                        }
                    }

                    if (!empty($dynamicParams)) {
                        $args[] = array_shift($dynamicParams);
                        continue;
                    }

                    if ($param->isDefaultValueAvailable()) {
                        $args[] = $param->getDefaultValue();
                        continue;
                    }

                    throw new \RuntimeException(
                        "No se pudo resolver el parámetro \$" . $param->getName()
                    );
                }

                return $args;
            };

            if (\is_array($handler) && \count($handler) === 2) {
                [$class, $action] = $handler;

                if (!\class_exists($class)) {
                    throw new \RuntimeException("Controller $class no existe");
                }

                $controller = new $class();

                if (!\method_exists($controller, $action)) {
                    throw new \RuntimeException("Método $action no existe en $class");
                }

                $ref  = new \ReflectionMethod($controller, $action);
                $args = $buildArgs($ref);

                $ref->invokeArgs($controller, $args);
                return;
            }

            if (\is_callable($handler)) {
                $ref  = new \ReflectionFunction(\Closure::fromCallable($handler));
                $args = $buildArgs($ref);

                $ref->invokeArgs($args);
                return;
            }

            throw new \RuntimeException('Handler de ruta no válido');
        };

        $runner = $coreHandler;

        foreach (\array_reverse($middlewares) as $name) {
            $mw = $this->middlewareMap[$name] ?? null;

            if (!$mw) {
                continue;
            }

            $next = $runner;

            $runner = function () use ($mw, $next) {
                $mw($next);
            };
        }

        $runner();

        if (!$response->isSent()) {
            $response->send();
        }
    }
}
