<?php
// src/Views/pages/about.php
?>
<h1><?= htmlspecialchars($title ?? 'Sobre nosotros') ?></h1>

<p>
    Bienvenido a la página de <strong>Sobre nosotros</strong> de esta aplicación creada con tu propio
    mini-framework PHP. Aquí puedes explicar quién eres, qué hace el proyecto o cualquier detalle relevante.
</p>

<section style="margin-top: 1.5rem;">
    <h2>Nuestra misión</h2>
    <p>
        Este proyecto está construido a mano, sin depender de frameworks pesados, con arquitectura limpia,
        routing propio, middlewares, sistema de plantillas, protección CSRF, sesiones avanzadas y más.
        La idea es aprender, experimentar y desarrollar un mini framework profesional desde cero.
    </p>
</section>

<section style="margin-top: 1.5rem;">
    <h2>¿Qué tecnologías usamos?</h2>
    <ul>
        <li>PHP nativo (sin frameworks externos)</li>
        <li>Ruteo avanzado con grupos, prefix y middlewares</li>
        <li>Controladores con inyección automática de parámetros</li>
        <li>Middleware pipeline estilo Laravel</li>
        <li>Sistema de vistas con plantillas</li>
        <li>Protección CSRF y sesiones seguras</li>
        <li>Autoload PSR-4</li>
    </ul>
</section>

