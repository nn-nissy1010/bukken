<?php
/**
 * Plugin Name: MRC Dev Mail (Mailpit)
 * Description: ローカル開発専用。全メールを Mailpit（host.docker.internal:1025）へ送り、
 *              管理画面 http://localhost:8025 で受信内容を確認できるようにする。
 *              本番には配布しない（wp-env の mappings 経由でのみ読み込まれる mu-plugin）。
 *
 * @package mrc-residents-dev
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 送信元が example.com 等でも Mailpit は受信するので、SMTP に切り替えるだけでよい。
 * 認証・TLS なしのローカル SMTP（Mailpit）へ流し込む。
 */
add_action(
	'phpmailer_init',
	function ( $mailer ) {
		$mailer->isSMTP();
		$mailer->Host        = 'host.docker.internal';
		$mailer->Port        = 1025;
		$mailer->SMTPAuth    = false;
		$mailer->SMTPAutoTLS = false;
		$mailer->SMTPSecure  = '';
	}
);

/**
 * ローカルの既定 From は wordpress@localhost（TLD なし）で PHPMailer が
 * 無効アドレスと判定し送信に失敗する。SMTP 検証を通る有効な From に補正する。
 * 実サーバーでは wordpress@（実ドメイン）になるため、この補正はローカル専用でよい。
 */
add_filter( 'wp_mail_from', function ( $from ) {
	return ( ! $from || str_ends_with( $from, '@localhost' ) ) ? 'no-reply@example.com' : $from;
} );
