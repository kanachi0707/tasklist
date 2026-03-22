<?php

require_once __DIR__ . '/inc/bootstrap.php';

$authMessage = (string) ($_GET['auth'] ?? '');
$currentUser = current_user();
$isProfilePage = $currentUser !== null;

render_page_start('設定', 'settings', [
    'authMessage' => $authMessage,
]);
?>
<?php if ($isProfilePage): ?>
<section class="panel-card profile-panel compact-panel">
    <div class="section-heading">
        <div>
            <p class="eyebrow">Profile</p>
            <h3>プロフィール</h3>
        </div>
    </div>
    <div class="stack-card profile-card compact-stack">
        <div class="profile-card-head">
            <div class="profile-avatar" aria-hidden="true">
                <span class="material-symbols-outlined">account_circle</span>
            </div>
            <div class="profile-copy">
                <strong><?= h($currentUser['email']) ?></strong>
                <p>ログイン中です。表示名や基本設定をここで整えられます。</p>
            </div>
        </div>

        <div class="info-list compact-info-list">
            <div class="info-row static">
                <span>ログイン状態</span>
                <strong>有効</strong>
            </div>
            <div class="info-row static">
                <span>メールアドレス</span>
                <strong><?= h($currentUser['email']) ?></strong>
            </div>
            <div class="info-row static">
                <span>ユーザー名</span>
                <strong id="settingsCurrentUsername"><?= h($currentUser['username'] ?: '未設定') ?></strong>
            </div>
            <div class="info-row static">
                <span>利用開始日</span>
                <strong><?= h(substr((string) $currentUser['created_at'], 0, 10)) ?></strong>
            </div>
        </div>

        <form class="form-grid compact-form" id="usernameForm">
            <label class="field">
                <span>公開ユーザー名</span>
                <input
                    id="usernameInput"
                    name="username"
                    type="text"
                    inputmode="latin"
                    autocomplete="off"
                    maxlength="20"
                    placeholder="todo_user"
                    value="<?= h($currentUser['username'] ?? '') ?>"
                >
            </label>
            <p class="field-note">半角英数と `_` のみ、3〜20文字でフィードに表示されます。</p>
            <button class="button button-primary" type="submit">ユーザー名を更新</button>
        </form>

        <div class="inline-actions profile-actions">
            <button class="button button-secondary" id="logoutButton" type="button">ログアウト</button>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($authMessage !== ''): ?>
<section class="notice-card <?= in_array($authMessage, ['expired', 'invalid', 'used'], true) ? 'error' : 'success' ?>" id="settingsBanner">
    <span class="material-symbols-outlined">
        <?= in_array($authMessage, ['expired', 'invalid', 'used'], true) ? 'error' : 'mark_email_read' ?>
    </span>
    <div>
        <?php if ($authMessage === 'expired'): ?>
            <strong>リンクの有効期限が切れました。</strong>
            <p>もう一度メールアドレスを入力して、新しいリンクを送信してください。</p>
        <?php elseif ($authMessage === 'invalid'): ?>
            <strong>ログインリンクを確認できませんでした。</strong>
            <p>URL が途中で切れていないか確認して、必要なら再送してください。</p>
        <?php elseif ($authMessage === 'used'): ?>
            <strong>このリンクはすでに使用されています。</strong>
            <p>必要であれば、もう一度ログイン用リンクを送信してください。</p>
        <?php else: ?>
            <strong>ログイン用リンクを送信しました。</strong>
            <p>メールをご確認ください。</p>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<?php if (!$isProfilePage): ?>
<section class="panel-card compact-panel">
    <div class="section-heading">
        <div>
            <p class="eyebrow">Authentication</p>
            <h3>ログイン</h3>
        </div>
    </div>
    <div class="stack-card compact-stack">
        <div class="auth-status-row">
            <div>
                <strong id="settingsAuthStatus">ログインすると他端末でも続きから使えます</strong>
                <p id="settingsAuthDetail">メールアドレスだけで、あとからログインできます。</p>
            </div>
            <button class="button button-secondary" id="logoutButton" type="button" hidden>ログアウト</button>
        </div>

        <form id="magicLinkForm" class="form-grid compact-form">
            <label class="field">
                <span>メールアドレスを入力してください</span>
                <input id="emailInput" name="email" type="email" inputmode="email" autocomplete="email" placeholder="you@example.com" required>
            </label>
            <button class="button button-primary" type="submit">ログイン用リンクを送信</button>
        </form>

        <div class="empty-state soft-empty" id="magicLinkSent" hidden>
            <img src="<?= h(asset_url('img/icon_play.png')) ?>" alt="メール送信の案内">
            <p>ログイン用リンクを送信しました。メールをご確認ください。</p>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="panel-card appearance-panel compact-panel">
    <div class="section-heading">
        <div>
            <p class="eyebrow">Appearance</p>
            <h3>テーマ</h3>
        </div>
    </div>
    <div class="appearance-panel-body">
        <div class="chip-row">
            <button class="chip-button" type="button" data-theme-option="light">ライト</button>
            <button class="chip-button" type="button" data-theme-option="dark">ダーク</button>
        </div>
        <div class="appearance-visual" aria-hidden="true">
            <img src="<?= h(asset_url('img/icon_readingbook.png')) ?>" alt="">
        </div>
    </div>
</section>

<section class="panel-card category-panel compact-panel">
    <div class="section-heading">
        <div>
            <p class="eyebrow">Categories</p>
            <h3>カテゴリ管理</h3>
        </div>
    </div>
    <div class="stack-card compact-stack">
        <p class="field-note" id="categoryPanelNote">
            <?= $isProfilePage ? 'デフォルトカテゴリは固定です。ユーザーカテゴリは10件まで追加できます。' : 'デフォルトカテゴリは固定です。ユーザーカテゴリを使うにはログインしてください。'; ?>
        </p>

        <form class="form-grid compact-form category-form" id="categoryForm" <?= $isProfilePage ? '' : 'hidden' ?>>
            <input type="hidden" id="categoryEditId" value="">
            <label class="field category-inline-field">
                <span>ユーザーカテゴリ追加 <span class="category-limit-inline" id="categoryLimitMeta"></span></span>
                <div class="category-inline-inputs">
                    <input id="categoryNameInput" name="name" type="text" maxlength="30" placeholder="新しいカテゴリ名">
                    <button class="button button-primary button-compact" id="categorySubmitButton" type="submit">追加</button>
                </div>
            </label>
            <div class="inline-actions category-form-actions">
                <button class="button button-secondary" id="categoryCancelButton" type="button" hidden>キャンセル</button>
            </div>
        </form>

        <div class="category-list" id="categoryList"></div>
    </div>
</section>

<section class="panel-card about-panel compact-panel">
    <div class="section-heading">
        <div>
            <p class="eyebrow">About</p>
            <h3>アプリ情報</h3>
        </div>
    </div>
    <div class="info-list compact-info-list">
        <a class="info-row" href="<?= h(page_url('privacy.php')) ?>">
            <span>プライバシーポリシー</span>
            <span class="material-symbols-outlined">open_in_new</span>
        </a>
        <a class="info-row" href="<?= h(page_url('updates.php')) ?>">
            <span>アップデート情報</span>
            <span class="material-symbols-outlined">open_in_new</span>
        </a>
        <div class="info-row static">
            <span>アプリ名</span>
            <strong>Mitchie ToDo! (β)</strong>
        </div>
    </div>
    <div class="install-hint-block">
        <button class="install-hint-button" id="install-button" type="button">ホーム画面に追加</button>
        <p class="install-hint-note">iPhone / Android の共有メニューから、このアプリをホーム画面に追加できます。</p>
    </div>
    <div class="install-dialog-overlay" id="install-dialog" hidden>
        <div class="dialog-card install-dialog-panel" role="dialog" aria-modal="true" aria-labelledby="install-dialog-title">
            <h2 id="install-dialog-title">ホーム画面に追加</h2>
            <div class="dialog-body" id="install-dialog-body"></div>
            <button class="button button-primary dialog-close" id="install-dialog-close" type="button">閉じる</button>
        </div>
    </div>
    <div class="about-meta">
        <p class="version-note">ver. 0.02</p>
        <p class="copyright-note">&copy; <?= h(now()->format('Y')) ?> <?= h(config('app.name', 'Mitchie ToDo!')) ?></p>
        <p class="copyright-note">&copy; 再生の道／みっちー／こみっちー</p>
    </div>
</section>
<?php render_page_end('settings', ['auth.js', 'settings.js'], true); ?>
