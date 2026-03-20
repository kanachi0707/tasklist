<?php

require_once __DIR__ . '/inc/bootstrap.php';

$authState = (string) ($_GET['auth'] ?? '');
$today = now();
$weekdayLabels = ['日', '月', '火', '水', '木', '金', '土'];
$homeYearLabel = $today->format('Y');
$homeDateLabel = $today->format('n/j');
$homeWeekdayLabel = $weekdayLabels[(int) $today->format('w')];
$monthEnd = $today->modify('last day of this month')->setTime(0, 0);
$yearEnd = $today->setDate((int) $today->format('Y'), 12, 31)->setTime(0, 0);
$todayStart = $today->setTime(0, 0);
$remainingMonthDays = (int) $todayStart->diff($monthEnd)->format('%a');
$remainingYearDays = (int) $todayStart->diff($yearEnd)->format('%a');

render_page_start('タスク', 'home', [
    'authState' => $authState,
]);
?>
<section class="mindful-hero">
    <div class="mindful-hero-copy">
        <p class="eyebrow"><?= h($homeYearLabel) ?></p>
        <h2><?= h($homeDateLabel) ?>(<?= h($homeWeekdayLabel) ?>)</h2>
        <p id="homeSummaryLine"><?= h(current_date_label()) ?>・残り 0 件のタスク</p>
    </div>
    <div class="mindful-hero-background" aria-hidden="true">
        <img class="hero-logo hero-logo-light" src="<?= h(asset_url('img/MitchieTodo_logo_p.png')) ?>" alt="">
        <img class="hero-logo hero-logo-dark" src="<?= h(asset_url('img/MitchieTodo_logo.png')) ?>" alt="">
    </div>
</section>

<?php if ($authState === 'verified'): ?>
    <section class="notice-card success">
        <span class="material-symbols-outlined">mark_email_read</span>
        <div>
            <strong>ログインが完了しました。</strong>
            <p>ゲスト利用中のタスクがあれば、現在のアカウントへ引き継がれています。</p>
        </div>
    </section>
<?php endif; ?>

<section class="task-bento-grid">
    <article class="score-card">
        <span class="material-symbols-outlined">bolt</span>
        <strong id="homeOpenCount">0</strong>
        <p>未完了タスク</p>
    </article>
    <article class="prompt-card">
        <span class="material-symbols-outlined">wb_twilight</span>
        <h3>整える時間です</h3>
        <p><span id="homeTodayCount">0</span> 件が今日の予定です</p>
    </article>
</section>

<div class="home-main-column">
    <section class="filter-toolbar filter-toolbar-home">
        <label class="task-search-field" aria-label="タスクを検索">
            <span class="material-symbols-outlined">search</span>
            <input id="taskSearchInput" type="search" placeholder="タスクを検索">
        </label>
        <div class="sort-chip-row" id="sortFilters">
            <button class="sort-chip-button" data-sort-mode="due" type="button">
                <span class="material-symbols-outlined">schedule</span>
                <span>日付順</span>
            </button>
            <button class="sort-chip-button" data-sort-mode="priority" type="button">
                <span class="material-symbols-outlined">flag</span>
                <span>優先度順</span>
            </button>
        </div>
    </section>

    <section class="task-stream-section">
        <div class="todo-list task-stream" id="todoOpenList"></div>
        <div class="empty-state" id="todoOpenEmpty" hidden>
            <img src="<?= h(asset_url('img/icon_play.png')) ?>" alt="空のタスク一覧アイコン">
            <p>まだタスクはありません。思いついたことを追加してみましょう。</p>
        </div>
    </section>

    <section class="completed-section" id="homeCompletedSection">
        <div class="completed-heading">
            <h3>完了済み</h3>
            <span class="counter-pill" id="doneListCount">0件</span>
        </div>
        <div class="todo-list task-stream completed-stream" id="todoDoneList"></div>
        <div class="empty-state" id="todoDoneEmpty" hidden>
            <img src="<?= h(asset_url('img/icon_readingbook.png')) ?>" alt="完了済みタスクのアイコン">
            <p>完了したタスクはここにまとまります。</p>
        </div>
    </section>
</div>

<aside class="home-side-column" aria-label="サイド情報">
    <section class="panel-card home-side-card home-date-card">
        <p class="eyebrow"><?= h($homeYearLabel) ?></p>
        <h3><?= h($homeDateLabel) ?>(<?= h($homeWeekdayLabel) ?>)</h3>
        <p>今月はあと<?= h($remainingMonthDays) ?>日、今年はあと<?= h($remainingYearDays) ?>日</p>
    </section>

    <section class="panel-card home-side-card home-open-summary-card">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Open</p>
                <h3>未完了タスク</h3>
            </div>
        </div>
        <strong class="home-side-total" id="homeSidebarOpenTotal">0件</strong>
        <div class="home-side-breakdown" id="homeSidebarOpenBreakdown"></div>
    </section>

    <section class="panel-card home-side-card home-upcoming-card">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Upcoming</p>
                <h3>期限が近いタスク</h3>
            </div>
        </div>
        <div class="home-upcoming-list" id="homeUpcomingList"></div>
        <p class="home-upcoming-empty" id="homeUpcomingEmpty" hidden>明日以降に期限があるタスクはまだありません。</p>
    </section>
</aside>

<a class="fab-button" href="<?= h(page_url('task-form.php')) ?>" aria-label="タスクを追加">
    <span class="material-symbols-outlined">add</span>
</a>
<?php render_page_end('home', ['auth.js', 'tasks.js']); ?>
