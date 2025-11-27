<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Representa la respuesta HTTP que se enviará al cliente.
 *
 * Permite:
 *  - Establecer código de estado.
 *  - Añadir cabeceras.
 *  - Construir el cuerpo HTML/texto.
 *  - Hacer redirecciones sin usar header() a pelo.
 *
 * Uso típico en controladores:
 *
 *   public function index(Request $request, Response $response): void
 *   {
 *       $html = View::renderToString('home', [...]);
 *
 *       $response
 *           ->setStatus(200)
 *           ->setContent($html);
 *   }
 *
 *   public function logout(Request $request, Response $response): void
 *   {
 *       // ... limpiar sesión ...
 *       $response->redirect('/');
 *   }
 */
final class Response
{
    /**
     * Código de estado HTTP (200, 404, 302, etc.)
     */
    private int $statusCode = 200;

    /**
     * Cabeceras HTTP a enviar.
     *
     * @var array<string, array{value: string, replace: bool}>
     */
    private array $headers = [];

    /**
     * Cuerpo de la respuesta (HTML, JSON, texto plano...).
     */
    private string $content = '';

    /**
     * Flag para evitar enviar la respuesta más de una vez.
     */
    private bool $sent = false;

    /**
     * Establece el código de estado HTTP.
     */
    public function setStatus(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * Añade o reemplaza una cabecera HTTP.
     *
     * @param string $name    Nombre de la cabecera (ej: 'Content-Type')
     * @param string $value   Valor de la cabecera
     * @param bool   $replace Si debe reemplazar una cabecera previa con el mismo nombre
     */
    public function header(string $name, string $value, bool $replace = true): self
    {
        $this->headers[$name] = [
            'value'   => $value,
            'replace' => $replace,
        ];

        return $this;
    }

    /**
     * Establece el contenido completo de la respuesta.
     *
     * Sobrescribe el contenido anterior.
     */
    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Añade contenido al cuerpo de la respuesta (append).
     */
    public function write(string $chunk): self
    {
        $this->content .= $chunk;
        return $this;
    }

    /**
     * Atajo para respuestas JSON.
     *
     * Cambia automáticamente el Content-Type a application/json.
     *
     * @param mixed $data Datos que se convertirán a JSON.
     */
    public function json(mixed $data, int $statusCode = 200): self
    {
        $this->setStatus($statusCode);
        $this->header('Content-Type', 'application/json; charset=utf-8');
        $this->content = \json_encode($data, JSON_UNESCAPED_UNICODE);

        return $this;
    }

    /**
     * Redirige a otra URL.
     *
     * Envía cabecera Location y, opcionalmente, un pequeño cuerpo.
     * No hace exit aquí; el Router llamará a send(), y si ya se envió,
     * no se reenviará.
     */
    public function redirect(string $url, int $statusCode = 302): self
    {
        $this->setStatus($statusCode);
        $this->header('Location', $url);

        // Opcionalmente un cuerpo mínimo (no obligatorio, pero útil)
        $this->content = '<html><body>Redirecting to <a href="' . \htmlspecialchars($url, ENT_QUOTES) . '">' . \htmlspecialchars($url, ENT_QUOTES) . '</a></body></html>';

        return $this;
    }

    /**
     * Envía cabeceras + contenido al cliente si aún no se ha enviado.
     */
    public function send(): void
    {
        // Evitamos reenvíos
        if ($this->sent) {
            return;
        }

        // Si aún no se han enviado cabeceras, las mandamos
        if (!headers_sent()) {
            http_response_code($this->statusCode);

            foreach ($this->headers as $name => $data) {
                header($name . ': ' . $data['value'], $data['replace']);
            }
        }

        // Enviamos el cuerpo (en redirect será el HTML de "Redirecting...")
        echo $this->content;

        $this->sent = true;
    }

    /**
     * Indica si la respuesta ya fue enviada.
     */
    public function isSent(): bool
    {
        return $this->sent;
    }


    /*
     * Devuelve una vista
     * return Response::view('login')
     */
    public static function view(string $view, array $data = [], int $status = 200): self
    {
        $html = \App\Core\View::renderToString($view, $data);

        return (new self())
            ->setStatus($status)
            ->header('Content-Type', 'text/html; charset=utf-8')
            ->setContent($html);
    }

    /*
     *
     * return Response::redirectTo('/');
     */
    public static function redirectTo(string $url, int $status = 302): self
    {
        return (new self())->redirect($url, $status);
    }

    /*
    * Response::json_response($data, 200);
    */
    public static function json_response(mixed $data, int $status = 200): self
    {
        return (new self())->json($data, $status);
    }

    /*
    * Response::status(404)->view('errors/404');
    */
    public static function status(int $code): self
    {
        return (new self())->setStatus($code);
    }

    /*
    * Soporte para descarga de archivos
    */
    public static function download(string $filePath, ?string $filename = null): self
    {
        if (!\file_exists($filePath)) {
            return self::status(404)->setContent('File not found');
        }

        $filename ??= \basename($filePath);

        $response = new self();
        $response->header('Content-Type', 'application/octet-stream');
        $response->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->setContent(\file_get_contents($filePath));

        return $response;
    }

    /*
    * Método para que un middleware pueda resetear la respuesta si lo requiere
    */
    public function clear(): self
    {
        $this->statusCode = 200;
        $this->headers = [];
        $this->content = '';
        return $this;
    }

}
