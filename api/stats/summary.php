<?php

require_once dirname(__DIR__, 2) . '/inc/bootstrap.php';

if (request_method() !== 'GET') {
    json_error('Method not allowed', 405);
}

$owner = todo_owner_where_clause('t');
$stmt = db()->prepare(
    'SELECT
         COUNT(*) AS total_count,
         SUM(CASE WHEN t.is_done = 1 THEN 1 ELSE 0 END) AS done_count,
         SUM(CASE WHEN t.is_done = 0 THEN 1 ELSE 0 END) AS open_count
     FROM todos t
     WHERE t.deleted_at IS NULL AND ' . $owner['sql']
);
$stmt->execute($owner['params']);
$row = $stmt->fetch() ?: ['total_count' => 0, 'done_count' => 0, 'open_count' => 0];

$total = (int) $row['total_count'];
$done = (int) $row['done_count'];
$open = (int) $row['open_count'];
$rate = $total > 0 ? round(($done / $total) * 100, 1) : 0.0;

json_success([
    'summary' => [
        'total_count' => $total,
        'done_count' => $done,
        'open_count' => $open,
        'completion_rate' => $rate,
    ],
]);
