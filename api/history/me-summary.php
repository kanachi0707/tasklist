<?php

require_once dirname(__DIR__, 2) . '/inc/bootstrap.php';

if (request_method() !== 'GET') {
    json_error('Method not allowed', 405);
}

$user = require_authenticated_user();

json_success(feed_history_summary((int) $user['id']));
