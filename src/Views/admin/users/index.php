<?php
// src/Views/admin/users/index.php
?>
<h1><?= htmlspecialchars($title ?? 'Listado de usuarios') ?></h1>

<table border="1" cellpadding="8" cellspacing="0">
    <thead>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Acciones</th>
        </tr>
    </thead>

    <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?= htmlspecialchars($user['id']) ?></td>
                <td><?= htmlspecialchars($user['name']) ?></td>
                <td>
                    <a href="/admin/users/<?= $user['id'] ?>">Ver</a>
                    |
                    <a href="/admin/users/<?= $user['id'] ?>/edit">Editar</a>
                    |
                    <form action="/admin/users/<?= $user['id'] ?>" method="POST" style="display:inline;">
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit">Eliminar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<p style="margin-top: 20px;">
    <a href="/admin/users/create">➕ Crear nuevo usuario</a>
</p>
