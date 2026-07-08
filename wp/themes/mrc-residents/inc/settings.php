<?php
/**
 * 物件基本設定・ご意見の窓口（通知先）・表示切替・メール送信
 * functions.php から読み込まれる機能モジュール。
 *
 * @package mrc-residents
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ============================================================
   設定：ご意見の窓口 通知先 ／ 物件基本設定
   ============================================================ */

/** 問い合わせ種別 */
function mrc_contact_types() {
	return array(
		'construction' => '工事のこと',
		'plan'         => '計画・全体のこと',
		'living'       => '生活・管理のこと',
		'other'        => 'その他',
	);
}

/** 工事段階 */
function mrc_contact_phases() {
	return array(
		'before' => '着工前',
		'after'  => '着工後',
	);
}

/** 通知先の既定値（着工前・全てMRC＝管理者メール／着工後は未設定） */
function mrc_default_contact_settings() {
	return array(
		'phase'        => 'before',
		'email_before' => get_option( 'admin_email' ),
		'email_after'  => '',
	);
}

/** 保存済み通知先（未設定は既定で補完） */
function mrc_get_contact_settings() {
	return wp_parse_args( (array) get_option( 'mrc_contact_settings', array() ), mrc_default_contact_settings() );
}

/** 現在の工事段階（before/after） */
function mrc_contact_current_phase() {
	$s = mrc_get_contact_settings();
	return ( 'after' === ( $s['phase'] ?? 'before' ) ) ? 'after' : 'before';
}

/** 現在の段階に応じた送信先メール（着工前＝MRC／着工後＝施工会社） */
function mrc_contact_recipient() {
	$s     = mrc_get_contact_settings();
	$email = ( 'after' === mrc_contact_current_phase() ) ? ( $s['email_after'] ?? '' ) : ( $s['email_before'] ?? '' );
	return ! empty( $email ) ? $email : get_option( 'admin_email' );
}

/**
 * スパム対策：入力フォームに埋め込む隠しフィールド（ハニーポット＋送信開始時刻）。
 * 外部サービス不要の自己完結型。本番でreCAPTCHA等を足す場合はここに追記。
 */
function mrc_spam_fields() {
	echo '<input type="hidden" name="mrc_hp_ts" value="' . esc_attr( time() ) . '">';
	echo '<div class="mrc-hp" aria-hidden="true">';
	echo '<label>URL（入力不要）<input type="text" name="mrc_hp_url" tabindex="-1" autocomplete="off" value=""></label>';
	echo '</div>';
}

/** スパム判定：ハニーポットに入力あり、または送信が早すぎる（2秒未満）場合に true。 */
function mrc_is_spam_submission() {
	if ( ! empty( $_POST['mrc_hp_url'] ) ) {
		return true;
	}
	$ts = isset( $_POST['mrc_hp_ts'] ) ? absint( $_POST['mrc_hp_ts'] ) : 0;
	if ( $ts > 0 && ( time() - $ts ) < 2 ) {
		return true;
	}
	return false;
}

/* ============================================================
   reCAPTCHA v3（任意・ネットワーク共通キー）
   キーが設定されていれば有効化、未設定ならハニーポットにフォールバック
   ============================================================ */

/** reCAPTCHAキー（ネットワーク共通） */
function mrc_recaptcha_keys() {
	return array(
		'site'   => (string) get_site_option( 'mrc_recaptcha_site', '' ),
		'secret' => (string) get_site_option( 'mrc_recaptcha_secret', '' ),
	);
}

/** 両キーが設定されていれば有効 */
function mrc_recaptcha_enabled() {
	$k = mrc_recaptcha_keys();
	return '' !== $k['site'] && '' !== $k['secret'];
}

/** 送信フォーム用：トークン格納の隠しフィールド（有効時のみ出力） */
function mrc_recaptcha_field() {
	if ( ! mrc_recaptcha_enabled() ) {
		return;
	}
	echo '<input type="hidden" name="g-recaptcha-response" class="mrc-recaptcha-token" value="">';
}

/** reCAPTCHA v3 スクリプトの読み込み（有効時・お問い合わせページのみ） */
function mrc_recaptcha_enqueue() {
	if ( is_admin() || ! mrc_recaptcha_enabled() || ! is_page( array( 'contact', 'contact-public' ) ) ) {
		return;
	}
	$k = mrc_recaptcha_keys();
	wp_enqueue_script( 'google-recaptcha', 'https://www.google.com/recaptcha/api.js?render=' . rawurlencode( $k['site'] ), array(), null, true );
	$site   = wp_json_encode( $k['site'] );
	$inline = 'document.addEventListener("submit",function(e){var f=e.target;var t=f.querySelector(".mrc-recaptcha-token");if(!t)return;e.preventDefault();grecaptcha.ready(function(){grecaptcha.execute(' . $site . ',{action:"submit"}).then(function(tok){t.value=tok;f.submit();});});},true);';
	wp_add_inline_script( 'google-recaptcha', $inline );
}
add_action( 'wp_enqueue_scripts', 'mrc_recaptcha_enqueue' );

/** トークンをGoogleで検証（無効時・通信失敗時は true＝ブロックしない） */
function mrc_recaptcha_verify( $token ) {
	if ( ! mrc_recaptcha_enabled() ) {
		return true;
	}
	if ( empty( $token ) ) {
		return false;
	}
	$k   = mrc_recaptcha_keys();
	$res = wp_remote_post(
		'https://www.google.com/recaptcha/api/siteverify',
		array(
			'timeout' => 8,
			'body'    => array(
				'secret'   => $k['secret'],
				'response' => $token,
				'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
			),
		)
	);
	if ( is_wp_error( $res ) ) {
		return true; // Googleへ到達できない場合は誤ブロックを避けて通す
	}
	$body = json_decode( wp_remote_retrieve_body( $res ), true );
	if ( empty( $body['success'] ) ) {
		return false;
	}
	$score = isset( $body['score'] ) ? (float) $body['score'] : 0.5;
	return $score >= 0.5;
}

/** ネットワーク管理 › 設定 に reCAPTCHA キー設定ページを追加 */
function mrc_recaptcha_admin_menu() {
	add_submenu_page( 'settings.php', 'スパム対策（reCAPTCHA）', 'スパム対策', 'manage_network_options', 'mrc-recaptcha', 'mrc_recaptcha_admin_page' );
}
add_action( 'network_admin_menu', 'mrc_recaptcha_admin_menu' );

function mrc_recaptcha_admin_page() {
	if ( ! current_user_can( 'manage_network_options' ) ) {
		wp_die( '権限がありません。' );
	}
	$updated = false;
	if ( isset( $_POST['mrc_recaptcha_nonce'] ) && wp_verify_nonce( $_POST['mrc_recaptcha_nonce'], 'mrc_recaptcha_save' ) ) {
		update_site_option( 'mrc_recaptcha_site', sanitize_text_field( wp_unslash( $_POST['mrc_recaptcha_site'] ?? '' ) ) );
		update_site_option( 'mrc_recaptcha_secret', sanitize_text_field( wp_unslash( $_POST['mrc_recaptcha_secret'] ?? '' ) ) );
		$updated = true;
	}
	$k = mrc_recaptcha_keys();
	?>
	<div class="wrap">
		<h1>スパム対策（reCAPTCHA v3）</h1>
		<?php if ( $updated ) : ?>
			<div class="notice notice-success is-dismissible"><p>保存しました。</p></div>
		<?php endif; ?>
		<p class="description" style="margin:12px 0;">
			GoogleのreCAPTCHA v3キーを入力すると、全物件のお問い合わせフォームでreCAPTCHAが有効になります（キー未入力の間は、キー不要のハニーポット対策で動作します）。<br>
			キーは <a href="https://www.google.com/recaptcha/admin" target="_blank" rel="noopener">Google reCAPTCHA管理画面</a> で「v3」を選び、対象ドメインを登録すると無料で取得できます。
			現在の状態：<strong><?php echo mrc_recaptcha_enabled() ? '有効（reCAPTCHA）' : '無効（ハニーポットのみ）'; ?></strong>
		</p>
		<form method="post" action="">
			<?php wp_nonce_field( 'mrc_recaptcha_save', 'mrc_recaptcha_nonce' ); ?>
			<table class="form-table"><tbody>
				<tr>
					<th scope="row"><label for="rc-site">サイトキー</label></th>
					<td><input type="text" id="rc-site" name="mrc_recaptcha_site" value="<?php echo esc_attr( $k['site'] ); ?>" class="regular-text" autocomplete="off"></td>
				</tr>
				<tr>
					<th scope="row"><label for="rc-secret">シークレットキー</label></th>
					<td><input type="text" id="rc-secret" name="mrc_recaptcha_secret" value="<?php echo esc_attr( $k['secret'] ); ?>" class="regular-text" autocomplete="off">
					<p class="description">シークレットキーはサーバー内部でのみ使用し、フロントには出力されません。</p></td>
				</tr>
			</tbody></table>
			<?php submit_button( '保存する' ); ?>
		</form>
	</div>
	<?php
}

/** 物件基本設定：対象ページ一覧 */
function mrc_property_pages() {
	return array(
		'news'         => 'お知らせ',
		'plan'         => '工事の計画',
		'document'     => '資料ダウンロード',
		'video'        => '動画アーカイブ',
		'qa'           => 'Q&A',
		'contact'      => 'ご意見の窓口',
		'construction' => '工事に関するお知らせ（工事期間・任意）',
	);
}

/** 物件基本設定の既定値（工事に関するお知らせのみ既定OFF） */
function mrc_default_property_settings() {
	$out = array();
	foreach ( array_keys( mrc_property_pages() ) as $k ) {
		$on         = ( 'construction' !== $k );
		$out[ $k ] = array( 'public' => $on ? 1 : 0, 'menu' => $on ? 1 : 0 );
	}
	return $out;
}

/** 保存済み物件基本設定（未設定は既定で補完） */
function mrc_get_property_settings() {
	$saved = (array) get_option( 'mrc_property_settings', array() );
	$out   = mrc_default_property_settings();
	foreach ( $out as $k => $v ) {
		if ( isset( $saved[ $k ] ) ) {
			$out[ $k ]['public'] = ! empty( $saved[ $k ]['public'] ) ? 1 : 0;
			$out[ $k ]['menu']   = ! empty( $saved[ $k ]['menu'] ) ? 1 : 0;
		}
	}
	return $out;
}

function mrc_page_menu_visible( $key ) {
	$s = mrc_get_property_settings();
	return ! isset( $s[ $key ] ) || ! empty( $s[ $key ]['menu'] );
}
function mrc_page_is_public( $key ) {
	$s = mrc_get_property_settings();
	return ! isset( $s[ $key ] ) || ! empty( $s[ $key ]['public'] );
}

/* --- 設定の登録 --- */
function mrc_register_settings() {
	register_setting(
		'mrc_contact_group',
		'mrc_contact_settings',
		array( 'type' => 'array', 'sanitize_callback' => 'mrc_sanitize_contact_settings', 'default' => mrc_default_contact_settings() )
	);
	register_setting(
		'mrc_property_group',
		'mrc_property_settings',
		array( 'type' => 'array', 'sanitize_callback' => 'mrc_sanitize_property_settings' )
	);
}
add_action( 'admin_init', 'mrc_register_settings' );

function mrc_sanitize_contact_settings( $in ) {
	return array(
		'phase'        => ( isset( $in['phase'] ) && 'after' === $in['phase'] ) ? 'after' : 'before',
		'email_before' => isset( $in['email_before'] ) ? sanitize_email( $in['email_before'] ) : '',
		'email_after'  => isset( $in['email_after'] ) ? sanitize_email( $in['email_after'] ) : '',
	);
}
function mrc_sanitize_property_settings( $in ) {
	$out = array();
	foreach ( array_keys( mrc_property_pages() ) as $k ) {
		$out[ $k ] = array(
			'public' => ! empty( $in[ $k ]['public'] ) ? 1 : 0,
			'menu'   => ! empty( $in[ $k ]['menu'] ) ? 1 : 0,
		);
	}
	return $out;
}

/* --- 管理メニューの登録 --- */
function mrc_add_admin_pages() {
	// トップレベル「物件基本設定」。配下に 物件基本設定／通知先設定 をまとめる。
	add_menu_page( '物件基本設定', '物件基本設定', 'manage_options', 'mrc-property', 'mrc_render_property_page', 'dashicons-admin-home', 59 );
	add_submenu_page( 'mrc-property', '物件基本設定', '物件基本設定', 'manage_options', 'mrc-property', 'mrc_render_property_page' );
	add_submenu_page( 'mrc-property', 'ご意見の窓口 通知先設定', '通知先設定', 'manage_options', 'mrc-contact', 'mrc_render_contact_page' );
}
add_action( 'admin_menu', 'mrc_add_admin_pages' );

function mrc_render_contact_page() {
	$s     = mrc_get_contact_settings();
	$phase = mrc_contact_current_phase();
	?>
	<div class="wrap">
		<h1>ご意見の窓口 通知先設定</h1>
		<p>お問い合わせの送信先を、工事の段階に応じて切り替えます。<strong>着工したら「工事段階」を「着工後」に切り替えるだけ</strong>で、送信先が施工会社に変わります。</p>
		<form method="post" action="options.php">
			<?php settings_fields( 'mrc_contact_group' ); ?>
			<table class="form-table"><tbody>
				<tr>
					<th scope="row">工事段階</th>
					<td>
						<label style="margin-right:20px;"><input type="radio" name="mrc_contact_settings[phase]" value="before" <?php checked( $phase, 'before' ); ?>> 着工前（送信先：着工前の宛先）</label>
						<label><input type="radio" name="mrc_contact_settings[phase]" value="after" <?php checked( $phase, 'after' ); ?>> 着工後（送信先：着工後の宛先）</label>
						<p class="description">現在の送信先：<strong><?php echo esc_html( mrc_contact_recipient() ); ?></strong></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="mc-before">着工前の送信先</label></th>
					<td>
						<input type="email" id="mc-before" name="mrc_contact_settings[email_before]" value="<?php echo esc_attr( $s['email_before'] ?? '' ); ?>" class="regular-text" placeholder="例：株式会社MRC のメール">
						<p class="description">着工前は、すべてのお問い合わせがこの宛先に届きます（通常は株式会社MRC）。</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="mc-after">着工後の送信先</label></th>
					<td>
						<input type="email" id="mc-after" name="mrc_contact_settings[email_after]" value="<?php echo esc_attr( $s['email_after'] ?? '' ); ?>" class="regular-text" placeholder="例：施工会社 のメール">
						<p class="description">着工後は、すべてのお問い合わせがこの宛先に届きます（通常は施工会社）。</p>
					</td>
				</tr>
			</tbody></table>
			<p class="description">※ 住民が選ぶ「種別（工事のこと／計画・全体／生活・管理／その他）」は分類・記録用として保持され、通知メールに記載されます。</p>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

function mrc_render_property_page() {
	$s     = mrc_get_property_settings();
	$pages = mrc_property_pages();
	?>
	<div class="wrap">
		<h1>物件基本設定</h1>
		<p>ページ・メニューの出し分けは、データ駆動の自動ではなく <strong>手動 ON/OFF</strong> です。（居住者からの見え方に反映されます）</p>
		<form method="post" action="options.php">
			<?php settings_fields( 'mrc_property_group' ); ?>
			<table class="widefat striped" style="max-width:760px; margin-top:12px;">
				<thead><tr><th>ページ</th><th style="width:180px;">このページを公開</th><th style="width:180px;">メニューに表示</th></tr></thead>
				<tbody>
				<?php foreach ( $pages as $k => $label ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $label ); ?></strong></td>
						<td><label><input type="checkbox" name="mrc_property_settings[<?php echo esc_attr( $k ); ?>][public]" value="1" <?php checked( $s[ $k ]['public'] ); ?>> 公開する</label></td>
						<td><label><input type="checkbox" name="mrc_property_settings[<?php echo esc_attr( $k ); ?>][menu]" value="1" <?php checked( $s[ $k ]['menu'] ); ?>> 表示する</label></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<p class="description" style="margin-top:8px;">※「工事に関するお知らせ」は既定OFF。要望のある物件だけONにする運用です。</p>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

/* --- 物件基本設定「公開OFF」を居住者の閲覧に反映（スタッフは準備のため閲覧可） --- */
function mrc_apply_property_visibility() {
	if ( is_admin() || mrc_is_staff() ) {
		return;
	}
	$key = null;
	if ( is_post_type_archive( 'news' ) || is_singular( 'news' ) || is_tax( 'news_category' ) ) {
		$key = 'news';
	} elseif ( is_page( 'plan' ) ) {
		$key = 'plan';
	} elseif ( is_post_type_archive( 'document' ) || is_singular( 'document' ) ) {
		$key = 'document';
	} elseif ( is_post_type_archive( 'video' ) || is_singular( 'video' ) ) {
		$key = 'video';
	} elseif ( is_post_type_archive( 'qa' ) || is_singular( 'qa' ) ) {
		$key = 'qa';
	} elseif ( is_page( 'contact' ) ) {
		$key = 'contact';
	}
	if ( $key && ! mrc_page_is_public( $key ) ) {
		wp_safe_redirect( home_url( '/member/' ) );
		exit;
	}
}
add_action( 'template_redirect', 'mrc_apply_property_visibility', 20 );

/* --- ご意見の窓口：メール送信 --- */
function mrc_send_contact_mail( $type, $name, $room, $body ) {
	$to      = mrc_contact_recipient();
	$types   = mrc_contact_types();
	$phases  = mrc_contact_phases();
	$label   = isset( $types[ $type ] ) ? $types[ $type ] : 'その他';
	$subject = '【' . get_bloginfo( 'name' ) . '】ご意見の窓口: ' . $label;
	$lines   = array(
		'種別: ' . $label,
		'工事段階: ' . $phases[ mrc_contact_current_phase() ],
		'お名前: ' . ( '' !== $name ? $name : '（未入力）' ),
		'部屋番号: ' . ( '' !== $room ? $room : '（未入力）' ),
		'',
		'お問い合わせ内容:',
		$body,
		'',
		'---',
		'このメールは居住者専用サイトのご意見の窓口から自動送信されています。',
	);
	return wp_mail( $to, $subject, implode( "\n", $lines ) );
}

