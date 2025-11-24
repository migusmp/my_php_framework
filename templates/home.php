<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Home - <?= APP_NAME ?></title>
</head>
<body>
    <h1><?= htmlspecialchars($saludo, ENT_QUOTES, 'UTF-8') ?></h1>

    <p>Edad: <?= $edad ?></p>
    <p><?= $mensajeEdad ?></p>

    <nav>
        <a href="/">Home</a> |
        <a href="/about">About</a>
    </nav>
</body>
</html>

