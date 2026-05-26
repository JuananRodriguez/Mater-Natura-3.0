<?php
/**
 * Mater-Natura — Asignar referencias numéricas de 4 dígitos
 *
 * Asigna una referencia única de 4 dígitos (1000-9999) a todos los posts
 * que aún no tengan reference (NULL o vacío).
 *
 * Uso: php scripts/assign-references.php
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/../config/database.php';
$db = new MaterNatura\Core\Database($config);

echo "=== Asignando referencias a posts sin ref ===\n\n";

// 1. Obtener posts sin referencia
$sinRef = $db->fetchAll(
    "SELECT id, title, reference FROM posts WHERE reference IS NULL OR reference = '' ORDER BY id"
);

if (empty($sinRef)) {
    echo "✓ Todos los posts tienen referencia. No hay nada que asignar.\n";
    exit(0);
}

echo "Posts sin referencia: " . count($sinRef) . "\n";

// 2. Obtener referencias existentes (para evitar duplicados)
$existing = $db->fetchAll(
    "SELECT reference FROM posts WHERE reference IS NOT NULL AND reference != ''"
);
$existingRefs = array_unique(array_column($existing, 'reference'));
echo "Referencias existentes: " . count($existingRefs) . " (" . implode(', ', $existingRefs) . ")\n";

// 3. Generar referencias únicas de 4 dígitos
$needed = count($sinRef);
$rangeStart = 1000;
$rangeEnd = 9999;
$available = $rangeEnd - $rangeStart + 1;

if ($needed > $available - count($existingRefs)) {
    echo "ERROR: No hay suficientes números disponibles (necesarios: $needed, disponibles: " . ($available - count($existingRefs)) . ")\n";
    exit(1);
}

// Generar pool de números 1000-9999 excluyendo los existentes
$pool = [];
for ($i = $rangeStart; $i <= $rangeEnd; $i++) {
    $num = (string) $i;
    if (!in_array($num, $existingRefs, true)) {
        $pool[] = $num;
    }
}
echo "Números disponibles en pool: " . count($pool) . "\n";

// Barajar y tomar los primeros N
shuffle($pool);
$assignments = array_slice($pool, 0, $needed);

// 4. Asignar referencias
$updated = 0;
foreach ($sinRef as $i => $post) {
    $newRef = $assignments[$i];
    $db->update('posts', ['reference' => $newRef], 'id = ?', [$post['id']]);
    $title = mb_substr($post['title'], 0, 40);
    echo "  id={$post['id']} → ref={$newRef} («{$title}»)\n";
    $updated++;
}

echo "\n✓ Asignadas {$updated} referencias.\n";
