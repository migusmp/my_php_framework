<?php

namespace App\Controllers;

use App\Services\SessionService;
use App\Services\UserService;

class AuthController
{
    private UserService $userService;
    private SessionService $sessionService;

    public function __construct()
    {
        $this->userService = new UserService();
        $this->sessionService = new SessionService();
    }

    /**
     * Muestra el formulario de Register.
     *
     * @param array       $errors   Errores de validación (opcional)
     * @param string|null $oldEmail Email previamente enviado (opcional)
     */
    private function renderRegister(array $errors = [], ?string $oldEmail = null): void
    {
        // Variables que estarán disponibles en la vista
        $errorsData = $errors;
        $oldEmail   = $oldEmail ?? '';

        require __DIR__ . '/../../../templates/register.php';
    }

    /**
     * Muestra el formulario de login.
     *
     * @param array       $errors   Errores de validación (opcional)
     * @param string|null $oldEmail Email previamente enviado (opcional)
     */
    private function renderLogin(array $errors = [], ?string $oldEmail = null): void
    {
        // Variables que estarán disponibles en la vista
        $errorsData = $errors;
        $oldEmail   = $oldEmail ?? '';

        require __DIR__ . '/../../../templates/login.php';
    }

    public function get_register(): void
    {
        $this->renderRegister();
    }

    public function post_register(): void
    {
        $name = filter_input(INPUT_POST, 'name');

        // Obtiene y sanea el valor enviado en el campo "email" desde el formulario POST.
        // FILTER_SANITIZE_EMAIL elimina caracteres no permitidos en una dirección de correo
        // para reducir riesgos de inyección y asegurar un formato seguro antes de validar.
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

        // Obtiene y sanea el valor enviado en el campo "password" desde el formulario POST.
        // FILTER_SANITIZE_SPECIAL_CHARS convierte caracteres especiales en entidades HTML,
        // evitando inyección de código (XSS) al mostrar el valor en la vista si es necesario.
        $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_SPECIAL_CHARS);

        $errors = [];

        if (!$email) {
            $errors['email'] = "El campo email es obligatorio.";
        }

        if (!$name) {
            $errors['name'] = "El campo nombre es obligatorio.";
        } elseif (!\filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "El email no tiene un formato válido.";
        }

        if (!$password) {
            $errors['password'] = "El campo contraseña es obligatorio";
        } elseif (\strlen($password) < 4) {
            $errors['password'] = "La contraseña debe de contener al menos 4 caracteres";
        }

        if (!empty($errors)) {
            $this->renderRegister($errors, $email);
            return;
        }

        // Verificar si el usuario que se va a registrar ya existe
        if ($this->userService->findUserByEmail($email)) {
            $errors['user'] = "Ya existe una cuenta con esa dirección de correo";
            $this->renderRegister($errors, $email);
            return;
        }

        // 4️⃣ Hashear la contraseña
        $hashedPassword = \password_hash($password, PASSWORD_BCRYPT);

        try {
            // Insertar en la BBDD (asegúrate de que createUser espera ya el hash)
            $userId = $this->userService->createUser($name, $email, $hashedPassword);

            // Crear sesión en BBDD
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $ipAddress = $_SERVER['REMOTE_ADDR']      ?? null;

            $token = $this->sessionService->createSession($userId, $userAgent, $ipAddress);

            // Guardar el token en una cookie segura
            \setcookie(
                'auth_token',
                $token,
                [
                    'expires'  => \time() + 60 * 60 * 24 * 7, // 7 días
                    'path'     => '/',
                    'httponly' => true,
                    'samesite' => 'Lax',
                    // 'secure' => true, // ponlo a true cuando estés en HTTPS
                ]
            );

            // Opcional: también usar $_SESSION para acceso rápido
            \session_regenerate_id(true);
            $_SESSION['user'] = [
                'id'    => $userId,
                'name'  => $name,
                'email' => $email,
            ];

            \header('Location: /');
            exit;
        } catch (\Throwable $e) {
            $errors['server'] = "Ha ocurrido un error al crear la cuenta. Inténtalo de nuevo más tarde.";
            $this->renderRegister($errors, $email);
            return;
        }
    }

    public function get_login(): void
    {
        $this->renderLogin();
    }

    public function post_login(): void
    {
        // Obtiene y sanea el valor enviado en el campo "email" desde el formulario POST.
        // FILTER_SANITIZE_EMAIL elimina caracteres no permitidos en una dirección de correo
        // para reducir riesgos de inyección y asegurar un formato seguro antes de validar.
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

        // Sanitizamos el email (formato) y eliminamos espacios laterales
        $email = $email !== null ? trim($email) : null;

        // La contraseña no hace falta sanearla, no se va a mostrar, solo verificar
        $password = filter_input(INPUT_POST, 'password');


        $errors = [];

        // Validación del email
        if (!$email) {
            $errors['email'] = "El email es obligatorio";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "El email no tiene un formato válido";
        }

        // Validación de la contraseña
        if (!$password) {
            $errors['password'] = "La contraseña es obligatoria";
        }

        // Si hay errores de validación de campos, volvemos a la vista
        if (!empty($errors)) {
            $this->renderLogin($errors, $email);
            return;
        }

        // Buscar usuario en BD
        $user = $this->userService->findUserByEmail($email);

        // Unificamos error de credenciales (usuario o password incorrectos)
        if (!$user || !password_verify($password, $user['password'])) {
            $errors['verification'] = "Correo o contraseña incorrectos.";
            $this->renderLogin($errors, $email);
            return;
        }

        // ✅ Login correcto

        $userId    = (int) $user['id'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $ipAddress = $_SERVER['REMOTE_ADDR']      ?? null;

        // Creamos sesión en tabla `sessions`
        $token = $this->sessionService->createSession($userId, $userAgent, $ipAddress);

        // Cookie con el token
        \setcookie(
            'auth_token',
            $token,
            [
                'expires'  => \time() + 60 * 60 * 24 * 7,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
                // 'secure' => true, // en HTTPS
            ]
        );

        // Opcional: también sesión nativa
        \session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'         => $userId,
            'name'       => $user['name'],
            'email'      => $user['email'],
            'created_at' => $user['created_at'],
        ];

        \header('Location: /');
        exit;
    }

    public function logout(): void
    {
        $token = $_COOKIE['auth_token'] ?? null;

        if ($token) {
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

        // Limpiar sesión nativa
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
}
