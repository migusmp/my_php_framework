<?php
// src/Views/admin/users/show.php

/** @var array|null $user */
/** @var int|null   $id */

$uid = $user['id'] ?? $id ?? null;
$name = $user['name'] ?? 'Usuario sin nombre';
$email = $user['email'] ?? 'Sin email';
?>

<h1>Detalle de usuario<?= $uid !== null ? ' #' . htmlspecialchars((string) $uid) : '' ?></h1>

<ul>
    <?php if ($uid !== null): ?>
        <li><strong>ID:</strong> <?= htmlspecialchars((string) $uid) ?></li>
    <?php endif; ?>

    <li><strong>Nombre:</strong> <?= htmlspecialchars($name) ?></li>
    <li><strong>Email:</strong> <?= htmlspecialchars($email) ?></li>
</ul>

<p style="margin-top: 20px;">
    <a href="/admin/users">⬅ Volver al listado</a>
    <?php if ($uid !== null): ?>
        | <a href="/admin/users/<?= htmlspecialchars((string) $uid) ?>/edit">✏ Editar</a>
    <?php endif; ?>
</p>
