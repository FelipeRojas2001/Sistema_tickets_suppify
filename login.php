<?php
/**
 * RF001 - Inicio de sesión con número de cédula y contraseña.
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

// Si ya hay sesión válida, se envía al panel del rol.
$actual = usuario_actual();
if ($actual !== null) {
    redirigir(ruta_inicio($actual['rol']));
}

$error  = null;
$cedula = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cedula   = normalizar_cedula((string) ($_POST['cedula'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    // 1) Bloqueo temporal por exceso de intentos fallidos
    $bloqueadoHasta = (int) ($_SESSION['login_bloqueado_hasta'] ?? 0);

    if (!csrf_valido($_POST['csrf'] ?? null)) {
        $error = 'La solicitud no es válida. Recargue la página e intente de nuevo.';
    } elseif ($bloqueadoHasta > time()) {
        $minutos = (int) ceil(($bloqueadoHasta - time()) / 60);
        $error = "Demasiados intentos fallidos. Intente de nuevo en {$minutos} minuto(s).";
    } elseif ($cedula === '' || $password === '') {
        $error = 'Ingrese su número de cédula y su contraseña.';
    } elseif (!cedula_valida($cedula)) {
        $error = 'El número de cédula no tiene un formato válido.';
    } else {
        $usuario = buscar_usuario_por_cedula($cedula);

        // Hash ficticio para que el tiempo de respuesta sea similar aunque el usuario no exista
        $hashComparar = $usuario['password_hash']
            ?? '$2y$10$1LdopyNtZVxwMmgmkN5/2.mEMVCrxOBGjwUoIlK5Cf.O4ZkBqJd8O';

        $passwordOk = password_verify($password, $hashComparar);

        if ($usuario !== null && $passwordOk) {
            if ((int) $usuario['activo'] !== 1) {
                // La cuenta existe y la contraseña es correcta, pero está desactivada (RF005)
                $error = 'Su cuenta está desactivada. Contacte al administrador.';
            } else {
                // Actualiza el hash si el algoritmo por defecto de PHP cambió
                if (password_needs_rehash($usuario['password_hash'], PASSWORD_DEFAULT)) {
                    $upd = db()->prepare('UPDATE usuarios SET password_hash = :h WHERE id_usuario = :id');
                    $upd->execute([
                        ':h'  => password_hash($password, PASSWORD_DEFAULT),
                        ':id' => $usuario['id_usuario'],
                    ]);
                }

                iniciar_sesion_usuario($usuario);
                redirigir(ruta_inicio($usuario['rol']));
            }
        } else {
            $error = 'Cédula o contraseña incorrectas.';
        }

        // Contabiliza el intento fallido
        if ($error !== null) {
            $_SESSION['login_intentos'] = (int) ($_SESSION['login_intentos'] ?? 0) + 1;
            if ($_SESSION['login_intentos'] >= LOGIN_MAX_INTENTOS) {
                $_SESSION['login_bloqueado_hasta'] = time() + LOGIN_BLOQUEO_SEG;
                $_SESSION['login_intentos'] = 0;
            }
        }
    }
}

$pageTitle = 'Iniciar sesión';
$usuario = null;
require __DIR__ . '/includes/header.php';
?>
<main class="content content--center">
    <section class="card card--login">
        <div class="login__brand">
            <span class="brand brand--lg">Supp<span>ify</span></span>
            <p>Sistema de gestión de tickets de soporte técnico</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert--danger" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= e(url('login.php')) ?>" autocomplete="off" novalidate id="formLogin">
            <?= csrf_campo() ?>

            <div class="field">
                <label for="cedula">Número de cédula</label>
                <input type="text" id="cedula" name="cedula" value="<?= e($cedula) ?>"
                       inputmode="numeric" maxlength="25" required autofocus placeholder="Ej: 111111111">
            </div>

            <div class="field">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" maxlength="100" required
                       autocomplete="current-password" placeholder="Su contraseña">
            </div>

            <button type="submit" class="btn btn--primary btn--block">Ingresar</button>
        </form>

        <p class="login__hint">Si olvidó sus credenciales, solicite apoyo al administrador del sistema.</p>
    </section>
</main>
<script>
    // Validación básica en el navegador (la validación real se hace siempre en el servidor)
    document.getElementById('formLogin').addEventListener('submit', function (ev) {
        var ced = document.getElementById('cedula').value.trim();
        var pwd = document.getElementById('password').value;
        if (ced === '' || pwd === '') {
            ev.preventDefault();
            alert('Ingrese su número de cédula y su contraseña.');
        }
    });
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
