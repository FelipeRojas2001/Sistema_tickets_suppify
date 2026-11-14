<?php
/**
 * Punto de entrada: redirige al panel del rol o al inicio de sesión.
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$usuario = usuario_actual();
redirigir($usuario ? ruta_inicio($usuario['rol']) : 'login.php');
