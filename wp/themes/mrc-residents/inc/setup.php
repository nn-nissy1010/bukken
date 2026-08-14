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
 * @param string $slug ページスラッグ（plan / member / contact / contact-public）
 * @return string HTML（未定義スラッグは空文字）
 */
function mrc_standard_page_content( $slug ) {
	switch ( $slug ) {
		case 'plan':
			// 見た目そのまま（カード・工程ステップ）を再現。SVGはCSSクラスで描画するため
			// 本文にSVGを置かず、どの権限で保存してもKSESで崩れない。wpautopの誤整形を
			// 避けるため、ブロック要素の間に空行を入れず1行で保持する。
			return '<p>当マンションで予定している大規模修繕工事について、目的や進め方をかんたんにご案内します。工事の詳しい内容は、住民説明会で使用した資料（PDF）をご覧ください。</p>'
				. '<div style="margin-bottom:48px;">'
				. '<div class="section-heading"><h2>大規模修繕工事とは（かんたんに）</h2></div>'
				. '<p>マンションは、およそ12〜15年ごとに、外壁・防水・鉄部などをまとめて直す大規模修繕工事を行います。建物を長く安全に使い、資産としての価値を守るための工事です。専門家（設計監理者）が調査・診断し、住民説明会と総会での合意を経て進めます。</p>'
				. '<div class="grid grid--3" style="margin-top:28px;">'
				. '<div class="card purpose-card"><span class="purpose-icon purpose-icon--safe"></span><h3>建物を長く安全に</h3><p>外壁や防水の劣化を放置せず、雨漏りや事故を防いで、安心して暮らせる状態を保ちます。</p></div>'
				. '<div class="card purpose-card"><span class="purpose-icon purpose-icon--value"></span><h3>資産価値を守る</h3><p>計画的に修繕することで、マンションの資産としての価値が下がるのを防ぎます。</p></div>'
				. '<div class="card purpose-card"><span class="purpose-icon purpose-icon--home"></span><h3>快適な住環境</h3><p>美観や住み心地を維持し、これからも気持ちよく暮らせる環境を整えます。</p></div>'
				. '</div></div>'
				. '<div style="margin-bottom:48px;">'
				. '<div class="section-heading"><h2>主な工事の対象</h2></div>'
				. '<p style="margin-bottom:16px;">大規模修繕工事では、主に次のような箇所をまとめて点検・修繕します。（対象箇所は建物により異なります）</p>'
				. '<ul class="spec-tags"><li>外壁塗装</li><li>防水工事（屋上・バルコニー）</li><li>鉄部塗装</li><li>タイル補修</li><li>シーリング打ち替え</li><li>給排水設備</li></ul>'
				. '</div>'
				. '<div>'
				. '<div class="section-heading"><h2>工事の流れ</h2></div>'
				. '<ol class="process-steps">'
				. '<li class="process-step"><span class="process-step__num">1</span><div class="process-step__body"><h3 class="process-step__title">調査・診断</h3><p class="process-step__desc">専門家（設計監理者）が建物の状態を詳しく調べ、劣化の程度を診断します。</p></div></li>'
				. '<li class="process-step"><span class="process-step__num">2</span><div class="process-step__body"><h3 class="process-step__title">改修設計</h3><p class="process-step__desc">調査・診断の結果をもとに、直す箇所や工事の方法・仕様を図面にまとめ、工事の内容を固めます。</p></div></li>'
				. '<li class="process-step"><span class="process-step__num">3</span><div class="process-step__body"><h3 class="process-step__title">住民説明会</h3><p class="process-step__desc">調査の結果や工事の進め方を、居住者の皆さまにわかりやすくご説明します。</p></div></li>'
				. '<li class="process-step"><span class="process-step__num">4</span><div class="process-step__body"><h3 class="process-step__title">施工会社の選定</h3><p class="process-step__desc">複数の会社を比較・検討し、工事を担当する施工会社を選びます。</p></div></li>'
				. '<li class="process-step"><span class="process-step__num">5</span><div class="process-step__body"><h3 class="process-step__title">総会での決議</h3><p class="process-step__desc">工事請負契約の承認を総会で決議し、工事が正式に決まります。</p></div></li>'
				. '<li class="process-step"><span class="process-step__num">6</span><div class="process-step__body"><h3 class="process-step__title">着工</h3><p class="process-step__desc">準備が整い次第、工事を開始します。工程はお知らせと掲示板でご案内します。</p></div></li>'
				. '</ol>'
				. '<p class="form-hint" style="margin-top:16px;">※ 着工の時期は決まり次第お知らせします。</p>'
				. '</div>';
		case 'member':
			return '<p>会員専用ページへようこそ。大規模修繕工事に関する最新のお知らせ・資料・動画などを、こちらでご確認いただけます。</p>';
		case 'contact':
			return '<p>ご質問・ご意見をお送りください。内容の「種別」に応じて、担当の窓口へお届けします。</p>';
		case 'contact-public':
			return '<p>ログイン・IDについてのお問い合わせ窓口です。工事や計画に関するご質問は、ログイン後の「ご意見の窓口」からお願いします。</p>';
		default:
			return '';
	}
}
