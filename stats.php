<?php

require_once __DIR__ . '/inc/bootstrap.php';

render_page_start('統計', 'stats');
?>
<section class="stats-hero-card">
    <p class="eyebrow">Productivity Score</p>
    <div class="stats-hero-score">
        <strong id="statsCompletionRate">0%</strong>
        <span>/100</span>
    </div>
    <p class="stats-hero-note"><span class="material-symbols-outlined">trending_up</span> 完了率の目安です</p>
</section>

<section class="stats-insights-grid">
    <article class="stats-mini-card">
        <div>
            <p>今週の完了数</p>
            <strong><span id="statsDoneCount">0</span> <small>タスク</small></strong>
        </div>
        <div class="stats-ring">
            <svg viewBox="0 0 100 100" aria-hidden="true">
                <circle cx="50" cy="50" r="34"></circle>
                <circle cx="50" cy="50" r="34" id="statsRingProgress"></circle>
            </svg>
            <span id="statsRingLabel">0%</span>
        </div>
    </article>
    <article class="stats-mini-card icon-card">
        <div class="icon-wrap"><span class="material-symbols-outlined">calendar_today</span></div>
        <div>
            <p>最も生産的な日</p>
            <strong id="statsBestDay">-</strong>
            <small><span id="statsOpenCount">0</span> 件が未完了</small>
        </div>
    </article>
</section>

<section class="activity-card">
    <div class="section-heading">
        <div>
            <h3>アクティビティ</h3>
            <p class="stats-subtle">過去7日間の推移</p>
        </div>
        <div class="chip-row">
            <button class="chip-button is-active" type="button" data-range="7d">7日</button>
            <button class="chip-button" type="button" data-range="30d">30日</button>
        </div>
    </div>
    <div class="chart-card stats-bars-card">
        <svg id="statsTrendSvg" viewBox="0 0 640 220" role="img" aria-label="完了推移グラフ"></svg>
        <div class="chart-legend" id="statsTrendLegend"></div>
    </div>
</section>

<section class="stats-breakdown-section">
    <div class="section-heading">
        <div>
            <h3>カテゴリー別内訳</h3>
        </div>
    </div>
    <div class="breakdown-list" id="statsCategoryList"></div>
</section>

<section class="stats-insight-note">
    <div class="icon-wrap"><span class="material-symbols-outlined">lightbulb</span></div>
    <div>
        <h3>マインドフルな洞察</h3>
        <p>午後の時間帯にタスクが進みやすい傾向があります。重要な作業を後半へ寄せると集中しやすくなります。</p>
        <p class="stats-total-line">総タスク数 <strong id="statsTotalCount">0</strong></p>
    </div>
</section>
<?php render_page_end('stats', ['auth.js', 'stats.js']); ?>
