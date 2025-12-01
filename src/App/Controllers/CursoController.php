<?php

namespace App\Controllers;

use App\Core\View;

class CursoController
{
    public function index()
    {
        View::render('/DAW/cliente/index');
    }
}
