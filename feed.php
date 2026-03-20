<?php

require_once __DIR__ . '/inc/bootstrap.php';

render_page_start('フィード', 'feed');
?>
<section class="hero-card feed-hero-card">
    <div class="hero-copy">
        <p class="eyebrow">Quiet Feed</p>
        <h2>今日の積み重ねを、静かに共有する</h2>
        <p class="lead">大きな声ではなく、小さな前進が見える場所です。みんなの24時間と、自分の積み重ねを振り返れます。</p>
    </div>
    <div class="hero-visual">
        <img src="<?= h(asset_url('img/03_komitchie.png')) ?>" alt="フィードのイメージ">
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
        <div class="feed-summary-preview">
            <span class="feed-summary-label">自動要約</span>
            <p id="feedAutoSummary">今日は一歩だけ前に進めました</p>
        </div>

        <div class="feed-icon-picker">
            <span class="feed-summary-label">投稿アイコン</span>
            <div class="feed-icon-grid" id="feedIconGrid"></div>
        </div>

        <div class="feed-template-picker">
            <span class="feed-summary-label">定型文を選ぶ（最大3つ）</span>
            <div class="feed-template-grid" id="feedTemplateGrid"></div>
        </div>

        <button class="button button-primary feed-share-button" type="submit">共有する</button>
    </form>
</section>

<section class="panel-card feed-tabs-card">
    <div class="chip-row feed-mode-tabs" id="feedModeTabs">
        <button class="chip-button is-active" type="button" data-feed-tab="public">みんな</button>
        <button class="chip-button" type="button" data-feed-tab="history">履歴</button>
    </div>
</section>

<section class="feed-grid">
    <section class="panel-card feed-stream-panel" data-feed-panel="public">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Everyone</p>
                <h3>直近24時間の進捗</h3>
            </div>
        </div>
        <div class="feed-stream" id="feedPublicList"></div>
        <div class="empty-state soft-empty" id="feedPublicEmpty" hidden>
            <img src="<?= h(asset_url('img/icon_play.png')) ?>" alt="公開フィードの空状態">
            <p>まだ投稿はありません。今日の成果を最初に共有してみましょう。</p>
        </div>
        <div class="feed-more-wrap">
            <button class="button button-secondary" id="feedPublicMore" type="button" hidden>もっと見る</button>
        </div>
    </section>

    <section class="panel-card feed-history-panel" data-feed-panel="history" hidden>
        <div class="section-heading">
            <div>
                <p class="eyebrow">My History</p>
                <h3>自分の積み重ね</h3>
            </div>
        </div>
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
