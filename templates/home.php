<?php
use App\Core\Auth;

$user = Auth::user();
?>

<?php if ($user): ?>
  <p>Bienvenido <?= htmlspecialchars($user['name']) ?></p>
  <a href="/dashboard">Dashboard</a><br>
  <a href="/logout">Cerrar sesión</a>
<?php else: ?>
  <a href="/login">Iniciar sesión</a>
<?php endif; ?>

