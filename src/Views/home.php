<?php
// Aquí puedes usar $user, $title o lo que pases en el controlador
?>
<h2>Bienvenido a la home</h2>

<?php if (!empty($user)): ?>
    <p>Hola, <?= htmlspecialchars($user['name']) ?> 👋</p>
<?php else: ?>
    <p>Hola, invitado. <a href="/login">Inicia sesión</a></p>
<?php endif; ?>
