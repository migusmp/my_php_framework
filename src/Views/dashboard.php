<?php
// Aquí se asume que el usuario está autenticado
?>
<h2>Dashboard</h2>

<p>Este es tu panel, <?= htmlspecialchars($user['name'] ?? 'Usuario') ?>.</p>
<p>Aquí pondrás estadísticas, últimas acciones, etc.</p>
