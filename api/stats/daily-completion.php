<?php

require_once dirname(__DIR__, 2) . '/inc/bootstrap.php';

if (request_method() !== 'GET') {
    json_error('Method not allowed', 405);
}

$range = (string) ($_GET['range'] ?? '7d');
$days = match ($range) {
    '30d' => 30,
    default => 7,
};

$owner = todo_owner_where_clause('t');
$start = now()->modify('-' . ($days - 1) . ' days')->format('Y-m-d 00:00:00');
$stmt = db()->prepare(
    'SELECT DATE(t.done_at) AS day, COUNT(*) AS completed_count
     FROM todos t
     WHERE t.deleted_at IS NULL
       AND t.is_done = 1
       AND t.done_at IS NOT NULL
       AND t.done_at >= :start_at
       AND ' . $owner['sql'] . '
     GROUP BY DATE(t.done_at)
     ORDER BY day ASC'
);
$stmt->execute(array_merge(['start_at' => $start], $owner['params']));
$rows = $stmt->fetchAll();

$indexed = [];
foreach ($rows as $row) {
    $indexed[$row['day']] = (int) $row['completed_count'];
}

$series = [];
for ($i = $days - 1; $i >= 0; $i--) {
    $date = now()->modify('-' . $i . ' days')->format('Y-m-d');
    $series[] = [
        'date' => $date,
        'label' => date('n/j', strtotime($date)),
        'count' => $indexed[$date] ?? 0,
    ];
}

json_success([
    'range' => $range,
    'series' => $series,
]);
