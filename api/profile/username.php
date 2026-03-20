<?php

require_once dirname(__DIR__, 2) . '/inc/bootstrap.php';

if (!in_array(request_method(), ['POST', 'PUT'], true)) {
    json_error('Method not allowed', 405);
}

csrf_validate_request();

try {
    $username = validate_username((string) (request_data()['username'] ?? ''));
    $user = update_username_for_current_user($username);
} catch (InvalidArgumentException $exception) {
    json_error($exception->getMessage(), 422);
}

json_success([
    'message' => 'ユーザー名を更新しました。',
    'user' => [
        'id' => (int) $user['id'],
        'email' => $user['email'],
        'username' => $user['username'],
    ],
]);
