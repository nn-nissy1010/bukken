<?php
/**
 * ◯◯マンション 居住者専用サイト テーマ関数（ローダー）
 *
 * 実際の機能は inc/ 配下のモジュールに分割しています。
 *
 * @package mrc-residents
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MRC_THEME_VERSION', '0.1.0' );

/**
 * 機能モジュールの読み込み。
 * 関数定義・フック登録のみのため読み込み順は結果に影響しません。
 */
$mrc_modules = array(
	'setup',        // テーマ基本設定・アセット・サイトアイコン
	'post-types',   // カスタム投稿タイプ／タクソノミー
	'access',       // ログイン・会員ゲート・居住者制限・noindex
	'privacy',      // プライバシーポリシー（全物件共通）
	'settings',     // 物件基本設定・ご意見の窓口（通知先）
	'admin',        // 管理画面・ネットワーク管理の整理
	'meta-boxes',   // 資料/動画のメタボックスと表示ヘルパー
);
foreach ( $mrc_modules as $mrc_module ) {
	require_once __DIR__ . "/inc/{$mrc_module}.php";
}
unset( $mrc_modules, $mrc_module );
