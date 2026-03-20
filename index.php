<?php

require_once __DIR__ . '/inc/bootstrap.php';

$query = [];
if (isset($_GET['auth']) && $_GET['auth'] !== '') {
    $query['auth'] = (string) $_GET['auth'];
}

$redirectTarget = page_url('task.php', $query);

render_page_start('Mitchie Todo', 'landing', [
    'header_mode' => 'none',
    'show_splash' => true,
    'redirect_after_splash' => $redirectTarget,
]);
?>
<section class="landing-redirect-shell" aria-hidden="true"></section>
<?php render_page_end('', ['auth.js'], false); ?>
