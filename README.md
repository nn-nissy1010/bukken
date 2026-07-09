# ◯◯マンション 大規模修繕 居住者専用サイト

マンション大規模修繕の居住者専用ポータル。WordPressマルチサイトで複数物件へ横展開する構成です。

## フォルダ構成

```
bukken/
├── CLAUDE.md            プロジェクトのデザイン・開発ガイド（指示書）
└── wp/                  実装本体（ローカルWordPress + テーマ）
    ├── .wp-env.json     wp-env（Docker）設定
    └── themes/
        └── mrc-residents/   横展開用の共通テーマ
```

- **wp/themes/mrc-residents/** … 実際に使う共通テーマ。全物件で共有し、物件名・ロゴ・問い合わせ先などは各物件の設定で差し替える。

## ローカル開発（wp-env）

`wp/` ディレクトリで実行します。

```bash
cd wp
npx @wordpress/env start     # 起動（http://localhost:8888/ ）
npx @wordpress/env stop      # 停止
npx @wordpress/env run cli wp <command>   # wp-cli 実行
```

- 管理画面：http://localhost:8888/wp-admin/
- ネットワーク管理：http://localhost:8888/wp-admin/network/
- 新しい物件は **NS Cloner** で雛形サイトを複製して追加します。

> エディタは `wp/` フォルダを開くと、`wp/.vscode/settings.json` によりWordPress関数の補完・警告抑制が有効になります。

## デモ環境の中身

- 居住者向けデモ：**サブサイト http://localhost:8888/house1/**（物件名「桜台レジデンス」）。
- 居住者デモアカウント：**`resident` / `resident1234`**（購読者）。トップの居住者ログインフォームで使用。

## メール検証（Mailpit）

お問い合わせ・ご意見の窓口フォームの送信メールは、ローカルでは **Mailpit** で受信確認できます。

```bash
# 初回のみ（wp-env とは別に常駐）
docker run -d --name mrc-mailpit -p 8025:8025 -p 1025:1025 axllent/mailpit
```

- 受信箱UI：http://localhost:8025
- 仕組み：`wp/dev-mail/` の mu-plugin（`wp/.wp-env.json` の `mappings` でマウント）が全メールを Mailpit へ転送。**本番テーマには含まれない**開発専用の仕掛け。

## ローカルをゼロから再構築

`npx @wordpress/env destroy` 後や、新しい物件サイトをローカルに作る場合の手順（`wp/` で実行）。
`dev/seed.php` 1本で、物件名・必須固定ページ・お知らせ/資料/動画/Q&A・居住者アカウントまで用意します（冪等・再実行安全）。

```bash
npx @wordpress/env start
# サブサイト作成（例: house1）
npx @wordpress/env run cli wp site create --slug=house1 --url=localhost:8888
# デモデータ投入
npx @wordpress/env run cli wp eval-file \
  wp-content/themes/mrc-residents/dev/seed.php --url=localhost:8888/house1
```

- 作成直後にトップ http://localhost:8888/house1/ で `resident` / `resident1234` ログインまで確認できます。
- メインビジュアル画像は物件ごとに管理画面（カスタマイザー）で設定（未設定時は同梱 `hero-building.svg`）。

## 新しい物件を NS Cloner で複製するとき（本番運用）

雛形サイトを NS Cloner で複製すると、**ページ・お知らせ・資料・動画・Q&A・物件設定・メインビジュアル画像・URL は自動で引き継がれます**（検証済み）。ただし：

- **居住者アカウントは複製されません。** 各物件は住民が異なるため意図的な仕様です（他物件の住民はログイン不可）。複製後に、その建物の居住者を新サイトへ登録してください。
- 複製直後に、物件名・問い合わせ通知先（着工前/着工後）・メインビジュアルを物件ごとに設定します。
