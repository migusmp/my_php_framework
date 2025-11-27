<?php
// Aquí puedes usar $user si lo pasas desde el controlador
?>
<nav class="site-nav">
    <a href="/">Inicio</a>
    <?php if (!empty($user)): ?>
        <a href="/dashboard">Dashboard</a>
    <a href="<?= url('logout') ?>">Cerrar sesión (<?= htmlspecialchars($user['name'] ?? '') ?>)</a>
    <?php else: ?>
        <a href="/login">Iniciar sesión</a>
    <?php endif; ?>
</nav>

