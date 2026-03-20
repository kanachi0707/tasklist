<?php

require_once dirname(__DIR__, 2) . '/inc/bootstrap.php';

if (request_method() !== 'GET') {
    json_error('Method not allowed', 405);
}

$owner = todo_owner_where_clause('t');
$stmt = db()->prepare(
    'SELECT c.id, c.slug, c.name, c.color, COUNT(t.id) AS task_count,
            SUM(CASE WHEN t.is_done = 1 THEN 1 ELSE 0 END) AS done_count
     FROM todos t
     LEFT JOIN categories c ON c.id = t.category_id
     WHERE t.deleted_at IS NULL AND ' . $owner['sql'] . '
     GROUP BY c.id, c.slug, c.name, c.color
     ORDER BY task_count DESC, c.name ASC'
);
$stmt->execute($owner['params']);
$rows = $stmt->fetchAll();

json_success([
    'breakdown' => array_map(static fn(array $row): array => [
        'category_id' => $row['id'] !== null ? (int) $row['id'] : null,
        'slug' => $row['slug'] ?? 'uncategorized',
        'name' => $row['name'] ?? '未分類',
        'color' => $row['color'] ?? '#bcaadf',
        'task_count' => (int) $row['task_count'],
        'done_count' => (int) $row['done_count'],
    ], $rows),
]);
