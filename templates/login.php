<!doctype html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Login - <?= APP_NAME ?></title>
</head>

<body>
    <form action="login" method="post">
        <div class="form-group">
            <label for="email"></label>
            <input type="text" name="email" id="email" placeholder="Example: paco@example.com" required>

            <?php if (!empty($errorsData['email'])): ?>
                <p class="error"><?= htmlspecialchars($errorsData['email']) ?></p>
            <?php endif; ?>
        </div>
        <div class="form-group">
            <label for="password"></label>
            <input type="password" name="password" id="password" placeholder="******" required>

            <?php if (!empty($errorsData['password'])): ?>
                <p class="error"><?= htmlspecialchars($errorsData['password']) ?></p>
            <?php endif; ?>
        </div>

        <?php if (!empty($errorsData['verification'])): ?>
            <div class="form-group">
                <p class="error"><?= htmlspecialchars($errorsData['verification']) ?></p>
            </div>
        <?php endif; ?>

        <button type="submit">Iniciar sesión</button>
    </form>
    <nav>
        <a href="/register">¿No tienes cuenta? Registrate</a>
        <a href="/">Volver al inicio</a>
    </nav>
</body>

</html>
