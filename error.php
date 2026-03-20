<?php

require_once __DIR__ . '/inc/bootstrap.php';

$status = isset($_SERVER['REDIRECT_STATUS']) ? (int) $_SERVER['REDIRECT_STATUS'] : 404;
$title = $status === 404 ? 'ページが見つかりません' : 'ただいまアクセスできません';
$message = $status === 404
    ? 'URLをご確認いただくか、下のボタンから一覧画面へお戻りください。'
    : 'しばらく時間をおいてから再度お試しください。';

render_error_document($title, $message, $status);
