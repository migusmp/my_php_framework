<?php

return [
    'name'     => env('APP_NAME', 'migus framework'),
    'host'     => env('DB_HOST', '127.0.0.1'),
    'port'     => env('DB_PORT', 3306),
    'dbname'     => env('DB_DATABASE', 'php_vanilla_server'),
    'user'     => env('DB_USERNAME', 'root'),
    'password' => env('DB_PASSWORD', ''),
    'charset'  => 'utf8mb4',
    'audit'    => true,
];
