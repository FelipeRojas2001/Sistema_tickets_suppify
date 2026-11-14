<?php
/**
 * Cierre de sesión. Solo se acepta por POST con token CSRF
 * (evita que un sitio externo cierre la sesión del usuario).
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valido($_POST['csrf'] ?? null)) {
    cerrar_sesion();
    session_start();
    flash('success', 'Sesión cerrada correctamente.');
    redirigir('login.php');
}

// Cualquier otro acceso (GET) simplemente regresa al inicio.
redirigir('index.php');
