<?php

require_once dirname(__DIR__, 2) . '/inc/bootstrap.php';

if (request_method() !== 'PATCH') {
    json_error('Method not allowed', 405);
}

csrf_validate_request();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    json_error('タスクIDが不正です。', 422);
}

$todo = find_owned_todo_or_fail($id);
$nextDone = !(bool) $todo['is_done'];
$nextSortOrder = next_todo_sort_order_for_current_actor($nextDone);

db()->prepare(
    'UPDATE todos
     SET is_done = :is_done,
         is_doing = 0,
         sort_order = :sort_order,
         done_at = :done_at,
         updated_at = NOW()
     WHERE id = :id AND deleted_at IS NULL'
)->execute([
    'id' => $id,
    'is_done' => $nextDone ? 1 : 0,
    'sort_order' => $nextSortOrder,
    'done_at' => $nextDone ? now()->format('Y-m-d H:i:s') : null,
]);

$updated = find_owned_todo_or_fail($id);

json_success([
    'message' => $nextDone ? 'タスクを完了にしました。' : '未完了に戻しました。',
    'todo' => serialize_todo_record($updated),
]);
