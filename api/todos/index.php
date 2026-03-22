<?php

require_once dirname(__DIR__, 2) . '/inc/bootstrap.php';

$method = request_method();

if ($method === 'GET') {
    $status = (string) ($_GET['status'] ?? 'all');
    $month = trim((string) ($_GET['month'] ?? ''));
    $date = trim((string) ($_GET['date'] ?? ''));
    $owner = todo_owner_where_clause('t');
    $conditions = ['t.deleted_at IS NULL', $owner['sql']];
    $params = $owner['params'];

    if (in_array($status, ['open', 'done'], true)) {
        $conditions[] = $status === 'done' ? 't.is_done = 1' : 't.is_done = 0';
    }

    if ($month !== '') {
        try {
            $bounds = month_bounds($month);
        } catch (InvalidArgumentException $exception) {
            json_error($exception->getMessage(), 422);
        }
        $conditions[] = 't.due_date BETWEEN :month_start AND :month_end';
        $params['month_start'] = $bounds['start'];
        $params['month_end'] = $bounds['end'];
    }

    if ($date !== '') {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            json_error('日付の形式が不正です。', 422);
        }
        $conditions[] = 't.due_date = :due_date';
        $params['due_date'] = $date;
    }

    $sql = 'SELECT t.*, c.name AS category_name, c.slug AS category_slug, c.color AS category_color
            FROM todos t
            LEFT JOIN categories c ON c.id = t.category_id
            WHERE ' . implode(' AND ', $conditions) . '
            ORDER BY
                t.is_done ASC,
                CASE WHEN t.sort_order > 0 THEN 0 ELSE 1 END ASC,
                t.sort_order ASC,
                CASE t.priority WHEN "high" THEN 1 WHEN "medium" THEN 2 ELSE 3 END,
                ISNULL(t.due_date), t.due_date ASC,
                t.created_at DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $todos = $stmt->fetchAll();

    json_success([
        'todos' => array_map('serialize_todo_record', $todos),
        'counts' => [
            'all' => count($todos),
            'open' => count(array_filter($todos, static fn(array $todo): bool => !(bool) $todo['is_done'])),
            'done' => count(array_filter($todos, static fn(array $todo): bool => (bool) $todo['is_done'])),
        ],
    ]);
}

if ($method === 'POST') {
    csrf_validate_request();

    $actor = current_actor();

    try {
        $payload = validate_todo_payload(request_data());
        $payload['category_id'] = resolve_accessible_category_id(
            $payload['category_id'],
            $actor['type'] === 'user' ? (int) $actor['user_id'] : null
        );
    } catch (InvalidArgumentException $exception) {
        json_error($exception->getMessage(), 422);
    }

    $sortOrder = next_todo_sort_order_for_current_actor(false);
    db()->prepare(
        'INSERT INTO todos (
            user_id, guest_session_id, title, description, category_id, priority, due_date, sort_order, is_done, done_at, created_at, updated_at, deleted_at
         ) VALUES (
            :user_id, :guest_session_id, :title, :description, :category_id, :priority, :due_date, :sort_order, 0, NULL, NOW(), NOW(), NULL
         )'
    )->execute([
        'user_id' => $actor['type'] === 'user' ? $actor['user_id'] : null,
        'guest_session_id' => $actor['type'] === 'guest' ? $actor['guest_session_id'] : null,
        'title' => $payload['title'],
        'description' => $payload['description'],
        'category_id' => $payload['category_id'],
        'priority' => $payload['priority'],
        'due_date' => $payload['due_date'],
        'sort_order' => $sortOrder,
    ]);

    $todo = find_owned_todo_or_fail((int) db()->lastInsertId());

    json_success([
        'message' => 'タスクを保存しました。',
        'todo' => serialize_todo_record($todo),
    ], 201);
}

json_error('Method not allowed', 405);
