<?php

require_once __DIR__ . '/inc/bootstrap.php';

render_page_start('アップデート情報', 'updates', [
    'header_mode' => 'transactional',
    'header_back_url' => page_url('settings.php'),
]);
?>
<section class="hero-card split-card">
    <div class="hero-copy">
        <div class="inline-actions hero-actions">
            <a class="button button-secondary" href="<?= h(page_url('settings.php')) ?>">戻る</a>
        </div>
    </div>
    <div class="hero-visual">
        <img src="<?= h(asset_url('img/icon_readingbook.png')) ?>" alt="アップデート情報のイメージ">
    </div>
</section>

<section class="panel-card prose-card">
    <h3>Ver. 0.01 | 2026.03.21</h3>
    <p>β版を公開しました。</p>

    <h3>Ver. 0.02 | 2026.03.21</h3>
    <p>タスクカードのレイアウトを調整し、ホーム画面に追加機能を実装しました。</p>
</section>
<?php render_page_end('', ['auth.js']); ?>
