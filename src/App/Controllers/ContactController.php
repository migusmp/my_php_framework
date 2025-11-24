<?php

namespace App\Controllers;

class ContactController
{
    public function index(): void
    {
        require __DIR__ . '/../../templates/contact.php';
    }
}
