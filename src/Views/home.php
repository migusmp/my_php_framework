<?php
use App\Core\Flash;

?>

<h2>Bienvenido a la home</h2>

<?= Flash::render('login_success') ?>
<?= Flash::render('register_success') ?>

<?php if (!empty($user)): ?>
    <p>Hola, <?= htmlspecialchars($user['name']) ?> 👋</p>

    <!-- 🔥 Enlace de prueba a product.show con parámetro {id} -->
    <p>
        <a href="<?= url('product.show', ['id' => 123]) ?>">
            Ver producto de prueba (ID 123)
        </a>
    </p>

<?php else: ?>
    <p>Hola, invitado. 
        <a href="<?= url('login') ?>">Inicia sesión</a>
    </p>

    <!-- 🔥 También puedes probar el product.show estando deslogueado -->
    <p>
        <a href="<?= url('product.show', ['id' => 456]) ?>">
            Ver producto de prueba (ID 456)
        </a>
    </p>
<?php endif; ?>

