<?php

declare(strict_types=1);

namespace App\Http;

use App\Core\Flash;

class RedirectResponse
{
    private string $url;
    private int $status;

    public function __construct(string $url, int $status = 302)
    {
        $this->url = $url;
        $this->status = $status;
    }

    /**
     * Añade un mensaje flash a la redirección.
     */
    public function with(string $key, string $message, string $type = Flash::TYPE_INFO): self
    {
        Flash::add($key, $message, $type);
        return $this;
    }

    /**
     * Guarda el old input para repoblar formularios.
     */
    public function withInput(Request $request): self
    {
        Flash::put('_old_input', $request->all());
        return $this;
    }

    /**
     * Ejecuta la redirección.
     */
    public function send(): void
    {
        header("Location: {$this->url}", true, $this->status);
        exit;
    }
}
