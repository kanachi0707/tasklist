<?php

require_once dirname(__DIR__, 2) . '/inc/bootstrap.php';

$method = request_method();

if ($method === 'GET') {
    $limit = max(1, min(30, (int) ($_GET['limit'] ?? 20)));
    $offset = max(0, (int) ($_GET['offset'] ?? 0));
    $page = feed_public_page($limit, $offset);

    json_success([
        'items' => $page['items'],
        'paging' => [
            'limit' => $limit,
            'offset' => $offset,
            'total' => $page['total'],
            'has_more' => $page['has_more'],
        ],
    ]);
}

if ($method === 'POST') {
    csrf_validate_request();
    $user = require_authenticated_user();

    if (!user_has_username($user)) {
        json_error('投稿するには先にユーザー名を設定してください。', 422);
    }

    $existing = todays_feed_post((int) $user['id']);
    if ($existing) {
        json_error('今日はすでに投稿済みです。', 409);
    }

    $activity = todays_completed_activity((int) $user['id']);

    try {
        $payload = request_data();
        $templateLines = validate_feed_template_lines((array) ($payload['template_lines'] ?? []));
        $iconKey = validate_feed_icon_key((string) ($payload['icon_key'] ?? ''));
        $autoSummary = build_auto_summary($activity);
    } catch (InvalidArgumentException $exception) {
        json_error($exception->getMessage(), 422);
    }

    $createdAt = now();
    $publicUntil = $createdAt->modify('+24 hours');

    try {
        db()->prepare(
            'INSERT INTO feed_posts (
                user_id, post_date, completed_count, category_summary, auto_summary, template_lines_json, icon_key, created_at, public_until, deleted_at
             ) VALUES (
                :user_id, :post_date, :completed_count, :category_summary, :auto_summary, :template_lines_json, :icon_key, :created_at, :public_until, NULL
             )'
        )->execute([
            'user_id' => $user['id'],
            'post_date' => $activity['date'],
            'completed_count' => $activity['completed_count'],
            'category_summary' => $activity['category_summary'],
            'auto_summary' => $autoSummary,
            'template_lines_json' => json_encode($templateLines, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'icon_key' => $iconKey,
            'created_at' => $createdAt->format('Y-m-d H:i:s'),
            'public_until' => $publicUntil->format('Y-m-d H:i:s'),
        ]);
    } catch (PDOException $exception) {
        if ((int) $exception->getCode() === 23000) {
            json_error('今日はすでに投稿済みです。', 409);
        }
        throw $exception;
    }

    $stmt = db()->prepare(
        'SELECT fp.*, u.username
         FROM feed_posts fp
         INNER JOIN users u ON u.id = fp.user_id
         WHERE fp.id = :id
         LIMIT 1'
    );
    $stmt->execute(['id' => (int) db()->lastInsertId()]);
    $post = $stmt->fetch();

    json_success([
        'message' => '今日の成果を共有しました。',
        'post' => serialize_feed_post($post, true),
    ], 201);
}

json_error('Method not allowed', 405);
