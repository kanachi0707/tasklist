<?php

function validate_email_address(string $email): string
{
    $normalized = trim(mb_strtolower($email));
    if ($normalized === '' || !filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('メールアドレスを正しく入力してください。');
    }
    return $normalized;
}

function validate_todo_payload(array $data): array
{
    $title = trim((string) ($data['title'] ?? ''));
    $description = trim((string) ($data['description'] ?? ''));
    $priority = trim((string) ($data['priority'] ?? 'medium'));
    $dueDate = trim((string) ($data['due_date'] ?? ''));
    $categoryId = $data['category_id'] ?? null;

    if ($title === '') {
        throw new InvalidArgumentException('タスク名は必須です。');
    }

    if (mb_strlen($title) > 120) {
        throw new InvalidArgumentException('タスク名は120文字以内で入力してください。');
    }

    if ($description !== '' && mb_strlen($description) > 2000) {
        throw new InvalidArgumentException('詳細メモは2000文字以内で入力してください。');
    }

    if (!in_array($priority, ['low', 'medium', 'high'], true)) {
        throw new InvalidArgumentException('優先度が正しくありません。');
    }

    if ($dueDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)) {
        throw new InvalidArgumentException('期限日は YYYY-MM-DD 形式で指定してください。');
    }

    if ($dueDate !== '') {
        $parts = explode('-', $dueDate);
        if (count($parts) !== 3 || !checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0])) {
            throw new InvalidArgumentException('期限日が正しくありません。');
        }
    }

    $categoryId = ($categoryId === '' || $categoryId === null) ? null : (int) $categoryId;

    return [
        'title' => $title,
        'description' => $description === '' ? null : $description,
        'priority' => $priority,
        'due_date' => $dueDate === '' ? null : $dueDate,
        'category_id' => $categoryId,
    ];
}

function validate_username(string $username): string
{
    $normalized = trim(mb_strtolower($username));

    if ($normalized === '') {
        throw new InvalidArgumentException('ユーザー名を入力してください。');
    }

    if (!preg_match('/^[a-z0-9_]{3,20}$/', $normalized)) {
        throw new InvalidArgumentException('ユーザー名は半角小文字・数字・アンダースコアのみ、3〜20文字で入力してください。');
    }

    if (filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('メールアドレス形式のユーザー名は使えません。');
    }

    if (preg_match('/https?:\/\/|www\./', $normalized)) {
        throw new InvalidArgumentException('URL形式のユーザー名は使えません。');
    }

    $reserved = [
        'admin', 'administrator', 'official', 'support', 'system', 'root', 'api', 'login', 'staff',
    ];

    if (in_array($normalized, $reserved, true)) {
        throw new InvalidArgumentException('そのユーザー名は利用できません。');
    }

    $blockedWords = [
        'fuck', 'shit', 'bitch', 'cunt', 'asshole', 'slut', 'nigger', 'fag', 'kill', 'suicide',
        'sex', 'porno', 'porn', 'chink', 'kike', 'rape', 'naz', 'hitler',
        'baka', 'aho', 'shine', 'kuso', 'ero', 'yariman', 'oppai',
    ];

    foreach ($blockedWords as $word) {
        if (str_contains($normalized, $word)) {
            throw new InvalidArgumentException('そのユーザー名は利用できません。');
        }
    }

    return $normalized;
}

function validate_feed_template_lines(array $lines): array
{
    $allowed = array_column(feed_template_options(), 'text');
    $selected = [];

    foreach ($lines as $line) {
        $line = trim((string) $line);
        if ($line === '') {
            continue;
        }

        if (!in_array($line, $allowed, true)) {
            throw new InvalidArgumentException('定型文の選択内容が正しくありません。');
        }

        if (in_array($line, $selected, true)) {
            continue;
        }

        $selected[] = $line;
    }

    if (count($selected) > 3) {
        throw new InvalidArgumentException('定型文は3つまで選択できます。');
    }

    return $selected;
}

function validate_feed_icon_key(string $iconKey): string
{
    $iconKey = trim($iconKey);
    if ($iconKey === '') {
        throw new InvalidArgumentException('投稿アイコンを選択してください。');
    }

    if (!array_key_exists($iconKey, feed_icon_options())) {
        throw new InvalidArgumentException('投稿アイコンの指定が正しくありません。');
    }

    return $iconKey;
}
