<h2>Registrarse</h2>

<form action="/register" method="post">
    <?php csrf_field(); ?>

    <div class="form-group">
        <label for="name">Nombre</label>
        <input type="text" name="name" id="name" placeholder="Paco Martínez" required>

        <?php if (!empty($errorsData['name'])): ?>
            <p class="error"><?= htmlspecialchars($errorsData['name']) ?></p>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="email">Correo</label>
        <input type="email" name="email" id="email" placeholder="paco@example.com" required>

        <?php if (!empty($errorsData['email'])): ?>
            <p class="error"><?= htmlspecialchars($errorsData['email']) ?></p>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="password">Contraseña</label>
        <input type="password" name="password" id="password" placeholder="******" required>

        <?php if (!empty($errorsData['password'])): ?>
            <p class="error"><?= htmlspecialchars($errorsData['password']) ?></p>
        <?php endif; ?>
    </div>

    <?php if (!empty($errorsData['verification'])): ?>
        <p class="error"><?= htmlspecialchars($errorsData['verification']) ?></p>
    <?php endif; ?>

    <?php if (!empty($errorsData['user'])): ?>
        <p class="error"><?= htmlspecialchars($errorsData['user']) ?></p>
    <?php endif; ?>

    <button type="submit">Registrarse</button>
</form>

<nav>
    <a href="/login">¿Ya tienes cuenta? Inicia sesión</a>
    <a href="/">Volver al inicio</a>
</nav>
