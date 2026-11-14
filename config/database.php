<?php
/**
 * Conexión a la base de datos mediante PDO (RNF007: capa separada).
 * Valores por defecto de XAMPP: usuario root sin contraseña.
 */
declare(strict_types=1);

const DB_HOST    = '';
const DB_PORT    = '3306';
const DB_NAME    = 'suppify';
const DB_USER    = 'root';
const DB_PASS    = '';
const DB_CHARSET = 'utf8mb4';

/**
 * Devuelve una única instancia de PDO (patrón singleton simple).
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', DB_HOST, DB_PORT, DB_NAME, DB_CHARSET);

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, 
        ]);
    } catch (PDOException $e) {
        // No se expone información sensible al usuario; se registra en el log de PHP.
        error_log('[Suppify] Error de conexión a la BD: ' . $e->getMessage());
        http_response_code(500);
        exit('No fue posible conectar con la base de datos. Verifique que MySQL esté activo en XAMPP '
           . 'y que la base de datos "suppify" haya sido importada.');
    }

    return $pdo;
}
