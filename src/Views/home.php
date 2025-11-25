<?php
// Aquí puedes usar $user, $title o lo que pases en el controlador
use App\Core\Flash;

?>
<h2>Bienvenido a la home</h2>
<?= Flash::render('login_success') ?>
<?= Flash::render('register_success') ?>

<?php if (!empty($user)): ?>
    <p>Hola, <?= htmlspecialchars($user['name']) ?> 👋</p>
<?php else: ?>
    <p>Hola, invitado. <a href="/login">Inicia sesión</a></p>
<?php endif; ?>
