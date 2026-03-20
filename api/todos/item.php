<?php

require_once dirname(__DIR__, 2) . '/inc/bootstrap.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    json_error('タスクIDが不正です。', 422);
}

$method = request_method();

if ($method === 'GET') {
    $todo = find_owned_todo_or_fail($id);
    json_success([
        'todo' => serialize_todo_record($todo),
    ]);
}

if ($method === 'PUT') {
    csrf_validate_request();

    try {
        $payload = validate_todo_payload(request_data());
    } catch (InvalidArgumentException $exception) {
        json_error($exception->getMessage(), 422);
    }

    find_owned_todo_or_fail($id);

    db()->prepare(
        'UPDATE todos
         SET title = :title,
             description = :description,
             category_id = :category_id,
             priority = :priority,
             due_date = :due_date,
             updated_at = NOW()
         WHERE id = :id AND deleted_at IS NULL'
    )->execute([
        'id' => $id,
        'title' => $payload['title'],
        'description' => $payload['description'],
        'category_id' => $payload['category_id'],
        'priority' => $payload['priority'],
        'due_date' => $payload['due_date'],
    ]);

    $todo = find_owned_todo_or_fail($id);
    json_success([
        'message' => 'タスクを更新しました。',
        'todo' => serialize_todo_record($todo),
    ]);
}

if ($method === 'DELETE') {
    csrf_validate_request();
    find_owned_todo_or_fail($id);

    db()->prepare('UPDATE todos SET deleted_at = NOW(), updated_at = NOW() WHERE id = :id')->execute([
        'id' => $id,
    ]);

    json_success([
        'message' => 'タスクを削除しました。',
    ]);
}

json_error('Method not allowed', 405);
