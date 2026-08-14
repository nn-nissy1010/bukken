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

/**
 * 通知先の項目一覧（キー => 画面ラベル）。
 * 会員「ご意見の窓口」の種別ごと＋ログイン前お問い合わせを、単一の情報源として扱う。
 * 種別を増減した場合もこの一覧が自動追従する。
 */
function mrc_contact_recipient_fields() {
	$fields = array();
	foreach ( mrc_contact_types() as $key => $label ) {
		$fields[ 'email_' . $key ] = $label;
	}
	$fields['email_public'] = 'ログイン前のお問い合わせ';
	return $fields;
}

/** 通知先の既定値（すべて空欄＝管理者メールにフォールバック） */
function mrc_default_contact_settings() {
	$out = array();
	foreach ( array_keys( mrc_contact_recipient_fields() ) as $key ) {
		$out[ $key ] = '';
	}
	return $out;
}

/** 保存済み通知先（未設定は既定で補完） */
function mrc_get_contact_settings() {
	return wp_parse_args( (array) get_option( 'mrc_contact_settings', array() ), mrc_default_contact_settings() );
}

/** 指定キーの送信先メール。全項目必須（保存時に検証）だが、未設定サイト・複製直後などで
 *  空欄が残っている場合にメールを失わないための最後の安全網として管理者メールへ寄せる。 */
function mrc_contact_recipient_by_key( $key ) {
	$s     = mrc_get_contact_settings();
	$email = isset( $s[ $key ] ) ? $s[ $key ] : '';
	return ! empty( $email ) ? $email : get_option( 'admin_email' );
}

/** 会員「ご意見の窓口」：種別ごとの送信先メール */
function mrc_contact_recipient_for( $type ) {
	$types = mrc_contact_types();
	$key   = isset( $types[ $type ] ) ? 'email_' . $type : 'email_other';
	return mrc_contact_recipient_by_key( $key );
}

/** ログイン前お問い合わせの送信先メール */
function mrc_public_contact_recipient() {
	return mrc_contact_recipient_by_key( 'email_public' );
}

/** 通知先が未設定（空欄・不正）の項目ラベル一覧。全項目OKなら空配列。 */
function mrc_contact_settings_missing() {
	$s       = mrc_get_contact_settings();
	$missing = array();
	foreach ( mrc_contact_recipient_fields() as $key => $label ) {
		$email = isset( $s[ $key ] ) ? $s[ $key ] : '';
		if ( empty( $email ) || ! is_email( $email ) ) {
			$missing[] = $label;
		}
	}
	return $missing;
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

/* --- 「はじめての方へ」FAQ（ログイン前トップ・物件ごとに編集可能） --- */

/** 既定のFAQ（現行フロントの4問）。未設定の物件はこれを表示する。 */
function mrc_default_first_faqs() {
	return array(
		array(
			'q' => 'ID・パスワードはどこでもらえますか？',
			'a' => '各住戸のポストに配布した書面（またはQRコード）に記載しています。お手元にない場合は、下の「お問い合わせ」からご連絡ください。',
		),
		array(
			'q' => 'ログインのしかた',
			'a' => '上の「居住者専用ログイン」に、配布したIDとパスワードを入力し「ログイン」を押してください。',
		),
		array(
			'q' => 'ログインできないとき',
			'a' => 'まずID・パスワードの打ち間違い（大文字・小文字、全角・半角）をご確認ください。それでもログインできない場合は、下の「お問い合わせ」からご連絡ください。',
		),
		array(
			'q' => '家族や同居の方も見られますか？',
			'a' => 'はい。1つの住戸につき、同じID・パスワードでご家族・同居の方もご利用いただけます。IDは住戸ごとにお配りしています。',
		),
	);
}

/** 表示用FAQ。一度も保存していなければ既定、保存済みならその内容（0件なら非表示）。 */
function mrc_get_first_faqs() {
	$saved = get_option( 'mrc_first_faqs', false );
	if ( false === $saved ) {
		return mrc_default_first_faqs();
	}
	$out = array();
	foreach ( (array) $saved as $row ) {
		$q = isset( $row['q'] ) ? (string) $row['q'] : '';
		$a = isset( $row['a'] ) ? (string) $row['a'] : '';
		if ( '' === $q && '' === $a ) {
			continue;
		}
		$out[] = array( 'q' => $q, 'a' => $a );
	}
	return $out;
}

/** FAQ入力のサニタイズ（空行は除外・連番へ振り直し）。 */
function mrc_sanitize_first_faqs( $in ) {
	$out = array();
	if ( is_array( $in ) ) {
		foreach ( $in as $row ) {
			$q = isset( $row['q'] ) ? sanitize_text_field( $row['q'] ) : '';
			$a = isset( $row['a'] ) ? sanitize_textarea_field( $row['a'] ) : '';
			if ( '' === $q && '' === $a ) {
				continue;
			}
			$out[] = array( 'q' => $q, 'a' => $a );
		}
	}
	return $out;
}

/* --- ログイン前トップ「サイトについて」（キャッチ・見出し・説明文） --- */

/** 既定値。説明文には物件名を差し込む。 */
function mrc_default_front_about() {
	return array(
		'kicker'  => '居住者専用ポータル',
		'heading' => '大規模修繕工事の計画状況を、いつでもご確認いただけます',
		'body'    => 'このサイトは、' . get_bloginfo( 'name' ) . 'にお住まいの皆さまへ、大規模修繕工事の計画に関するお知らせ・スケジュール・資料などをお届けする、居住者専用のサイトです。掲示板を見に行かなくても、スマートフォンやパソコンからいつでもご確認いただけます。',
	);
}

/** 表示用。各項目とも未入力なら既定にフォールバック。 */
function mrc_get_front_about() {
	$saved   = (array) get_option( 'mrc_front_about', array() );
	$default = mrc_default_front_about();
	$out     = array();
	foreach ( $default as $k => $v ) {
		$val       = isset( $saved[ $k ] ) ? trim( (string) $saved[ $k ] ) : '';
		$out[ $k ] = '' !== $val ? $val : $v;
	}
	return $out;
}

/** 入力のサニタイズ。 */
function mrc_sanitize_front_about( $in ) {
	$in = (array) $in;
	return array(
		'kicker'  => isset( $in['kicker'] ) ? sanitize_text_field( $in['kicker'] ) : '',
		'heading' => isset( $in['heading'] ) ? sanitize_text_field( $in['heading'] ) : '',
		'body'    => isset( $in['body'] ) ? sanitize_textarea_field( $in['body'] ) : '',
	);
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
	register_setting(
		'mrc_first_faq_group',
		'mrc_first_faqs',
		array( 'type' => 'array', 'sanitize_callback' => 'mrc_sanitize_first_faqs' )
	);
	register_setting(
		'mrc_first_faq_group',
		'mrc_front_about',
		array( 'type' => 'array', 'sanitize_callback' => 'mrc_sanitize_front_about' )
	);
}
add_action( 'admin_init', 'mrc_register_settings' );

function mrc_sanitize_contact_settings( $in ) {
	$fields  = mrc_contact_recipient_fields();
	$out     = array();
	$invalid = array();
	foreach ( $fields as $key => $label ) {
		$email = isset( $in[ $key ] ) ? sanitize_email( $in[ $key ] ) : '';
		if ( empty( $email ) || ! is_email( $email ) ) {
			$invalid[] = $label;
		}
		$out[ $key ] = $email;
	}
	// 全項目必須。1つでも空欄・不正なら保存を破棄し、既存設定を維持する。
	if ( $invalid ) {
		add_settings_error(
			'mrc_contact_settings',
			'mrc_contact_required',
			'通知先メールは全項目が必須です。次の項目を正しいメールアドレスで入力してください：' . implode( '、', $invalid ),
			'error'
		);
		return wp_parse_args( (array) get_option( 'mrc_contact_settings', array() ), mrc_default_contact_settings() );
	}
	return $out;
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
	add_submenu_page( 'mrc-property', 'ログイン前トップ 編集', 'ログイン前トップ 編集', 'manage_options', 'mrc-first-faq', 'mrc_render_first_faq_page' );
}

/**
 * サイドバーに「ページ編集」トップレベルメニューを追加。
 * 一覧（本文編集リンク集）＋各ページの編集画面へ直接遷移するサブメニューを出す。
 * 固定ページの標準メニューは誤操作防止で非表示のため、本文編集はここから行う。
 */
function mrc_add_pages_menu() {
	add_menu_page( 'ページ編集', 'ページ編集', 'edit_pages', 'mrc-edit-pages', 'mrc_render_edit_pages_page', 'dashicons-admin-page', 58 );
	// 先頭サブメニューのラベルを「一覧」に整える。
	add_submenu_page( 'mrc-edit-pages', 'ページ本文の編集', '一覧', 'edit_pages', 'mrc-edit-pages', 'mrc_render_edit_pages_page' );
	// 各ページの編集画面へ直接遷移。
	foreach ( mrc_editable_body_pages() as $slug => $label ) {
		$page = get_page_by_path( $slug );
		if ( $page ) {
			add_submenu_page( 'mrc-edit-pages', $label . 'の本文を編集', $label, 'edit_pages', 'post.php?post=' . (int) $page->ID . '&action=edit' );
		}
	}
}
add_action( 'admin_menu', 'mrc_add_pages_menu' );

/** 本文を編集できる固定ページ（スラッグ => ラベル）。 */
function mrc_editable_body_pages() {
	return array(
		'plan'           => '工事の計画',
		'member'         => '会員トップ',
		'contact'        => 'ご意見の窓口',
		'contact-public' => 'お問い合わせ（ログイン前）',
	);
}

/** ページ本文の編集画面（各ページの編集へのリンク集。削除・新規追加はさせない）。 */
function mrc_render_edit_pages_page() {
	?>
	<div class="wrap">
		<h1>ページ本文の編集</h1>
		<p>各ページの本文（説明文）を編集できます。「本文を編集」を開き、<strong>本文欄</strong>を書き換えて更新してください。</p>
		<table class="widefat striped" style="max-width:820px;margin-top:12px;">
			<thead><tr><th>ページ</th><th style="width:260px;">操作</th></tr></thead>
			<tbody>
			<?php
			foreach ( mrc_editable_body_pages() as $slug => $label ) :
				$page = get_page_by_path( $slug );
				if ( ! $page ) {
					continue;
				}
				?>
				<tr>
					<td><strong><?php echo esc_html( $label ); ?></strong><br><span class="description">/<?php echo esc_html( $slug ); ?>/</span></td>
					<td>
						<a class="button button-primary" href="<?php echo esc_url( get_edit_post_link( $page->ID ) ); ?>">本文を編集</a>
						<a class="button" href="<?php echo esc_url( get_permalink( $page->ID ) ); ?>" target="_blank" rel="noopener">表示</a>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<p class="description" style="margin-top:12px;">※ 見た目の作り込み（工事の計画のカード等）はそのまま、文章だけを編集できます。本文を空にすると標準の説明文に戻ります。ページの削除・新規追加はできません（誤操作防止）。</p>
	</div>
	<?php
}
add_action( 'admin_menu', 'mrc_add_admin_pages' );

/* --- 通知先が未設定なら管理画面に警告バナーを出す --- */
function mrc_contact_settings_admin_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$missing = mrc_contact_settings_missing();
	if ( ! $missing ) {
		return;
	}
	$url = admin_url( 'admin.php?page=mrc-contact' );
	echo '<div class="notice notice-warning"><p><strong>ご意見の窓口の通知先が未設定です。</strong> 次の項目にメールアドレスを設定してください：'
		. esc_html( implode( '、', $missing ) )
		. ' &nbsp;<a href="' . esc_url( $url ) . '">通知先設定を開く</a></p></div>';
}
add_action( 'admin_notices', 'mrc_contact_settings_admin_notice' );

function mrc_render_contact_page() {
	$s      = mrc_get_contact_settings();
	$fields = mrc_contact_recipient_fields();
	?>
	<div class="wrap">
		<h1>ご意見の窓口 通知先設定</h1>
		<?php settings_errors( 'mrc_contact_settings' ); ?>
		<p>お問い合わせを「種別」ごとに、どのメールアドレスへ届けるかを設定します。<strong>全項目が必須です。空欄・不正な形式のままでは保存できません。</strong></p>
		<form method="post" action="options.php">
			<?php settings_fields( 'mrc_contact_group' ); ?>
			<table class="form-table"><tbody>
			<?php foreach ( $fields as $key => $label ) : ?>
				<tr>
					<th scope="row"><label for="mc-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?> <span style="color:#d63638;">*</span></label></th>
					<td>
						<input type="email" id="mc-<?php echo esc_attr( $key ); ?>" name="mrc_contact_settings[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $s[ $key ] ?? '' ); ?>" class="regular-text" placeholder="example@mrc-archi.com" required>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody></table>
			<p class="description">
				例：「工事のこと」＝施工会社、「計画・全体のこと」「その他」「ログイン前のお問い合わせ」＝株式会社MRC、「生活・管理のこと」＝管理会社／管理組合、のように振り分けできます。<br>
				工事会社が未定の段階では、いったん株式会社MRC宛（info@mrc-archi.com など）を入れておき、決定後にこの画面で差し替えてください。<br>
				<strong>雛形サイトで全項目を設定しておけば、NS Cloner で複製した新しい物件にも引き継がれます。</strong>
			</p>
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

/* --- 「はじめての方へ」FAQ 編集画面（行の追加・削除ができる） --- */
function mrc_render_first_faq_page() {
	$faqs  = mrc_get_first_faqs();
	$about = mrc_get_front_about();
	?>
	<div class="wrap">
		<h1>ログイン前トップ 編集</h1>
		<?php settings_errors( 'mrc_first_faqs' ); ?>
		<p>ログイン前トップ（未ログインの方が見るページ）に表示される文言を編集します。</p>
		<form method="post" action="options.php">
			<?php settings_fields( 'mrc_first_faq_group' ); ?>

			<h2 style="margin-top:24px;">サイトについて</h2>
			<p class="description">ヒーロー下の「サイトについて」に表示される文言です。空欄にすると標準の文言に戻ります。</p>
			<table class="form-table" style="max-width:760px;"><tbody>
				<tr>
					<th scope="row"><label for="fa-kicker">小見出し</label></th>
					<td><input type="text" id="fa-kicker" name="mrc_front_about[kicker]" value="<?php echo esc_attr( $about['kicker'] ); ?>" class="large-text"></td>
				</tr>
				<tr>
					<th scope="row"><label for="fa-heading">見出し</label></th>
					<td><input type="text" id="fa-heading" name="mrc_front_about[heading]" value="<?php echo esc_attr( $about['heading'] ); ?>" class="large-text"></td>
				</tr>
				<tr>
					<th scope="row"><label for="fa-body">説明文</label></th>
					<td><textarea id="fa-body" name="mrc_front_about[body]" rows="4" class="large-text"><?php echo esc_textarea( $about['body'] ); ?></textarea>
					<p class="description">改行できます。</p></td>
				</tr>
			</tbody></table>

			<h2 style="margin-top:32px;">はじめての方へ（よくある質問）</h2>
			<p class="description"><strong>行の追加・削除</strong>ができ、回答は改行できます。すべて削除して保存すると、この欄は非表示になります。</p>
			<div id="mrc-faq-rows">
				<?php foreach ( $faqs as $i => $row ) : ?>
					<div class="mrc-faq-row" style="border:1px solid #dcdcde;border-radius:6px;padding:12px 16px;margin:0 0 12px;max-width:760px;background:#fff;">
						<p style="margin-top:0;"><label><strong>質問</strong><br><input type="text" name="mrc_first_faqs[<?php echo (int) $i; ?>][q]" value="<?php echo esc_attr( $row['q'] ); ?>" class="large-text"></label></p>
						<p><label><strong>回答</strong><br><textarea name="mrc_first_faqs[<?php echo (int) $i; ?>][a]" rows="3" class="large-text"><?php echo esc_textarea( $row['a'] ); ?></textarea></label></p>
						<button type="button" class="button-link mrc-faq-remove" style="color:#b32d2e;">この項目を削除</button>
					</div>
				<?php endforeach; ?>
			</div>
			<p><button type="button" class="button" id="mrc-faq-add">＋ 項目を追加</button></p>
			<p class="description"><strong>雛形サイトで設定しておけば、NS Cloner で複製した新しい物件にも引き継がれます。</strong></p>
			<?php submit_button(); ?>
		</form>

		<template id="mrc-faq-template">
			<div class="mrc-faq-row" style="border:1px solid #dcdcde;border-radius:6px;padding:12px 16px;margin:0 0 12px;max-width:760px;background:#fff;">
				<p style="margin-top:0;"><label><strong>質問</strong><br><input type="text" name="mrc_first_faqs[__i__][q]" value="" class="large-text"></label></p>
				<p><label><strong>回答</strong><br><textarea name="mrc_first_faqs[__i__][a]" rows="3" class="large-text"></textarea></label></p>
				<button type="button" class="button-link mrc-faq-remove" style="color:#b32d2e;">この項目を削除</button>
			</div>
		</template>
		<script>
		( function () {
			var wrap = document.getElementById( 'mrc-faq-rows' );
			var tpl  = document.getElementById( 'mrc-faq-template' );
			var idx  = <?php echo (int) count( $faqs ); ?>;
			document.getElementById( 'mrc-faq-add' ).addEventListener( 'click', function () {
				var div = document.createElement( 'div' );
				div.innerHTML = tpl.innerHTML.replace( /__i__/g, idx++ ).trim();
				wrap.appendChild( div.firstChild );
			} );
			wrap.addEventListener( 'click', function ( e ) {
				if ( e.target.classList.contains( 'mrc-faq-remove' ) ) {
					var row = e.target.closest( '.mrc-faq-row' );
					if ( row ) { row.remove(); }
				}
			} );
		} )();
		</script>
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
	$to      = mrc_contact_recipient_for( $type );
	$types   = mrc_contact_types();
	$label   = isset( $types[ $type ] ) ? $types[ $type ] : 'その他';
	$subject = '【' . get_bloginfo( 'name' ) . '】ご意見の窓口: ' . $label;
	$lines   = array(
		'種別: ' . $label,
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

