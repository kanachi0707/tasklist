# どこを手動で設定するか一覧

## `config.php` で必ず設定する項目

- `app.base_url`
- `app.env`
- `app.debug`
- `security.app_key`
- `security.cookie_secure`
- `db.host`
- `db.port`
- `db.database`
- `db.username`
- `db.password`
- `smtp.enabled`
- `smtp.host`
- `smtp.port`
- `smtp.encryption`
- `smtp.username`
- `smtp.password`
- `smtp.from_email`
- `smtp.from_name`
- `contact.operator_name`
- `contact.support_email`

## Xserver 管理画面で手動設定する項目

- MySQL データベース作成
- MySQL ユーザー作成と権限付与
- SSL / HTTPS の有効化
- 必要に応じたメールアカウントや SMTP 利用設定

## 公開前に差し替える文言

- `privacy.php` の運営者名
- `privacy.php` のお問い合わせ先
- 必要なら `config.php` のアプリ名

## 動作確認として手動で行う項目

- マジックリンクメールの受信確認
- ログイン後のタスク移行確認
- ライト / ダークテーマ切替確認
- スマホ表示確認
- Cookie の Secure / HttpOnly / SameSite 確認
