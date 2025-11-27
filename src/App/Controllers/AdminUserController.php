<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Http\Request;
use App\Http\Response;

class AdminUserController
{
    public function index(Request $request, Response $response): void
    {
        // Aquí podrías sacar usuarios de BBDD, de momento estático:
        $users = [
            ['id' => 1, 'name' => 'Miguel'],
            ['id' => 2, 'name' => 'Ana'],
        ];

        View::render('admin/users/index', [
            'title' => 'Listado de usuarios',
            'users' => $users,
        ]);
    }

    public function create(Request $request, Response $response): void
    {
        View::render('admin/users/create', [
            'title' => 'Crear usuario',
        ]);
    }

    public function store(Request $request, Response $response): void
    {
        // Aquí leerías $request->input('...') etc.
        // Para probar redirección con route() y nombre:
        $response->redirect(\App\Core\Route::route('users.index'));
    }

    public function show(Request $request, Response $response, int $id): void
    {
        // Aquí iría tu lookup real en BBDD; de momento, fake:
        $user = [
            'id'    => $id,
            'name'  => 'Usuario ' . $id,
            'email' => 'usuario' . $id . '@example.com',
        ];

        View::render('admin/users/show', [
            'title' => 'Detalle de usuario',
            'user'  => $user,
            'id'    => $id,
        ]);
    }

    public function edit(Request $request, Response $response, int $id): void
    {
        $user = [
            'id'    => $id,
            'name'  => 'Usuario ' . $id,
            'email' => 'usuario' . $id . '@example.com',
        ];

        View::render('admin/users/edit', [
            'title' => 'Editar usuario',
            'user'  => $user,
            'id'    => $id,
        ]);
    }

    public function update(Request $request, Response $response, int $id): void
    {
        echo "Actualizando usuario con ID {$id}";
    }

    public function destroy(Request $request, Response $response, int $id): void
    {
        echo "Eliminando usuario con ID {$id}";
    }
}
