<?php

require_once dirname(__DIR__, 2) . '/inc/bootstrap.php';

if (request_method() !== 'GET') {
    json_error('Method not allowed', 405);
}

$user = current_user();
$actor = current_actor();

json_success([
    'authenticated' => $user !== null,
    'actor' => $actor['type'],
    'user' => $user ? [
        'id' => (int) $user['id'],
        'email' => $user['email'],
        'username' => $user['username'],
    ] : null,
    'guest_session_id' => (int) $actor['guest_session_id'],
]);
