<?php
/**
 * Plugin Name: MRC SMTP (production mail)
 * Description: wp-config の MRC_SMTP_* / MRC_MAIL_* 定数があれば、問い合わせメールを
 *              外部SMTP（SendGrid / Amazon SES / Mailgun / 契約メールサーバー等）で送る。
 *              定数が未定義なら何もしない（＝ローカルや未設定環境では従来動作）。
 *
 * 配置先：本番サーバーの wp-content/mu-plugins/mrc-smtp.php
 *   ※ mu-plugins 直下の .php は WordPress が自動で読み込む（有効化操作は不要）。
 *   ※ 認証情報はこのファイルに書かず、必ず wp-config.php の定数で渡すこと（Git に載せない）。
 *
 * @package mrc-residents-ops
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// SMTP ホスト定数が無ければ、このファイルは何もしない。
if ( ! defined( 'MRC_SMTP_HOST' ) || '' === MRC_SMTP_HOST ) {
	return;
}

add_action(
	'phpmailer_init',
	function ( $mailer ) {
		$mailer->isSMTP();
		$mailer->Host = MRC_SMTP_HOST;
		$mailer->Port = defined( 'MRC_SMTP_PORT' ) ? (int) MRC_SMTP_PORT : 587;

		if ( defined( 'MRC_SMTP_USER' ) && '' !== MRC_SMTP_USER ) {
			$mailer->SMTPAuth = true;
			$mailer->Username = MRC_SMTP_USER;
			$mailer->Password = defined( 'MRC_SMTP_PASS' ) ? MRC_SMTP_PASS : '';
		}

		// '' | 'tls' | 'ssl'
		$secure              = defined( 'MRC_SMTP_SECURE' ) ? MRC_SMTP_SECURE : 'tls';
		$mailer->SMTPSecure  = $secure;
		$mailer->SMTPAutoTLS = ( '' !== $secure );
	}
);

// 送信元（From）。実ドメインの有効なアドレスにして到達性を上げる。
if ( defined( 'MRC_MAIL_FROM' ) && '' !== MRC_MAIL_FROM ) {
	add_filter(
		'wp_mail_from',
		function ( $from ) {
			return MRC_MAIL_FROM;
		},
		99
	);
}
if ( defined( 'MRC_MAIL_FROM_NAME' ) && '' !== MRC_MAIL_FROM_NAME ) {
	add_filter(
		'wp_mail_from_name',
		function ( $name ) {
			return MRC_MAIL_FROM_NAME;
		},
		99
	);
}
