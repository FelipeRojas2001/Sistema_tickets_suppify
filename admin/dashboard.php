<?php
/**
 * Panel inicial del Administrador.
 * Solo accesible para el rol "administrador".
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$usuario = requerir_rol(ROL_ADMIN);
$pdo = db();

// Usuarios por rol (activos / total)
$usuarios = $pdo->query(
    "SELECT r.nombre AS rol, COUNT(u.id_usuario) AS total, COALESCE(SUM(u.activo = 1), 0) AS activos
       FROM roles r
       LEFT JOIN usuarios u ON u.id_rol = r.id_rol
      GROUP BY r.id_rol, r.nombre"
)->fetchAll();

$porRol = [];
foreach ($usuarios as $fila) {
    $porRol[$fila['rol']] = $fila;
}

// Tickets por estado
$ticketsPorEstado = ['pendiente' => 0, 'en_proceso' => 0, 'resuelto' => 0];
foreach ($pdo->query('SELECT estado, COUNT(*) AS total FROM tickets GROUP BY estado')->fetchAll() as $fila) {
    $ticketsPorEstado[$fila['estado']] = (int) $fila['total'];
}
$totalTickets = array_sum($ticketsPorEstado);

$pageTitle  = 'Dashboard del administrador';
$menuActivo = 'admin/dashboard.php';
require __DIR__ . '/../includes/header.php';
?>
<h1>Panel de administración</h1>
<p class="lead">Bienvenido, <?= e($usuario['nombre']) ?>. Desde aquí podrá gestionar usuarios y supervisar los tickets del sistema.</p>

<h2>Usuarios</h2>
<div class="stats">
    <div class="stat">
        <span class="stat__value"><?= (int) ($porRol[ROL_EMPLEADO]['activos'] ?? 0) ?></span>
        <span class="stat__label">Empleados activos</span>
        <small>de <?= (int) ($porRol[ROL_EMPLEADO]['total'] ?? 0) ?> registrados</small>
    </div>
    <div class="stat">
        <span class="stat__value"><?= (int) ($porRol[ROL_TECNICO]['activos'] ?? 0) ?></span>
        <span class="stat__label">Técnicos activos</span>
        <small>de <?= (int) ($porRol[ROL_TECNICO]['total'] ?? 0) ?> registrados</small>
    </div>
    <div class="stat">
        <span class="stat__value"><?= (int) ($porRol[ROL_ADMIN]['activos'] ?? 0) ?></span>
        <span class="stat__label">Administradores</span>
        <small>de <?= (int) ($porRol[ROL_ADMIN]['total'] ?? 0) ?> registrados</small>
    </div>
</div>

<h2>Tickets (<?= $totalTickets ?>)</h2>
<div class="stats">
    <div class="stat stat--pendiente">
        <span class="stat__value"><?= $ticketsPorEstado['pendiente'] ?></span>
        <span class="stat__label">Pendientes</span>
    </div>
    <div class="stat stat--proceso">
        <span class="stat__value"><?= $ticketsPorEstado['en_proceso'] ?></span>
        <span class="stat__label">En proceso</span>
    </div>
    <div class="stat stat--resuelto">
        <span class="stat__value"><?= $ticketsPorEstado['resuelto'] ?></span>
        <span class="stat__label">Resueltos</span>
    </div>
</div>

<section class="card">
    <h2>Próximamente</h2>
    <p>La gestión de empleados, técnicos y tickets estará disponible en el Avance 2.</p>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
