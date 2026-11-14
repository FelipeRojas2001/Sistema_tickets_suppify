<?php
/** Pie común: cierra los contenedores abiertos en header.php. */
declare(strict_types=1);
$usuario = $usuario ?? null;
?>
<?php if ($usuario): ?>
        </main>
    </div>
    <script>
        // Menú lateral desplegable en pantallas pequeñas
        (function () {
            var btn = document.getElementById('btnMenu');
            var side = document.getElementById('sidebar');
            if (btn && side) {
                btn.addEventListener('click', function () { side.classList.toggle('is-open'); });
            }
        })();
    </script>
<?php endif; ?>
</body>
</html>
