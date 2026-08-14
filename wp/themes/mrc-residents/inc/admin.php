<?php
/**
 * 管理画面・ネットワーク管理の整理／新規サイト自動設定／NS Cloner日本語化
 * functions.php から読み込まれる機能モジュール。
 *
 * @package mrc-residents
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ネットワーク管理 › 設定 の未使用セクションを非表示にして整理する。
 * 残す：運用設定（ネットワーク名・管理者メール）／アップロード設定（PDF最大サイズ等）
 * 隠す：登録の設定・新規サイト設定・言語の設定・メニュー設定
 * （見出しテキストで対象セクションを丸ごと非表示。ネットワーク設定ページのみ）
 */
function mrc_declutter_network_settings() {
	if ( ! is_network_admin() || 'settings.php' !== $GLOBALS['pagenow'] || ! empty( $_GET['page'] ) ) {
		return;
	}
	?>
	<script>
	( function () {
		var hide = ['登録の設定', '新規サイト設定', '言語の設定', 'メニュー設定'];
		document.querySelectorAll( '.wrap h2' ).forEach( function ( h2 ) {
			if ( hide.indexOf( h2.textContent.trim() ) === -1 ) { return; }
			h2.style.display = 'none';
			var node = h2.nextElementSibling;
			while ( node && node.tagName !== 'H2' ) {
				node.style.display = 'none';
				node = node.nextElementSibling;
			}
		} );
	} )();
	</script>
	<?php
}
add_action( 'admin_footer', 'mrc_declutter_network_settings' );

/* ============================================================
   管理画面の整理（MRC運用向け）
   ============================================================ */

/**
 * コメント機能を全面的に無効化する。
 */
function mrc_disable_comments() {
	// 各投稿タイプからコメント/トラックバックのサポートを外す
	foreach ( get_post_types() as $pt ) {
		if ( post_type_supports( $pt, 'comments' ) ) {
			remove_post_type_support( $pt, 'comments' );
			remove_post_type_support( $pt, 'trackbacks' );
		}
	}
}
add_action( 'admin_init', 'mrc_disable_comments' );

add_filter( 'comments_open', '__return_false', 20, 2 );
add_filter( 'pings_open', '__return_false', 20, 2 );
add_filter( 'comments_array', '__return_empty_array', 10, 2 );

/**
 * ダッシュボードの整理：不要な既定ウィジェットを撤去し、
 * MRC向けの案内＋クイックリンクだけにする。
 */
function mrc_customize_dashboard() {
	remove_meta_box( 'dashboard_right_now', 'dashboard', 'normal' );       // 概要
	remove_meta_box( 'dashboard_activity', 'dashboard', 'normal' );        // アクティビティ
	remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' ); // コメント
	remove_meta_box( 'dashboard_site_health', 'dashboard', 'normal' );     // サイトヘルス
	remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );       // クイックドラフト
	remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );           // WordPress イベントとニュース

	wp_add_dashboard_widget( 'mrc_dashboard_guide', 'この居住者専用サイトの管理', 'mrc_dashboard_guide_html' );
	// メインサイト（雛形）では公開前提の設定がないため、物件サイトのみ表示。
	if ( ! is_main_site() ) {
		wp_add_dashboard_widget( 'mrc_dashboard_readiness', '公開前チェック（この物件の準備状況）', 'mrc_dashboard_readiness_html' );
	}

	// ようこそパネルを非表示（この時点なら既定アクションが登録済みで確実に外れる）
	remove_action( 'welcome_panel', 'wp_welcome_panel' );
}

/**
 * 公開前チェックの各項目を評価して返す。
 * 各要素 = array( done(bool), label, hint, url )。
 */
function mrc_readiness_items() {
	$items = array();

	// 居住者アカウント
	$subs    = count_users();
	$sub_num = isset( $subs['avail_roles']['subscriber'] ) ? (int) $subs['avail_roles']['subscriber'] : 0;
	$items[] = array(
		'done'  => $sub_num > 0,
		'label' => '居住者アカウントの登録',
		'hint'  => $sub_num > 0 ? ( $sub_num . '人 登録済み' ) : 'まだ0人。追加すると本人に設定メールが届きます',
		'url'   => admin_url( 'user-new.php' ),
	);

	// ご意見の窓口 通知先
	$missing = function_exists( 'mrc_contact_settings_missing' ) ? mrc_contact_settings_missing() : array();
	$items[] = array(
		'done'  => empty( $missing ),
		'label' => 'ご意見の窓口 通知先メール',
		'hint'  => empty( $missing ) ? '設定済み' : ( '未設定：' . implode( '、', $missing ) ),
		'url'   => admin_url( 'admin.php?page=mrc-contact' ),
	);

	// メインビジュアル
	$has_hero = (bool) get_theme_mod( 'mrc_hero_image' );
	$items[]  = array(
		'done'  => $has_hero,
		'label' => 'メインビジュアル画像',
		'hint'  => $has_hero ? '設定済み' : '既定のサンプル画像のまま',
		'url'   => admin_url( 'customize.php?autofocus[control]=mrc_hero_image' ),
	);

	// コンテンツ（お知らせ／資料／動画／Q&A のいずれか投稿があるか）
	$content_total = 0;
	foreach ( array( 'news', 'document', 'video', 'qa' ) as $pt ) {
		$content_total += (int) wp_count_posts( $pt )->publish;
	}
	$items[] = array(
		'done'  => $content_total > 0,
		'label' => 'コンテンツの投入（お知らせ・資料など）',
		'hint'  => $content_total > 0 ? ( '公開 ' . $content_total . '件' ) : 'まだ0件',
		'url'   => admin_url( 'edit.php?post_type=news' ),
	);

	return $items;
}

/** 公開前チェックのウィジェット本体。 */
function mrc_dashboard_readiness_html() {
	$items = mrc_readiness_items();
	$done  = 0;
	foreach ( $items as $it ) {
		if ( $it['done'] ) {
			$done++;
		}
	}
	$total = count( $items );
	?>
	<p style="margin-top:0;">実居住者に公開する前に、この物件で必要な設定・コンテンツの状況です。<strong><?php echo (int) $done; ?>/<?php echo (int) $total; ?> 完了</strong></p>
	<ul style="margin:0;">
		<?php foreach ( $items as $it ) : ?>
			<li style="padding:6px 0;border-top:1px solid #f0f0f1;">
				<span style="font-size:15px;<?php echo $it['done'] ? 'color:#008a20;' : 'color:#b32d2e;'; ?>"><?php echo $it['done'] ? '✓' : '⚠'; ?></span>
				<a href="<?php echo esc_url( $it['url'] ); ?>"><?php echo esc_html( $it['label'] ); ?></a>
				<span class="description">— <?php echo esc_html( $it['hint'] ); ?></span>
			</li>
		<?php endforeach; ?>
	</ul>
	<p class="description" style="margin-bottom:0;">※このチェックは目安です。すべて完了してから、居住者へIDをお配りください。</p>
	<?php
}
add_action( 'wp_dashboard_setup', 'mrc_customize_dashboard' );

/** ダッシュボードの案内ウィジェットの中身 */
function mrc_dashboard_guide_html() {
	?>
	<p><strong><?php echo esc_html( get_bloginfo( 'name' ) ); ?></strong> の管理画面です。よく使う操作はこちら。</p>
	<p style="margin:0 0 4px;"><strong>コンテンツを追加</strong></p>
	<p style="display:flex;gap:8px;flex-wrap:wrap;margin-top:0;">
		<a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=news' ) ); ?>">お知らせ</a>
		<a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=document' ) ); ?>">資料</a>
		<a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=video' ) ); ?>">動画</a>
		<a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=qa' ) ); ?>">Q&amp;A</a>
	</p>
	<p style="margin:12px 0 4px;"><strong>ページ・文言を編集</strong></p>
	<p style="display:flex;gap:8px;flex-wrap:wrap;margin-top:0;">
		<?php foreach ( mrc_content_edit_links() as $mrc_label => $mrc_url ) : ?>
			<a class="button" href="<?php echo esc_url( $mrc_url ); ?>"><?php echo esc_html( $mrc_label ); ?></a>
		<?php endforeach; ?>
	</p>
	<hr>
	<p style="margin-bottom:0;">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=mrc-property' ) ); ?>">物件基本設定</a>　｜
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=mrc-contact' ) ); ?>">通知先設定</a>　｜
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=mrc-edit-pages' ) ); ?>">ページ編集</a>　｜
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener">サイトを表示</a>
	</p>
	<?php
}

/** 管理バーからコメントを撤去 */
function mrc_remove_comments_admin_bar( $wp_admin_bar ) {
	$wp_admin_bar->remove_node( 'comments' );
}
add_action( 'admin_bar_menu', 'mrc_remove_comments_admin_bar', 999 );

/**
 * 使わない管理メニューを非表示にする。
 * ・投稿（デフォルト。この案件はカスタム投稿タイプを使用）
 * ・コメント
 * ・ツール
 */
function mrc_hide_admin_menus() {
	remove_menu_page( 'edit.php' );                    // 投稿
	remove_menu_page( 'edit-comments.php' );           // コメント
	remove_menu_page( 'edit.php?post_type=page' );     // 固定ページ（骨組みページ・MRC誤操作防止）
	remove_menu_page( 'plugins.php' );                 // プラグイン（管理は開発/ネットワーク側。物件側では不要）
	remove_menu_page( 'ns-cloner' );                   // NS Cloner（複製はネットワーク管理で行う。物件側では不要）

	// ダッシュボード配下の「参加サイト」を非表示（切替はツールバー／ネットワーク管理で可能）
	remove_submenu_page( 'index.php', 'my-sites.php' );

	// ツール：サイトヘルスと個人データ（書き出し／消去）だけ残し、未使用のものを非表示
	remove_submenu_page( 'tools.php', 'tools.php' );   // 利用可能なツール（カテゴリー変換等・未使用）
	remove_submenu_page( 'tools.php', 'import.php' );  // インポート（未使用）
	remove_submenu_page( 'tools.php', 'export.php' );  // エクスポート（バックアップはサーバ側で実施）

	// 設定サブメニュー：一般だけ残し、未使用・危険なものを非表示
	remove_submenu_page( 'options-general.php', 'options-writing.php' );    // 投稿設定
	remove_submenu_page( 'options-general.php', 'options-discussion.php' ); // ディスカッション
	remove_submenu_page( 'options-general.php', 'options-media.php' );      // メディア設定
	remove_submenu_page( 'options-general.php', 'options-permalink.php' );  // パーマリンク（URL固定・誤変更防止）
	remove_submenu_page( 'options-general.php', 'options-connectors.php' ); // コネクタ（NS Clonerのリモート複製用・物件側では不要）

	// 外観のサブメニューで未使用のもの（メニュー／パターン／フォント）を非表示
	remove_submenu_page( 'themes.php', 'nav-menus.php' );                // メニュー（ナビはコード側で出力のため未使用）
	remove_submenu_page( 'themes.php', 'site-editor.php?p=%2Fpattern' ); // パターン
	remove_submenu_page( 'themes.php', 'site-editor.php?p=/pattern' );   // パターン（念のため）
	remove_submenu_page( 'themes.php', 'font-library.php' );             // フォント
}
add_action( 'admin_menu', 'mrc_hide_admin_menus', 999 );

/**
 * 管理メニューの並び替え。
 * ユーザー・物件基本設定を上部へ、メディアを最下部へ。
 */
add_filter( 'custom_menu_order', '__return_true' );
function mrc_custom_menu_order( $menu_order ) {
	$priority = array(
		'index.php',                     // ダッシュボード
		'users.php',                     // ユーザー（最上位）
		'mrc-property',                  // 物件基本設定
		'edit.php?post_type=news',       // お知らせ
		'edit.php?post_type=document',   // 資料
		'edit.php?post_type=video',      // 動画
		'edit.php?post_type=qa',         // Q&A
		'edit.php?post_type=page',       // 固定ページ
		'themes.php',                    // 外観
		'options-general.php',           // 設定（外観の下・通知先設定を含む）
	);
	// priority と メディア以外を中間に、メディアは常に最後
	$rest = array_diff( $menu_order, $priority, array( 'upload.php' ) );
	return array_merge( $priority, $rest, array( 'upload.php' ) );
}
add_filter( 'menu_order', 'mrc_custom_menu_order' );

/**
 * 新しい物件サイトが作成されたときの自動設定。
 * サブサイト既定の /blog/ 付きパーマリンクを /%postname%/ に統一し、
 * リライトルールを再構築（カスタム投稿タイプのアーカイブを有効化）。
 */
function mrc_configure_new_site( $new_site ) {
	switch_to_blog( $new_site->blog_id );
	switch_theme( 'mrc-residents' );   // 新しい物件サイトは共通テーマを有効化
	update_option( 'permalink_structure', '/%postname%/' );
	update_option( 'date_format', 'Y年n月j日' );
	update_option( 'time_format', 'g:i A' );
	update_option( 'blog_public', 0 ); // 検索エンジンにインデックスさせない（居住者専用）
	flush_rewrite_rules( false );
	restore_current_blog();
}
add_action( 'wp_initialize_site', 'mrc_configure_new_site', 99 );

/**
 * マルチサイトの最大アップロードサイズを引き上げる（メインビジュアル画像・資料PDF共通）。
 * 既定は約1.5MB（fileupload_maxk=1500）で資料PDFやヒーロー画像に不足するため、
 * 約30MBまで許可する。サーバーの upload_max_filesize / post_max_size を超えないよう
 * クランプする（0＝無制限は無視）。site_option を読み取り時にフィルタするので、
 * 実際のアップロード判定（check_upload_size）とメディア画面の表示上限の両方に効く。
 *
 * @return int 許可する最大サイズ（KB単位）
 */
function mrc_max_upload_kb() {
	$candidates = array( 30 * MB_IN_BYTES ); // 目標: 約30MB
	foreach ( array( 'upload_max_filesize', 'post_max_size' ) as $ini ) {
		$bytes = wp_convert_hr_to_bytes( ini_get( $ini ) );
		if ( $bytes > 0 ) {
			$candidates[] = $bytes;
		}
	}
	return (int) floor( min( $candidates ) / KB_IN_BYTES );
}
add_filter( 'site_option_fileupload_maxk', 'mrc_max_upload_kb' );
add_filter( 'default_site_option_fileupload_maxk', 'mrc_max_upload_kb' );

/**
 * カスタマイザー：メインビジュアル画像（物件ごとに差し替え可能）
 */
function mrc_customize_register( $wp_customize ) {
	$wp_customize->add_section(
		'mrc_hero',
		array( 'title' => 'メインビジュアル', 'priority' => 30 )
	);
	$wp_customize->add_setting(
		'mrc_hero_image',
		array( 'default' => '', 'sanitize_callback' => 'esc_url_raw', 'transport' => 'refresh' )
	);
	$wp_customize->add_control(
		new WP_Customize_Image_Control(
			$wp_customize,
			'mrc_hero_image',
			array(
				'label'       => 'メインビジュアル画像',
				'description' => 'ログイン前トップ上部の背景画像（建物外観など）。未設定時はサンプル画像を表示します。',
				'section'     => 'mrc_hero',
			)
		)
	);
}
add_action( 'customize_register', 'mrc_customize_register' );

/**
 * ヒーローのメインビジュアルURLを返す（カスタム設定 → 無ければ同梱プレースホルダー）。
 */
function mrc_hero_image_url() {
	$custom = get_theme_mod( 'mrc_hero_image' );
	return $custom ? $custom : get_theme_file_uri( 'assets/img/hero-building.svg' );
}

/**
 * ネットワーク管理サイドバーから不要メニューを非表示にする。
 * （テーマ／プラグイン／ユーザー。必須のダッシュボード・サイト・設定・NS Clonerは残す）
 */
function mrc_hide_network_menus() {
	remove_menu_page( 'themes.php' );   // テーマ
	remove_menu_page( 'plugins.php' );  // プラグイン
	remove_menu_page( 'users.php' );    // ユーザー
	// 設定 › サイトネットワークの設置（構築済みのため不要な初期設置ガイド）
	remove_submenu_page( 'settings.php', 'setup.php' );
	// サイト › サイトを追加（物件はNS Clonerで雛形を複製して追加するため、標準の空サイト作成は無効化）
	remove_submenu_page( 'sites.php', 'site-new.php' );
	// NS Cloner › Logs / Status（クローン履歴のログ閲覧のみ・複製操作には不要）
	remove_submenu_page( 'ns-cloner', 'ns-cloner-logs' );
}
add_action( 'network_admin_menu', 'mrc_hide_network_menus', 999 );

/**
 * サイト一覧ページ上部の「サイトを追加」ボタンを非表示にする。
 * （メニュー同様、標準の空サイト作成を防ぐ。物件追加はNS Clonerで行う）
 */
function mrc_hide_add_site_button() {
	if ( ! is_network_admin() || 'sites.php' !== $GLOBALS['pagenow'] ) {
		return;
	}
	echo "<style>.wrap .page-title-action{display:none !important;}</style>\n";
}
add_action( 'admin_head', 'mrc_hide_add_site_button' );

/**
 * NS Cloner（プラグイン）の主要UIを日本語化する。
 * 公式の日本語言語ファイルが無いため、複製画面で見える文字列を gettext フィルタで差し替える。
 * （マーケティング/アップセル等の一部文字列は対象外＝英語のまま）
 */
function mrc_nscloner_ja_map() {
	static $map = null;
	if ( null !== $map ) {
		return $map;
	}
	$map = array(
		// クローン画面・基本
		'NS Cloner'                                    => 'NS Cloner（サイト複製）',
		'Standard Clone'                               => '標準クローン',
		'Clone'                                        => '複製する',
		'Site cloned successfully!'                    => 'サイトを複製しました！',
		'Source Site'                                  => '複製元サイト',
		'Target Site'                                  => '複製先サイト',
		'Select Source'                                => '複製元を選択',
		'The cloning source is set to the current site.' => '複製元は現在のサイトに設定されています。',
		'You can use this plugin in Network mode  to choose from other source sites.' => 'ネットワークモードでは、他のサイトを複製元に選べます。',
		'Select a site to clone'                       => '複製するサイトを選択',
		'Choose an existing source site to clone.'     => '複製元にする既存サイトを選んでください。',
		'Create New Site'                              => '新しいサイトを作成',
		'Create Site'                                  => 'サイトを作成',
		'Give the target site a title'                 => '複製先サイトのタイトルを入力',
		'New Site Title'                               => '新しいサイトのタイトル',
		'Site title'                                   => 'サイトタイトル',
		'Give the target site a URL'                   => '複製先サイトのURLを入力',
		'Site URL'                                     => 'サイトURL',
		'Select Cloning Mode'                          => 'クローンモードを選択',
		'Collapse All'                                 => 'すべて閉じる',
		'Expand All'                                   => 'すべて開く',
		'No cloning modes are currently available for this site.' => '現在このサイトで利用できるクローンモードはありません。',
		// セクション・設定
		'Additional Settings'                          => '追加設定',
		'Search and Replace'                           => '検索と置換',
		'Copy Users'                                   => 'ユーザーをコピー',
		'Copy Media Files'                             => 'メディアファイルをコピー',
		'Post Types'                                   => '投稿タイプ',
		'All post types will be copied by default. '   => '既定ですべての投稿タイプがコピーされます。',
		'Copy Tables'                                  => 'テーブルをコピー',
		'All database tables will be copied by default. ' => '既定ですべてのデータベーステーブルがコピーされます。',
		'Database'                                     => 'データベース',
		'Skip views?'                                  => 'ビューをスキップしますか？',
		'Skip constraints?'                            => '制約をスキップしますか？',
		'Performance'                                  => 'パフォーマンス',
		'Rows per query'                               => '1クエリあたりの行数',
		'This controls how many database records will be copied at one time.' => '一度にコピーするデータベースのレコード数を指定します。',
		'Progress update interval'                     => '進捗の更新間隔',
		'Flush object cache after cloning'             => 'クローン後にオブジェクトキャッシュをクリア',
		'Debugging'                                    => 'デバッグ',
		'Enable logging'                               => 'ログを有効化',
		'Logs may contain sensitive information from your database.' => 'ログにはデータベースの機微な情報が含まれる場合があります。',
		// 進捗・状態
		'Start Time'                                   => '開始時刻',
		'End Time'                                     => '終了時刻',
		'Total Time'                                   => '合計時間',
		'%d item processed'                            => '%d 件を処理しました',
		'%d replacement made'                          => '%d 件を置換しました',
		'Replacements'                                 => '置換',
		'Log File'                                     => 'ログファイル',
		'Success'                                      => '成功',
		'Files'                                        => 'ファイル',
		'Rows'                                         => '行',
		'Tables'                                       => 'テーブル',
		'Refresh'                                      => '更新',
		'Cancel'                                       => 'キャンセル',
		'started...'                                   => '開始しました…',
		'finished...'                                  => '完了しました…',
		'Current status'                               => '現在の状態',
		'items processed'                              => '件処理済み',
		'Close'                                        => '閉じる',
		'Click here to try continuing.'                => 'ここをクリックして続行を試みる。',
		'During the cloning process we encountered some errors' => 'クローン処理中にエラーが発生しました',
		'click here'                                   => 'ここをクリック',
		'to view them'                                 => 'して確認',
		// ログ / 状態ページ
		'Logs / Status'                                => 'ログ / 状態',
		'Logs & Status'                                => 'ログと状態',
		'Scheduled Operations'                         => '予約された操作',
		'No scheduled cloning operations.'             => '予約されたクローン操作はありません。',
		'Scheduled'                                    => '予約済み',
		'Type'                                         => '種類',
		'Created'                                      => '作成日時',
		'View'                                         => '表示',
		'Delete'                                       => '削除',
		'Manage Logs'                                  => 'ログの管理',
		'No logs currently saved.'                     => '保存されたログはありません。',
		'Delete All Logs'                              => 'すべてのログを削除',
		'Clear Plugin Data'                            => 'プラグインデータを消去',
		'This will clear all plugin settings and any in-progress cloning data.' => 'プラグインの設定と進行中のクローンデータをすべて消去します。',
		'Delete All Plugin Data'                       => 'すべてのプラグインデータを削除',
		'View Logs'                                    => 'ログを表示',
		'View Log'                                     => 'ログを表示',
		'Date'                                         => '日付',
		'Size'                                         => 'サイズ',
		// バリデーション・エラー
		'Validation errors found.'                     => '入力エラーがあります。',
		'A cloning process is already in progress. Please wait until it completes.' => 'すでにクローン処理が実行中です。完了までお待ちください。',
		'Site URL is required.'                        => 'サイトURLは必須です。',
		'Site URLs can only contain letters (a-z), numbers and hyphens.' => 'サイトURLには英字(a-z)・数字・ハイフンのみ使用できます。',
		'Sorry, that site already exists!'             => 'そのサイトはすでに存在します。',
		'That URL is reserved by WordPress.'           => 'そのURLはWordPressで予約されています。',
		'A site title is required'                     => 'サイトタイトルは必須です。',
		"You don't have sufficient permissions for this action." => 'この操作を行う権限がありません。',
		'An unknown error occurred. Check the logs for info.' => '不明なエラーが発生しました。詳細はログを確認してください。',
		'Source and target prefix the same. Cannot clone tables.' => '複製元と複製先のプレフィックスが同じです。テーブルを複製できません。',
		'Source and target uploads directories are the same. Cannot clone files.' => '複製元と複製先のアップロード先が同じです。ファイルを複製できません。',
	);
	return $map;
}

function mrc_nscloner_translate( $translated, $text, $domain ) {
	if ( 'ns-cloner-site-copier' !== $domain ) {
		return $translated;
	}
	$map = mrc_nscloner_ja_map();
	return isset( $map[ $text ] ) ? $map[ $text ] : $translated;
}
add_filter( 'gettext', 'mrc_nscloner_translate', 10, 3 );

function mrc_nscloner_translate_ctx( $translated, $text, $context, $domain ) {
	if ( 'ns-cloner-site-copier' !== $domain ) {
		return $translated;
	}
	$map = mrc_nscloner_ja_map();
	return isset( $map[ $text ] ) ? $map[ $text ] : $translated;
}
add_filter( 'gettext_with_context', 'mrc_nscloner_translate_ctx', 10, 4 );

/**
 * ネットワーク管理のダッシュボードを整理する。
 * 既定の「現在の状況」「WordPressイベントとニュース」を撤去し、
 * 物件管理用のシンプルな運用ガイドに置き換える。
 */
function mrc_network_dashboard_setup() {
	remove_meta_box( 'network_dashboard_right_now', 'dashboard-network', 'normal' ); // 現在の状況
	remove_meta_box( 'dashboard_primary', 'dashboard-network', 'side' );             // WordPressイベントとニュース
	remove_meta_box( 'dashboard_primary', 'dashboard-network', 'normal' );           // （念のため）
	wp_add_dashboard_widget( 'mrc_network_guide', '物件サイトの管理', 'mrc_network_guide_html' );
}
add_action( 'wp_network_dashboard_setup', 'mrc_network_dashboard_setup' );

function mrc_network_guide_html() {
	$sites = (int) get_blog_count();
	?>
	<p>現在の登録サイト数：<strong><?php echo esc_html( number_format_i18n( $sites ) ); ?></strong>（雛形サイトを含む）</p>
	<p style="display:flex;gap:8px;flex-wrap:wrap;">
		<a href="<?php echo esc_url( network_admin_url( 'sites.php' ) ); ?>" class="button button-primary">物件サイト一覧</a>
		<a href="<?php echo esc_url( network_admin_url( 'admin.php?page=ns-cloner' ) ); ?>" class="button button-primary">物件を追加（複製）</a>
		<a href="<?php echo esc_url( network_admin_url( 'settings.php?page=mrc-privacy' ) ); ?>" class="button button-primary">プライバシーポリシーを編集</a>
	</p>
	<p class="description" style="margin-top:8px;">新しい物件は「NS Cloner」で雛形サイトを複製して追加します。</p>
	<?php
}


/* ============================================================
   ユーザーの権限グループを、この仕組みで実際に使う3つに整理する。
   - 居住者（購読者）… フロント閲覧のみ。日常的に追加する対象。
   - 編集者          … 施工会社（着工後の窓口）がお知らせ等を投稿。
   - 管理者          … MRC管理スタッフ（設定・複製まで）。
   使い道のない「寄稿者」「投稿者」は選択肢から外し、迷いを減らす。
   ============================================================ */

/** 権限グループの選択肢から未使用ロール（寄稿者・投稿者）を除外する。 */
function mrc_restrict_editable_roles( $roles ) {
	unset( $roles['contributor'], $roles['author'] );
	return $roles;
}
add_filter( 'editable_roles', 'mrc_restrict_editable_roles' );

/**
 * 「購読者（Subscriber）」の表示名を「居住者」に置き換える。
 * ロールの表示名は translate_user_role() 経由で 'User role' コンテキストの
 * gettext を通るため、そこを差し替える。スラッグ（subscriber）や権限は不変。
 */
function mrc_rename_subscriber_label( $translated, $text, $context, $domain ) {
	if ( 'User role' === $context && 'Subscriber' === $text ) {
		return '居住者';
	}
	return $translated;
}
add_filter( 'gettext_with_context', 'mrc_rename_subscriber_label', 10, 4 );

/** ユーザー追加画面（物件サイト）に、どの権限グループを選ぶかの案内を出す。 */
function mrc_user_new_role_hint() {
	if ( is_network_admin() || 'user-new.php' !== $GLOBALS['pagenow'] ) {
		return;
	}
	?>
	<div class="notice notice-info">
		<p style="font-size:14px;line-height:1.7;">
			<strong>権限グループの選び方</strong><br>
			<span style="display:inline-block;min-width:6em;">■ 居住者</span>… マンションの居住者（サイトを見るだけ）<br>
			<span style="display:inline-block;min-width:6em;">■ 編集者</span>… 施工会社など、お知らせ等を投稿する担当者<br>
			<span style="display:inline-block;min-width:6em;">■ 管理者</span>… MRCの管理スタッフ（設定・物件追加まで）
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', 'mrc_user_new_role_hint' );

