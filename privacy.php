<?php

require_once __DIR__ . '/inc/bootstrap.php';

render_page_start('プライバシーポリシー', 'privacy');
?>
<section class="hero-card split-card">
    <div class="hero-copy">
        <p class="eyebrow">Privacy Policy Draft</p>
        <h2>取得する情報と、その使い方について。</h2>
        <div class="inline-actions hero-actions">
            <a class="button button-secondary" href="<?= h(page_url('settings.php')) ?>">設定へ戻る</a>
        </div>
    </div>
    <div class="hero-visual">
        <img src="<?= h(asset_url('img/icon_readingbook.png')) ?>" alt="プライバシーポリシーのイメージ">
    </div>
</section>

<section class="panel-card prose-card">
    <h3>1. 取得する情報</h3>
    <p>本アプリでは、タスク管理機能を提供するために、タスク内容、カテゴリ、期限、優先度、詳細メモ、ログイン用メールアドレス、Cookie情報を取得します。</p>

    <h3>2. Cookie の利用</h3>
    <p>ゲスト利用を継続するための guest_token、ログイン状態を保持するための session Cookie、表示テーマの保存のための Cookie を利用します。</p>

    <h3>3. タスクデータの保存</h3>
    <p>入力されたタスクデータは MariaDB / MySQL に保存され、ゲスト利用時は guest session、ログイン後はユーザーアカウントに紐づけて管理します。</p>

    <h3>4. ログイン用メールアドレスの利用</h3>
    <p>メールアドレスは、パスワードレスのマジックリンク認証を行う目的に限って利用します。ログインリンクは短時間で失効し、使い捨てです。</p>

    <h3>5. 第三者提供</h3>
    <p>法令に基づく場合を除き、取得した情報を第三者へ提供しません。</p>

    <h3>6. お問い合わせ先</h3>
    <p><!-- TODO: 運営者名とお問い合わせ先を本番公開前に差し替えてください。 --> 運営者: <?= h(config('contact.operator_name', 'TODO: 運営者名を設定')) ?><br>お問い合わせ: <?= h(config('contact.support_email', 'TODO: support@example.com を設定')) ?></p>

    <h3>7. 改定について</h3>
    <p>本ポリシーは、必要に応じて改定されることがあります。重要な変更がある場合は、アプリ上または掲載ページで告知します。</p>
</section>
<?php render_page_end('', ['auth.js']); ?>
