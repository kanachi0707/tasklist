# Mitchie Todo

Xserver にそのまま置きやすい、PHP + MariaDB + HTML/CSS/JavaScript の Todo アプリです。  
ゲスト利用から始めて、あとからメールのマジックリンクでログインできる構成です。

今回の追加で、Todo を主役にしたまま「人の頑張りが見える Todo」として使えるよう、静かなフィード機能と自分専用の履歴機能を実装しています。

## 概要

- ゲストで今すぐ使える Todo / タスク管理アプリ
- ログイン後にゲスト中のタスクを引き継ぎ可能
- 日本語 UI、モバイルファースト
- ライト / ダークテーマ対応
- 進捗を共有するフィード機能を追加
- 自分専用の履歴機能を追加

## フィード機能の概要

- ログインユーザーのみ、その日の進捗を投稿できます
- 投稿内容は「自動要約 + 定型文 0〜3 個 + アイコン選択」です
- 自由投稿はありません
- 公開フィードには直近 24 時間以内の投稿のみ表示されます
- 投稿は 1 日 1 回までです
- 投稿後の編集はありません
- 投稿削除は自分のもののみ可能です

## 自分の履歴機能の概要

- 自分だけが過去の投稿履歴を見返せます
- 履歴は全件保存されます
- 一度に全件は読み込まず、ページングで取得します
- 直近 20 件ずつ表示し、続きを読み込めます
- 直近 7 日 / 30 日の完了件数、投稿回数、連続日数を簡易表示します

## 投稿仕様

- 投稿はログイン必須
- 当日の完了件数が 1 件以上ある日のみ投稿可能
- 同一 `user_id + post_date` の重複投稿は禁止
- 自動要約はサーバー側で生成
- 定型文はサーバー側許可リストから選択
- 定型文は最大 3 個、重複不可
- 投稿アイコンは `assets/img` 内の `01_` 〜 `03_` の画像から選択

## username 制約

フィードで公開される `username` は次の制約です。

- ASCII のみ
- 使用可能文字: `a-z`, `0-9`, `_`
- 3〜20 文字
- 小文字へ正規化して保存
- 日本語不可
- メール形式不可
- URL 形式不可
- 予約語不可
- NG ワード、ローマ字 NG ワードを禁止

正規表現:

```txt
^[a-z0-9_]{3,20}$
```

## 動作環境

- PHP 8.0 以上
- MariaDB 10.4+ または MySQL 8.0+
- Apache
- HTTPS 利用推奨

## ディレクトリ構成

```text
/
  index.php
  calendar.php
  stats.php
  feed.php
  settings.php
  task-form.php
  privacy.php
  error.php
  .htaccess
  config.sample.php
  README.md
  migration.sql

/assets
  /css
    style.css
  /js
    app.js
    tasks.js
    calendar.js
    stats.js
    auth.js
    settings.js
    feed.js
    task-form-menu.js
  /img

/api
  /auth
  /categories
  /feed
  /history
  /profile
  /stats
  /todos

/inc
  bootstrap.php
  db.php
  auth.php
  guest.php
  feed.php
  mailer.php
  csrf.php
  response.php
  validators.php
  helpers.php
```

## Xserver への設置方法

1. `tasklist` フォルダ一式を Xserver の公開ディレクトリへアップロードします
2. 配置先に合わせて `config.php` の `app.base_url` を設定します
3. `.htaccess` を同梱したまま配置します
4. rewrite が効かない環境でも、`index.php` など直接アクセスで動作します

## DB 作成手順

1. MySQL / MariaDB に `tasklist_db` などの DB を作成
2. 文字コードは `utf8mb4`
3. 照合順序は `utf8mb4_unicode_ci`
4. `migration.sql` をインポート

## config.php の作り方

1. `config.sample.php` をコピーして `config.php` を作成
2. 次を環境に合わせて設定
   - `app.base_url`
   - `security.app_key`
   - `db.*`
   - `smtp.*`
   - `contact.*`

## migration.sql の流し方

### phpMyAdmin

1. 対象 DB を選択
2. `インポート` を開く
3. `migration.sql` を指定して実行

### MySQL コマンド

```bash
mysql -h HOST -u USER -p DATABASE_NAME < migration.sql
```

## SMTP 設定箇所

`config.php` の `smtp` セクションを設定します。

```php
'smtp' => [
    'enabled' => true,
    'host' => 'smtp.example.com',
    'port' => 587,
    'encryption' => 'tls',
    'username' => 'smtp-user@example.com',
    'password' => 'CHANGE_ME',
    'from_email' => 'no-reply@example.com',
    'from_name' => 'Mitchie Todo',
    'timeout' => 15,
],
```

## 追加したテーブル

- `users` に `username` を追加
- `feed_posts`

## 追加した API

### 認証 / プロフィール

- `GET /api/auth/me.php`
- `POST /api/auth/request-magic-link.php`
- `GET /api/auth/verify-magic-link.php?token=...`
- `POST /api/auth/logout.php`
- `POST /api/profile/username.php`

### Todo

- `GET /api/todos/index.php`
- `POST /api/todos/index.php`
- `GET /api/todos/item.php?id=...`
- `PUT /api/todos/item.php?id=...`
- `PATCH /api/todos/toggle.php?id=...`
- `PATCH /api/todos/doing.php?id=...`
- `POST /api/todos/reorder.php`
- `DELETE /api/todos/item.php?id=...`

### Feed / History

- `GET /api/feed/index.php`
- `POST /api/feed/index.php`
- `GET /api/feed/my-status.php`
- `DELETE /api/feed/item.php?id=...`
- `GET /api/history/me.php`
- `GET /api/history/me-summary.php`

### Stats / Categories

- `GET /api/stats/summary.php`
- `GET /api/stats/daily-completion.php?range=7d`
- `GET /api/stats/category-breakdown.php`
- `GET /api/categories/index.php`

## セキュリティ

- PDO のプリペアドステートメントを使用
- CSRF 対策
- XSS 対策
- セッション Cookie / guest_token Cookie を分離
- マジックリンクは平文保存せずハッシュ化
- 所有権チェックを API 側で実施
- feed 投稿はログインユーザーの `user_id` だけを使用
- 定型文はサーバー側許可リストで照合
- username バリデーションを厳格化
- 同日重複投稿を DB と API の両方で防止

## 公開前チェックリスト

- `config.php` を本番値に変更した
- `app.base_url` を本番 URL にした
- `security.app_key` をランダム文字列にした
- `cookie_secure` を HTTPS 前提に合わせた
- DB 接続情報を本番値にした
- SMTP 設定を本番値にした
- 運営者名 / 問い合わせ先を更新した
- マジックリンクメールを本番環境で確認した
- username 更新、投稿、履歴、削除の動作を確認した

## 今後の拡張案

- フィード投稿時のカテゴリ別アイコン最適化
- 履歴の月別アーカイブ
- 投稿日の詳細統計表示
- 通知なしの静かなリマインド
- PWA 化
