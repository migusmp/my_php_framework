<?php
// src/Views/admin/users/create.php
?>
<h1><?= htmlspecialchars($title ?? 'Crear usuario') ?></h1>

<form action="/admin/users" method="POST" style="max-width: 400px;">
    <label for="name">Nombre:</label><br>
    <input type="text" id="name" name="name" required><br><br>

    <label for="email">Correo:</label><br>
    <input type="email" id="email" name="email" required><br><br>

    <button type="submit">Crear usuario</button>
</form>

<p style="margin-top: 20px;">
    <a href="/admin/users">⬅ Volver al listado</a>
</p>

