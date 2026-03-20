<?php

require_once dirname(__DIR__, 2) . '/inc/bootstrap.php';

if (request_method() !== 'GET') {
    json_error('Method not allowed', 405);
}

$stmt = db()->query('SELECT id, slug, name, color, sort_order FROM categories ORDER BY sort_order ASC, id ASC');
$rows = $stmt->fetchAll();

json_success([
    'categories' => array_map(static fn(array $row): array => [
        'id' => (int) $row['id'],
        'slug' => $row['slug'],
        'name' => $row['name'],
        'color' => $row['color'],
        'sort_order' => (int) $row['sort_order'],
    ], $rows),
]);
