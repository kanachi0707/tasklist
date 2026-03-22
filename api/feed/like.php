<?php

require_once dirname(__DIR__, 2) . '/inc/bootstrap.php';

if (request_method() !== 'POST') {
    json_error('Method not allowed', 405);
}

csrf_validate_request();
$user = require_authenticated_user();
$id = (int) ($_GET['id'] ?? 0);

if ($id < 1) {
    json_error('投稿IDが正しくありません。', 422);
}

$post = toggle_feed_like($id, (int) $user['id']);

$message = $post['liked_by_viewer']
    ? (!empty($post['owned_by_viewer']) ? 'みっちーが「いいね」してくれました。' : 'いいねしました。')
    : 'いいねを外しました。';

json_success([
    'message' => $message,
    'post' => $post,
]);
