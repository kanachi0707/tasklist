<?php

require_once dirname(__DIR__, 2) . '/inc/bootstrap.php';

if (request_method() !== 'POST') {
    json_error('Method not allowed', 405);
}

csrf_validate_request();

try {
    $email = validate_email_address((string) (request_data()['email'] ?? ''));
} catch (InvalidArgumentException $exception) {
    json_error($exception->getMessage(), 422);
}

$windowSeconds = (int) config('security.magic_link_rate_limit_window', 900);
$attemptLimit = (int) config('security.magic_link_rate_limit_attempts', 5);
$requestIp = request_ip();
$windowStart = now()->modify('-' . $windowSeconds . ' seconds')->format('Y-m-d H:i:s');

$stmt = db()->prepare(
    'SELECT COUNT(*) FROM magic_login_tokens
     WHERE created_at >= :window_start
       AND (email = :email OR request_ip = :request_ip)'
);
$stmt->execute([
    'window_start' => $windowStart,
    'email' => $email,
    'request_ip' => $requestIp,
]);

if ((int) $stmt->fetchColumn() >= $attemptLimit) {
    json_error('送信回数が上限に達しました。しばらく時間をおいてからお試しください。', 429);
}

$rawToken = random_token(32);
$tokenHash = app_secret_hash($rawToken);
$expiresAt = now()->modify('+' . (int) config('security.magic_link_ttl', 900) . ' seconds');

db()->prepare(
    'INSERT INTO magic_login_tokens (email, token_hash, expires_at, used_at, created_at, request_ip, user_agent)
     VALUES (:email, :token_hash, :expires_at, NULL, NOW(), :request_ip, :user_agent)'
)->execute([
    'email' => $email,
    'token_hash' => $tokenHash,
    'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
    'request_ip' => $requestIp,
    'user_agent' => request_user_agent(),
]);

$link = absolute_build_url('api/auth/verify-magic-link.php', ['token' => $rawToken]);
$mailSent = send_magic_link_email($email, $link, $expiresAt);

if (!$mailSent && !is_debug()) {
    json_error('メール送信に失敗しました。SMTP設定を確認してください。', 500);
}

json_success([
    'message' => 'ログイン用リンクを送信しました。',
    'expires_at' => $expiresAt->format(DateTimeInterface::ATOM),
    'debug_link' => is_debug() ? $link : null,
]);
