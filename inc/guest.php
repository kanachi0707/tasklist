<?php

function guest_cookie_name(): string
{
    return (string) config('security.guest_cookie_name', 'guest_token');
}

function current_guest_token(): ?string
{
    $cookieName = guest_cookie_name();
    return isset($_COOKIE[$cookieName]) && $_COOKIE[$cookieName] !== '' ? (string) $_COOKIE[$cookieName] : null;
}

function ensure_guest_session(): array
{
    static $cached = null;

    if ($cached !== null) {
        return $cached;
    }

    $token = current_guest_token();
    $pdo = db();

    if ($token !== null) {
        $stmt = $pdo->prepare('SELECT * FROM guest_sessions WHERE guest_token = :token LIMIT 1');
        $stmt->execute(['token' => $token]);
        $existing = $stmt->fetch();
        if ($existing) {
            $pdo->prepare('UPDATE guest_sessions SET last_seen_at = NOW() WHERE id = :id')->execute([
                'id' => $existing['id'],
            ]);
            set_cookie_value(guest_cookie_name(), $token, (int) config('security.guest_ttl', 31536000), true);
            $cached = $existing;
            return $cached;
        }
    }

    $token = random_token(32);
    $pdo->prepare('INSERT INTO guest_sessions (guest_token, created_at, last_seen_at) VALUES (:token, NOW(), NOW())')
        ->execute(['token' => $token]);

    $cached = [
        'id' => (int) $pdo->lastInsertId(),
        'guest_token' => $token,
        'created_at' => now()->format('Y-m-d H:i:s'),
        'last_seen_at' => now()->format('Y-m-d H:i:s'),
    ];

    set_cookie_value(guest_cookie_name(), $token, (int) config('security.guest_ttl', 31536000), true);

    return $cached;
}

function guest_session_id(): int
{
    return (int) ensure_guest_session()['id'];
}
