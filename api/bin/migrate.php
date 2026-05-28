<?php

declare(strict_types=1);

// Runner de migraciones SQL. Aplica los archivos db/migrations/*.sql que aun no
// se hayan corrido contra la BD configurada en .env, en orden por nombre, y los
// registra en la tabla schema_migrations para no repetirlos.
//
// Uso:
//   php bin/migrate.php            aplica las migraciones pendientes
//   php bin/migrate.php --dry-run  lista las pendientes sin aplicarlas
//
// Idempotente: las migraciones usan CREATE TABLE IF NOT EXISTS / INSERT ... ON
// DUPLICATE KEY, y el runner ignora sentencias USE / CREATE DATABASE (esta
// atado a la conexion de .env, no debe cambiar de base). Por eso es seguro
// correrlo sobre una BD que ya tenga migraciones viejas aplicadas: las repite
// como no-ops y solo las nuevas tienen efecto.

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Prooq\Api\Db\Connection;

Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();

$dryRun = in_array('--dry-run', $argv, true);

// Repo: db/migrations (api/bin/../../db/migrations). Deploy: api/migrations.
$dir = null;
foreach ([__DIR__ . '/../../db/migrations', __DIR__ . '/../migrations'] as $cand) {
    if (is_dir($cand)) {
        $dir = $cand;
        break;
    }
}
if ($dir === null) {
    fwrite(STDERR, "✗ No se encontro el directorio de migraciones.\n");
    exit(1);
}

try {
    $pdo = Connection::get();
} catch (\Throwable $e) {
    fwrite(STDERR, '✗ ' . $e->getMessage() . "\n");
    exit(1);
}

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS schema_migrations (
        filename   VARCHAR(255) PRIMARY KEY,
        applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);

$applied = array_flip(
    $pdo->query('SELECT filename FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN)
);

$files = glob($dir . '/*.sql') ?: [];
sort($files);

$pending = array_filter($files, static fn (string $f): bool => !isset($applied[basename($f)]));

if ($pending === []) {
    echo "✓ Sin migraciones pendientes. BD al dia.\n";
    exit(0);
}

if ($dryRun) {
    echo "Pendientes (" . count($pending) . "):\n";
    foreach ($pending as $f) {
        echo '  · ' . basename($f) . "\n";
    }
    exit(0);
}

$record = $pdo->prepare('INSERT INTO schema_migrations (filename) VALUES (?)');
$ran = 0;

foreach ($pending as $file) {
    $name = basename($file);
    $sql = file_get_contents($file);
    if ($sql === false) {
        fwrite(STDERR, "✗ No se pudo leer {$name}\n");
        exit(1);
    }

    echo "▶ {$name}\n";
    try {
        foreach (migrate_split($sql) as $stmt) {
            // El runner esta atado a la BD de la conexion: ignora cambios de base.
            if (preg_match('/^\s*(USE\b|CREATE\s+DATABASE\b)/i', $stmt) === 1) {
                continue;
            }
            $pdo->exec($stmt);
        }
        $record->execute([$name]);
        $ran++;
        echo "  ✓\n";
    } catch (\Throwable $e) {
        fwrite(STDERR, "  ✗ fallo en {$name}: " . $e->getMessage() . "\n");
        exit(1);
    }
}

echo "✓ Listo: {$ran} migracion(es) aplicada(s).\n";

/**
 * Parte un archivo .sql en sentencias individuales: quita comentarios de linea
 * (-- ...) y de bloque, separa por ';' y descarta las vacias. Asume que no hay
 * ';' dentro de literales (cierto para nuestras migraciones controladas).
 *
 * @return list<string>
 */
function migrate_split(string $sql): array
{
    $sql = preg_replace('/--[^\n]*/', '', $sql) ?? $sql;
    $sql = preg_replace('#/\*.*?\*/#s', '', $sql) ?? $sql;
    $out = [];
    foreach (explode(';', $sql) as $chunk) {
        $chunk = trim($chunk);
        if ($chunk !== '') {
            $out[] = $chunk;
        }
    }
    return $out;
}
