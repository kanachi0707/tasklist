<?php

function feed_template_options(): array
{
    return [
        ['id' => 'small_step', 'text' => '小さくても前進できてよかった'],
        ['id' => 'one_step', 'text' => '今日も一歩前へ'],
        ['id' => 'steady', 'text' => '少しずつ積み重ねていく'],
        ['id' => 'consistent', 'text' => 'コツコツ継続中'],
        ['id' => 'did_what_i_could', 'text' => 'できることをやった'],
        ['id' => 'gentle', 'text' => '無理せず進めた'],
        ['id' => 'enough', 'text' => '今日はこれで十分'],
        ['id' => 'tomorrow', 'text' => '明日も続けていく'],
        ['id' => 'good_flow', 'text' => 'いい流れを作れた'],
        ['id' => 'steady_pace', 'text' => '焦らず進めていく'],
    ];
}

function feed_icon_options(): array
{
    return [
        '01_mitchie' => ['file' => '01_mitchie.png', 'label' => 'Mitchie'],
        '02_komitchie' => ['file' => '02_komitchie.png', 'label' => 'Komitchie A'],
        '03_komitchie' => ['file' => '03_komitchie.png', 'label' => 'Komitchie B'],
    ];
}

function require_authenticated_user(): array
{
    $user = current_user();
    if (!$user) {
        if (is_api_request()) {
            json_error('ログインが必要です。', 401);
        }

        redirect_to(page_url('settings.php'));
    }

    return $user;
}

function user_has_username(array $user): bool
{
    return isset($user['username']) && is_string($user['username']) && $user['username'] !== '';
}

function today_bounds(): array
{
    $start = now()->setTime(0, 0, 0);
    $end = $start->modify('+1 day');

    return [
        'start' => $start->format('Y-m-d H:i:s'),
        'end' => $end->format('Y-m-d H:i:s'),
        'date' => $start->format('Y-m-d'),
    ];
}

function todays_completed_activity(int $userId): array
{
    $bounds = today_bounds();
    $stmt = db()->prepare(
        'SELECT c.name AS category_name, COUNT(*) AS completed_count
         FROM todos t
         LEFT JOIN categories c ON c.id = t.category_id
         WHERE t.user_id = :user_id
           AND t.deleted_at IS NULL
           AND t.is_done = 1
           AND t.done_at IS NOT NULL
           AND t.done_at >= :start_at
           AND t.done_at < :end_at
         GROUP BY c.id, c.name
         ORDER BY completed_count DESC, c.sort_order ASC, c.name ASC'
    );
    $stmt->execute([
        'user_id' => $userId,
        'start_at' => $bounds['start'],
        'end_at' => $bounds['end'],
    ]);
    $rows = $stmt->fetchAll();

    $completedCount = 0;
    $parts = [];
    foreach ($rows as $row) {
        $count = (int) $row['completed_count'];
        $completedCount += $count;
        $name = $row['category_name'] ?: 'その他';
        $parts[] = ['name' => $name, 'count' => $count];
    }

    return [
        'date' => $bounds['date'],
        'completed_count' => $completedCount,
        'category_parts' => $parts,
        'category_summary' => build_category_summary($parts),
    ];
}

function build_category_summary(array $parts): ?string
{
    if ($parts === []) {
        return null;
    }

    $labels = [];
    foreach (array_slice($parts, 0, 3) as $part) {
        $labels[] = $part['name'] . 'を' . $part['count'] . '件';
    }

    return implode('、', $labels);
}

function build_auto_summary(array $activity): string
{
    $count = (int) ($activity['completed_count'] ?? 0);
    $parts = $activity['category_parts'] ?? [];

    if ($count <= 0) {
        throw new InvalidArgumentException('完了したタスクがある日にのみ投稿できます。');
    }

    if ($count === 1) {
        return count($parts) === 1 && ($parts[0]['name'] ?? '') !== 'その他'
            ? '今日は' . $parts[0]['name'] . 'を1件進めました'
            : '今日は一歩だけ前に進めました';
    }

    if (count($parts) >= 2) {
        $first = $parts[0];
        $second = $parts[1];
        return '今日は' . $first['name'] . 'を' . $first['count'] . '件、' . $second['name'] . 'を' . $second['count'] . '件進めました';
    }

    if (count($parts) === 1 && ($parts[0]['count'] ?? 0) >= 2 && ($parts[0]['name'] ?? '') !== 'その他') {
        return '今日は' . $parts[0]['name'] . 'カテゴリを中心に進めました';
    }

    return match ($count) {
        2 => '今日は2件のタスクを終えました',
        default => '今日は' . $count . '件完了しました',
    };
}

function todays_feed_post(int $userId): ?array
{
    $date = today_bounds()['date'];
    $stmt = db()->prepare(
        'SELECT * FROM feed_posts
         WHERE user_id = :user_id AND post_date = :post_date
         LIMIT 1'
    );
    $stmt->execute([
        'user_id' => $userId,
        'post_date' => $date,
    ]);

    $row = $stmt->fetch();
    return $row ?: null;
}

function serialize_feed_post(array $row, bool $private = false): array
{
    $icons = feed_icon_options();
    $iconKey = (string) ($row['icon_key'] ?? '01_mitchie');
    $icon = $icons[$iconKey] ?? $icons['01_mitchie'];
    $templateLines = json_decode((string) ($row['template_lines_json'] ?? '[]'), true);

    return [
        'id' => (int) $row['id'],
        'username' => $private ? ($row['username'] ?? '') : ($row['username'] ?? ''),
        'post_date' => $row['post_date'],
        'completed_count' => (int) $row['completed_count'],
        'category_summary' => $row['category_summary'],
        'auto_summary' => $row['auto_summary'],
        'template_lines' => is_array($templateLines) ? array_values($templateLines) : [],
        'icon_key' => $iconKey,
        'icon_url' => asset_url('img/' . $icon['file']),
        'created_at' => $row['created_at'],
        'public_until' => $row['public_until'],
        'is_deleted' => $row['deleted_at'] !== null,
    ];
}

function feed_public_page(int $limit, int $offset): array
{
    $stmt = db()->prepare(
        'SELECT fp.*, u.username
         FROM feed_posts fp
         INNER JOIN users u ON u.id = fp.user_id
         WHERE fp.deleted_at IS NULL
           AND fp.public_until > NOW()
           AND u.username IS NOT NULL
           AND u.username <> ""
         ORDER BY fp.created_at DESC, fp.id DESC
         LIMIT :limit OFFSET :offset'
    );
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $posts = $stmt->fetchAll();

    $countStmt = db()->query(
        'SELECT COUNT(*) FROM feed_posts fp
         INNER JOIN users u ON u.id = fp.user_id
         WHERE fp.deleted_at IS NULL
           AND fp.public_until > NOW()
           AND u.username IS NOT NULL
           AND u.username <> ""'
    );
    $total = (int) $countStmt->fetchColumn();

    return [
        'items' => array_map(static fn(array $row): array => serialize_feed_post($row), $posts),
        'total' => $total,
        'has_more' => ($offset + $limit) < $total,
    ];
}

function feed_history_page(int $userId, int $limit, int $offset): array
{
    $stmt = db()->prepare(
        'SELECT fp.*, u.username
         FROM feed_posts fp
         INNER JOIN users u ON u.id = fp.user_id
         WHERE fp.user_id = :user_id
           AND fp.deleted_at IS NULL
         ORDER BY fp.post_date DESC, fp.created_at DESC, fp.id DESC
         LIMIT :limit OFFSET :offset'
    );
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    $countStmt = db()->prepare('SELECT COUNT(*) FROM feed_posts WHERE user_id = :user_id AND deleted_at IS NULL');
    $countStmt->execute(['user_id' => $userId]);
    $total = (int) $countStmt->fetchColumn();

    return [
        'items' => array_map(static fn(array $row): array => serialize_feed_post($row, true), $rows),
        'total' => $total,
        'has_more' => ($offset + $limit) < $total,
    ];
}

function feed_history_summary(int $userId): array
{
    $summaryStmt = db()->prepare(
        'SELECT
            COUNT(*) AS total_posts,
            SUM(CASE WHEN post_date >= CURDATE() - INTERVAL 6 DAY THEN completed_count ELSE 0 END) AS completed_7d,
            SUM(CASE WHEN post_date >= CURDATE() - INTERVAL 29 DAY THEN completed_count ELSE 0 END) AS completed_30d,
            SUM(CASE WHEN post_date >= DATE_FORMAT(CURDATE(), "%Y-%m-01") THEN 1 ELSE 0 END) AS posts_this_month
         FROM feed_posts
         WHERE user_id = :user_id AND deleted_at IS NULL'
    );
    $summaryStmt->execute(['user_id' => $userId]);
    $row = $summaryStmt->fetch() ?: [];

    $datesStmt = db()->prepare(
        'SELECT post_date
         FROM feed_posts
         WHERE user_id = :user_id AND deleted_at IS NULL
         ORDER BY post_date DESC'
    );
    $datesStmt->execute(['user_id' => $userId]);
    $dates = array_map(static fn(array $item): string => (string) $item['post_date'], $datesStmt->fetchAll());

    $streak = 0;
    $expected = today_bounds()['date'];
    foreach ($dates as $date) {
        if ($date === $expected) {
            $streak++;
            $expected = (new DateTimeImmutable($expected))->modify('-1 day')->format('Y-m-d');
            continue;
        }

        if ($streak === 0 && $date === (new DateTimeImmutable($expected))->modify('-1 day')->format('Y-m-d')) {
            $streak++;
            $expected = (new DateTimeImmutable($date))->modify('-1 day')->format('Y-m-d');
            continue;
        }

        break;
    }

    return [
        'total_posts' => (int) ($row['total_posts'] ?? 0),
        'completed_7d' => (int) ($row['completed_7d'] ?? 0),
        'completed_30d' => (int) ($row['completed_30d'] ?? 0),
        'posts_this_month' => (int) ($row['posts_this_month'] ?? 0),
        'streak_days' => $streak,
    ];
}
