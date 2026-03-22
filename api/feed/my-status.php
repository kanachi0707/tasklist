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
        'message_options' => [],
        'default_message_variant' => 'default',
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
$todayPostsCount = todays_feed_posts_count((int) $user['id']);
$latestPost = todays_feed_post((int) $user['id']);
$remainingPosts = max(0, max_daily_feed_posts() - $todayPostsCount);
$messageOptions = feed_message_options((int) $activity['completed_count']);
$canPost = user_has_username($user) && $remainingPosts > 0 && $activity['completed_count'] > 0;
$defaultMessageVariant = $messageOptions[0]['id'] ?? 'default';
$autoSummary = $activity['completed_count'] > 0 ? build_auto_summary($activity, $defaultMessageVariant) : null;

json_success([
    'authenticated' => true,
    'user' => [
        'id' => (int) $user['id'],
        'email' => $user['email'],
        'username' => $user['username'],
    ],
    'posted_today' => $todayPostsCount > 0,
    'today_posts_count' => $todayPostsCount,
    'remaining_posts' => $remainingPosts,
    'can_post' => $canPost,
    'reason' => $canPost ? null : (
        !user_has_username($user) ? 'username_required' :
        ($remainingPosts < 1 ? 'daily_limit_reached' :
        ($activity['completed_count'] < 1 ? 'no_completed_tasks' : null))
    ),
    'completed_count' => $activity['completed_count'],
    'category_summary' => $activity['category_summary'],
    'auto_summary' => $autoSummary,
    'message_options' => $messageOptions,
    'default_message_variant' => $defaultMessageVariant,
    'post' => $latestPost ? serialize_feed_post(array_merge($latestPost, ['username' => $user['username']]), true) : null,
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
