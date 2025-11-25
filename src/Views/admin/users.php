<?php
// views/admin/dashboard.php
// Aquí NO se hace session_start, ni require, ni router, ni dispatch.
// Solo marcas HTML/PHP que será incrustado en el layout.
?>

<h1>Panel de administración de usuarios</h1>

<p>
    Hola,
    <?= htmlspecialchars($_SESSION['user']['name'] ?? 'Admin', ENT_QUOTES, 'UTF-8') ?>
    (rol: <?= htmlspecialchars($_SESSION['user']['role'] ?? 'desconocido', ENT_QUOTES, 'UTF-8') ?>)
</p>

<ul>
    <li><a href="/admin/dashboard">Dashboard admin</a></li>
    <li><a href="/dashboard">Dashboard usuario</a></li>
    <li><a href="/admin/users">Administración de usuarios</a></li>
    <li><a href="/">Volver al inicio</a></li>
</ul>
