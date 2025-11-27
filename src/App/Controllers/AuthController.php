<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Flash;
use App\Core\View;
use App\Core\Validator;
use App\Http\Request;
use App\Http\Response;
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
     * @param Response    $response Respuesta HTTP donde se volcará el HTML
     * @param array       $errors   Errores de validación (opcional)
     * @param string|null $oldEmail Email previamente enviado (opcional)
     */
    private function renderRegister(Response $response, array $errors = [], ?string $oldEmail = null): void
    {
        View::renderToResponse('auth/register', [
            'title'      => 'Registro',
            'errorsData' => $errors,
            'oldEmail'   => $oldEmail ?? '',
            'styles'     => ['/assets/css/auth.css'],
        ], $response);
    }

    /**
     * Renderiza la vista del formulario de login.
     *
     * @param Response    $response Respuesta HTTP donde se volcará el HTML
     * @param array       $errors   Errores de validación (opcional)
     * @param string|null $oldEmail Email previamente enviado (opcional)
     */
    private function renderLogin(Response $response, array $errors = [], ?string $oldEmail = null): void
    {
        View::renderToResponse('auth/login', [
            'title'      => 'Login',
            'errorsData' => $errors,
            'oldEmail'   => $oldEmail ?? '',
            'styles'     => ['/assets/css/auth.css'],
        ], $response);
    }

    // ================================================================
    //                             REGISTER
    // ================================================================

    /**
     * Muestra el formulario de registro.
     */
    public function get_register(Response $response): void
    {
        $this->renderRegister($response);
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
    public function post_register(Request $request, Response $response): void
    {
        // 1) Captura de datos enviados por el formulario
        $nameRaw  = $request->input('name');
        $name     = $nameRaw !== null ? \trim((string) $nameRaw) : null;

        $emailRaw = $request->input('email');
        $email    = $emailRaw !== null
            ? \filter_var(\trim((string) $emailRaw), FILTER_SANITIZE_EMAIL)
            : null;

        // La contraseña no se sanea para no alterar su valor
        $password = $request->input('password');

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
            $this->renderRegister($response, $errors, $email);
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
                $response,
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
                $response,
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
        $userAgent = $request->userAgent();
        $ipAddress = $request->ip();

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

        // Redirección usando la Response inyectada por el Router
        $response->redirect('/');
    }

    // ================================================================
    //                               LOGIN
    // ================================================================

    /**
     * Muestra el formulario de login.
     */
    public function get_login(Response $response): void
    {
        $this->renderLogin($response);
    }

    /**
     * Procesa el formulario de login.
     *
     * Flujo:
     *  1) Validar campos (email y password)
     *  2) Autenticar credenciales mediante UserService::login()
     *  3) Crear sesión persistente + cookie y sesión en $_SESSION
     */
    public function post_login(Request $request, Response $response): void
    {
        // 1) Captura de datos de entrada
        $emailRaw = $request->input('email');
        $email    = $emailRaw !== null ? \trim((string) $emailRaw) : null;
        $email    = $email !== null ? \filter_var($email, FILTER_SANITIZE_EMAIL) : null;

        $password = $request->input('password');

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
            // Errores de validación → volvemos a login con errores
            $errors = $this->flattenErrors($validator->errors());

            // Si quieres seguir pintando la vista directamente:
            // $this->renderLogin($response, $errors, $email);
            // return;

            // 🔥 Mucho más claro: usar flash + redirect
            Flash::add('login_error', 'Correo o contraseña incorrectos.', Flash::TYPE_ERROR);

            // Podrías guardar el old input en sesión si quieres
            $response->redirect(url('login'));
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
            // Credenciales inválidas

            // Opción A: re-renderizar la vista (como tenías)
            // $this->renderLogin(
            //     $response,
            //     ['login' => 'Correo o contraseña incorrectos.'],
            //     $email
            // );
            // return;

            // Opción B (recomendada): flash + redirect
            Flash::add('login_error', 'Correo o contraseña incorrectos.', Flash::TYPE_ERROR);

            $response->redirect(url('login'));
            return;
        }

        /**
         * 3) Login correcto → creación de sesión persistente + cookie.
         */
        $userAgent = $request->userAgent();
        $ipAddress = $request->ip();

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

        // ⬅️ Aquí redirigimos a la ruta nombrada "index"
        $response->redirect(url('index'));
        return;
    }

    public function logout(Response $response): void
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

        // Mejor usar la ruta nombrada:
        $response->redirect(url('login'));
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
