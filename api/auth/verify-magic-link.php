<?php

require_once dirname(__DIR__, 2) . '/inc/bootstrap.php';

if (request_method() !== 'GET') {
    json_error('Method not allowed', 405);
}

$token = trim((string) ($_GET['token'] ?? ''));
if ($token === '') {
    redirect_to(page_url('settings.php', ['auth' => 'invalid']));
}

$tokenHash = app_secret_hash($token);
$pdo = db();
$stmt = $pdo->prepare(
    'SELECT * FROM magic_login_tokens
     WHERE token_hash = :token_hash
     LIMIT 1'
);
$stmt->execute(['token_hash' => $tokenHash]);
$loginToken = $stmt->fetch();

if (!$loginToken) {
    redirect_to(page_url('settings.php', ['auth' => 'invalid']));
}

if ($loginToken['used_at'] !== null) {
    redirect_to(page_url('settings.php', ['auth' => 'used']));
}

if (strtotime((string) $loginToken['expires_at']) < time()) {
    redirect_to(page_url('settings.php', ['auth' => 'expired']));
}

$guestId = guest_session_id();

$pdo->beginTransaction();

try {
    $pdo->prepare('UPDATE magic_login_tokens SET used_at = NOW() WHERE id = :id AND used_at IS NULL')
        ->execute(['id' => $loginToken['id']]);

    $user = find_or_create_user_by_email((string) $loginToken['email']);
    move_guest_todos_to_user($guestId, (int) $user['id']);
    login_user_by_id((int) $user['id']);
    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $exception;
}

redirect_to(page_url('task.php', ['auth' => 'verified']));
