<?php
/** @var array $product */
/** @var string $title */

use App\Core\Flash;

?>

<h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>

<?= Flash::render('login_success') ?>
<?= Flash::render('register_success') ?>

<p>
    <strong>ID del producto:</strong>
    <?= htmlspecialchars($product['id'], ENT_QUOTES, 'UTF-8') ?>
</p>

<p>
    <strong>Nombre:</strong>
    <?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>
</p>

<p>
    <a href="<?= url('index') ?>">Volver a la home</a>
</p>

