# Xserver 手動設定手順メモ

## 1. 配置先を決める

- 例: `public_html/tasklist/`
- 公開 URL 例: `https://example.com/tasklist`

## 2. ファイルをアップロードする

- `tasklist` ディレクトリ配下をすべてアップロード
- `config.php` は `config.sample.php` を元にサーバー上で作成するか、ローカルで作ってからアップロード

## 3. MySQL を作成する

- Xserver サーバーパネルで MySQL を新規作成
- MySQL ユーザーを作成
- 対象 DB に権限を付与

## 4. テーブルを作成する

- phpMyAdmin へログイン
- 作成した DB を選択
- `migration.sql` をインポート

## 5. `config.php` を設定する

- `app.base_url` を本番 URL にする
- `security.app_key` を十分長いランダム文字列へ変更する
- `db` 接続情報を Xserver の MySQL 情報に合わせる
- `smtp` 情報を使うメールサーバーに合わせる
- `contact` 情報を公開用の運営者情報へ変更する

## 6. SMTP を確認する

- Xserver メール、または外部 SMTP を使う
- 送信元アドレスが認証情報と一致しているか確認
- `tls` / `ssl`、ポート番号が正しいか確認

## 7. rewrite の確認

- `.htaccess` を配置
- `https://example.com/tasklist/calendar` へアクセスできれば rewrite が効いています
- rewrite が効かない場合でも `calendar.php` で動作確認できます

## 8. HTTPS と Cookie を確認する

- SSL 設定が有効なドメインで公開する
- `config.php` の `cookie_secure` を本番では `true` にする

## 9. 動作確認

- ゲストでタスクを追加
- タスクの編集 / 完了 / 削除
- カレンダー表示
- 統計表示
- マジックリンク送信
- メールのリンクからログイン
- ゲストタスクの引き継ぎ
