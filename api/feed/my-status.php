<?php

require_once dirname(__DIR__, 2) . '/inc/bootstrap.php';

if (request_method() !== 'GET') {
    json_error('Method not allowed', 405);
}

$user = current_user();

if (!$user) {
    json_success([
        'authenticated' => false,
        'can_post' => false,
        'reason' => 'login_required',
        'templates' => feed_template_options(),
        'icons' => array_map(
            static fn(string $key, array $item): array => [
                'key' => $key,
                'label' => $item['label'],
                'url' => asset_url('img/' . $item['file']),
            ],
            array_keys(feed_icon_options()),
            feed_icon_options()
        ),
    ]);
}

$activity = todays_completed_activity((int) $user['id']);
$existing = todays_feed_post((int) $user['id']);
$canPost = user_has_username($user) && !$existing && $activity['completed_count'] > 0;
$autoSummary = $activity['completed_count'] > 0 ? build_auto_summary($activity) : null;

json_success([
    'authenticated' => true,
    'user' => [
        'id' => (int) $user['id'],
        'email' => $user['email'],
        'username' => $user['username'],
    ],
    'posted_today' => $existing !== null,
    'can_post' => $canPost,
    'reason' => $canPost ? null : (
        !user_has_username($user) ? 'username_required' :
        ($existing ? 'already_posted' :
        ($activity['completed_count'] < 1 ? 'no_completed_tasks' : null))
    ),
    'completed_count' => $activity['completed_count'],
    'category_summary' => $activity['category_summary'],
    'auto_summary' => $autoSummary,
    'post' => $existing ? serialize_feed_post(array_merge($existing, ['username' => $user['username']]), true) : null,
    'templates' => feed_template_options(),
    'icons' => array_map(
        static fn(string $key, array $item): array => [
            'key' => $key,
            'label' => $item['label'],
            'url' => asset_url('img/' . $item['file']),
        ],
        array_keys(feed_icon_options()),
        feed_icon_options()
    ),
]);
