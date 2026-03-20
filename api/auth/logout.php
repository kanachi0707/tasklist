<?php

require_once dirname(__DIR__, 2) . '/inc/bootstrap.php';

if (request_method() !== 'POST') {
    json_error('Method not allowed', 405);
}

csrf_validate_request();
logout_current_user();
ensure_guest_session();

json_success([
    'message' => 'ログアウトしました。',
]);
