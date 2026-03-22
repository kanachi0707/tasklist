<?php

require_once __DIR__ . '/inc/bootstrap.php';

render_page_start('フィード', 'feed');
?>
<section class="hero-card feed-hero-card">
    <div class="hero-copy">
        <p class="eyebrow">FEED</p>
        <p class="lead">みんなの直近24時間の頑張りが見える場所です。いいねボタンで応援し合いましょう。1日2回まで作業のきりの良い所で、あなたの頑張りを投稿してください。</p>
    </div>
</section>

<section class="panel-card feed-compose-card" id="feedComposeCard">
    <div class="section-heading">
        <div>
            <p class="eyebrow">Share</p>
            <h3>今日の成果を共有</h3>
        </div>
    </div>
    <div id="feedComposeState" class="feed-compose-state"></div>
    <form id="feedComposeForm" class="feed-compose-form" hidden>
        <label class="feed-message-field" for="feedMessageSelect">
            <span class="feed-summary-label">投稿メッセージ</span>
            <select class="feed-message-select" id="feedMessageSelect" name="message_variant"></select>
        </label>

        <div class="feed-icon-picker">
            <span class="feed-summary-label">投稿アイコン</span>
            <div class="feed-icon-grid" id="feedIconGrid"></div>
        </div>

        <div class="feed-template-picker">
            <span class="feed-summary-label">定型文を選ぶ・最大3つ</span>
            <div class="feed-template-grid" id="feedTemplateGrid"></div>
            <details class="feed-template-more" id="feedTemplateMore">
                <summary>これ以外のメッセージ</summary>
                <div class="feed-template-grid feed-template-grid-extra" id="feedTemplateExtra"></div>
            </details>
        </div>

        <div class="feed-compose-confirm" id="feedComposeConfirm"></div>
        <div class="feed-compose-actions">
            <button class="button button-primary feed-share-button" type="submit">共有する</button>
        </div>
    </form>
</section>

<section class="panel-card feed-shell-card">
    <div class="feed-shell-head">
        <div class="chip-row feed-mode-tabs" id="feedModeTabs">
            <button class="chip-button is-active" type="button" data-feed-tab="public">みんな</button>
            <button class="chip-button" type="button" data-feed-tab="history">履歴</button>
        </div>
    </div>

    <section class="feed-panel" data-feed-panel="public">
        <div class="feed-stream" id="feedPublicList"></div>
        <div class="empty-state soft-empty" id="feedPublicEmpty" hidden>
            <img src="<?= h(asset_url('img/icon_play.png')) ?>" alt="公開フィードの空状態">
            <p>まだ投稿はありません。今日の成果を最初に共有してみましょう。</p>
        </div>
        <div class="feed-more-wrap">
            <button class="button button-secondary" id="feedPublicMore" type="button" hidden>もっと見る</button>
        </div>
    </section>

    <section class="feed-panel" data-feed-panel="history" hidden>
        <div class="feed-history-summary" id="feedHistorySummary"></div>
        <div class="feed-stream" id="feedHistoryList"></div>
        <div class="empty-state soft-empty" id="feedHistoryEmpty" hidden>
            <img src="<?= h(asset_url('img/icon_readingbook.png')) ?>" alt="履歴の空状態">
            <p>あなたの積み重ねがここに残ります。</p>
        </div>
        <div class="feed-more-wrap">
            <button class="button button-secondary" id="feedHistoryMore" type="button" hidden>もっと見る</button>
        </div>
    </section>
</section>
<?php render_page_end('feed', ['auth.js', 'feed.js']); ?>
