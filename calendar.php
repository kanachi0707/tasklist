<?php

require_once __DIR__ . '/inc/bootstrap.php';

render_page_start('カレンダー', 'calendar');
?>
<section class="calendar-month-header">
    <div>
        <p class="eyebrow" id="calendarMonthSubLabel">2024年 5月</p>
        <h2 id="calendarMonthLabel">MAY</h2>
    </div>
    <div class="inline-actions compact-actions">
        <button class="icon-button" id="calendarPrev" type="button" aria-label="前の月へ">
            <span class="material-symbols-outlined">chevron_left</span>
        </button>
        <button class="icon-button" id="calendarNext" type="button" aria-label="次の月へ">
            <span class="material-symbols-outlined">chevron_right</span>
        </button>
    </div>
</section>

<section class="month-view-card">
    <div class="calendar-grid calendar-grid-stitch" id="calendarGrid"></div>
</section>

<section class="agenda-section">
    <div class="agenda-heading">
        <div class="agenda-heading-copy">
            <h3>本日の予定</h3>
            <span id="selectedDateLabel">日付を選択</span>
        </div>
        <span class="counter-pill calendar-status-summary" id="selectedDateCount">未完了 0件 / 完了 0件</span>
    </div>
    <div class="chip-row calendar-status-filters" id="calendarStatusFilters">
        <button class="chip-button is-active" data-calendar-filter="all" type="button">すべて</button>
        <button class="chip-button" data-calendar-filter="open" type="button">未完了</button>
        <button class="chip-button" data-calendar-filter="done" type="button">完了</button>
    </div>
    <div class="todo-list agenda-stream" id="calendarDayList"></div>
    <div class="empty-state" id="calendarDayEmpty">
        <img src="<?= h(asset_url('img/icon_shoppingbag.png')) ?>" alt="空の予定アイコン">
        <p>この日には予定されているタスクがありません。</p>
    </div>
</section>

<a class="fab-button" href="<?= h(page_url('task-form.php')) ?>" aria-label="タスクを追加">
    <span class="material-symbols-outlined">add</span>
</a>
<?php render_page_end('calendar', ['auth.js', 'calendar.js']); ?>
