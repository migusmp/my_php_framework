<h2>Iniciar sesión</h2>

<form action="/login" method="post">
    <div class="form-group">
        <label for="email">Correo electrónico</label>
        <input
            type="text"
            name="email"
            id="email"
            placeholder="Example: paco@example.com"
            required
        >

        <?php if (!empty($errorsData['email'])): ?>
            <p class="error"><?= htmlspecialchars($errorsData['email']) ?></p>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="password">Contraseña</label>
        <input
            type="password"
            name="password"
            id="password"
            placeholder="******"
            required
        >

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
    <a href="/register">¿No tienes cuenta? Regístrate</a>
    <a href="/">Volver al inicio</a>
</nav>
