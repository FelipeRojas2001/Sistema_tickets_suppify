# Suppify – Avance 1 (estructura, base de datos y autenticación)

## Instalación en XAMPP
1. Iniciar **Apache** y **MySQL** desde el panel de XAMPP.
2. Copiar la carpeta `suppify` dentro de `C:\xampp\htdocs\`.
3. Utiliza el archivo `/database/suppify.sql` para la creación de las base de datos y las tablas con sus datos de prueba.
4. Abrir http://localhost/suppify/
5. Si su MySQL tiene otra contraseña/puerto, ajustar `config/database.php`.

## Usuarios de prueba
| Rol                   | Cédula    | Contraseña    |
|-----------------------|-----------|---------------|
| Administrador         | 123456789 | Admin123*     |
| Empleado              | 102030405 | Empleado123*  |
| Técnico               | 112131415 | Tecnico123*   |
| Técnico(desactivado)  | 122232425 | Tecnico123*   |

## Estructura
- `config/database.php` conexión PDO · `includes/auth.php` sesión, roles, CSRF
- `login.php`, `logout.php`, `index.php` · `admin/`, `empleado/`, `tecnico/` dashboards
- `assets/css/style.css` estilos (sin librerías externas, funciona sin internet)
- `database/suppify.sql` script de la base de datos

## Pruebas rápidas para las capturas
1. Login válido con cada rol → redirige a su panel (CP-01).
2. Login con contraseña incorrecta → mensaje de error (CP-02).
3. Con sesión de empleado abrir `/suppify/admin/dashboard.php` → 403 (CP-15).
4. En phpMyAdmin, tabla `usuarios`: `password_hash` empieza con `$2y$` (RNF002).
5. `UPDATE usuarios SET activo = 0 WHERE cedula='102030405';` → el empleado ya no entra (CP-06).