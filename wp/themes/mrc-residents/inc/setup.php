<?php
/**
 * テーマ基本設定・アセット読み込み・サイトアイコン
 * functions.php から読み込まれる機能モジュール。
 *
 * @package mrc-residents
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * テーマの基本サポート・メニュー登録
 */
function mrc_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'automatic-feed-links' );

	register_nav_menus(
		array(
			'public' => 'ログイン前メニュー',
			'member' => '会員メニュー',
		)
	);
}
add_action( 'after_setup_theme', 'mrc_setup' );

/**
 * スタイル・スクリプトの読み込み
 */
function mrc_assets() {
	// Noto Sans JP（Google Fonts）
	wp_enqueue_style(
		'mrc-fonts',
		'https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&display=swap',
		array(),
		null
	);

	// テーマ本体のデザイン（更新時にキャッシュも更新されるよう filemtime を版数に）
	$css_path = get_theme_file_path( 'assets/css/app.css' );
	wp_enqueue_style(
		'mrc-app',
		get_theme_file_uri( 'assets/css/app.css' ),
		array(),
		file_exists( $css_path ) ? filemtime( $css_path ) : MRC_THEME_VERSION
	);

	// UI制御（アコーディオン・メニュー開閉・フォーム等）
	$js_path = get_theme_file_path( 'assets/js/main.js' );
	wp_enqueue_script(
		'mrc-main',
		get_theme_file_uri( 'assets/js/main.js' ),
		array(),
		file_exists( $js_path ) ? filemtime( $js_path ) : MRC_THEME_VERSION,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'mrc_assets' );

/**
 * サイトアイコン（favicon）を全物件共通でテーマから出力する。
 * 管理画面で物件ごとに設定させず、テーマ内の共通アイコンを配信する。
 * フロント・ログイン画面・管理画面のすべてに適用。
 */
function mrc_site_icon_tags() {
	$svg_path = get_theme_file_path( 'assets/img/site-icon.svg' );
	$ver      = file_exists( $svg_path ) ? filemtime( $svg_path ) : MRC_THEME_VERSION;
	$svg      = get_theme_file_uri( 'assets/img/site-icon.svg' );
	$png32    = get_theme_file_uri( 'assets/img/site-icon-32.png' );
	$apple    = get_theme_file_uri( 'assets/img/apple-touch-icon.png' );

	printf( "<link rel=\"icon\" href=\"%s?v=%s\" type=\"image/svg+xml\">\n", esc_url( $svg ), esc_attr( $ver ) );
	printf( "<link rel=\"icon\" href=\"%s?v=%s\" sizes=\"32x32\" type=\"image/png\">\n", esc_url( $png32 ), esc_attr( $ver ) );
	printf( "<link rel=\"apple-touch-icon\" href=\"%s?v=%s\">\n", esc_url( $apple ), esc_attr( $ver ) );
}
add_action( 'wp_head', 'mrc_site_icon_tags', 2 );
add_action( 'login_head', 'mrc_site_icon_tags' );
add_action( 'admin_head', 'mrc_site_icon_tags' );

/**
 * 設定 › 一般 の「サイトアイコン」欄を非表示にする。
 * アイコンは全物件共通でテーマから固定配信するため、物件ごとに設定させず、
 * 標準欄との二重設定・誤操作を防ぐ。（一般設定ページのみに適用）
 */
function mrc_hide_site_icon_setting() {
	echo "<style>.site-icon-section{display:none !important;}</style>\n";
}
add_action( 'admin_head-options-general.php', 'mrc_hide_site_icon_setting' );
