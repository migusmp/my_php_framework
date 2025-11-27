<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Representa la petición HTTP actual.
 *
 * Envuelve las superglobales ($_GET, $_POST, $_COOKIE, $_FILES, $_SERVER)
 * y ofrece una API más limpia para acceder a datos de entrada.
 *
 * Uso típico en un controlador:
 *
 *   use App\Http\Request;
 *
 *   public function store(Request $request): void
 *   {
 *       $email = $request->input('email');
 *       $page  = $request->query('page', 1);
 *   }
 */
final class Request
{
    /**
     * Método HTTP lógico de la petición (GET, POST, PUT, PATCH, DELETE...).
     *
     * Si el Router ha hecho override con _method, aquí ya llega el método final.
     */
    private string $method;

    /**
     * URI completa (ej: "/users/1?active=1").
     */
    private string $uri;

    /**
     * Path sin query string (ej: "/users/1").
     */
    private string $path;

    /**
     * Parámetros de query string ($_GET).
     *
     * @var array<string, mixed>
     */
    private array $query;

    /**
     * Datos del cuerpo de la petición ($_POST normalmente).
     *
     * @var array<string, mixed>
     */
    private array $body;

    /**
     * Cookies de la petición ($_COOKIE).
     *
     * @var array<string, mixed>
     */
    private array $cookies;

    /**
     * Archivos subidos ($_FILES).
     *
     * @var array<string, mixed>
     */
    private array $files;

    /**
     * Información del servidor ($_SERVER).
     *
     * @var array<string, mixed>
     */
    private array $server;

    /**
     * Constructor privado: forzamos el uso de fromGlobals().
     */
    private function __construct(
        string $method,
        string $uri,
        string $path,
        array $query,
        array $body,
        array $cookies,
        array $files,
        array $server
    ) {
        $this->method  = $method;
        $this->uri     = $uri;
        $this->path    = $path;
        $this->query   = $query;
        $this->body    = $body;
        $this->cookies = $cookies;
        $this->files   = $files;
        $this->server  = $server;
    }

    /**
     * Crea una instancia de Request a partir de las superglobales.
     *
     * @param string|null $overrideMethod Método HTTP ya normalizado por el Router
     *                                    (por ejemplo tras aplicar _method).
     */
    public static function fromGlobals(?string $overrideMethod = null): self
    {
        $server = $_SERVER ?? [];

        $uri  = $server['REQUEST_URI'] ?? '/';
        $path = \parse_url($uri, PHP_URL_PATH) ?? '/';

        $method = $overrideMethod
            ? \strtoupper($overrideMethod)
            : \strtoupper($server['REQUEST_METHOD'] ?? 'GET');

        return new self(
            $method,
            $uri,
            $path,
            $_GET   ?? [],
            $_POST  ?? [],
            $_COOKIE ?? [],
            $_FILES ?? [],
            $server
        );
    }

    /**
     * Devuelve el método HTTP lógico (GET, POST, PUT, PATCH, DELETE...).
     */
    public function method(): string
    {
        return $this->method;
    }

    /**
     * Comprueba si el método coincide con el dado.
     *
     *   if ($request->isMethod('post')) { ... }
     */
    public function isMethod(string $method): bool
    {
        return $this->method === \strtoupper($method);
    }

    /**
     * Devuelve el path sin query string (ej: "/users/1").
     */
    public function path(): string
    {
        return $this->path;
    }

    /**
     * Devuelve la URI completa (path + query string).
     */
    public function uri(): string
    {
        return $this->uri;
    }

    /**
     * Devuelve un parámetro de query ($_GET) o todos si no pasas clave.
     *
     *   $page = $request->query('page', 1);
     *
     * @return mixed
     */
    public function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->query;
        }

        return $this->query[$key] ?? $default;
    }

    /**
     * Devuelve un parámetro de cuerpo ($_POST) o todos si no pasas clave.
     *
     *   $email = $request->input('email');
     *
     * @return mixed
     */
    public function input(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->body;
        }

        return $this->body[$key] ?? $default;
    }

    /**
     * Devuelve un parámetro buscando primero en body y luego en query.
     *
     * Útil para tratar inputs de forma unificada:
     *
     *   $search = $request->value('search');
     *
     * @return mixed
     */
    public function value(string $key, mixed $default = null): mixed
    {
        if (\array_key_exists($key, $this->body)) {
            return $this->body[$key];
        }

        if (\array_key_exists($key, $this->query)) {
            return $this->query[$key];
        }

        return $default;
    }

    /**
     * Devuelve todas las entradas (body + query fusionados).
     *
     * Los valores de body tienen prioridad sobre query.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return \array_merge($this->query, $this->body);
    }

    /**
     * Devuelve una cookie por nombre o todas si no pasas clave.
     *
     * @return mixed
     */
    public function cookie(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->cookies;
        }

        return $this->cookies[$key] ?? $default;
    }

    /**
     * Devuelve los datos de archivos subidos ($_FILES).
     *
     * @return array<string, mixed>
     */
    public function files(): array
    {
        return $this->files;
    }

    /**
     * Devuelve una cabecera HTTP en minúsculas (normalizada).
     *
     *   $ua = $request->header('user-agent');
     *
     * @return mixed
     */
    public function header(string $name, mixed $default = null): mixed
    {
        $name = \strtoupper(str_replace('-', '_', $name));
        $key  = 'HTTP_' . $name;

        return $this->server[$key] ?? $default;
    }

    /**
     * Dirección IP del cliente si está disponible.
     */
    public function ip(): ?string
    {
        return $this->server['REMOTE_ADDR'] ?? null;
    }

    /**
     * User-Agent del cliente si está disponible.
     */
    public function userAgent(): ?string
    {
        return $this->server['HTTP_USER_AGENT'] ?? null;
    }

    /**
     * Devuelve el array $_SERVER completo (por si necesitas algo específico).
     *
     * @return array<string, mixed>
     */
    public function server(): array
    {
        return $this->server;
    }

    public function isPost(): bool
    {
        return $this->isMethod('POST');
    }

    public function isGet(): bool
    {
        return $this->isMethod('GET');
    }

    public function isAjax(): bool
    {
        return ($this->header('x-requested-with') === 'XMLHttpRequest');
    }

    /**
     * Devuelve solo algunas claves de body+query.
     *
     * $request->only(['email', 'password'])
     *
     * @param string[] $keys
     * @return array<string, mixed>
     */
    public function only(array $keys): array
    {
        $data = $this->all();
        $result = [];

        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                $result[$key] = $data[$key];
            }
        }

        return $result;
    }
}
