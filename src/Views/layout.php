<?php
// $styles opcional, viene del controlador/vista
$styles = $styles ?? [];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
        <title>
            <?= isset($title)
                ? htmlspecialchars($title) . ' | ' . env('APP_NAME')
                : env('APP_NAME')
?>
        </title>

    <link rel="stylesheet" href="/assets/css/app.css">
    <link rel="stylesheet" href="/assets/css/base.css">
    <link rel="stylesheet" href="/assets/css/layout.css">

    <?php foreach ($styles as $css): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($css) ?>">
    <?php endforeach; ?>
</head>

<body>
    <?php include __DIR__ . '/partials/_header.php'; ?>
    <?php include __DIR__ . '/partials/_nav.php'; ?>

    <main class="container">
        <?= $content ?? '' ?>
    </main>

    <?php include __DIR__ . '/partials/_footer.php'; ?>
</body>

</html>
