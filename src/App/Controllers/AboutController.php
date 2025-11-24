<?php

namespace App\Controllers;

class AboutController
{
    public function index(): void
    {
        $titulo = 'Página About';
        $descripcion = 'Este es un súper mini framework en Vanilla PHP.';

        require __DIR__ . '/../../../templates/about.php';
    }
}
