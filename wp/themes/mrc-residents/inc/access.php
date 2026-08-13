<?php
/**
 * アクセス制御：ログイン・会員ゲート・居住者制限・noindex
 * functions.php から読み込まれる機能モジュール。
 *
 * @package mrc-residents
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 居住者専用サイトのため常に noindex, nofollow を強制する。
 * WordPress標準の wp_robots を使うのでメタタグは1つに集約され、
 * 「検索エンジンでの表示」設定（blog_public）とも重複しない。
 */
function mrc_force_noindex( $robots ) {
	$robots['noindex']  = true;
	$robots['nofollow'] = true;
	unset( $robots['follow'], $robots['index'] );
	return $robots;
}
add_filter( 'wp_robots', 'mrc_force_noindex', 99 );

/**
 * robots.txt も全クロール拒否（居住者専用サイトのため）。
 */
function mrc_robots_txt( $output, $public ) {
	return "User-agent: *\nDisallow: /\n";
}
add_filter( 'robots_txt', 'mrc_robots_txt', 99, 2 );

/**
 * 「MRCスタッフ（管理側）」かどうかの判定。
 * コンテンツ編集権限（edit_posts）を持つ＝管理者・編集者など。
 * 持たない購読者（居住者）は会員フロントのみ利用可。
 *
 * @param WP_User|null $user 判定対象。null なら現在のユーザー。
 * @return bool
 */
function mrc_is_staff( $user = null ) {
	if ( $user instanceof WP_User ) {
		return user_can( $user, 'edit_posts' );
	}
	return current_user_can( 'edit_posts' );
}

/**
 * ログイン後のリダイレクト
 * MRCスタッフは管理画面、居住者は会員トップへ。
 */
function mrc_login_redirect( $redirect_to, $requested_redirect_to, $user ) {
	if ( $user instanceof WP_User && ! mrc_is_staff( $user ) ) {
		return home_url( '/member/' );
	}
	return $redirect_to;
}
add_filter( 'login_redirect', 'mrc_login_redirect', 10, 3 );

/**
 * 居住者（編集権限なし）を管理画面 wp-admin から締め出す。
 * admin-ajax.php は許可（フロントの非同期処理のため）。
 */
function mrc_block_wp_admin_for_residents() {
	if ( ! is_user_logged_in() || wp_doing_ajax() ) {
		return;
	}
	if ( ! mrc_is_staff() ) {
		wp_safe_redirect( home_url( '/member/' ) );
		exit;
	}
}
add_action( 'admin_init', 'mrc_block_wp_admin_for_residents' );

/**
 * フロント（住民用画面）では管理ツールバーを表示しない。
 * ※ show_admin_bar フィルターはフロントのみに作用し、wp-admin 内の
 *   ツールバーには影響しない（管理者は管理画面では従来どおり利用可）。
 */
add_filter( 'show_admin_bar', '__return_false' );

/**
 * 会員ゾーンのアクセス制御（未ログインは締め出す）
 * 会員トップ・各投稿タイプの個別/アーカイブを保護。
 */
function mrc_member_gate() {
	if ( is_admin() || is_user_logged_in() ) {
		return;
	}

	$member_types = array( 'news', 'document', 'video', 'qa' );

	$is_protected =
		is_page( array( 'member', 'plan', 'contact' ) )
		|| is_singular( $member_types )
		|| is_post_type_archive( $member_types )
		|| is_tax( 'news_category' );

	if ( $is_protected ) {
		// 現在アクセス中の完全URL（マルチサイトのパスを二重化しないよう REQUEST_URI から組み立てる）
		$current = ( is_ssl() ? 'https' : 'http' ) . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		wp_safe_redirect( wp_login_url( esc_url_raw( $current ) ) );
		exit;
	}
}
add_action( 'template_redirect', 'mrc_member_gate' );

/**
 * 居住者向けログインの処理（トップのログインボックスで完結）
 * 管理画面の wp-login.php とは別系統。失敗時もトップ内でエラー表示する。
 */
function mrc_handle_resident_login() {
	if ( empty( $_POST['mrc_login'] ) ) {
		return;
	}

	// CSRF 対策
	if ( ! isset( $_POST['mrc_login_nonce'] ) || ! wp_verify_nonce( $_POST['mrc_login_nonce'], 'mrc_login' ) ) {
		$GLOBALS['mrc_login_error'] = 'セッションの有効期限が切れました。もう一度お試しください。';
		return;
	}

	$creds = array(
		'user_login'    => isset( $_POST['log'] ) ? wp_unslash( $_POST['log'] ) : '',
		'user_password' => isset( $_POST['pwd'] ) ? $_POST['pwd'] : '',
		'remember'      => true,
	);

	if ( '' === trim( $creds['user_login'] ) || '' === $creds['user_password'] ) {
		$GLOBALS['mrc_login_error'] = 'IDとパスワードを入力してください。';
		return;
	}

	// 認証のみ（この時点ではログインクッキーを発行しない）
	$user = wp_authenticate( $creds['user_login'], $creds['user_password'] );

	if ( is_wp_error( $user ) ) {
		$GLOBALS['mrc_login_error'] = 'IDまたはパスワードが正しくありません。ご確認のうえ、もう一度お試しください。';
		return;
	}

	// このマンション（サイト）の登録者かを確認。他物件の居住者はログインさせない。
	if ( ! mrc_is_staff( $user ) && ! is_super_admin( $user->ID ) && ! is_user_member_of_blog( $user->ID, get_current_blog_id() ) ) {
		$GLOBALS['mrc_login_error'] = 'このマンションの居住者アカウントではありません。配布されたID・パスワードをご確認ください。';
		return;
	}

	// 認証・所属OK → ここでログインを確立
	wp_set_auth_cookie( $user->ID, ! empty( $creds['remember'] ) );
	wp_set_current_user( $user->ID );

	// 会員トップへ（MRCスタッフは管理画面へ）
	$dest = mrc_is_staff( $user ) ? admin_url() : home_url( '/member/' );
	wp_safe_redirect( $dest );
	exit;
}
add_action( 'template_redirect', 'mrc_handle_resident_login', 5 );

/**
 * ログイン中の居住者がログイン前トップに来たら会員トップへ転送。
 * （管理者は開発・確認のため転送しない）
 */
function mrc_redirect_logged_in_from_front() {
	if ( is_front_page() && is_user_logged_in() && ! mrc_is_staff() ) {
		wp_safe_redirect( home_url( '/member/' ) );
		exit;
	}
}
add_action( 'template_redirect', 'mrc_redirect_logged_in_from_front' );

/**
 * ───────────────────────────────────────────────────────────────
 * アカウント発行時の「初期パスワード設定メール」
 * ───────────────────────────────────────────────────────────────
 * マルチサイト標準の英語メールは使わず、日本語のブランドメールに一本化する。
 * ・新規ユーザーは管理者が追加した時点で即時有効化（英語の確認リンクを介さない）。
 *   → SMTP不達でもアカウント自体は作成され、管理者が手動でパスワードを配布できる。
 * ・サイトに追加された各ユーザーへ、パスワード設定リンク付きの案内メールを送る（1ユーザー1回）。
 */

// 標準（英語）の確認メール・ウェルカムメールを抑止する。
add_filter( 'wpmu_signup_user_notification', '__return_false' );
add_filter( 'wpmu_welcome_user_notification', '__return_false' );

/**
 * 管理者がサイトに追加した新規ユーザーを、英語の確認メールを介さず即時有効化する。
 * 有効化により add_user_to_blog が発火し、独自の設定メール送信につながる。
 *
 * @param string $user       申請ユーザー名
 * @param string $user_email 申請メールアドレス
 * @param string $key        有効化キー
 * @param array  $meta       追加先サイト・ロール等のメタ
 */
function mrc_auto_activate_signup( $user, $user_email, $key, $meta ) {
	if ( ! empty( $key ) ) {
		wpmu_activate_signup( $key );
	}
}
add_action( 'after_signup_user', 'mrc_auto_activate_signup', 10, 4 );

/**
 * サイトにユーザーが追加されたら、初期パスワード設定メールを送る（1ユーザー1回のみ）。
 *
 * add_user_to_blog アクションは、関数の引数順（$blog_id, $user_id, $role）とは異なり
 * ($user_id, $role, $blog_id) の順でコールバックへ渡す点に注意。
 *
 * @param int    $user_id 対象ユーザーID
 * @param string $role    付与ロール
 * @param int    $blog_id 追加先サイトID
 */
function mrc_send_welcome_email( $user_id, $role, $blog_id ) {
	// 既に送信済みなら二重送信しない（複数物件へ追加された場合も初回だけ）。
	if ( get_user_meta( $user_id, 'mrc_welcome_email_sent', true ) ) {
		return;
	}

	$user = get_userdata( $user_id );
	if ( ! $user || '' === $user->user_email ) {
		return;
	}

	// パスワード設定用のワンタイムキー付きURL（wp-login.php の標準リセット画面）。
	$key = get_password_reset_key( $user );
	if ( is_wp_error( $key ) ) {
		return;
	}
	$reset_url = get_site_url(
		$blog_id,
		'wp-login.php?action=rp&key=' . $key . '&login=' . rawurlencode( $user->user_login )
	);

	$site_name = get_blog_option( $blog_id, 'blogname' );
	$site_url  = get_site_url( $blog_id, '/' );
	$greeting  = '' !== $user->display_name ? $user->display_name : $user->user_login;

	$subject = '【' . $site_name . '】居住者専用サイト パスワード設定のお願い';
	$lines   = array(
		$greeting . ' 様',
		'',
		$site_name . ' の居住者専用サイトのアカウントを発行しました。',
		'下記のリンクを開き、ご自身のパスワードを設定してください。',
		'',
		'▼ パスワード設定ページ',
		$reset_url,
		'',
		'パスワード設定後は、居住者専用サイトのトップページにある',
		'「居住者専用ログイン」から、次の情報でログインできます。',
		'',
		'　ログインID：' . $user->user_login,
		'　パスワード：上記ページでご自身が設定したもの',
		'',
		'▼ 居住者専用サイト',
		$site_url,
		'',
		'※このリンクは一定時間で無効になります。開けない場合は、サイト管理者までご連絡ください。',
		'※このメールにお心当たりがない場合は、破棄してください。',
		'',
		'---',
		$site_name,
	);

	$sent = wp_mail( $user->user_email, $subject, implode( "\n", $lines ) );

	// 送信できたときだけ済みフラグを立てる（不達時は再追加でリトライできる）。
	if ( $sent ) {
		update_user_meta( $user_id, 'mrc_welcome_email_sent', time() );
	}
}
add_action( 'add_user_to_blog', 'mrc_send_welcome_email', 10, 3 );
