<?php
/**
 * Panel inicial del Técnico (Avance 1).
 * Solo accesible para el rol "tecnico" (RNF001).
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$usuario = requerir_rol(ROL_TECNICO);
$pdo = db();

// Tickets disponibles: pendientes y sin técnico asignado (RF010)
$disponibles = (int) $pdo->query(
    "SELECT COUNT(*) FROM tickets WHERE estado = 'pendiente' AND id_tecnico IS NULL"
)->fetchColumn();

// Tickets asignados al técnico autenticado, por estado (RF014)
$asignados = ['en_proceso' => 0, 'resuelto' => 0];
$stmt = $pdo->prepare('SELECT estado, COUNT(*) AS total FROM tickets WHERE id_tecnico = :id GROUP BY estado');
$stmt->execute([':id' => $usuario['id_usuario']]);
foreach ($stmt->fetchAll() as $fila) {
    if (isset($asignados[$fila['estado']])) {
        $asignados[$fila['estado']] = (int) $fila['total'];
    }
}

$pageTitle  = 'Dashboard del técnico';
$menuActivo = 'tecnico/dashboard.php';
require __DIR__ . '/../includes/header.php';
?>
<h1>Panel del técnico</h1>
<p class="lead">Bienvenido, <?= e($usuario['nombre']) ?>. Aquí podrá asumir tickets, registrar seguimientos y resolver solicitudes.</p>

<div class="stats">
    <div class="stat stat--pendiente">
        <span class="stat__value"><?= $disponibles ?></span>
        <span class="stat__label">Tickets disponibles</span>
        <small>pendientes y sin técnico</small>
    </div>
    <div class="stat stat--proceso">
        <span class="stat__value"><?= $asignados['en_proceso'] ?></span>
        <span class="stat__label">Mis tickets en proceso</span>
    </div>
    <div class="stat stat--resuelto">
        <span class="stat__value"><?= $asignados['resuelto'] ?></span>
        <span class="stat__label">Mis tickets resueltos</span>
    </div>
</div>

<section class="card">
    <h2>Próximamente</h2>
    <p>La atención de tickets y el registro de seguimientos estarán disponibles en el Avance 3.</p>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
