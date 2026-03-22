<?php

function default_category_seed_data(): array
{
    return [
        ['slug' => 'work', 'name' => '仕事', 'color' => '#6f4abf', 'sort_order' => 10],
        ['slug' => 'personal', 'name' => 'プライベート', 'color' => '#b66ad8', 'sort_order' => 20],
        ['slug' => 'health', 'name' => '健康', 'color' => '#6f93d6', 'sort_order' => 30],
        ['slug' => 'learning', 'name' => '学び', 'color' => '#6da88f', 'sort_order' => 40],
        ['slug' => 'housework', 'name' => '家事', 'color' => '#d69a6f', 'sort_order' => 50],
        ['slug' => 'money', 'name' => 'お金', 'color' => '#c28a5b', 'sort_order' => 60],
        ['slug' => 'other', 'name' => 'その他', 'color' => '#9e9aa8', 'sort_order' => 70],
    ];
}

function custom_category_limit(): int
{
    return 10;
}

function validate_category_name(string $name): string
{
    $name = trim($name);

    if ($name === '') {
        throw new InvalidArgumentException('カテゴリ名を入力してください。');
    }

    if (mb_strlen($name) > 30) {
        throw new InvalidArgumentException('カテゴリ名は30文字以内で入力してください。');
    }

    return $name;
}

function category_row_to_view(array $row): array
{
    $isDefault = $row['user_id'] === null;

    return [
        'id' => (int) $row['id'],
        'slug' => (string) $row['slug'],
        'name' => (string) $row['name'],
        'color' => (string) $row['color'],
        'sort_order' => (int) $row['sort_order'],
        'is_default' => $isDefault,
        'is_custom' => !$isDefault,
        'is_editable' => !$isDefault,
        'is_deletable' => !$isDefault,
    ];
}

function default_categories_list(): array
{
    $stmt = db()->query(
        'SELECT id, user_id, slug, name, color, sort_order
         FROM categories
         WHERE user_id IS NULL
         ORDER BY sort_order ASC, id ASC'
    );

    return array_map('category_row_to_view', $stmt->fetchAll());
}

function user_categories_list(int $userId): array
{
    $stmt = db()->prepare(
        'SELECT id, user_id, slug, name, color, sort_order
         FROM categories
         WHERE user_id = :user_id
         ORDER BY sort_order ASC, id ASC'
    );
    $stmt->execute(['user_id' => $userId]);

    return array_map('category_row_to_view', $stmt->fetchAll());
}

function categories_list_for_viewer(?int $userId = null): array
{
    $defaults = default_categories_list();
    $userCategories = $userId !== null ? user_categories_list($userId) : [];

    return [
        'categories' => array_merge($defaults, $userCategories),
        'default_categories' => $defaults,
        'user_categories' => $userCategories,
        'custom_limit' => custom_category_limit(),
        'custom_count' => count($userCategories),
    ];
}

function find_category(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT id, user_id, slug, name, color, sort_order
         FROM categories
         WHERE id = :id
         LIMIT 1'
    );
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();

    if (!$row) {
        return null;
    }

    return [
        'id' => (int) $row['id'],
        'user_id' => $row['user_id'] !== null ? (int) $row['user_id'] : null,
        'slug' => (string) $row['slug'],
        'name' => (string) $row['name'],
        'color' => (string) $row['color'],
        'sort_order' => (int) $row['sort_order'],
        'is_default' => $row['user_id'] === null,
        'is_custom' => $row['user_id'] !== null,
    ];
}

function custom_categories_count(int $userId): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM categories WHERE user_id = :user_id');
    $stmt->execute(['user_id' => $userId]);
    return (int) $stmt->fetchColumn();
}

function ensure_unique_category_name(string $name, int $userId, ?int $ignoreId = null): void
{
    $sql = 'SELECT COUNT(*)
            FROM categories
            WHERE (user_id IS NULL OR user_id = :user_id)
              AND LOWER(name) = LOWER(:name)';
    $params = [
        'user_id' => $userId,
        'name' => $name,
    ];

    if ($ignoreId !== null) {
        $sql .= ' AND id <> :ignore_id';
        $params['ignore_id'] = $ignoreId;
    }

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    if ((int) $stmt->fetchColumn() > 0) {
        throw new RuntimeException('同じ名前のカテゴリは使えません。');
    }
}

function next_custom_category_color(int $userId): string
{
    $palette = ['#6f4abf', '#b66ad8', '#6f93d6', '#6da88f', '#d69a6f', '#c28a5b', '#9e9aa8'];
    return $palette[custom_categories_count($userId) % count($palette)];
}

function next_custom_category_sort_order(int $userId): int
{
    $stmt = db()->prepare('SELECT COALESCE(MAX(sort_order), 0) FROM categories WHERE user_id = :user_id');
    $stmt->execute(['user_id' => $userId]);
    return ((int) $stmt->fetchColumn()) + 10;
}

function generate_custom_category_slug(): string
{
    do {
        $slug = 'custom_' . strtolower(bin2hex(random_bytes(4)));
        $stmt = db()->prepare('SELECT COUNT(*) FROM categories WHERE slug = :slug');
        $stmt->execute(['slug' => $slug]);
    } while ((int) $stmt->fetchColumn() > 0);

    return $slug;
}

function require_owned_custom_category(int $id, int $userId): array
{
    $category = find_category($id);

    if (!$category) {
        throw new RuntimeException('カテゴリが見つかりません。');
    }

    if ($category['is_default']) {
        throw new RuntimeException('デフォルトカテゴリは編集できません。');
    }

    if ((int) $category['user_id'] !== $userId) {
        throw new RuntimeException('このカテゴリは編集できません。');
    }

    return $category;
}

function create_custom_category(int $userId, string $name): array
{
    if (custom_categories_count($userId) >= custom_category_limit()) {
        throw new RuntimeException('ユーザーカテゴリは10個まで追加できます。');
    }

    $name = validate_category_name($name);
    ensure_unique_category_name($name, $userId);

    $stmt = db()->prepare(
        'INSERT INTO categories (user_id, slug, name, color, sort_order)
         VALUES (:user_id, :slug, :name, :color, :sort_order)'
    );
    $stmt->execute([
        'user_id' => $userId,
        'slug' => generate_custom_category_slug(),
        'name' => $name,
        'color' => next_custom_category_color($userId),
        'sort_order' => next_custom_category_sort_order($userId),
    ]);

    $category = find_category((int) db()->lastInsertId());
    if (!$category) {
        throw new RuntimeException('カテゴリの取得に失敗しました。');
    }

    return category_row_to_view($category);
}

function update_custom_category_name(int $id, int $userId, string $name): array
{
    require_owned_custom_category($id, $userId);

    $name = validate_category_name($name);
    ensure_unique_category_name($name, $userId, $id);

    db()->prepare('UPDATE categories SET name = :name WHERE id = :id')->execute([
        'id' => $id,
        'name' => $name,
    ]);

    $category = find_category($id);
    if (!$category) {
        throw new RuntimeException('カテゴリの取得に失敗しました。');
    }

    return category_row_to_view($category);
}

function delete_custom_category(int $id, int $userId): void
{
    require_owned_custom_category($id, $userId);

    $stmt = db()->prepare(
        'SELECT COUNT(*)
         FROM todos
         WHERE category_id = :id
           AND deleted_at IS NULL'
    );
    $stmt->execute(['id' => $id]);

    if ((int) $stmt->fetchColumn() > 0) {
        throw new RuntimeException('使用中のカテゴリは削除できません。');
    }

    db()->prepare('DELETE FROM categories WHERE id = :id')->execute(['id' => $id]);
}

function resolve_accessible_category_id(?int $categoryId, ?int $userId): ?int
{
    if ($categoryId === null || $categoryId === 0) {
        return null;
    }

    $category = find_category($categoryId);

    if (!$category) {
        throw new InvalidArgumentException('カテゴリが見つかりません。');
    }

    if ($category['is_default']) {
        return $categoryId;
    }

    if ($userId === null || (int) $category['user_id'] !== $userId) {
        throw new InvalidArgumentException('このカテゴリは選択できません。');
    }

    return $categoryId;
}
