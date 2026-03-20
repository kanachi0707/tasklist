<?php

require_once dirname(__DIR__, 2) . '/inc/bootstrap.php';

if (request_method() !== 'GET') {
    json_error('Method not allowed', 405);
}

$user = require_authenticated_user();
$limit = max(1, min(50, (int) ($_GET['limit'] ?? 20)));
$offset = max(0, (int) ($_GET['offset'] ?? 0));
$page = feed_history_page((int) $user['id'], $limit, $offset);

json_success([
    'items' => $page['items'],
    'paging' => [
        'limit' => $limit,
        'offset' => $offset,
        'total' => $page['total'],
        'has_more' => $page['has_more'],
    ],
]);
