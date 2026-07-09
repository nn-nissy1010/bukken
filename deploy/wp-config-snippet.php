<?php
/**
 * wp-config.php に追記する定数（本番用）。
 * この中身を「/* That's all, stop editing! * /」の行より“上”に貼り付ける。
 * 値はすべて本番の実値に置き換えること。
 *
 * ※ このファイル自体はメモ用。サーバーには置かない（wp-config.php に転記して使う）。
 */

/* =========================================================================
 * 1) マルチサイト（サブディレクトリ型）
 *    導入は2段階：
 *    (A) まず WP_ALLOW_MULTISITE だけ有効化し、管理画面
 *        「ツール › ネットワークの設置」で「サブディレクトリ」を選んで設置。
 *    (B) 設置画面が生成する定数（下記）を貼り、.htaccess を差し替える。
 * ====================================================================== */

// --- (A) 最初はこの1行だけ ---
define( 'WP_ALLOW_MULTISITE', true );

// --- (B) ネットワーク設置後に、以下をまとめて追加 ---
define( 'MULTISITE', true );
define( 'SUBDOMAIN_INSTALL', false );          // パス型（/house1/ 等）
define( 'DOMAIN_CURRENT_SITE', 'example.com' ); // ← 本番ドメイン（www無し推奨・実値へ）
define( 'PATH_CURRENT_SITE', '/' );
define( 'SITE_ID_CURRENT_SITE', 1 );
define( 'BLOG_ID_CURRENT_SITE', 1 );

/* =========================================================================
 * 2) 問い合わせメール（外部SMTPリレー推奨）
 *    値は SendGrid / Amazon SES / Mailgun / 契約メールサーバー等のもの。
 *    これらがあると deploy/mu-plugins/mrc-smtp.php が自動でSMTP送信に切替える。
 * ====================================================================== */
define( 'MRC_SMTP_HOST', 'smtp.example.com' );
define( 'MRC_SMTP_PORT', 587 );
define( 'MRC_SMTP_USER', 'smtp-user-or-apikey' );
define( 'MRC_SMTP_PASS', 'smtp-secret' );
define( 'MRC_SMTP_SECURE', 'tls' );             // 'tls' | 'ssl' | ''
define( 'MRC_MAIL_FROM', 'no-reply@example.com' );
define( 'MRC_MAIL_FROM_NAME', '居住者専用サイト' );

/* =========================================================================
 * 3) ハードニング（居住者専用サイト向け）
 * ====================================================================== */
define( 'DISALLOW_FILE_EDIT', true );  // 管理画面のテーマ/プラグイン編集を禁止
define( 'WP_DEBUG', false );           // 本番はエラー非表示
define( 'WP_DEBUG_DISPLAY', false );
define( 'FORCE_SSL_ADMIN', true );     // 管理画面/ログインを常にHTTPS

/* =========================================================================
 * 4) 認証キー（SALT）
 *    https://api.wordpress.org/secret-key/1.1/salt/ で生成して
 *    既存の AUTH_KEY 〜 NONCE_SALT を必ず本番用に差し替える。
 * ====================================================================== */
