<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Core\Validator;
use App\Services\SessionService;
use App\Services\UserService;

/**
 * Controlador encargado de la autenticación del usuario:
 * registro, login y logout.
 *
 * Implementa una capa de validación reutilizable mediante
 * la clase App\Core\Validator, lo que permite mantener
 * los controladores limpios y coherentes.
 */
class AuthController
{
    private UserService $userService;
    private SessionService $sessionService;

    public function __construct()
    {
        // Servicios principales del módulo de autenticación
        $this->userService    = new UserService();
        $this->sessionService = new SessionService();
    }

    /**
     * Renderiza la vista del formulario de registro.
     * Se usa internamente para reutilizar código.
     */
    private function renderRegister(array $errors = [], ?string $oldEmail = null): void
    {
        View::render('auth/register', [
            'title'      => 'Registro',
            'errorsData' => $errors,
            'oldEmail'   => $oldEmail ?? '',
            'styles' => ['/assets/css/auth.css'],
        ]);
    }

    /**
     * Renderiza la vista del login.
     */
    private function renderLogin(array $errors = [], ?string $oldEmail = null): void
    {
        View::render('auth/login', [
            'title'      => 'Login',
            'errorsData' => $errors,
            'oldEmail'   => $oldEmail ?? '',
            'styles' => ['/assets/css/auth.css'],
        ]);
    }

    // ================================================================
    //                             REGISTER
    // ================================================================

    /** Muestra el formulario de registro */
    public function get_register(): void
    {
        $this->renderRegister();
    }

    /**
     * Procesa el formulario de registro.
     *
     * Flujo:
     * 1) Validación de campos usando Validator
     * 2) Comprobar si el usuario ya existe
     * 3) Crear usuario
     * 4) Crear sesión + cookie de autenticación
     */
    public function post_register(): void
    {
        // Capturamos los valores enviados
        $name     = \filter_input(INPUT_POST, 'name');
        $emailRaw = \filter_input(INPUT_POST, 'email');
        $email    = $emailRaw ? \filter_var($emailRaw, FILTER_SANITIZE_EMAIL) : null;
        $password = \filter_input(INPUT_POST, 'password'); // no se sanea para no modificar su valor

        /**
         * 1) VALIDACIÓN REUTILIZABLE
         *
         * Aquí usamos App\Core\Validator con sus reglas tipo:
         * - required
         * - email
         * - min:x
         * - max:x
         *
         * Esto mantiene el controlador limpio y asegura que
         * cualquier formulario pueda usar el mismo sistema.
         */
        $validator = Validator::make(
            [
                'name'     => $name,
                'email'    => $email,
                'password' => $password,
            ],
            [
                'name'     => 'required|min:2|max:50',
                'email'    => 'required|email',
                'password' => 'required|min:4',
            ]
        );

        // Si la validación falla, reenviamos los errores a la vista
        if ($validator->fails()) {
            $errors = $this->flattenErrors($validator->errors());
            $this->renderRegister($errors, $email);
            return;
        }

        // Recuperar datos ya validados y limpios
        $validated = $validator->validated();
        $name      = $validated['name'];
        $email     = $validated['email'];
        $password  = $validated['password'];

        /**
         * 2) Verificar si el email ya existe
         */
        if ($this->userService->findUserByEmail($email)) {
            $this->renderRegister(['user' => "Ya existe una cuenta con ese correo."], $email);
            return;
        }

        /**
         * 3) Registrar usuario
         */
        $hashedPassword = \password_hash($password, PASSWORD_BCRYPT);

        try {
            $userId = $this->userService->createUser($name, $email, $hashedPassword);

            /**
             * 4) Crear sesión persistente + cookie de autenticación
             */
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $ipAddress = $_SERVER['REMOTE_ADDR']      ?? null;

            // Crear sesión en la BBDD y obtener token
            $token = $this->sessionService->createSession($userId, $userAgent, $ipAddress);

            // Enviar cookie segura
            \setcookie(
                'auth_token',
                $token,
                [
                    'expires'  => \time() + 60 * 60 * 24 * 7, // 7 días
                    'path'     => '/',
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]
            );

            // También guardamos datos rápidos en $_SESSION
            \session_regenerate_id(true);
            $_SESSION['user'] = [
                'id'    => $userId,
                'name'  => $name,
                'email' => $email,
            ];

            \header('Location: /');
            exit;
        } catch (\Throwable $e) {
            // Error inesperado al guardar en BD
            $this->renderRegister(['server' => "Error al crear la cuenta. Inténtalo más tarde."], $email);
            return;
        }
    }

    // ================================================================
    //                               LOGIN
    // ================================================================

    /** Muestra el formulario de login */
    public function get_login(): void
    {
        $this->renderLogin();
    }

    /**
     * Procesa el formulario de login.
     *
     * Flujo:
     * 1) Validación
     * 2) Comprobar credenciales
     * 3) Crear sesión + cookie
     */
    public function post_login(): void
    {
        // Capturar datos enviados
        $emailRaw = \filter_input(INPUT_POST, 'email');
        $email    = $emailRaw ? \filter_var($emailRaw, FILTER_SANITIZE_EMAIL) : null;
        $email    = $email !== null ? \trim($email) : null;
        $password = \filter_input(INPUT_POST, 'password');

        /**
         * 1) VALIDACIÓN REUTILIZABLE
         */
        $validator = Validator::make(
            [
                'email'    => $email,
                'password' => $password,
            ],
            [
                'email'    => 'required|email',
                'password' => 'required',
            ]
        );

        if ($validator->fails()) {
            $errors = $this->flattenErrors($validator->errors());
            $this->renderLogin($errors, $email);
            return;
        }

        $validated = $validator->validated();
        $email     = $validated['email'];
        $password  = $validated['password'];

        /**
         * 2) Buscar usuario y verificar contraseña
         */
        $user = $this->userService->findUserByEmail($email);

        if (!$user || !\password_verify($password, $user['password'])) {
            // Error unificado para no revelar si el email existe
            $this->renderLogin(['verification' => "Correo o contraseña incorrectos."], $email);
            return;
        }

        /**
         * 3) Login correcto → crear sesión persistente y cookie
         */
        $userId    = (int) $user['id'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $ipAddress = $_SERVER['REMOTE_ADDR']      ?? null;

        $token = $this->sessionService->createSession($userId, $userAgent, $ipAddress);

        \setcookie(
            'auth_token',
            $token,
            [
                'expires'  => \time() + 60 * 60 * 24 * 7,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );

        // Sesión rápida en $_SESSION
        \session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'         => $userId,
            'name'       => $user['name'],
            'email'      => $user['email'],
            'role'       => $user['role'],
            'created_at' => $user['created_at'],
        ];

        \header('Location: /');
        exit;
    }

    // ================================================================
    //                              LOGOUT
    // ================================================================

    /**
     * Elimina la sesión del usuario tanto en BBDD como en navegador.
     */
    public function logout(): void
    {
        $token = $_COOKIE['auth_token'] ?? null;

        if ($token) {
            // Eliminar sesión persistente
            $this->sessionService->deleteSessionByToken($token);

            // Borrar cookie
            \setcookie(
                'auth_token',
                '',
                [
                    'expires'  => \time() - 3600,
                    'path'     => '/',
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]
            );
        }

        /**
         * Limpiar sesión nativa completa
         */
        $_SESSION = [];
        if (\ini_get('session.use_cookies')) {
            $params = \session_get_cookie_params();
            \setcookie(
                \session_name(),
                '',
                \time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        \session_destroy();

        \header('Location: /login');
        exit;
    }

    // ================================================================
    //                         MÉTODO AUXILIAR
    // ================================================================

    /**
     * Aplana los errores provenientes del Validator.
     *
     * Ejemplo:
     *   ['email' => ['Campo requerido', 'Formato inválido']]
     * → ['email' => 'Campo requerido']
     *
     * Esto encaja con tus vistas actuales.
     */
    private function flattenErrors(array $errors): array
    {
        $out = [];

        foreach ($errors as $field => $messages) {
            if (\is_array($messages)) {
                $out[$field] = $messages[0] ?? '';
            } else {
                $out[$field] = $messages;
            }
        }

        return $out;
    }
}
