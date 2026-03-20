<?php

require_once __DIR__ . '/inc/bootstrap.php';

$authMessage = (string) ($_GET['auth'] ?? '');
$currentUser = current_user();
$isProfilePage = $currentUser !== null;

render_page_start($isProfilePage ? 'プロフィール' : '設定', 'settings', [
    'authMessage' => $authMessage,
]);
?>
<?php if ($isProfilePage): ?>
    <section class="panel-card profile-panel">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Profile</p>
                <h3>プロフィール</h3>
            </div>
        </div>
        <div class="stack-card profile-card">
            <div class="profile-card-head">
                <div class="profile-avatar" aria-hidden="true">
                    <span class="material-symbols-outlined">account_circle</span>
                </div>
                <div class="profile-copy">
                    <strong><?= h($currentUser['email']) ?></strong>
                    <p>ログイン済みです。ここで表示名や見た目を整えられます。</p>
                </div>
            </div>

            <div class="info-list">
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

            <form class="form-grid" id="usernameForm">
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
                <p class="field-note">英小文字・数字・`_` のみ、3〜20文字。フィードに公開表示されます。</p>
                <button class="button button-primary" type="submit">ユーザー名を保存</button>
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
                <p>もう一度メールアドレスを入力して、新しいリンクを受け取ってください。</p>
            <?php elseif ($authMessage === 'invalid'): ?>
                <strong>ログインリンクを確認できませんでした。</strong>
                <p>URL が途中で切れていないか確認して、必要なら再送してください。</p>
            <?php elseif ($authMessage === 'used'): ?>
                <strong>このリンクはすでに使用されています。</strong>
                <p>必要であれば、新しいログインリンクを送信してください。</p>
            <?php else: ?>
                <strong>ログイン用リンクを送信しました。</strong>
                <p>メールを確認してください。</p>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>

<?php if (!$isProfilePage): ?>
    <section class="panel-card">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Authentication</p>
                <h3>ログイン</h3>
            </div>
        </div>
        <div class="stack-card">
            <div class="auth-status-row">
                <div>
                    <strong id="settingsAuthStatus">ログインすると他端末でも続きから使えます</strong>
                    <p id="settingsAuthDetail">メールアドレスだけで、あとから安全にログインできます。</p>
                </div>
                <button class="button button-secondary" id="logoutButton" type="button" hidden>ログアウト</button>
            </div>

            <form id="magicLinkForm" class="form-grid">
                <label class="field">
                    <span>メールアドレスを入力してください</span>
                    <input id="emailInput" name="email" type="email" inputmode="email" autocomplete="email" placeholder="you@example.com" required>
                </label>
                <button class="button button-primary" type="submit">ログイン用リンクを送る</button>
            </form>

            <div class="empty-state soft-empty" id="magicLinkSent" hidden>
                <img src="<?= h(asset_url('img/icon_play.png')) ?>" alt="メール送信のアイコン">
                <p>ログイン用リンクを送信しました。メールを確認してください。</p>
                <a id="debugMagicLink" class="text-link" href="#" hidden>デバッグ用リンクを開く</a>
            </div>
        </div>
    </section>
<?php endif; ?>

<section class="panel-card appearance-panel">
    <div class="section-heading">
        <div>
            <p class="eyebrow">Appearance</p>
            <h3>テーマ</h3>
        </div>
    </div>
    <div class="appearance-panel-body">
        <div class="appearance-visual" aria-hidden="true">
            <img src="<?= h(asset_url('img/icon_readingbook.png')) ?>" alt="">
        </div>
        <div class="chip-row">
            <button class="chip-button" type="button" data-theme-option="light">ライト</button>
            <button class="chip-button" type="button" data-theme-option="dark">ダーク</button>
        </div>
    </div>
</section>

<section class="panel-card">
    <div class="section-heading">
        <div>
            <p class="eyebrow">About</p>
            <h3>アプリ情報</h3>
        </div>
    </div>
    <div class="info-list">
        <a class="info-row" href="<?= h(page_url('privacy.php')) ?>">
            <span>プライバシーポリシー</span>
            <span class="material-symbols-outlined">open_in_new</span>
        </a>
        <div class="info-row static">
            <span>アプリ名</span>
            <strong><?= h(config('app.name', 'Mitchie Todo')) ?></strong>
        </div>
        <div class="info-row static">
            <span>技術</span>
            <strong>PHP / MariaDB / Vanilla JS</strong>
        </div>
    </div>
    <div class="about-meta">
        <p class="version-note">ver. 0.01</p>
        <p class="copyright-note">&copy; <?= h(now()->format('Y')) ?> <?= h(config('app.name', 'Mitchie Todo')) ?></p>
        <p class="copyright-note">&copy; 再生の道／みっちー／こみっちー</p>
    </div>
</section>
<?php render_page_end('settings', ['auth.js', 'settings.js'], !$isProfilePage); ?>
