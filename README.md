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
