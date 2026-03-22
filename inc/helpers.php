<?php

function config(?string $path = null, mixed $default = null): mixed
{
    static $config = null;

    if ($config === null) {
        $root = dirname(__DIR__);
        $configFile = $root . '/config.php';
        $sampleFile = $root . '/config.sample.php';
        $loaded = file_exists($configFile) ? require $configFile : require $sampleFile;
        $config = is_array($loaded) ? $loaded : [];
    }

    if ($path === null || $path === '') {
        return $config;
    }

    $value = $config;
    foreach (explode('.', $path) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }

    return $value;
}

function app_root(): string
{
    return dirname(__DIR__);
}

function base_url(): string
{
    $url = trim((string) config('app.base_url', ''), '/');
    return $url === '' ? '' : $url;
}

function detected_base_path(): string
{
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $scriptDir = $scriptDir === '.' ? '' : rtrim($scriptDir, '/');

    if (preg_match('#^(.*?)/api(?:/.*)?$#', $scriptDir, $matches)) {
        return $matches[1] === '' ? '' : $matches[1];
    }

    return $scriptDir;
}

function build_url(string $path = '', array $query = []): string
{
    $path = ltrim($path, '/');
    $base = base_url();
    $queryString = $query !== [] ? '?' . http_build_query($query) : '';

    if ($base !== '') {
        return $base . ($path !== '' ? '/' . $path : '') . $queryString;
    }

    $basePath = detected_base_path();
    $localPath = ($basePath !== '' ? $basePath . '/' : '/') . $path;
    $localPath = preg_replace('#/+#', '/', $localPath) ?: '/';

    return $localPath . $queryString;
}

function absolute_build_url(string $path = '', array $query = []): string
{
    $configured = base_url();
    if ($configured !== '') {
        return build_url($path, $query);
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    return $scheme . '://' . $host . build_url($path, $query);
}

function asset_url(string $path): string
{
    return build_url('assets/' . ltrim($path, '/'));
}

function page_url(string $page, array $query = []): string
{
    return build_url(ltrim($page, '/'), $query);
}

function api_url(string $path): string
{
    return build_url('api/' . ltrim($path, '/'));
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function h(mixed $value): string
{
    return e($value);
}

function now(): DateTimeImmutable
{
    return new DateTimeImmutable('now', new DateTimeZone((string) config('app.timezone', 'Asia/Tokyo')));
}

function current_date_label(): string
{
    $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
    $today = now();
    return $today->format('Y年n月j日') . '（' . $weekdays[(int) $today->format('w')] . '）';
}

function current_month_value(): string
{
    return now()->format('Y-m');
}

function format_priority_label(string $priority): string
{
    return match ($priority) {
        'high' => '高',
        'low' => '低',
        default => '中',
    };
}

function month_bounds(string $month): array
{
    $start = DateTimeImmutable::createFromFormat(
        'Y-m-d',
        $month . '-01',
        new DateTimeZone((string) config('app.timezone', 'Asia/Tokyo'))
    );

    if (!$start) {
        throw new InvalidArgumentException('月の形式が正しくありません。');
    }

    return [
        'start' => $start->format('Y-m-d'),
        'end' => $start->modify('last day of this month')->format('Y-m-d'),
    ];
}

function serialize_todo_record(array $todo): array
{
    return [
        'id' => (int) $todo['id'],
        'title' => $todo['title'],
        'description' => $todo['description'],
        'category_id' => $todo['category_id'] !== null ? (int) $todo['category_id'] : null,
        'category_name' => $todo['category_name'] ?? null,
        'category_slug' => $todo['category_slug'] ?? null,
        'category_color' => $todo['category_color'] ?? null,
        'priority' => $todo['priority'],
        'priority_label' => format_priority_label((string) $todo['priority']),
        'due_date' => $todo['due_date'],
        'sort_order' => isset($todo['sort_order']) ? (int) $todo['sort_order'] : 0,
        'is_done' => (bool) $todo['is_done'],
        'is_doing' => !empty($todo['is_doing']),
        'done_at' => $todo['done_at'],
        'created_at' => $todo['created_at'],
        'updated_at' => $todo['updated_at'],
    ];
}

function request_path(): string
{
    return parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
}

function request_method(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

function request_ip(): ?string
{
    return $_SERVER['REMOTE_ADDR'] ?? null;
}

function request_user_agent(): ?string
{
    return isset($_SERVER['HTTP_USER_AGENT']) ? mb_strimwidth($_SERVER['HTTP_USER_AGENT'], 0, 255, '') : null;
}

function is_api_request(): bool
{
    return str_contains(request_path(), '/api/');
}

function is_debug(): bool
{
    return (bool) config('app.debug', false);
}

function cookie_secure(): bool
{
    return (bool) config('security.cookie_secure', true);
}

function cookie_samesite(): string
{
    return (string) config('security.cookie_samesite', 'Lax');
}

function set_cookie_value(string $name, string $value, int $ttl, bool $httpOnly = true): void
{
    setcookie($name, $value, [
        'expires' => time() + $ttl,
        'path' => '/',
        'domain' => '',
        'secure' => cookie_secure(),
        'httponly' => $httpOnly,
        'samesite' => cookie_samesite(),
    ]);
    $_COOKIE[$name] = $value;
}

function clear_cookie_value(string $name, bool $httpOnly = true): void
{
    setcookie($name, '', [
        'expires' => time() - 3600,
        'path' => '/',
        'domain' => '',
        'secure' => cookie_secure(),
        'httponly' => $httpOnly,
        'samesite' => cookie_samesite(),
    ]);
    unset($_COOKIE[$name]);
}

function app_secret_hash(string $plain): string
{
    return hash_hmac('sha256', $plain, (string) config('security.app_key', 'change-this'));
}

function random_token(int $bytes = 32): string
{
    return rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '=');
}

function request_headers_normalized(): array
{
    $headers = [];

    if (function_exists('getallheaders')) {
        foreach (getallheaders() as $key => $value) {
            $headers[strtolower($key)] = $value;
        }
        return $headers;
    }

    foreach ($_SERVER as $key => $value) {
        if (str_starts_with($key, 'HTTP_')) {
            $headers[strtolower(str_replace('_', '-', substr($key, 5)))] = $value;
        }
    }

    return $headers;
}

function current_theme(): string
{
    $cookieName = (string) config('security.theme_cookie_name', 'todo_theme');
    $theme = $_COOKIE[$cookieName] ?? 'light';
    return in_array($theme, ['light', 'dark'], true) ? $theme : 'light';
}

function render_page_start(string $title, string $pageKey, array $payload = []): void
{
    $headerMode = (string) ($payload['header_mode'] ?? 'default');
    $headerBackUrl = (string) ($payload['header_back_url'] ?? page_url('task.php'));
    $headerTrailingIcon = (string) ($payload['header_trailing_icon'] ?? 'more_horiz');
    $showSplash = (bool) ($payload['show_splash'] ?? false);
    $siteName = (string) config('app.name', 'Todo App');
    $siteDescription = 'みっちーToDo! ｜ シンプルなタスク管理と皆の応援ツール';
    $gaMeasurementId = (string) config('analytics.ga_measurement_id', '');
    $siteDescription = 'みっちーToDo! ｜ シンプルなタスク管理と皆の応援ツール';
    $siteDescription = 'みっちーToDo! ｜ シンプルなタスク管理と皆の応援ツール';
    $pageTitle = $title . ' | ' . $siteName;
    $currentPage = basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'task.php'));
    $pageUrl = absolute_build_url($currentPage);
    $ogImageUrl = absolute_build_url('image/icon/OGP.png');
    $appPayload = array_merge([
        'page' => $pageKey,
        'today' => now()->format('Y-m-d'),
        'month' => current_month_value(),
        'csrfToken' => csrf_token(),
        'routes' => [
            'me' => api_url('auth/me.php'),
            'logout' => api_url('auth/logout.php'),
            'requestMagicLink' => api_url('auth/request-magic-link.php'),
            'updateUsername' => api_url('profile/username.php'),
            'todos' => api_url('todos/index.php'),
            'todoItem' => api_url('todos/item.php'),
            'todoToggle' => api_url('todos/toggle.php'),
            'todoDoing' => api_url('todos/doing.php'),
            'todoReorder' => api_url('todos/reorder.php'),
            'categories' => api_url('categories/index.php'),
            'statsSummary' => api_url('stats/summary.php'),
            'statsDaily' => api_url('stats/daily-completion.php'),
            'statsCategory' => api_url('stats/category-breakdown.php'),
            'feedList' => api_url('feed/index.php'),
            'feedStatus' => api_url('feed/my-status.php'),
            'feedItem' => api_url('feed/item.php'),
            'feedLike' => api_url('feed/like.php'),
            'historyList' => api_url('history/me.php'),
            'historySummary' => api_url('history/me-summary.php'),
        ],
        'pages' => [
            'home' => page_url('task.php'),
            'calendar' => page_url('calendar.php'),
            'stats' => page_url('stats.php'),
            'feed' => page_url('feed.php'),
            'settings' => page_url('settings.php'),
            'taskForm' => page_url('task-form.php'),
            'privacy' => page_url('privacy.php'),
        ],
        'assets' => [
            'mitchie01' => asset_url('img/01_mitchie.png'),
        ],
        'themeCookieName' => (string) config('security.theme_cookie_name', 'todo_theme'),
        'cookieSecure' => cookie_secure(),
        'showSplash' => $showSplash,
    ], $payload);
    ?>
<!DOCTYPE html>
<html lang="ja" data-theme="<?= h(current_theme()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= h($pageTitle) ?></title>
    <meta name="description" content="<?= h($siteDescription) ?>">
    <meta property="og:title" content="<?= h($pageTitle) ?>">
    <meta property="og:description" content="<?= h($siteDescription) ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= h($pageUrl) ?>">
    <meta property="og:image" content="<?= h($ogImageUrl) ?>">
    <meta property="og:site_name" content="<?= h($siteName) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= h($pageTitle) ?>">
    <meta name="twitter:description" content="<?= h($siteDescription) ?>">
    <meta name="twitter:image" content="<?= h($ogImageUrl) ?>">
    <meta name="theme-color" content="#efe8fb">
    <meta name="apple-mobile-web-app-title" content="<?= h($siteName) ?>">
    <meta name="csrf-token" content="<?= h(csrf_token()) ?>">
    <link rel="icon" href="<?= h(build_url('image/icon/fabicon.jpg')) ?>" type="image/jpeg">
    <link rel="apple-touch-icon" href="<?= h(build_url('image/icon/icon_180.png')) ?>">
    <link rel="manifest" href="<?= h(build_url('manifest.webmanifest')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Manrope:wght@700;800&family=Noto+Sans+JP:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@300..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= h(asset_url('css/style.css')) ?>">
    <?php if ($gaMeasurementId !== ''): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= h($gaMeasurementId) ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        gtag('js', new Date());
        gtag('config', <?= json_encode($gaMeasurementId, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>);
    </script>
    <?php endif; ?>
    <script>
        (function () {
            try {
                var stored = localStorage.getItem("todo-theme");
                if (stored) {
                    document.documentElement.setAttribute("data-theme", stored);
                }
            } catch (error) {
                console.warn(error);
            }
        }());
        (function () {
            window.__SHOW_APP_SPLASH__ = <?= json_encode($showSplash) ?>;
            if (window.__SHOW_APP_SPLASH__) {
                document.documentElement.classList.add("has-app-splash");
            }
        }());
        window.APP = <?= json_encode($appPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    </script>
</head>
<body class="page-<?= h($pageKey) ?>">
<div class="app-splash" id="appSplash" aria-hidden="true">
    <div class="app-splash-logo-wrap">
        <img class="app-splash-logo app-splash-logo-light" src="<?= h(asset_url('img/MitchieTodo_logo_pp.png')) ?>" alt="">
        <img class="app-splash-logo app-splash-logo-dark" src="<?= h(asset_url('img/MitchieTodo_logo_wp.png')) ?>" alt="">
    </div>
</div>
<div class="site-shell">
    <div class="site-backdrop site-backdrop-a"></div>
    <div class="site-backdrop site-backdrop-b"></div>
    <?php if ($headerMode === 'transactional'): ?>
    <header class="topbar topbar-transactional">
        <a class="topbar-icon-button" href="<?= h($headerBackUrl) ?>" aria-label="戻る">
            <span class="material-symbols-outlined">arrow_back</span>
        </a>
        <div class="topbar-title">
            <h1><?= h($title) ?></h1>
        </div>
        <button class="topbar-icon-button is-passive" type="button" aria-label="その他">
            <span class="material-symbols-outlined"><?= h($headerTrailingIcon) ?></span>
        </button>
    </header>
    <?php elseif ($headerMode !== 'none'): ?>
    <header class="topbar topbar-default">
        <div class="topbar-left">
            <button class="topbar-icon-button is-passive" type="button" aria-label="メニュー">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <div class="topbar-title">
                <h1><?= h($title) ?></h1>
            </div>
        </div>
        <a class="profile-pill profile-icon-only" href="<?= h(page_url('settings.php')) ?>" id="authBadge" aria-label="プロフィール">
            <span class="material-symbols-outlined">account_circle</span>
            <span data-auth-label>ゲスト利用中</span>
        </a>
    </header>
    <?php endif; ?>
    <main class="page-main">
    <?php
}

function render_page_end(string $activePage, array $scripts = [], bool $showNav = true): void
{
    $navItems = [
        ['key' => 'home', 'href' => page_url('task.php'), 'icon' => 'checklist', 'label' => 'タスク'],
        ['key' => 'calendar', 'href' => page_url('calendar.php'), 'icon' => 'calendar_month', 'label' => 'カレンダー'],
        ['key' => 'stats', 'href' => page_url('stats.php'), 'icon' => 'bar_chart', 'label' => '統計'],
        ['key' => 'feed', 'href' => page_url('feed.php'), 'icon' => 'rss_feed', 'label' => 'フィード'],
        ['key' => 'settings', 'href' => page_url('settings.php'), 'icon' => 'settings', 'label' => '設定'],
    ];
    ?>
    </main>
    <?php if ($showNav): ?>
    <nav class="bottom-nav" aria-label="メインナビゲーション">
        <a class="desktop-nav-brand" href="<?= h(page_url('index.php')) ?>" aria-label="トップへ">
            <img class="desktop-nav-brand-logo desktop-nav-brand-logo-light" src="<?= h(asset_url('img/MitchieTodo_logo_pp.png')) ?>" alt="">
            <img class="desktop-nav-brand-logo desktop-nav-brand-logo-dark" src="<?= h(asset_url('img/MitchieTodo_logo_wp.png')) ?>" alt="">
        </a>
        <?php foreach ($navItems as $item): ?>
            <a href="<?= h($item['href']) ?>" class="bottom-nav-link<?= $activePage === $item['key'] ? ' is-active' : '' ?>">
                <span class="material-symbols-outlined"><?= h($item['icon']) ?></span>
                <span><?= h($item['label']) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
    <?php endif; ?>
</div>
<div class="toast" id="toast" hidden></div>
<script src="<?= h(asset_url('js/app.js')) ?>"></script>
<?php foreach ($scripts as $script): ?>
<script src="<?= h(asset_url('js/' . $script)) ?>"></script>
<?php endforeach; ?>
</body>
</html>
    <?php
}

function render_error_document(string $title, string $message, int $status = 500): void
{
    http_response_code($status);
    ?>
<!DOCTYPE html>
<html lang="ja" data-theme="<?= h(current_theme()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= h($title) ?></title>
    <link rel="stylesheet" href="<?= h(asset_url('css/style.css')) ?>">
</head>
<body class="page-error">
<div class="site-shell">
    <main class="page-main">
        <section class="hero-card split-card">
            <div class="hero-copy">
                <p class="eyebrow">Error <?= (int) $status ?></p>
                <h1><?= h($title) ?></h1>
                <p class="lead"><?= h($message) ?></p>
                <div class="inline-actions">
                    <a class="button button-primary" href="<?= h(page_url('task.php')) ?>">一覧へ戻る</a>
                    <a class="button button-secondary" href="<?= h(page_url('settings.php')) ?>">設定を見る</a>
                </div>
            </div>
            <div class="hero-visual">
                <img src="<?= h(asset_url('img/icon_readingbook.png')) ?>" alt="エラーイメージ">
            </div>
        </section>
    </main>
</div>
</body>
</html>
    <?php
}
