<?php

require_once dirname(__DIR__, 2) . '/inc/bootstrap.php';

if (request_method() !== 'PATCH') {
    json_error('Method not allowed', 405);
}

csrf_validate_request();

$payload = request_data();
$ids = $payload['ids'] ?? null;
$isDone = !empty($payload['is_done']);

if (!is_array($ids) || count($ids) === 0) {
    json_error('並び順データが正しくありません。', 422);
}

$ids = array_values(array_unique(array_map(static fn($id): int => (int) $id, $ids)));
$ids = array_values(array_filter($ids, static fn(int $id): bool => $id > 0));

if (count($ids) === 0) {
    json_error('並び替え対象のタスクが見つかりません。', 422);
}

$owner = todo_owner_where_clause('t');
$countSql = 'SELECT COUNT(*) AS task_count
             FROM todos t
             WHERE t.deleted_at IS NULL
               AND t.is_done = :is_done
               AND ' . $owner['sql'];
$countStmt = db()->prepare($countSql);
$countStmt->execute(array_merge([
    'is_done' => $isDone ? 1 : 0,
], $owner['params']));
$totalCount = (int) ($countStmt->fetch()['task_count'] ?? 0);

if ($totalCount !== count($ids)) {
    json_error('一覧を更新してからもう一度お試しください。', 409);
}

$placeholders = [];
$params = array_merge([
    'is_done' => $isDone ? 1 : 0,
], $owner['params']);

foreach ($ids as $index => $id) {
    $key = 'id_' . $index;
    $placeholders[] = ':' . $key;
    $params[$key] = $id;
}

$verifySql = 'SELECT t.id
              FROM todos t
              WHERE t.deleted_at IS NULL
                AND t.is_done = :is_done
                AND ' . $owner['sql'] . '
                AND t.id IN (' . implode(', ', $placeholders) . ')';
$verifyStmt = db()->prepare($verifySql);
$verifyStmt->execute($params);
$foundIds = array_map('intval', array_column($verifyStmt->fetchAll(), 'id'));
sort($foundIds);
$sortedIds = $ids;
sort($sortedIds);

if ($foundIds !== $sortedIds) {
    json_error('並び替え対象に無効なタスクが含まれています。', 403);
}

$pdo = db();
$pdo->beginTransaction();

try {
    $updateStmt = $pdo->prepare(
        'UPDATE todos
         SET sort_order = :sort_order,
             updated_at = NOW()
         WHERE id = :id AND deleted_at IS NULL'
    );

    foreach ($ids as $index => $id) {
        $updateStmt->execute([
            'id' => $id,
            'sort_order' => $index + 1,
        ]);
    }

    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $exception;
}

json_success([
    'message' => '並び順を更新しました。',
]);
