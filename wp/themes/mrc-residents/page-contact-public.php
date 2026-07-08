<?php
/**
 * ログイン前 お問い合わせ（公開 / slug: contact-public）
 * ログイン・IDに関する簡易フォーム（種別なし・運営宛て）。
 * 入力 → 確認 → 完了 のサーバーサイド多段フォーム。
 * ※ 会員用「ご意見の窓口」(page-contact.php) とは別系統・別テンプレート。
 *
 * @package mrc-residents
 */

get_header();

$stage = 'input';
$error = '';
$data  = array( 'name' => '', 'room' => '', 'contact' => '', 'body' => '' );

if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['mrc_pub_nonce'] ) && wp_verify_nonce( $_POST['mrc_pub_nonce'], 'mrc_pub_contact' ) ) {
	$data['name']    = sanitize_text_field( wp_unslash( $_POST['mrc_pub_name'] ?? '' ) );
	$data['room']    = sanitize_text_field( wp_unslash( $_POST['mrc_pub_room'] ?? '' ) );
	$data['contact'] = sanitize_text_field( wp_unslash( $_POST['mrc_pub_contact'] ?? '' ) );
	$data['body']    = sanitize_textarea_field( wp_unslash( $_POST['mrc_pub_body'] ?? '' ) );
	$action          = isset( $_POST['mrc_pub_action'] ) ? $_POST['mrc_pub_action'] : 'confirm';

	if ( mrc_is_spam_submission() ) {
		$stage = 'done'; // ボットには成功に見せて、実際には送信しない
	} elseif ( '' === trim( $data['body'] ) ) {
		$error = 'お問い合わせ内容を入力してください。';
		$stage = 'input';
	} elseif ( 'send' === $action && ! mrc_recaptcha_verify( $_POST['g-recaptcha-response'] ?? '' ) ) {
		$error = '送信の確認に失敗しました。お手数ですが、もう一度「送信する」を押してください。';
		$stage = 'confirm';
	} elseif ( 'send' === $action ) {
		$to      = get_option( 'admin_email' );
		$subject = '【' . get_bloginfo( 'name' ) . '】ログイン前お問い合わせ';
		$lines   = array(
			'お名前: ' . ( '' !== $data['name'] ? $data['name'] : '（未入力）' ),
			'部屋番号: ' . ( '' !== $data['room'] ? $data['room'] : '（未入力）' ),
			'ご連絡先: ' . ( '' !== $data['contact'] ? $data['contact'] : '（未入力）' ),
			'',
			'お問い合わせ内容:',
			$data['body'],
			'',
			'---',
			'ログイン前のお問い合わせフォームから自動送信されています。',
		);
		wp_mail( $to, $subject, implode( "\n", $lines ) );
		$stage = 'done';
	} else {
		$stage = 'confirm';
	}
}

$step_state = array(
	'input'   => array( 'input' => 'is-active', 'confirm' => '', 'done' => '' ),
	'confirm' => array( 'input' => 'is-done', 'confirm' => 'is-active', 'done' => '' ),
	'done'    => array( 'input' => 'is-done', 'confirm' => 'is-done', 'done' => 'is-active' ),
);
$st = $step_state[ $stage ];
?>

<main>
	<section class="section">
		<div class="container container--narrow">
			<div class="page-intro">
				<h1>お問い合わせ</h1>
				<p>ログイン・IDについてのお問い合わせ窓口です。工事や計画に関するご質問は、ログイン後の「ご意見の窓口」からお願いします。</p>
			</div>

			<div class="panel card--pad-lg">
				<ol class="stepbar">
					<li class="<?php echo esc_attr( $st['input'] ); ?>"><span class="step-num">1</span>入力</li>
					<li class="<?php echo esc_attr( $st['confirm'] ); ?>"><span class="step-num">2</span>確認</li>
					<li class="<?php echo esc_attr( $st['done'] ); ?>"><span class="step-num">3</span>完了</li>
				</ol>

				<?php if ( 'input' === $stage ) : ?>
					<?php if ( $error ) : ?>
						<p class="login-error" role="alert"><?php echo esc_html( $error ); ?></p>
					<?php endif; ?>
					<form action="<?php echo esc_url( home_url( '/contact-public/' ) ); ?>" method="post" novalidate>
						<?php wp_nonce_field( 'mrc_pub_contact', 'mrc_pub_nonce' ); ?>
						<?php mrc_spam_fields(); ?>
						<input type="hidden" name="mrc_pub_action" value="confirm">
						<div class="form-group">
							<label class="form-label" for="p-name">お名前<span class="opt">任意</span></label>
							<input class="form-control" type="text" id="p-name" name="mrc_pub_name" autocomplete="name" value="<?php echo esc_attr( $data['name'] ); ?>">
						</div>
						<div class="form-group">
							<label class="form-label" for="p-room">部屋番号<span class="opt">任意</span></label>
							<input class="form-control" type="text" id="p-room" name="mrc_pub_room" inputmode="numeric" value="<?php echo esc_attr( $data['room'] ); ?>">
						</div>
						<div class="form-group">
							<label class="form-label" for="p-contact">ご連絡先（メールなど）<span class="opt">任意</span></label>
							<input class="form-control" type="text" id="p-contact" name="mrc_pub_contact" value="<?php echo esc_attr( $data['contact'] ); ?>">
						</div>
						<div class="form-group">
							<label class="form-label" for="p-body">お問い合わせ内容<span class="req">必須</span></label>
							<textarea class="form-control" id="p-body" name="mrc_pub_body" required><?php echo esc_textarea( $data['body'] ); ?></textarea>
						</div>
						<button type="submit" class="btn btn--navy btn--block btn--lg">入力内容を確認する</button>
					</form>

				<?php elseif ( 'confirm' === $stage ) : ?>
					<?php if ( $error ) : ?>
						<p class="login-error" role="alert"><?php echo esc_html( $error ); ?></p>
					<?php endif; ?>
					<p class="note" style="margin-bottom:24px;">入力内容をご確認ください。修正する場合は「修正する」を押してください。</p>
					<ul class="confirm-review">
						<li><span class="review-label">お名前</span><span class="review-value"><?php echo esc_html( '' !== $data['name'] ? $data['name'] : '（未入力）' ); ?></span></li>
						<li><span class="review-label">部屋番号</span><span class="review-value"><?php echo esc_html( '' !== $data['room'] ? $data['room'] : '（未入力）' ); ?></span></li>
						<li><span class="review-label">ご連絡先</span><span class="review-value"><?php echo esc_html( '' !== $data['contact'] ? $data['contact'] : '（未入力）' ); ?></span></li>
						<li><span class="review-label">お問い合わせ内容</span><span class="review-value"><?php echo esc_html( $data['body'] ); ?></span></li>
					</ul>
					<div class="form-actions">
						<form action="<?php echo esc_url( home_url( '/contact-public/' ) ); ?>" method="post">
							<?php wp_nonce_field( 'mrc_pub_contact', 'mrc_pub_nonce' ); ?>
							<input type="hidden" name="mrc_pub_action" value="input">
							<input type="hidden" name="mrc_pub_name" value="<?php echo esc_attr( $data['name'] ); ?>">
							<input type="hidden" name="mrc_pub_room" value="<?php echo esc_attr( $data['room'] ); ?>">
							<input type="hidden" name="mrc_pub_contact" value="<?php echo esc_attr( $data['contact'] ); ?>">
							<input type="hidden" name="mrc_pub_body" value="<?php echo esc_attr( $data['body'] ); ?>">
							<button type="submit" class="btn btn--outline">修正する</button>
						</form>
						<form action="<?php echo esc_url( home_url( '/contact-public/' ) ); ?>" method="post">
							<?php wp_nonce_field( 'mrc_pub_contact', 'mrc_pub_nonce' ); ?>
							<?php mrc_recaptcha_field(); ?>
							<input type="hidden" name="mrc_pub_action" value="send">
							<input type="hidden" name="mrc_pub_name" value="<?php echo esc_attr( $data['name'] ); ?>">
							<input type="hidden" name="mrc_pub_room" value="<?php echo esc_attr( $data['room'] ); ?>">
							<input type="hidden" name="mrc_pub_contact" value="<?php echo esc_attr( $data['contact'] ); ?>">
							<input type="hidden" name="mrc_pub_body" value="<?php echo esc_attr( $data['body'] ); ?>">
							<button type="submit" class="btn btn--primary">この内容で送信する</button>
						</form>
					</div>

				<?php else : ?>
					<div class="completion">
						<div class="check-icon" aria-hidden="true"></div>
						<h2>お問い合わせを受け付けました</h2>
						<p>内容を確認し、担当より順次ご連絡いたします。ご協力ありがとうございました。</p>
						<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn btn--navy">トップに戻る</a>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</section>
</main>

<?php
get_footer();
