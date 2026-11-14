<?php
/** Vista 403: se carga desde requerir_rol() cuando el rol no tiene permiso. */
declare(strict_types=1);

$usuario = usuario_actual();
$pageTitle = 'Acceso denegado';
$inicio = $usuario ? ruta_inicio($usuario['rol']) : 'login.php';

require __DIR__ . '/header.php';
?>
<?php if (!$usuario): ?><main class="content content--center"><?php endif; ?>
    <section class="card card--denied">
        <h1>403 &middot; Acceso denegado</h1>
        <p>No tiene permisos para acceder a este módulo con su perfil actual.</p>
        <a class="btn btn--primary" href="<?= e(url($inicio)) ?>">Volver a mi panel</a>
    </section>
<?php if (!$usuario): ?></main><?php endif; ?>
<?php require __DIR__ . '/footer.php'; ?>
