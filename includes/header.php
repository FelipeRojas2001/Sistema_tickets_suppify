<?php
/**
 * Cabecera común. Variables esperadas:
 *   $pageTitle (string)  título de la página
 *   $usuario   (array|null) usuario autenticado; si es null se muestra el diseño sin menú
 *   $menuActivo (string|null) url del menú que debe resaltarse
 */
declare(strict_types=1);

$pageTitle  = $pageTitle ?? 'Suppify';
$usuario    = $usuario ?? null;
$menuActivo = $menuActivo ?? null;
$flash      = flash_obtener();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | Suppify</title>
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body class="<?= $usuario ? 'app' : 'publico' ?>">
<?php if ($usuario): ?>
    <header class="topbar">
        <div class="topbar__brand">
            <button type="button" class="topbar__toggle" id="btnMenu" aria-label="Mostrar u ocultar menú">&#9776;</button>
            <span class="brand">Supp<span>ify</span></span>
        </div>
        <div class="topbar__user">
            <div class="topbar__info">
                <strong><?= e($usuario['nombre']) ?></strong>
                <small><?= e(etiqueta_rol($usuario['rol'])) ?></small>
            </div>
            <form method="post" action="<?= e(url('logout.php')) ?>" class="inline-form">
                <?= csrf_campo() ?>
                <button type="submit" class="btn btn--outline btn--sm">Cerrar sesión</button>
            </form>
        </div>
    </header>

    <div class="layout">
        <aside class="sidebar" id="sidebar">
            <nav>
                <ul>
                    <?php foreach (menu_por_rol($usuario['rol']) as $item): ?>
                        <li>
                            <?php if ($item['url'] !== null): ?>
                                <a href="<?= e(url($item['url'])) ?>"
                                   class="<?= $menuActivo === $item['url'] ? 'is-active' : '' ?>"><?= e($item['texto']) ?></a>
                            <?php else: ?>
                                <span class="is-disabled" title="Disponible en el <?= e((string) $item['avance']) ?>">
                                    <?= e($item['texto']) ?> <em class="tag"><?= e((string) $item['avance']) ?></em>
                                </span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        </aside>
        <main class="content">
<?php endif; ?>
<?php if ($flash): ?>
            <div class="alert alert--<?= e($flash['tipo']) ?>" role="alert"><?= e($flash['mensaje']) ?></div>
<?php endif; ?>
