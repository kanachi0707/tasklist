<?php

require_once dirname(__DIR__, 2) . '/inc/bootstrap.php';

if (request_method() !== 'PATCH') {
    json_error('Method not allowed', 405);
}

csrf_validate_request();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    json_error('Invalid todo id', 422);
}

$todo = find_owned_todo_or_fail($id);
$nextDoing = $todo['is_done'] ? 0 : ((bool) $todo['is_doing'] ? 0 : 1);

db()->prepare(
    'UPDATE todos
     SET is_doing = :is_doing,
         updated_at = NOW()
     WHERE id = :id AND deleted_at IS NULL'
)->execute([
    'id' => $id,
    'is_doing' => $nextDoing,
]);

$updated = find_owned_todo_or_fail($id);

json_success([
    'message' => $nextDoing ? 'DOING mode enabled' : 'DOING mode cleared',
    'todo' => serialize_todo_record($updated),
]);
