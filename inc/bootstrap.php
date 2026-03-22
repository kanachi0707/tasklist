<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/response.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/validators.php';
require_once __DIR__ . '/categories.php';
require_once __DIR__ . '/guest.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/feed.php';
require_once __DIR__ . '/mailer.php';

date_default_timezone_set((string) config('app.timezone', 'Asia/Tokyo'));

ini_set('display_errors', is_debug() ? '1' : '0');
ini_set('log_errors', '1');

set_exception_handler(static function (Throwable $exception): void {
    error_log('[Mitchie Todo] ' . $exception->getMessage() . "\n" . $exception->getTraceAsString());

    if (is_api_request()) {
        json_error('サーバーでエラーが発生しました。時間を置いて、もう一度お試しください。', 500);
    }

    render_error_document('エラーが発生しました', 'ただいま処理に失敗しました。時間を置いて、もう一度お試しください。', 500);
    exit;
});

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }

    throw new ErrorException($message, 0, $severity, $file, $line);
});

csrf_ensure_token();
ensure_guest_session();
