<?php

namespace App\Services;

class UserService
{
    public function isMayorEdad(int $age): bool
    {
        return $age >= 18;
    }

    public function mensajeEdad(int $age): string
    {
        return $this->isMayorEdad($age)
            ? 'Eres mayor de edad'
            : 'Eres menor de edad';
    }
}
