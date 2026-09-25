<?php
declare(strict_types=1);

/**
 * Runner de migraciones de base de datos de IPStream.
 *
 * Aplica en orden alfabetico los archivos .sql de php/migrations/ que aun no
 * hayan sido ejecutados. Lleva registro en la tabla `schema_migrations`, por lo
 * que es seguro ejecutarlo cuantas veces se quiera.
 *
 * --- Uso web (produccion) ---
 *   https://ipstream.cl/migrate.php?token=TU_MIGRATION_TOKEN
 *   https://ipstream.cl/migrate.php?token=TU_MIGRATION_TOKEN&dry_run=1
 *
 * --- Uso CLI (dentro del contenedor web) ---
 *   php migrate.php
 *   php migrate.php --dry-run
 *
 * El token se lee de la variable de entorno MIGRATION_TOKEN. Si no esta
 * definida, el acceso web queda bloqueado (403) y solo funciona por CLI.
 *
 * Para agregar una migracion nueva: crea un archivo .sql en php/migrations/
 * con nombre ordenable, por ejemplo 2026_10_01_01_add_campo_x.sql.
 */

// Evita warnings al detectar el entorno cuando se ejecuta por CLI.
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? '';

require_once __DIR__ . '/php/config/config.php';
require_once __DIR__ . '/php/config/database.php';

$isCli = PHP_SAPI === 'cli';

if ($isCli) {
    $dryRun = in_array('--dry-run', $argv ?? [], true);
} else {
    header('Content-Type: text/plain; charset=utf-8');
    header('X-Robots-Tag: noindex, nofollow');

    $expected = (string)(getenv('MIGRATION_TOKEN') ?: '');
    $provided = (string)($_GET['token'] ?? '');

    if ($expected === '' || !hash_equals($expected, $provided)) {
        http_response_code(403);
        echo "403 - Acceso denegado.\n";
        echo "Token de migracion invalido o MIGRATION_TOKEN no configurado.\n";
        exit;
    }

    $dryRun = isset($_GET['dry_run']);
}

$migrationsDir = __DIR__ . '/php/migrations';

function out(string $line): void
{
    echo $line . "\n";
    if (PHP_SAPI !== 'cli') {
        @ob_flush();
        @flush();
    }
}

out('== Migraciones IPStream ==');

try {
    $pdo = getDatabase();
} catch (Throwable $e) {
    http_response_code(500);
    exit('Error de conexion a la base de datos: ' . $e->getMessage() . "\n");
}

out('Base de datos: ' . DB_NAME . ' @ ' . DB_HOST . ':' . DB_PORT);
out($dryRun ? 'Modo: DRY-RUN (no se aplica nada)' : 'Modo: APLICAR');
out('');

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `schema_migrations` (
        `migration` varchar(255) NOT NULL,
        `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`migration`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    $applied = $pdo->query('SELECT migration FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
    $applied = array_flip($applied);
} catch (Throwable $e) {
    http_response_code(500);
    exit('No se pudo preparar schema_migrations: ' . $e->getMessage() . "\n");
}

$files = glob($migrationsDir . '/*.sql') ?: [];
sort($files, SORT_STRING);

if (empty($files)) {
    exit("No hay archivos de migracion en php/migrations/.\n");
}

$pending = 0;
$executed = 0;

foreach ($files as $file) {
    $name = basename($file);

    if (isset($applied[$name])) {
        out('SKIP   ' . $name . ' (ya aplicada)');
        continue;
    }

    $pending++;

    if ($dryRun) {
        out('PEND   ' . $name);
        continue;
    }

    $sql = file_get_contents($file);
    if ($sql === false || trim($sql) === '') {
        out('WARN   ' . $name . ' (archivo vacio, se omite)');
        continue;
    }

    try {
        $pdo->exec($sql);
        $pdo->prepare('INSERT INTO schema_migrations (migration) VALUES (?)')->execute([$name]);
        out('OK     ' . $name);
        $executed++;
    } catch (Throwable $e) {
        out('ERROR  ' . $name . ': ' . $e->getMessage());
        http_response_code(500);
        exit("\nMigracion interrumpida. Corrige el error y vuelve a ejecutar.\n");
    }
}

out('');
out($dryRun
    ? 'Dry-run finalizado: ' . $pending . ' migracion(es) pendiente(s).'
    : 'Completado: ' . $executed . ' aplicada(s), ' . $pending . ' pendiente(s) detectada(s).');
