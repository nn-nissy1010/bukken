<?php
/**
 * プライバシーポリシー（全物件共通・ネットワーク一元管理・/privacy/ 表示）
 * functions.php から読み込まれる機能モジュール。
 *
 * @package mrc-residents
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ===========================================================================
 * プライバシーポリシー（全物件共通・ネットワークで一元管理）
 *  - 本文はネットワーク共通の site_option 'mrc_privacy_policy' に保存
 *  - ネットワーク管理 › 設定 で編集（＝MRC本部/開発のみ・物件担当は触れない）
 *  - /privacy/ の仮想ページで全文表示、全ページのフッターからリンク
 * ======================================================================== */

/** 初期ひな形（未設定時に表示・編集の下敷きにする一般的な文面）。 */
function mrc_default_privacy_policy() {
	return <<<'HTML'
<p>株式会社MRC（以下「当社」といいます）は、本サイト（居住者専用サイト）の運営にあたり、居住者の皆さまの個人情報を適切に取り扱います。</p>
<h2>1. 取得する情報</h2>
<p>本サイトでは、ログイン用のID・お問い合わせ時にご入力いただくお名前・部屋番号・ご連絡先・お問い合わせ内容などを取得する場合があります。</p>
<h2>2. 利用目的</h2>
<ul>
<li>大規模修繕工事に関する情報提供・ご連絡のため</li>
<li>お問い合わせ・ご意見への対応のため</li>
<li>本サイトの運営・保守・改善のため</li>
</ul>
<h2>3. 第三者への提供</h2>
<p>法令に基づく場合を除き、ご本人の同意なく個人情報を第三者へ提供することはありません。工事の実施に必要な範囲で、施工会社・設計監理者等と情報を共有する場合があります。</p>
<h2>4. 安全管理</h2>
<p>取得した個人情報の漏えい・滅失・毀損を防止するため、必要かつ適切な安全管理措置を講じます。</p>
<h2>5. お問い合わせ窓口</h2>
<p>個人情報の取り扱いに関するお問い合わせは、本サイトの「ご意見の窓口」よりご連絡ください。</p>
<h2>6. 本ポリシーの改定</h2>
<p>本ポリシーの内容は、必要に応じて改定することがあります。改定後の内容は本ページに掲載した時点から適用されます。</p>
HTML;
}

/** 表示用のプライバシーポリシー本文（未設定なら初期ひな形）。 */
function mrc_get_privacy_policy() {
	$content = get_site_option( 'mrc_privacy_policy', '' );
	if ( '' === trim( wp_strip_all_tags( (string) $content ) ) ) {
		$content = mrc_default_privacy_policy();
	}
	return $content;
}

/** プライバシーポリシーページのURL（各物件のサイト内 /privacy/）。 */
function mrc_privacy_url() {
	return home_url( '/privacy/' );
}

/** ネットワーク管理 › 設定 の下に、プライバシーポリシー専用ページを追加。 */
function mrc_privacy_admin_menu() {
	add_submenu_page(
		'settings.php',                // 親＝ネットワーク管理の「設定」
		'プライバシーポリシー',        // ページタイトル
		'プライバシーポリシー',        // メニュー名
		'manage_network_options',      // 権限（スーパー管理者）
		'mrc-privacy',                 // スラッグ
		'mrc_privacy_admin_page'       // 表示コールバック
	);
}
add_action( 'network_admin_menu', 'mrc_privacy_admin_menu' );

/** プライバシーポリシー専用ページ（表示＋保存）。 */
function mrc_privacy_admin_page() {
	if ( ! current_user_can( 'manage_network_options' ) ) {
		wp_die( '権限がありません。' );
	}

	$updated = false;
	if ( isset( $_POST['mrc_privacy_nonce'] ) && wp_verify_nonce( $_POST['mrc_privacy_nonce'], 'mrc_privacy_save' ) ) {
		$val = wp_kses_post( wp_unslash( $_POST['mrc_privacy_policy'] ) );
		update_site_option( 'mrc_privacy_policy', $val );
		$updated = true;
	}

	$content = get_site_option( 'mrc_privacy_policy', '' );
	if ( '' === trim( wp_strip_all_tags( (string) $content ) ) ) {
		$content = mrc_default_privacy_policy();
	}
	?>
	<div class="wrap">
		<h1>プライバシーポリシー（全物件共通）</h1>
		<?php if ( $updated ) : ?>
			<div class="notice notice-success is-dismissible"><p>保存しました。全物件・全ページのフッター「プライバシーポリシー」に反映されます。</p></div>
		<?php endif; ?>
		<p class="description" style="margin:12px 0;">ここで編集した内容が、<strong>全物件・全ページのフッター「プライバシーポリシー」</strong>に共通で表示されます。物件ごとの個別設定はありません。見出しや箇条書きなどの装飾が使えます。<br>法的文書のため、内容は必ず自社の実際の方針に合わせてご確認ください。</p>
		<form method="post" action="">
			<?php wp_nonce_field( 'mrc_privacy_save', 'mrc_privacy_nonce' ); ?>
			<?php
			wp_editor(
				$content,
				'mrc_privacy_policy',
				array(
					'textarea_name' => 'mrc_privacy_policy',
					'textarea_rows' => 20,
					'media_buttons' => false,
					'teeny'         => true,
				)
			);
			?>
			<?php submit_button( '保存する' ); ?>
		</form>
	</div>
	<?php
}

/** /privacy/ を仮想ページとして扱うためのリライトルール。 */
function mrc_privacy_rewrite() {
	add_rewrite_rule( '^privacy/?$', 'index.php?mrc_privacy=1', 'top' );
}
add_action( 'init', 'mrc_privacy_rewrite' );

function mrc_privacy_query_var( $vars ) {
	$vars[] = 'mrc_privacy';
	return $vars;
}
add_filter( 'query_vars', 'mrc_privacy_query_var' );

/**
 * /privacy/ の仮想ページが「ホーム/フロントページ」と誤認されないようクエリフラグを正す。
 * template_redirect より前の 'wp' フックで実行する必要がある。
 * （template_redirect では、ログイン済みユーザーをトップから会員トップへ飛ばす処理などが
 *   フロントページ誤認のまま /privacy/ をリダイレクトしてしまうため）
 */
function mrc_privacy_fix_query() {
	if ( 1 === intval( get_query_var( 'mrc_privacy' ) ) ) {
		global $wp_query;
		$wp_query->is_404        = false;
		$wp_query->is_home       = false;
		$wp_query->is_front_page = false;
	}
}
add_action( 'wp', 'mrc_privacy_fix_query' );

/** /privacy/ アクセス時に専用テンプレートを読み込む（404を回避）。 */
function mrc_privacy_template( $template ) {
	if ( 1 === intval( get_query_var( 'mrc_privacy' ) ) ) {
		status_header( 200 );
		$t = get_theme_file_path( 'page-privacy.php' );
		if ( file_exists( $t ) ) {
			return $t;
		}
	}
	return $template;
}
add_filter( 'template_include', 'mrc_privacy_template' );
