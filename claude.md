# CLAUDE.md — マンション大規模修繕 居住者専用サイト 開発ガイド

## 1. プロジェクト概要
マンションの大規模修繕工事における、居住者専用ポータルサイト。工事情報・スケジュール・資料・動画の提供と、問い合わせ対応を行う。

- **実装形態**：**WordPressマルチサイト**（path-based）上で動く**横展開用の共通テーマ** `mrc-residents`。
- **横展開の考え方**：全物件で1つのテーマを共有し、物件名・ロゴ・メインビジュアル・問い合わせ先などは各物件（サブサイト）の設定で差し替える。新しい物件は **NS Cloner** で雛形サイトを複製して追加する。
- **運用会社**：株式会社MRC（管理側スタッフ）。着工前はMRC、着工後は施工会社が問い合わせ窓口になる想定。
- **公開範囲**：居住者専用のため常に `noindex, nofollow`／robots.txt 全拒否。

> 注：本リポジトリ直下の `remixed-47dca715.html` は初期のデザインカンプ（静的HTML）で、**現行の実装ではない**。実装本体は下記WPテーマ。

---

## 2. ディレクトリ構成

```
bukken/
├── claude.md                    このガイド
├── README.md                    セットアップ手順
├── remixed-47dca715.html        初期デザインカンプ（参考・非実装）
├── screens/                     参考スクリーンショット
└── wp/                          実装本体
    ├── .wp-env.json             wp-env（Docker）設定・マルチサイト構成
    └── themes/mrc-residents/    横展開用の共通テーマ
        ├── style.css            テーマヘッダー（デザイン本体は assets/css/app.css）
        ├── functions.php        inc/ モジュールのローダー
        ├── header.php / footer.php
        ├── front-page.php       ログイン前トップ（公開ゾーン）
        ├── page-member.php      会員トップ（slug: member）
        ├── page-plan.php        工事の計画（slug: plan）
        ├── page-contact.php     ご意見の窓口（会員・slug: contact）
        ├── page-contact-public.php  お問い合わせ（ログイン前・slug: contact-public）
        ├── page-privacy.php     プライバシーポリシー（全物件共通・/privacy/）
        ├── archive-{news,document,video,qa}.php   各CPT一覧
        ├── single-{news,document,video,qa}.php     各CPT詳細
        ├── index.php            フォールバック
        ├── inc/                 機能モジュール（後述）
        ├── assets/css/app.css   デザイン本体（全画面共通・CSS変数管理）
        ├── assets/js/main.js    UI制御（Vanilla JS）
        ├── assets/img/          ロゴ・アイコン・ヒーロー等の同梱画像
        └── dev/                 デモデータ投入スクリプト（seed.php ほか）
```

### inc/ 機能モジュール（`functions.php` が読み込み）
- `setup.php` … テーマサポート、アセット読み込み（Noto Sans JP＋app.css＋main.js、`filemtime`でキャッシュバスティング）、サイトアイコンの共通配信、ナビメニュー登録。
- `post-types.php` … カスタム投稿タイプ／タクソノミーの登録。
- `access.php` … 居住者ログイン・会員ゲート・wp-admin締め出し・ログイン後リダイレクト・noindex/robots。
- `settings.php` … 物件基本設定、ご意見の窓口 通知先、スパム対策（ハニーポット＋任意reCAPTCHA v3）、問い合わせメール送信。
- `admin.php` … 管理画面／ネットワーク管理の整理、新規物件サイトの自動設定、カスタマイザー（メインビジュアル）、NS Cloner日本語化。
- `meta-boxes.php` … 資料PDF・動画URLのメタボックスと、埋め込み／サムネ／ダウンロードURLのヘルパー。ブロックエディタ無効化。
- `privacy.php` … 全物件共通のプライバシーポリシー本文（ネットワーク共通 site_option）。

---

## 3. ローカル開発環境（wp-env）

`wp/` ディレクトリで実行する。

```bash
cd wp
npx @wordpress/env start                    # 起動
npx @wordpress/env stop
npx @wordpress/env run cli wp <command> --url=localhost:8888/house1   # wp-cli
```

- ルート `http://localhost:8888/` … 雛形（メインサイト）。
- **居住者向けデモは サブサイト `http://localhost:8888/house1/`**（`front-page.php` がレンダリング）。
- 管理画面 `…/wp-admin/`、ネットワーク管理 `…/wp-admin/network/`。
- 居住者デモアカウント：`resident` / `resident1234`（subscriber。seedが全物件共通で用意）。
  ※ house1 には旧アカウント `user`（ID3）も残置。ログイン確認は `resident` を使う。
- デモデータ一括投入：`dev/seed.php` を `wp eval-file` で（物件名・固定ページ・お知らせ/資料/動画/Q&A・居住者アカウントまで単独で構築。冪等・再実行安全）。
  - ローカルからの完全再構築手順は `README.md` の「ローカルをゼロから再構築」を参照。
- メール検証：`wp/dev-mail/` の mu-plugin が全メールを Mailpit へ転送。受信確認は http://localhost:8025 （起動 `docker run -d --name mrc-mailpit -p 8025:8025 -p 1025:1025 axllent/mailpit`）。本番には配布されない（wp-env の mappings 経由のみ）。

### 見た目確認・スクショ（ヘッドレスChrome、JSも実行される）
```bash
"/Applications/Google Chrome.app/Contents/MacOS/Google Chrome" --headless --disable-gpu \
  --hide-scrollbars --window-size=390,844 --screenshot=/tmp/out.png "http://localhost:8888/house1/"
```
- `window-size` でビューポート＝ファーストビューを再現（実機はブラウザchrome分で使える高さ〜650-700px想定）。
- ピクセル単位で余白を厳密に測る場合は、`getBoundingClientRect` は行アキを拾えず不正確なので、**実描画ピクセル**で測る（PNGをデコードして色境界を走査）。
- 会員ページ確認は Chrome CDP で `mrc_login` フォームを送信→スクショ。

---

## 4. デザイン方針とトンマナ
信頼感・見やすさ・シニア層を含む幅広い住民への「分かりやすさ」を最優先。落ち着いた配色と広めの余白。設計値はすべて `assets/css/app.css` の `:root` カスタムプロパティで管理する。

### カラーパレット（実装値）
- メイン（ヘッダー・見出し）：ディープネイビー `--color-navy: #1A365D`（濃 `#142B4A` / 明 `#2C4A73`）
- アクセント（CTA・重要ボタン）：**テラコッタ `--color-gold: #C26B4C`**（濃 `#A9583C`）※MRCの寄り添いトーン。変数名は `gold` だが実際はテラコッタ系。
- 背景：オフホワイト `--color-bg: #F8FAFC` ／ 面 `#FFFFFF` ／ 薄い面 `#F1F5F9`
- テキスト：ダークチャコール `--color-text: #2D3748`（補足 `#64748B` / 弱 `#94A3B8`）
- 境界線：ソフトグレー `--color-border: #E2E8F0`

### タイポグラフィ・余白
- フォント：`--font-base: "Noto Sans JP", "Hiragino Sans", …`（Google Fontsで Noto Sans JP を読み込み）。
- 本文 `--fs-body: 16px` / 行間 `--lh-body: 1.8`。
- 余白は `--space-1`〜`-8`（4〜64px）、角丸 `--radius*`、影 `--shadow*`、コンテナ幅 `--container: 1120px` / `--container-narrow: 800px` を使う。生の数値ではなく変数を使うこと。

### レスポンシブ
- モバイルファースト。主なブレークポイントは `max-width: 860px` / `560px`。
- SPはハンバーガーメニュー（`[data-nav-toggle]`）、PCはヘッダー横並びナビ。

---

## 5. 技術構成（機能マップ）

### カスタム投稿タイプ／タクソノミー（`inc/post-types.php`）
| CPT | slug/アーカイブ | タクソノミー | 用途 |
|---|---|---|---|
| `news` | `/news/` | `news_category`（お知らせ／スケジュール／工事の計画） | お知らせ（掲示板） |
| `document` | `/documents/` | `doc_category`（種別） | 資料（PDF） |
| `video` | `/videos/` | — | 動画アーカイブ（YouTube/Vimeo埋め込み） |
| `qa` | `/qa/` | — | Q&A（アコーディオン） |

- 「工事の計画」ページ（`plan`）と「ご意見の窓口」（`contact`）は固定ページ。

### アクセス制御（`inc/access.php`）
- 会員ゾーン（`member`/`plan`/`contact` ページ、各CPTの単一・アーカイブ、`news_category`）は未ログインだと `wp_login_url` へリダイレクト。
- 居住者ログインはトップの独自フォーム（`mrc_login` → `wp_authenticate`）。**当該サイトの登録者か**を `is_user_member_of_blog` で検証（他物件の居住者はログイン不可）。
- 「スタッフ」判定は `edit_posts` 権限（`mrc_is_staff()`）。居住者（購読者）は wp-admin から締め出し、ログイン後は `/member/` へ。スタッフは管理画面へ。
- ログイン済み居住者がトップに来たら `/member/` へ転送（管理者は開発確認のため転送しない）。

### 物件基本設定・問い合わせ（`inc/settings.php`）
- **物件基本設定**（管理メニュー「物件基本設定」）：ページ／CPTごとに「公開 ON/OFF」「メニュー表示 ON/OFF」を手動トグル。`mrc_page_is_public()` / `mrc_page_menu_visible()` でテンプレ・ヘッダーに反映。「工事に関するお知らせ」のみ既定OFF。
- **ご意見の窓口 通知先**：工事段階（着工前／着工後）で送信先メールを切替（`mrc_contact_recipient()`）。種別（工事のこと／計画・全体／生活・管理／その他）は分類・記録用でメールに記載。
- **スパム対策**：ハニーポット＋送信時刻チェックが標準。ネットワーク共通の reCAPTCHA v3 キーを入れると自動で有効化（未設定時はハニーポットにフォールバック）。

### 管理画面の整理（`inc/admin.php`）
- コメント全面無効化、不要な管理メニュー・ダッシュボードウィジェット撤去、MRC向け案内＋クイックリンクに置換。
- 新規物件サイト作成時（`wp_initialize_site`）に共通テーマ有効化・パーマリンク `/%postname%/`・`blog_public=0` を自動設定。
- カスタマイザーでメインビジュアル画像を物件ごとに差し替え（未設定時は同梱 `hero-building.svg`）。
- NS Cloner の複製画面UIを gettext フィルタで日本語化。

### フロントJS（`assets/js/main.js`・Vanilla JSのみ、jQuery/フレームワーク不使用）
- `initNavToggle` … SPメニュー開閉
- `initAccordions` … `[data-accordion-trigger]`（はじめての方へ・Q&A等）
- `initFilters` … `[data-filter-group]` のカテゴリ絞り込みチップ
- `initFormFlow` … `[data-form-flow]` の 入力→確認→完了 ステップ表示

---

## 6. コーディング規約

- **PHP**：WordPressコーディング規約（タブインデント、Yoda条件、`esc_*`/`wp_kses` によるエスケープ、nonceでCSRF対策）。全ファイル冒頭で `if ( ! defined( 'ABSPATH' ) ) exit;`。
- **命名**：関数・オプションは `mrc_` プレフィックス。機能追加は原則 `inc/` の該当モジュールに置き、`functions.php` は肥大化させない。
- **CSS**：`assets/css/app.css` に集約。色・余白・角丸などは `:root` の変数を使い、ハードコード値を増やさない。コンポーネント（`.card` / `.btn` / `.badge` / `.chip` / `.process-step` など）は共通クラスで再利用。
- **JS**：`assets/js/main.js` に集約。DOMフックは `data-*` 属性で行い、挙動をマークアップから分離。
- **横展開の配慮**：物件名・電話番号・ロゴなど物件固有値はテンプレートに直書きせず、`bloginfo()`・設定・カスタマイザー・`mrc_*` ヘルパー経由で出力する。

---

## 7. 変更時の進め方
1. 変更対象を上記ファイルマップで特定する（デザインは `app.css`、挙動は `main.js`、機能は `inc/`、画面は各テンプレート）。
2. 変更後は `http://localhost:8888/house1/` をヘッドレスChromeでスクショし、SP/PC双方で確認する（会員ページは要ログイン）。
3. 全物件共通テーマである点を常に意識し、特定物件に依存する値を埋め込まない。
