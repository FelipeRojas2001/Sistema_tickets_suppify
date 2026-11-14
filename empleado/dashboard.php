<?php
/**
 * Panel inicial del Empleado.
 * Solo accesible para el rol "empleado".
 * Los datos mostrados se filtran siempre por el empleado autenticado.
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$usuario = requerir_rol(ROL_EMPLEADO);

$estados = ['pendiente' => 0, 'en_proceso' => 0, 'resuelto' => 0];
$stmt = db()->prepare('SELECT estado, COUNT(*) AS total FROM tickets WHERE id_empleado = :id GROUP BY estado');
$stmt->execute([':id' => $usuario['id_usuario']]);
foreach ($stmt->fetchAll() as $fila) {
    $estados[$fila['estado']] = (int) $fila['total'];
}
$total = array_sum($estados);

$pageTitle  = 'Dashboard del empleado';
$menuActivo = 'empleado/dashboard.php';
require __DIR__ . '/../includes/header.php';
?>
<h1>Mi panel</h1>
<p class="lead">Bienvenido, <?= e($usuario['nombre']) ?>. Aquí podrá registrar solicitudes de soporte y consultar el estado de sus tickets.</p>

<h2>Mis tickets (<?= $total ?>)</h2>
<div class="stats">
    <div class="stat stat--pendiente">
        <span class="stat__value"><?= $estados['pendiente'] ?></span>
        <span class="stat__label">Pendientes</span>
    </div>
    <div class="stat stat--proceso">
        <span class="stat__value"><?= $estados['en_proceso'] ?></span>
        <span class="stat__label">En proceso</span>
    </div>
    <div class="stat stat--resuelto">
        <span class="stat__value"><?= $estados['resuelto'] ?></span>
        <span class="stat__label">Resueltos</span>
    </div>
</div>

<section class="card">
    <h2>Próximamente</h2>
    <p>La creación y consulta de tickets estará disponible en el Avance 2.</p>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
