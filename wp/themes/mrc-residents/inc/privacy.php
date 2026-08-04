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
<h2>はじめに</h2>
<p>株式会社MRC（以下、「当社」）は、各種サービスのご提供にあたり、お客さまの個人情報をお預かりしております。当社は個人情報を保護し、お客さまに更なる信頼性と安心感をご提供できるように努めて参ります。当社は、個人情報に関する法令を遵守し、個人情報の適切な取り扱いを実現いたします。</p>
<h2>1. 個人情報の取得について</h2>
<p>当社は、偽りその他不正の手段によらず適正に個人情報を取得いたします。なお、次のような場合に必要な範囲で個人情報を収集する場合があります。</p>
<ul>
<li>ご相談・お問い合わせ</li>
</ul>
<h2>2. 個人情報の利用について</h2>
<p>当社は、個人情報を以下の利用目的の達成に必要な範囲内で、利用いたします。以下に定めのない目的で個人情報を利用する場合、あらかじめご本人の同意を得た上で行ないます。</p>
<ul>
<li>ご相談・お問い合わせに対する回答や確認のご連絡のため</li>
<li>個人情報を特定しない統計情報に利用するため</li>
</ul>
<h2>3. 個人情報の安全管理について</h2>
<p>当社は、取り扱う個人情報の漏洩、滅失またはき損の防止その他の個人情報の安全管理のために必要かつ適切な措置を講じます。</p>
<h2>4. 個人情報の委託について</h2>
<p>当社は、個人情報の取り扱いの全部または一部を第三者に委託する場合は、当該第三者について厳正な調査を行い、取り扱いを委託された個人情報の安全管理が図られるよう当該第三者に対する必要かつ適切な監督を行います。</p>
<h2>5. 個人情報の第三者提供について</h2>
<p>当社は、個人情報保護法等の法令に定めのある場合を除き、個人情報をあらかじめご本人の同意を得ることなく、第三者に提供いたしません。</p>
<h2>6. 個人情報の開示・訂正等について</h2>
<p>当社は、ご本人から自己の個人情報についての開示の請求がある場合、速やかに開示をいたします。その際、ご本人であることが確認できない場合には、開示に応じません。</p>
<p>個人情報の内容に誤りがあり、ご本人から訂正・追加・削除の請求がある場合、調査の上、速やかにこれらの請求に対応いたします。その際、ご本人であることが確認できない場合には、これらの請求に応じません。</p>
<p>当社の個人情報の取り扱いにつきまして、上記の請求・お問い合わせ等ございましたら、下記までご連絡くださいますようお願い申し上げます。</p>
<p><strong>【 連絡先 】</strong><br>
名称　株式会社MRC（エムアールシー）<br>
所在地　〒101-0033　東京都千代田区神田岩本町4-5　都築ビル6階<br>
電話番号　TEL：03（6384）0024　FAX：03（6384）0064</p>
<h2>7. 組織・体制</h2>
<p>当社は、代表取締役　平松 直也を個人情報管理責任者とし、個人情報の適正な管理及び継続的な改善を実施いたします。</p>
<h2>8. その他の注意事項</h2>
<p>当社が運営するコンテンツや掲載広告などからリンクされている第三者のサイト及びサービスは、当社とは独立した個人情報の保護に関する規定やデータ収集の規約を定めています。当サイトはこれらの規約や活動に対していかなる義務や責任も負いません。</p>
<h2>9. 個人情報の管理方法の継続的改善について</h2>
<p>当社は、個人情報の管理方法を見直し、継続的に改善を実施します。</p>
<h2>10. 本方針の変更</h2>
<p>本方針の内容は変更されることがあります。変更後の本方針については、当社が別途定める場合を除いて、当サイトに掲載した時から効力を生じるものとします。</p>
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
