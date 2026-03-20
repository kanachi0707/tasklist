<?php

return [
    'app' => [
        'name' => 'Mitchie Todo',
        'base_url' => '',
        'env' => 'production',
        'debug' => false,
        'timezone' => 'Asia/Tokyo',
        'force_https' => true,
    ],
    'security' => [
        'app_key' => 'PLEASE_CHANGE_THIS_TO_A_LONG_RANDOM_STRING',
        'cookie_secure' => true,
        'cookie_samesite' => 'Lax',
        'guest_cookie_name' => 'guest_token',
        'session_cookie_name' => 'todo_session',
        'csrf_cookie_name' => 'csrf_token',
        'theme_cookie_name' => 'todo_theme',
        'guest_ttl' => 31536000,
        'session_ttl' => 2592000,
        'magic_link_ttl' => 900,
        'magic_link_rate_limit_window' => 900,
        'magic_link_rate_limit_attempts' => 5,
    ],
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'database' => 'tasklist_db',
        'username' => 'tasklist_user',
        'password' => 'CHANGE_ME',
        'charset' => 'utf8mb4',
    ],
    'smtp' => [
        'enabled' => true,
        'host' => 'smtp.example.com',
        'port' => 587,
        'encryption' => 'tls',
        'username' => 'smtp-user@example.com',
        'password' => 'CHANGE_ME',
        'from_email' => 'no-reply@example.com',
        'from_name' => 'Mitchie Todo',
        'timeout' => 15,
    ],
    'contact' => [
        'operator_name' => 'TODO: 運営者名を設定',
        'support_email' => 'TODO: support@example.com を設定',
    ],
];
