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

/**
 * 固定ページ本文（編集可能）に関するテンプレート用ヘルパー。
 * 各ページテンプレートは「本文が入力されていればそれを表示、未入力なら
 * 従来の定型文（既定）を表示」する形で、本文をまるごと差し替えられるようにする。
 */

/** 現在の固定ページに本文が入力されているか。 */
function mrc_has_page_body() {
	$post = get_queried_object();
	return ( $post instanceof WP_Post ) && '' !== trim( (string) $post->post_content );
}

/** 現在の固定ページ本文を整形して出力（the_content 相当：wpautop・ショートコード適用）。 */
function mrc_the_page_body() {
	$post = get_queried_object();
	if ( $post instanceof WP_Post ) {
		echo apply_filters( 'the_content', $post->post_content );
	}
}

/**
 * 各ページの「標準説明」を、編集しやすい本文（見出し・段落・箇条書き）として返す。
 * 固定ページ本文の初期値に使う。物件ごとにこの内容を編集して差し替えられる。
 * 資料リスト・お知らせ一覧・問い合わせフォーム等の機能部品はテンプレート側で表示するため、
 * ここには含めない。
 *
 * 工事の計画は専用フォーム（mrc_plan_content）で編集するため、ここには含めない。
 *
 * @param string $slug ページスラッグ（contact / contact-public）
 * @return string HTML（未定義スラッグは空文字）
 */
function mrc_standard_page_content( $slug ) {
	switch ( $slug ) {
		case 'contact':
			return '<p>ご質問・ご意見をお送りください。内容の「種別」に応じて、担当の窓口へお届けします。</p>';
		case 'contact-public':
			return '<p>ログイン・IDについてのお問い合わせ窓口です。工事や計画に関するご質問は、ログイン後の「ご意見の窓口」からお願いします。</p>';
		default:
			return '';
	}
}
