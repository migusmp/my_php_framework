<!doctype html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Register - <?= APP_NAME ?></title>
</head>

<body>
    <form action="register" method="post">
        <div class="form-group">
            <label for="name"></label>
            <input type="text" name="name" id="name" placeholder="Paco Martínez" required>

            <?php if (!empty($errorsData['name'])): ?>
                <p class="error"><?= htmlspecialchars($errorsData['name']) ?></p>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="email"></label>
            <input type="text" name="email" id="email" placeholder="paco@example.com" required>

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

        <?php if (!empty($errorsData['user'])): ?>
            <div class="form-group">
                <p class="error"><?= htmlspecialchars($errorsData['user']) ?></p>
            </div>
        <?php endif; ?>

        <button type="submit">Registrarse</button>
    </form>
    <nav>
        <a href="/login">¿Ya tienes cuenta? Inicia sesión</a>
        <a href="/">Volver al inicio</a>
    </nav>
</body>

</html>
