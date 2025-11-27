<?php

namespace App\Controllers;

use App\Core\View;
use App\Http\Response;

class HomeController
{
    public function index(Response $response): void
    {
        // tu lógica de home...
        View::renderToResponse('home', [
            'title' => 'Home',
            'user'  => $_SESSION['user'] ?? null,
        ], $response);
    }

    public function show(Response $response, string $id): void
    {
        // Aquí ya tienes:
        // - $request  → la petición HTTP
        // - $response → la respuesta HTTP
        // - $id       → el {id} de la ruta /product/{id}

        // Ejemplo simple para probar:
        View::renderToResponse('product/show', [
            'title'   => 'Detalle producto',
            'product' => [
                'id'   => $id,
                'name' => 'Producto de prueba ' . $id,
            ],
        ], $response);
    }
}
