<?php

function session_cookie_name(): string
{
    return (string) config('security.session_cookie_name', 'todo_session');
}

function current_user(): ?array
{
    static $cached = false;

    if ($cached !== false) {
        return $cached ?: null;
    }

    $sessionToken = $_COOKIE[session_cookie_name()] ?? null;
    if (!is_string($sessionToken) || $sessionToken === '') {
        $cached = null;
        return null;
    }

    $hash = app_secret_hash($sessionToken);
    $stmt = db()->prepare(
        'SELECT us.id AS session_id, us.user_id, u.email, u.username, u.created_at, u.updated_at
         FROM user_sessions us
         INNER JOIN users u ON u.id = us.user_id
         WHERE us.session_token_hash = :hash AND us.expires_at > NOW()
         LIMIT 1'
    );
    $stmt->execute(['hash' => $hash]);
    $row = $stmt->fetch();

    if (!$row) {
        clear_cookie_value(session_cookie_name(), true);
        $cached = null;
        return null;
    }

    db()->prepare('UPDATE user_sessions SET last_seen_at = NOW() WHERE id = :id')->execute([
        'id' => $row['session_id'],
    ]);

    set_cookie_value(session_cookie_name(), $sessionToken, (int) config('security.session_ttl', 2592000), true);

    $cached = [
        'id' => (int) $row['user_id'],
        'email' => $row['email'],
        'username' => $row['username'],
        'created_at' => $row['created_at'],
        'updated_at' => $row['updated_at'],
    ];

    return $cached;
}

function current_actor(): array
{
    $user = current_user();
    if ($user) {
        return [
            'type' => 'user',
            'user' => $user,
            'user_id' => (int) $user['id'],
            'guest_session_id' => guest_session_id(),
        ];
    }

    return [
        'type' => 'guest',
        'user' => null,
        'user_id' => null,
        'guest_session_id' => guest_session_id(),
    ];
}

function login_user_by_id(int $userId): void
{
    $rawToken = random_token(32);
    $ttl = (int) config('security.session_ttl', 2592000);
    $expiresAt = now()->modify('+' . $ttl . ' seconds')->format('Y-m-d H:i:s');

    db()->prepare(
        'INSERT INTO user_sessions (user_id, session_token_hash, expires_at, created_at, last_seen_at)
         VALUES (:user_id, :token_hash, :expires_at, NOW(), NOW())'
    )->execute([
        'user_id' => $userId,
        'token_hash' => app_secret_hash($rawToken),
        'expires_at' => $expiresAt,
    ]);

    set_cookie_value(session_cookie_name(), $rawToken, $ttl, true);
}

function logout_current_user(): void
{
    $sessionToken = $_COOKIE[session_cookie_name()] ?? null;
    if (is_string($sessionToken) && $sessionToken !== '') {
        db()->prepare('DELETE FROM user_sessions WHERE session_token_hash = :hash')->execute([
            'hash' => app_secret_hash($sessionToken),
        ]);
    }

    clear_cookie_value(session_cookie_name(), true);
}

function find_or_create_user_by_email(string $email): array
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();
    if ($user) {
        return $user;
    }

    $pdo->prepare('INSERT INTO users (email, username, created_at, updated_at) VALUES (:email, NULL, NOW(), NOW())')
        ->execute(['email' => $email]);

    return [
        'id' => (int) $pdo->lastInsertId(),
        'email' => $email,
        'username' => null,
    ];
}

function update_username_for_current_user(string $username): array
{
    $user = require_authenticated_user();
    $normalized = validate_username($username);

    $stmt = db()->prepare('SELECT id FROM users WHERE username = :username AND id <> :id LIMIT 1');
    $stmt->execute([
        'username' => $normalized,
        'id' => $user['id'],
    ]);
    if ($stmt->fetch()) {
        throw new InvalidArgumentException('そのユーザー名はすでに使われています。');
    }

    db()->prepare('UPDATE users SET username = :username, updated_at = NOW() WHERE id = :id')->execute([
        'username' => $normalized,
        'id' => $user['id'],
    ]);

    $stmt = db()->prepare('SELECT id, email, username, created_at, updated_at FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $user['id']]);
    $updated = $stmt->fetch();

    return [
        'id' => (int) $updated['id'],
        'email' => $updated['email'],
        'username' => $updated['username'],
        'created_at' => $updated['created_at'],
        'updated_at' => $updated['updated_at'],
    ];
}

function move_guest_todos_to_user(int $guestSessionId, int $userId): void
{
    db()->prepare(
        'UPDATE todos
         SET user_id = :user_id, guest_session_id = NULL, updated_at = NOW()
         WHERE guest_session_id = :guest_session_id AND user_id IS NULL AND deleted_at IS NULL'
    )->execute([
        'user_id' => $userId,
        'guest_session_id' => $guestSessionId,
    ]);
}

function todo_owner_where_clause(string $alias = 't'): array
{
    $actor = current_actor();

    if ($actor['type'] === 'user') {
        return [
            'sql' => $alias . '.user_id = :owner_user_id',
            'params' => ['owner_user_id' => $actor['user_id']],
        ];
    }

    return [
        'sql' => $alias . '.guest_session_id = :owner_guest_session_id AND ' . $alias . '.user_id IS NULL',
        'params' => ['owner_guest_session_id' => $actor['guest_session_id']],
    ];
}

function find_owned_todo_or_fail(int $id): array
{
    $owner = todo_owner_where_clause('t');
    $sql = 'SELECT t.*, c.name AS category_name, c.slug AS category_slug, c.color AS category_color
            FROM todos t
            LEFT JOIN categories c ON c.id = t.category_id
            WHERE t.id = :id AND t.deleted_at IS NULL AND ' . $owner['sql'] . '
            LIMIT 1';

    $stmt = db()->prepare($sql);
    $stmt->execute(array_merge(['id' => $id], $owner['params']));
    $todo = $stmt->fetch();

    if (!$todo) {
        json_error('タスクが見つかりません。', 404);
    }

    return $todo;
}

function next_todo_sort_order_for_current_actor(bool $isDone): int
{
    $owner = todo_owner_where_clause('t');
    $sql = 'SELECT COALESCE(MAX(t.sort_order), 0) + 1 AS next_sort_order
            FROM todos t
            WHERE t.deleted_at IS NULL
              AND t.is_done = :is_done
              AND ' . $owner['sql'];

    $stmt = db()->prepare($sql);
    $stmt->execute(array_merge([
        'is_done' => $isDone ? 1 : 0,
    ], $owner['params']));

    $row = $stmt->fetch();
    return max(1, (int) ($row['next_sort_order'] ?? 1));
}
