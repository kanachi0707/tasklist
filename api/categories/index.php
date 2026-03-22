<?php

require_once dirname(__DIR__, 2) . '/inc/bootstrap.php';

$method = request_method();
$user = current_user();
$userId = $user ? (int) $user['id'] : null;

if ($method === 'GET') {
    json_success(categories_list_for_viewer($userId));
}

if (!$user) {
    json_error('ログインが必要です。', 401);
}

csrf_validate_request();

if ($method === 'POST') {
    try {
        $payload = request_data();
        $category = create_custom_category($userId, (string) ($payload['name'] ?? ''));
    } catch (InvalidArgumentException | RuntimeException $exception) {
        json_error($exception->getMessage(), 422);
    }

    json_success([
        'message' => 'ユーザーカテゴリを追加しました。',
        'category' => $category,
    ] + categories_list_for_viewer($userId), 201);
}

$id = (int) ($_GET['id'] ?? 0);
if ($id < 1) {
    json_error('カテゴリIDが正しくありません。', 422);
}

if ($method === 'PUT') {
    try {
        $payload = request_data();
        $category = update_custom_category_name($id, $userId, (string) ($payload['name'] ?? ''));
    } catch (InvalidArgumentException | RuntimeException $exception) {
        json_error($exception->getMessage(), 422);
    }

    json_success([
        'message' => 'ユーザーカテゴリを更新しました。',
        'category' => $category,
    ] + categories_list_for_viewer($userId));
}

if ($method === 'DELETE') {
    try {
        delete_custom_category($id, $userId);
    } catch (RuntimeException $exception) {
        json_error($exception->getMessage(), 422);
    }

    json_success([
        'message' => 'ユーザーカテゴリを削除しました。',
    ] + categories_list_for_viewer($userId));
}

json_error('Method not allowed', 405);
