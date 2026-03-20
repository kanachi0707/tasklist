<?php

require_once dirname(__DIR__, 2) . '/inc/bootstrap.php';

if (request_method() !== 'DELETE') {
    json_error('Method not allowed', 405);
}

csrf_validate_request();
$user = require_authenticated_user();
$id = (int) ($_GET['id'] ?? 0);

if ($id < 1) {
    json_error('投稿IDが正しくありません。', 422);
}

$stmt = db()->prepare(
    'SELECT id, user_id
     FROM feed_posts
     WHERE id = :id AND deleted_at IS NULL
     LIMIT 1'
);
$stmt->execute(['id' => $id]);
$post = $stmt->fetch();

if (!$post) {
    json_error('投稿が見つかりません。', 404);
}

if ((int) $post['user_id'] !== (int) $user['id']) {
    json_error('削除権限がありません。', 403);
}

db()->prepare('UPDATE feed_posts SET deleted_at = NOW() WHERE id = :id')->execute(['id' => $id]);

json_success([
    'message' => '投稿を削除しました。',
]);
