<?php
// src/Views/admin/users/edit.php

/** @var array|null $user */
/** @var int|null   $id */

$uid   = $user['id']    ?? $id           ?? null;
$name  = $user['name']  ?? 'Nombre demo';
$email = $user['email'] ?? 'correo@demo.test';
?>

<h1>Editar usuario<?= $uid !== null ? ' #' . htmlspecialchars((string) $uid) : '' ?></h1>

<form action="/admin/users/<?= htmlspecialchars((string) $uid) ?>" method="POST" style="max-width: 400px;">
    <!-- Spoofing de método PUT para tu router -->
    <input type="hidden" name="_method" value="PUT">

    <label for="name">Nombre:</label><br>
    <input
        type="text"
        id="name"
        name="name"
        value="<?= htmlspecialchars($name) ?>"
        required
    ><br><br>

    <label for="email">Correo:</label><br>
    <input
        type="email"
        id="email"
        name="email"
        value="<?= htmlspecialchars($email) ?>"
        required
    ><br><br>

    <button type="submit">Guardar cambios</button>
</form>

<p style="margin-top: 20px;">
    <a href="/admin/users">⬅ Volver al listado</a>
    <?php if ($uid !== null): ?>
        | <a href="/admin/users/<?= htmlspecialchars((string) $uid) ?>">👁 Ver detalle</a>
    <?php endif; ?>
</p>
