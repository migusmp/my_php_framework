<?php

declare(strict_types=1);

namespace App\Core;

use App\Http\Request;
use App\Http\Response;

/**
 * Router principal del mini-framework.
 *
 * Características:
 *  - Rutas GET / POST / PUT / PATCH / DELETE.
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
 *
 *  - Override de método HTTP vía campo oculto _method en formularios:
 *
 *        <form method="POST" action="/producto/1">
 *            <input type="hidden" name="_method" value="DELETE">
 *        </form>
 *
 *  - Inyección de Request y Response en controladores y closures:
 *
 *        public function show(Request $request, Response $response, string $id) { ... }
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
     *     params: string[]
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
     * Middlewares que se aplicarán a la siguiente ruta registrada.
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
     * Cambia el namespace base donde se buscan controladores.
     */
    public function setControllerNamespace(string $namespace): self
    {
        $this->controllerNamespace = rtrim($namespace, '\\') . '\\';
        return $this;
    }

    /**
     * Registrar un middleware por nombre.
     */
    public function registerMiddleware(string $name, callable $middleware): void
    {
        $this->middlewareMap[$name] = $middleware;
    }

    /**
     * Definir middlewares para la siguiente ruta declarada.
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
     */
    public function prefix(string $prefix): self
    {
        $this->currentPrefix = \rtrim(
            ($this->currentPrefix === '' ? '' : $this->currentPrefix . '/') . \ltrim($prefix, '/'),
            '/'
        );

        return $this;
    }

    /**
     * Define un grupo de rutas con un prefijo y middlewares comunes.
     */
    public function group(string $prefix, callable $callback, array $middleware = []): void
    {
        $parentPrefix      = $this->currentPrefix;
        $parentMiddlewares = $this->currentGroupMiddlewares;

        $this->currentPrefix = \rtrim($parentPrefix . $prefix, '/');
        $this->currentGroupMiddlewares = \array_merge($parentMiddlewares, $middleware);

        $callback($this);

        $this->currentPrefix           = $parentPrefix;
        $this->currentGroupMiddlewares = $parentMiddlewares;
    }

    /**
     * Registra una ruta GET.
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
     * Registra una ruta PATCH.
     */
    public function patch(string $path, callable|array|string $handler): void
    {
        $this->register('PATCH', $path, $handler);
    }

    /**
     * Registra una ruta DELETE.
     */
    public function delete(string $path, callable|array|string $handler): void
    {
        $this->register('DELETE', $path, $handler);
    }

    /**
     * Registro interno de rutas.
     */
    private function register(string $method, string $path, callable|array|string $handler): void
    {
        $fullPath = $this->currentPrefix . $path;

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
        ];

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

    /**
     * Compila una ruta con parámetros a un patrón regex.
     */
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

    /**
     * Resuelve la petición HTTP y ejecuta el handler asociado.
     */
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

        // Creamos Request y Response
        $request  = Request::fromGlobals($effectiveMethod);
        $response = new Response();

        $path = $request->path();

        $routesForMethod = $this->routes[$effectiveMethod] ?? [];

        $matchedRoute = null;
        $routeParams  = [];

        // 1) Ruta estática
        if (isset($routesForMethod[$path]) && $routesForMethod[$path]['pattern'] === null) {
            $matchedRoute = $routesForMethod[$path];
            $routeParams  = [];
        } else {
            // 2) Buscar entre rutas dinámicas
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

        /**
         * Handler central que ejecuta el controlador o callable
         * recibiendo SIEMPRE Request y Response como primeros parámetros.
         */
        $coreHandler = function () use ($handler, $request, $response, $routeParams) {
            // Caso 1: [Controller::class, 'metodo']
            if (\is_array($handler) && \count($handler) === 2) {
                [$class, $action] = $handler;

                if (!\class_exists($class)) {
                    throw new \RuntimeException("Controller $class no existe");
                }

                $controller = new $class();

                if (!\method_exists($controller, $action)) {
                    throw new \RuntimeException("Método $action no existe en $class");
                }

                $controller->{$action}($request, $response, ...$routeParams);
                return;
            }

            // Caso 2: closure / callable normal
            if (\is_callable($handler)) {
                \call_user_func_array($handler, array_merge([$request, $response], $routeParams));
                return;
            }

            throw new \RuntimeException('Handler de ruta no válido');
        };

        /**
         * Encadenar middlewares alrededor del core handler.
         */
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

        // Ejecutar pipeline
        $runner();

        // Enviar la respuesta al cliente (si no se ha enviado ya)
        if (!$response->isSent()) {
            $response->send();
        }
    }
}
