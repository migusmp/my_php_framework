<?php

/**
 * Autocargador de clases personalizado.
 *
 * Esta función se ejecuta automáticamente cada vez que PHP
 * necesita una clase que aún no ha sido cargada.
 */
spl_autoload_register(function (string $class) {

    // Prefijo que deben tener todas las clases del proyecto
    // Ej: App\Controllers\HomeController
    $prefix  = 'App\\';

    // Carpeta base donde están las clases
    // src/App/
    $baseDir = __DIR__ . '/App/';

    /**
     * 1. Verificar si la clase pertenece a nuestro proyecto.
     *
     * Si no empieza por "App\", la ignoramos porque puede ser
     * una clase interna de PHP u otra librería.
     */
    if (\strncmp($prefix, $class, \strlen($prefix)) !== 0) {
        return; // no hacemos nada
    }

    /**
     * 2. Obtener la parte de la clase que va después del prefijo.
     *
     * Ejemplo:
     *  Clase: App\Controllers\HomeController
     *  Resultado: Controllers\HomeController
     */
    $relative = \substr($class, \strlen($prefix));

    /**
     * 3. Convertir la clase en una ruta de archivo.
     *
     * Reemplazamos los "\" por "/" y añadimos ".php"
     *
     * Ej:
     *   Controllers\HomeController
     *   → Controllers/HomeController.php
     *   → /ruta/src/App/Controllers/HomeController.php
     */
    $file = $baseDir . \str_replace('\\', '/', $relative) . '.php';

    /**
     * 4. Incluir el archivo si existe.
     */
    if (\is_file($file)) {
        require $file;
    }
});

require_once __DIR__ . '/helpers.php';
