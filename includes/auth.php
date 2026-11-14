<?php
/**
 * Sesiones, autenticación y control de acceso basado en roles (RF001, RNF001, RNF002).
 * Se incluye al inicio de TODAS las páginas del sistema.
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

/* ---------------------------------------------------------------------
 * Constantes
 * ------------------------------------------------------------------- */
const ROL_ADMIN    = 'administrador';
const ROL_EMPLEADO = 'empleado';
const ROL_TECNICO  = 'tecnico';

const LOGIN_MAX_INTENTOS = 5;      // intentos fallidos permitidos
const LOGIN_BLOQUEO_SEG  = 300;    // 5 minutos de bloqueo
const SESION_INACTIVIDAD = 1800;   // 30 minutos sin actividad cierran la sesión

/**
 * URL base del proyecto (por ejemplo "/suppify"), calculada automáticamente
 * para que funcione sin importar el nombre de la carpeta dentro de htdocs.
 */
$__raiz = str_replace('\\', '/', (string) realpath(__DIR__ . '/..'));
$__doc  = str_replace('\\', '/', (string) realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? '')));
if ($__doc !== '' && stripos($__raiz, $__doc) === 0) {
    define('BASE_URL', rtrim(substr($__raiz, strlen($__doc)), '/'));
} else {
    define('BASE_URL', '/suppify');
}
unset($__raiz, $__doc);

/* ---------------------------------------------------------------------
 * Sesión segura
 * ------------------------------------------------------------------- */
if (session_status() === PHP_SESSION_NONE) {
    session_name('SUPPIFYSESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    session_start();
}

// Cabeceras de seguridad básicas y sin caché (evita ver páginas privadas con el botón "atrás").
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

/* ---------------------------------------------------------------------
 * Utilidades generales
 * ------------------------------------------------------------------- */

/** Escapa texto para imprimirlo en HTML (previene XSS). */
function e(?string $texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Genera una URL absoluta dentro del proyecto. */
function url(string $ruta = ''): string
{
    return BASE_URL . '/' . ltrim($ruta, '/');
}

/** Redirige a una ruta del proyecto y termina la ejecución. */
function redirigir(string $ruta): never
{
    header('Location: ' . url($ruta));
    exit;
}

/** Quita espacios y guiones de la cédula y valida su formato. */
function normalizar_cedula(string $cedula): string
{
    return preg_replace('/[\s\-]+/', '', trim($cedula)) ?? '';
}

function cedula_valida(string $cedula): bool
{
    return (bool) preg_match('/^[0-9A-Za-z]{5,20}$/', $cedula);
}

/** Etiqueta legible del rol. */
function etiqueta_rol(string $rol): string
{
    return match ($rol) {
        ROL_ADMIN    => 'Administrador',
        ROL_EMPLEADO => 'Empleado',
        ROL_TECNICO  => 'Técnico',
        default      => ucfirst($rol),
    };
}

/** Página de inicio (dashboard) según el rol. */
function ruta_inicio(string $rol): string
{
    return match ($rol) {
        ROL_ADMIN    => 'admin/dashboard.php',
        ROL_EMPLEADO => 'empleado/dashboard.php',
        ROL_TECNICO  => 'tecnico/dashboard.php',
        default      => 'login.php',
    };
}

/* ---------------------------------------------------------------------
 * Mensajes flash (se muestran una sola vez)
 * ------------------------------------------------------------------- */
function flash(string $tipo, string $mensaje): void
{
    $_SESSION['flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

function flash_obtener(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $f;
}

/* ---------------------------------------------------------------------
 * Protección CSRF
 * ------------------------------------------------------------------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valido(?string $token): bool
{
    return is_string($token) && isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

/* ---------------------------------------------------------------------
 * Autenticación
 * ------------------------------------------------------------------- */

/** Busca un usuario (con el nombre de su rol) por cédula. */
function buscar_usuario_por_cedula(string $cedula): ?array
{
    $stmt = db()->prepare(
        'SELECT u.id_usuario, u.cedula, u.nombre, u.correo, u.password_hash, u.activo, r.nombre AS rol
           FROM usuarios u
           INNER JOIN roles r ON r.id_rol = u.id_rol
          WHERE u.cedula = :cedula
          LIMIT 1'
    );
    $stmt->execute([':cedula' => $cedula]);
    $u = $stmt->fetch();
    return $u === false ? null : $u;
}

/** Marca la sesión como autenticada (regenera el ID para evitar fijación de sesión). */
function iniciar_sesion_usuario(array $usuario): void
{
    session_regenerate_id(true);
    $_SESSION['id_usuario'] = (int) $usuario['id_usuario'];
    $_SESSION['ultima_actividad'] = time();
    unset($_SESSION['login_intentos'], $_SESSION['login_bloqueado_hasta']);
}

/** Destruye por completo la sesión. */
function cerrar_sesion(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], (bool) $p['secure'], (bool) $p['httponly']);
    }
    session_destroy();
}

/**
 * Devuelve el usuario autenticado consultando la BD en cada petición.
 * Así, si el administrador desactiva una cuenta, la sesión deja de ser válida de inmediato.
 */
function usuario_actual(): ?array
{
    static $cache = false;
    if ($cache !== false) {
        return $cache;
    }

    if (empty($_SESSION['id_usuario'])) {
        return $cache = null;
    }

    // Cierre por inactividad
    if (isset($_SESSION['ultima_actividad']) && (time() - (int) $_SESSION['ultima_actividad']) > SESION_INACTIVIDAD) {
        cerrar_sesion();
        session_start();
        flash('warning', 'Su sesión expiró por inactividad. Inicie sesión nuevamente.');
        return $cache = null;
    }

    $stmt = db()->prepare(
        'SELECT u.id_usuario, u.cedula, u.nombre, u.correo, u.activo, r.nombre AS rol
           FROM usuarios u
           INNER JOIN roles r ON r.id_rol = u.id_rol
          WHERE u.id_usuario = :id
          LIMIT 1'
    );
    $stmt->execute([':id' => $_SESSION['id_usuario']]);
    $u = $stmt->fetch();

    if ($u === false || (int) $u['activo'] !== 1) {
        cerrar_sesion();
        session_start();
        flash('danger', 'Su cuenta no está disponible. Contacte al administrador.');
        return $cache = null;
    }

    $_SESSION['ultima_actividad'] = time();
    return $cache = $u;
}

/** Exige que exista una sesión válida; si no, redirige al login. */
function requerir_login(): array
{
    $usuario = usuario_actual();
    if ($usuario === null) {
        redirigir('login.php');
    }
    return $usuario;
}

/**
 * Exige sesión válida Y que el rol del usuario esté entre los permitidos (RNF001).
 * Si el rol no corresponde, responde 403 y muestra la pantalla de acceso denegado.
 */
function requerir_rol(string ...$rolesPermitidos): array
{
    $usuario = requerir_login();

    if (!in_array($usuario['rol'], $rolesPermitidos, true)) {
        http_response_code(403);
        $pageTitle = 'Acceso denegado';
        require __DIR__ . '/acceso_denegado_vista.php';
        exit;
    }

    return $usuario;
}

/* ---------------------------------------------------------------------
 * Menú lateral por rol (las opciones sin URL llegan en los próximos avances)
 * ------------------------------------------------------------------- */
function menu_por_rol(string $rol): array
{
    return match ($rol) {
        ROL_ADMIN => [
            ['texto' => 'Dashboard',          'url' => 'admin/dashboard.php', 'avance' => null],
            ['texto' => 'Gestionar empleados', 'url' => null, 'avance' => 'Avance 2'],
            ['texto' => 'Gestionar técnicos',  'url' => null, 'avance' => 'Avance 2'],
            ['texto' => 'Gestionar tickets',   'url' => null, 'avance' => 'Avance 2'],
        ],
        ROL_EMPLEADO => [
            ['texto' => 'Dashboard',        'url' => 'empleado/dashboard.php', 'avance' => null],
            ['texto' => 'Crear ticket',     'url' => null, 'avance' => 'Avance 2'],
            ['texto' => 'Mis tickets',      'url' => null, 'avance' => 'Avance 2'],
        ],
        ROL_TECNICO => [
            ['texto' => 'Dashboard',          'url' => 'tecnico/dashboard.php', 'avance' => null],
            ['texto' => 'Tickets disponibles', 'url' => null, 'avance' => 'Avance 3'],
            ['texto' => 'Mis tickets asignados', 'url' => null, 'avance' => 'Avance 3'],
        ],
        default => [],
    };
}
