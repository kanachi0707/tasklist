<?php

require_once __DIR__ . '/inc/bootstrap.php';

$taskId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEdit = $taskId > 0;
$pageTitle = $isEdit ? 'タスクを編集' : '新しいタスク';

render_page_start($pageTitle, 'task-form', [
    'taskId' => $taskId,
    'header_mode' => 'transactional',
    'header_back_url' => page_url('task.php'),
]);
?>
<div class="task-form-header-menu">
    <button class="topbar-icon-button" type="button" aria-label="メニュー" aria-expanded="false" id="taskFormMenuToggle">
        <span class="material-symbols-outlined">more_horiz</span>
    </button>
    <div class="topbar-dropdown" id="taskFormHeaderMenu" hidden>
        <a class="topbar-dropdown-link" href="<?= h(page_url('task.php')) ?>">
            <span class="material-symbols-outlined">checklist</span>
            <span>タスク</span>
        </a>
        <a class="topbar-dropdown-link" href="<?= h(page_url('calendar.php')) ?>">
            <span class="material-symbols-outlined">calendar_month</span>
            <span>カレンダー</span>
        </a>
        <a class="topbar-dropdown-link" href="<?= h(page_url('stats.php')) ?>">
            <span class="material-symbols-outlined">bar_chart</span>
            <span>統計</span>
        </a>
        <a class="topbar-dropdown-link" href="<?= h(page_url('feed.php')) ?>">
            <span class="material-symbols-outlined">rss_feed</span>
            <span>FEED</span>
        </a>
        <a class="topbar-dropdown-link" href="<?= h(page_url('settings.php')) ?>">
            <span class="material-symbols-outlined">settings</span>
            <span>設定</span>
        </a>
    </div>
</div>

<section class="task-editor-shell">
    <div class="inline-actions hero-actions">
        <a class="button button-secondary" href="<?= h(page_url('task.php')) ?>">戻る</a>
    </div>
    <form id="taskForm" class="task-editor-form">
        <input type="hidden" id="taskId" value="<?= (int) $taskId ?>">

        <label class="task-editor-field">
            <span class="task-editor-label">タスク名</span>
            <input
                id="taskTitle"
                name="title"
                type="text"
                maxlength="120"
                placeholder="やることを入力してください"
                required
            >
        </label>

        <div class="task-editor-group">
            <span class="task-editor-label">カテゴリ</span>
            <input id="taskCategory" name="category_id" type="hidden" value="">
            <div class="task-category-pills" id="taskCategoryPills"></div>
        </div>

        <div class="task-editor-split">
            <label class="task-editor-field has-icon">
                <span class="task-editor-label">期限</span>
                <input id="taskDueDate" name="due_date" type="date">
                <span class="material-symbols-outlined">calendar_today</span>
            </label>

            <div class="task-editor-group">
                <span class="task-editor-label">優先度</span>
                <div class="task-priority-pills">
                    <label class="task-priority-pill">
                        <input type="radio" name="priority" value="low">
                        <span>低</span>
                    </label>
                    <label class="task-priority-pill">
                        <input type="radio" name="priority" value="medium" checked>
                        <span>中</span>
                    </label>
                    <label class="task-priority-pill">
                        <input type="radio" name="priority" value="high">
                        <span>高</span>
                    </label>
                </div>
            </div>
        </div>

        <label class="task-editor-field">
            <span class="task-editor-label">詳細メモ</span>
            <textarea
                id="taskDescription"
                name="description"
                rows="3"
                maxlength="2000"
                placeholder="補足があれば入力してください"
            ></textarea>
        </label>

        <div class="task-editor-actions">
            <button class="task-save-button" type="submit">
                <span class="material-symbols-outlined">check</span>
                <span>タスクを保存</span>
            </button>
        </div>
    </form>

    <div class="task-editor-footer-line" aria-hidden="true"></div>
</section>
<?php render_page_end('', ['auth.js', 'tasks.js', 'task-form-menu.js'], false); ?>
