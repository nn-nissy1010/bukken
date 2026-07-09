# 本番デプロイ手順書（居住者専用サイト / WordPressマルチサイト）

お名前.com のサーバーを想定した本番構築の手順。**上から順に**実施する。
コード化済みの補助ファイルはこの `deploy/` にある：

| ファイル | 役割 |
|---|---|
| `wp-config-snippet.php` | wp-config.php に追記する定数（マルチサイト/SMTP/ハードニング）のひな形 |
| `htaccess-multisite.txt` | サブディレクトリ型マルチサイト用 `.htaccess` |
| `mu-plugins/mrc-smtp.php` | 問い合わせメールを外部SMTPで送る本番用 mu-plugin |
| `create-pages.php` | 雛形サイトに必須固定ページだけ作る本番安全版スクリプト（デモ無し） |
| `deploy-theme.sh` | テーマ＋mu-plugin をサーバーへ同期（rsync/ssh） |
| `bootstrap.sh` | テーマ/プラグイン有効化・ページ作成・メール疎通（ssh+WP-CLI） |
| `.env.example` | 上記スクリプトの接続情報。コピーして `deploy/.env` に実値を入れる |

---

## 事前に共有いただきたいサーバー情報（このチェックリストを埋める）

スクリプトの host 固有部分を確定するために必要：

- [ ] プラン種別（お名前.com **共用レンタルサーバー** / **VPS** のどちら）
- [ ] **SSH 接続**の可否（ホスト/ユーザー/ポート/鍵 or パスワード）
- [ ] **WP-CLI** が使えるか（`wp --info` が通るか。無ければ phar を置けるか）
- [ ] PHP バージョン（**8.1+ 推奨**）／MySQL(MariaDB) バージョン
- [ ] 独自ドメインと **SSL証明書**（Let's Encrypt 等）の準備状況
- [ ] WordPress ルートの絶対パス（`public_html` 等）
- [ ] メール送信手段（外部SMTP契約の有無：SendGrid/SES/Mailgun 等）
- [ ] コントロールパネルの種類（phpMyAdmin / ファイルマネージャの有無）

> これらが分かれば `deploy/.env` を確定し、`deploy-theme.sh` / `bootstrap.sh` をそのまま使えます。
> SSH や WP-CLI が使えない共用プランの場合は、各フェーズの「WP-CLIが無い場合」を手動で行います。

---

## フェーズ1：サーバーとドメインの準備
1. 独自ドメインをサーバーに割り当て、**HTTPS（SSL）を有効化**（お名前.com の無料SSL/Let's Encrypt）。
2. PHP を **8.1以上**、必要拡張（mysqli, mbstring, gd/imagick, curl, zip）を確認。
3. データベース（MySQL/MariaDB）を1つ作成し、DB名・ユーザー・パスワードを控える。

## フェーズ2：WordPress 本体の設置
- お名前.com の「WordPress簡単インストール」を使うか、手動で最新版を配置。
- インストール時の管理者は仮でよい（後で MRC 用に整える）。
- この時点で `https://ドメイン/wp-admin/` にログインできること。

## フェーズ3：マルチサイト化（最重要）
> サブディレクトリ型（`/house1/` のようなパス型）で構築する。ローカルと同じ構成。

1. **wp-config.php に (A) を追記**（`deploy/wp-config-snippet.php` の 1-(A)）:
   ```php
   define( 'WP_ALLOW_MULTISITE', true );
   ```
2. 管理画面 **ツール › ネットワークの設置** →「**サブディレクトリ**」を選択して設置。
   （※ サイト開設から日が浅い新規インストールならサブディレクトリ選択で問題なし）
3. 画面の指示どおり、**wp-config.php に (B) の定数**を追記
   （`deploy/wp-config-snippet.php` の 1-(B)。`DOMAIN_CURRENT_SITE` を実ドメインに）。
4. **.htaccess を差し替え**（`deploy/htaccess-multisite.txt` の WordPress ブロック。
   画面が表示する内容と同じならそちらを優先）。
5. 再ログインし、**ネットワーク管理（サイトネットワーク管理者）**が出ることを確認。
6. ついでに `wp-config-snippet.php` の 3)ハードニング・4)SALT差し替えも実施。

## フェーズ4：テーマ・プラグイン・mu-plugin の配置
**WP-CLI/SSH がある場合：**
```bash
cp deploy/.env.example deploy/.env   # 実値を記入
deploy/deploy-theme.sh               # テーマ + mrc-smtp.php を同期
```
**WP-CLIが無い場合（SFTP/ファイルマネージャ）：**
- `wp/themes/mrc-residents/`（`dev/` は除く）を
  `wp-content/themes/mrc-residents/` にアップロード。
- `deploy/mu-plugins/mrc-smtp.php` を `wp-content/mu-plugins/` にアップロード。
- NS Cloner プラグインを管理画面「プラグイン › 新規追加」から導入。

## フェーズ5：初期セットアップ（雛形サイト）
**WP-CLI/SSH がある場合：**
```bash
deploy/bootstrap.sh
```
（テーマ/NS Clonerのネットワーク有効化 → 必須ページ作成 → メール疎通テスト）

**WP-CLIが無い場合（手動）：**
1. ネットワーク管理 › テーマ で **mrc-residents をネットワーク有効化**。
2. ネットワーク管理 › プラグイン で **NS Cloner をネットワーク有効化**。
3. メインサイトで **固定ページを4枚**作成（本文は空でよい。slug 厳守）：
   - `member`（会員トップ） / `plan`（工事の計画）
   - `contact`（ご意見の窓口） / `contact-public`（お問い合わせ）
4. 設定 › パーマリンク を `/%postname%/` に。設定 › 表示設定 で
   「検索エンジンでの表示」を**インデックスさせない**に。

> ⚠️ **`wp/themes/mrc-residents/dev/seed.php` は本番で実行しない**。
> デモ記事と弱いデモアカウント（resident）を作るため。本番の記事・資料・
> 居住者アカウントは MRC が実データで登録する。

## フェーズ6：メール（SMTP）設定と疎通
1. 外部SMTP（**SendGrid / Amazon SES / Mailgun** 等、または契約メールサーバー）の
   認証情報を用意。送信元ドメインの **SPF / DKIM** を設定（到達性の要）。
2. `wp-config.php` に `MRC_SMTP_*` / `MRC_MAIL_*` 定数を記入
   （`deploy/wp-config-snippet.php` の 2)）。
3. `mu-plugins/mrc-smtp.php` が自動で SMTP 送信に切替える。
4. 疎通テスト（WP-CLIがあれば `bootstrap.sh` が自動実施。無ければ
   「ご意見の窓口」から実際に送信して受信を確認）。
5. 管理画面「**物件基本設定 › ご意見の窓口 通知先**」で着工前/着工後の宛先を設定。

## フェーズ7：公開前チェック（居住者専用の担保）
- [ ] 全ページに `noindex, nofollow`（テーマが強制。View Source で確認）
- [ ] `https://ドメイン/robots.txt` が `Disallow: /`
- [ ] 未ログインで `/member/` 等が**ログインへリダイレクト**される
- [ ] 居住者（購読者）が **wp-admin に入れない**
- [ ] 管理画面・ログインが **HTTPS**（`FORCE_SSL_ADMIN`）
- [ ] 「ご意見の窓口」送信 → 正しい宛先にメール受信
- [ ] スパム対策（ハニーポット。必要なら reCAPTCHA v3 キーを設定）

## フェーズ8：物件の追加（NS Cloner）
1. ネットワーク管理 › **NS Cloner** で雛形サイトを複製し、新物件サイトを作成。
   → ページ・設定・メインビジュアル・URL は自動で引き継がれる（検証済み）。
2. **居住者アカウントは複製されない**ので、その建物の住民を新サイトに登録する。
3. 物件名・キャッチ・メインビジュアル（カスタマイザー）・通知先を物件ごとに設定。

## フェーズ9：バックアップ・保守
- DB＋`wp-content` の定期バックアップ（サーバーの自動バックアップ or プラグイン）。
- WordPress本体・NS Cloner の更新運用。
- 独自ドメインSSLの自動更新確認。

---

## 更新デプロイ（2回目以降）
テーマを直すたびに実行するのはこれだけ：
```bash
deploy/deploy-theme.sh
```
共通テーマなので**全物件に即時反映**される。DB や設定は触らない。

## CI/CD 化（任意・後日）
`deploy-theme.sh` を GitHub Actions から `main` push 時に実行すれば自動デプロイ化できる
（SSH鍵を Secrets に登録）。ホスティング確定後にワークフローを追加可能。
