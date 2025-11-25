<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Flash;
use App\Core\View;
use App\Core\Validator;
use App\Services\SessionService;
use App\Services\UserService;
use App\Models\User;
use App\Security\Csrf;

/**
 * Controlador responsable de la autenticación de usuarios.
 *
 * Gestiona:
 *  - Registro (alta de cuenta)
 *  - Login (inicio de sesión)
 *  - Logout (cierre de sesión)
 *
 * Se apoya en:
 *  - UserService para la lógica de negocio de usuarios
 *  - SessionService para la gestión de sesiones persistentes
 *  - Validator para la validación reutilizable de formularios
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
     *
     * @param array       $errors   Errores de validación (opcional)
     * @param string|null $oldEmail Email previamente enviado (opcional)
     */
    private function renderRegister(array $errors = [], ?string $oldEmail = null): void
    {
        View::render('auth/register', [
            'title'      => 'Registro',
            'errorsData' => $errors,
            'oldEmail'   => $oldEmail ?? '',
            'styles'     => ['/assets/css/auth.css'],
        ]);
    }

    /**
     * Renderiza la vista del formulario de login.
     *
     * @param array       $errors   Errores de validación (opcional)
     * @param string|null $oldEmail Email previamente enviado (opcional)
     */
    private function renderLogin(array $errors = [], ?string $oldEmail = null): void
    {
        View::render('auth/login', [
            'title'      => 'Login',
            'errorsData' => $errors,
            'oldEmail'   => $oldEmail ?? '',
            'styles'     => ['/assets/css/auth.css'],
        ]);
    }

    // ================================================================
    //                             REGISTER
    // ================================================================

    /**
     * Muestra el formulario de registro.
     */
    public function get_register(): void
    {
        $this->renderRegister();
    }

    /**
     * Procesa el formulario de registro.
     *
     * Flujo:
     *  1) Obtener y validar datos de entrada
     *  2) Comprobar si el email ya está registrado
     *  3) Crear usuario mediante UserService
     *  4) Crear sesión persistente + cookie de autenticación
     */
    public function post_register(): void
    {
        // 1) Captura de datos brutos enviados por el formulario
        $name     = \filter_input(INPUT_POST, 'name');
        $emailRaw = \filter_input(INPUT_POST, 'email');
        $email    = $emailRaw ? \filter_var($emailRaw, FILTER_SANITIZE_EMAIL) : null;
        // La contraseña no se sanea para no alterar su valor
        $password = \filter_input(INPUT_POST, 'password');

        /**
         * 2) Validación de datos mediante el validador reutilizable.
         *
         * Reglas:
         *  - name: requerido, longitud 2-50
         *  - email: requerido, formato email
         *  - password: requerido, mínimo 4 caracteres
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

        if ($validator->fails()) {
            // Normalizamos la estructura de errores para encajar con las vistas
            $errors = $this->flattenErrors($validator->errors());
            $this->renderRegister($errors, $email);
            return;
        }

        // Datos ya validados y normalizados
        $validated = $validator->validated();
        $name      = $validated['name'];
        $email     = $validated['email'];
        $password  = $validated['password'];

        /**
         * 3) Verificar si ya existe un usuario con ese correo.
         *
         * UserService::findUserByEmail() devuelve:
         *  - User|null
         */
        $existingUser = $this->userService->findUserByEmail($email);
        if ($existingUser instanceof User) {
            $this->renderRegister(
                ['user' => 'Ya existe una cuenta con ese correo.'],
                $email
            );
            return;
        }

        /**
         * 4) Registrar usuario mediante el servicio.
         *
         *  - El servicio se encarga de:
         *      * validar reglas de negocio adicionales
         *      * hashear la contraseña
         *      * delegar la inserción al UserRepository
         */
        try {
            $user = $this->userService->createUser($name, $email, $password);
        } catch (\Throwable $e) {
            // En caso de error inesperado en BBDD/servicio, no exponemos detalles.
            $this->renderRegister(
                ['server' => 'Error al crear la cuenta. Inténtalo de nuevo más tarde.'],
                $email
            );
            return;
        }

        /**
         * 5) Crear sesión persistente + cookie de autenticación.
         *
         *  - Se almacena un token en la tabla de sesiones
         *  - Se envía dicho token en una cookie segura (httponly, samesite)
         *  - Además, se mantiene una sesión "rápida" en $_SESSION
         */
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $ipAddress = $_SERVER['REMOTE_ADDR']      ?? null;

        // Crear sesión en BBDD y obtener token
        $token = $this->sessionService->createSession($user->id, $userAgent, $ipAddress);

        // Enviar cookie con el token de sesión persistente
        \setcookie(
            'auth_token',
            $token,
            [
                'expires'  => \time() + 60 * 60 * 24 * 7, // 7 días
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
                // 'secure' => true, // habilitar en producción con HTTPS
            ]
        );

        // Refrescamos el ID de sesión y guardamos información básica
        \session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'         => $user->id,
            'name'       => $user->name,
            'email'      => $user->email,
            'role'       => $user->role,
            'created_at' => $user->created_at,
        ];

        Flash::add('register_success', '¡Has sido registrado con éxito!', Flash::TYPE_SUCCESS);

        \header('Location: /');
        exit;
    }

    // ================================================================
    //                               LOGIN
    // ================================================================

    /**
     * Muestra el formulario de login.
     */
    public function get_login(): void
    {
        $this->renderLogin();
    }

    /**
     * Procesa el formulario de login.
     *
     * Flujo:
     *  1) Validar campos (email y password)
     *  2) Autenticar credenciales mediante UserService::login()
     *  3) Crear sesión persistente + cookie y sesión en $_SESSION
     */
    public function post_login(): void
    {
        // 1) Captura de datos de entrada
        $emailRaw = \filter_input(INPUT_POST, 'email');
        $email    = $emailRaw ? \filter_var($emailRaw, FILTER_SANITIZE_EMAIL) : null;
        $email    = $email !== null ? \trim($email) : null;
        $password = \filter_input(INPUT_POST, 'password');

        /**
         * Validación reutilizable:
         *  - email: requerido, formato válido
         *  - password: requerido
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
         * 2) Autenticación de usuario.
         *
         *  - UserService::login() devuelve:
         *      * User si las credenciales son correctas
         *      * null si son incorrectas
         */
        $user = $this->userService->login($email, $password);

        if (!$user instanceof User) {
            Flash::add('login_error', 'Correo o contraseña incorrectos.', Flash::TYPE_ERROR);
            \header('Location: /login');
            exit;
        }
        /**
         * 3) Login correcto → creación de sesión persistente + cookie.
         */
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $ipAddress = $_SERVER['REMOTE_ADDR']      ?? null;

        $token = $this->sessionService->createSession($user->id, $userAgent, $ipAddress);

        \setcookie(
            'auth_token',
            $token,
            [
                'expires'  => \time() + 60 * 60 * 24 * 7, // 7 días
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
                // 'secure' => true, // habilitar en producción con HTTPS
            ]
        );

        // Sesión nativa con datos mínimos del usuario
        \session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'         => $user->id,
            'name'       => $user->name,
            'email'      => $user->email,
            'role'       => $user->role,
            'created_at' => $user->created_at,
        ];

        Flash::add(
            'login_success',
            '¡Has iniciado sesión correctamente!',
            Flash::TYPE_SUCCESS
        );

        Csrf::regenerateToken();

        \header('Location: /');
        exit;
    }

    // ================================================================
    //                              LOGOUT
    // ================================================================

    /**
     * Cierra la sesión del usuario:
     *  - Elimina la sesión persistente en BBDD
     *  - Elimina la cookie de autenticación
     *  - Limpia y destruye la sesión nativa de PHP
     */
    public function logout(): void
    {
        $token = $_COOKIE['auth_token'] ?? null;

        if ($token) {
            // Eliminar sesión persistente en la BBDD
            $this->sessionService->deleteSessionByToken($token);

            // Borrar cookie de autenticación
            \setcookie(
                'auth_token',
                '',
                [
                    'expires'  => \time() - 3600,
                    'path'     => '/',
                    'httponly' => true,
                    'samesite' => 'Lax',
                    // 'secure' => true, // habilitar en producción con HTTPS
                ]
            );
        }

        // Limpiar completamente la sesión nativa
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

        Csrf::regenerateToken();

        \header('Location: /login');
        exit;
    }

    // ================================================================
    //                         MÉTODO AUXILIAR
    // ================================================================

    /**
     * Aplana los errores del Validator para simplificar su uso en las vistas.
     *
     * Ejemplo de entrada:
     *  [
     *      'email' => ['Campo requerido', 'Formato inválido'],
     *      'password' => ['Campo requerido']
     *  ]
     *
     * Ejemplo de salida:
     *  [
     *      'email'    => 'Campo requerido',
     *      'password' => 'Campo requerido'
     *  ]
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
