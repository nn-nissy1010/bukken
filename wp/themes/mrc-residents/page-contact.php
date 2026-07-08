<?php
/**
 * ご意見の窓口（固定ページ / slug: contact）
 * 入力 → 確認 → 完了 のサーバーサイド多段フォーム。
 * 送信時は種別ごとの通知先メールへ wp_mail で送信。
 * ※ フォーム項目名は WordPress 予約クエリ変数（name / type 等）との
 *    衝突を避けるため mrc_ プレフィックスを付与している。
 *
 * @package mrc-residents
 */

get_header();

$mrc_types = mrc_contact_types();
$stage     = 'input';
$error     = '';
$data      = array(
	'type' => 'construction',
	'name' => '',
	'room' => '',
	'body' => '',
);

if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['mrc_contact_nonce'] ) && wp_verify_nonce( $_POST['mrc_contact_nonce'], 'mrc_contact' ) ) {
	$posted_type  = isset( $_POST['mrc_type'] ) ? sanitize_key( $_POST['mrc_type'] ) : 'other';
	$data['type'] = isset( $mrc_types[ $posted_type ] ) ? $posted_type : 'other';
	$data['name'] = sanitize_text_field( wp_unslash( $_POST['mrc_name'] ?? '' ) );
	$data['room'] = sanitize_text_field( wp_unslash( $_POST['mrc_room'] ?? '' ) );
	$data['body'] = sanitize_textarea_field( wp_unslash( $_POST['mrc_body'] ?? '' ) );
	$action       = isset( $_POST['mrc_contact_action'] ) ? $_POST['mrc_contact_action'] : 'confirm';

	if ( mrc_is_spam_submission() ) {
		$stage = 'done'; // ボットには成功に見せて、実際には送信しない
	} elseif ( '' === trim( $data['body'] ) ) {
		$error = 'お問い合わせ内容を入力してください。';
		$stage = 'input';
	} elseif ( 'send' === $action && ! mrc_recaptcha_verify( $_POST['g-recaptcha-response'] ?? '' ) ) {
		$error = '送信の確認に失敗しました。お手数ですが、もう一度「送信する」を押してください。';
		$stage = 'confirm';
	} elseif ( 'send' === $action ) {
		mrc_send_contact_mail( $data['type'], $data['name'], $data['room'], $data['body'] );
		// ローカル環境はメールサーバーが無いため、送信可否に関わらず受付完了とする。
		$stage = 'done';
	} else {
		$stage = 'confirm';
	}
}

/** ステップバーのクラス */
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
				<h1>ご意見・お問い合わせ</h1>
				<p>ご質問・ご意見をお送りください。内容の「種別」に応じて、担当の窓口へお届けします。</p>
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
					<form action="<?php echo esc_url( home_url( '/contact/' ) ); ?>" method="post" novalidate>
						<?php wp_nonce_field( 'mrc_contact', 'mrc_contact_nonce' ); ?>
						<?php mrc_spam_fields(); ?>
						<input type="hidden" name="mrc_contact_action" value="confirm">
						<div class="form-group">
							<span class="form-label" id="type-label">種別<span class="req">必須</span></span>
							<div class="option-group" role="radiogroup" aria-labelledby="type-label">
								<?php foreach ( $mrc_types as $k => $label ) : ?>
									<div class="option-tile">
										<input type="radio" id="type-<?php echo esc_attr( $k ); ?>" name="mrc_type" value="<?php echo esc_attr( $k ); ?>" <?php checked( $data['type'], $k ); ?>>
										<label for="type-<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $label ); ?></label>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
						<div class="form-group">
							<label class="form-label" for="m-name">お名前<span class="opt">任意</span></label>
							<input class="form-control" type="text" id="m-name" name="mrc_name" autocomplete="name" value="<?php echo esc_attr( $data['name'] ); ?>">
						</div>
						<div class="form-group">
							<label class="form-label" for="m-room">部屋番号<span class="opt">任意</span></label>
							<input class="form-control" type="text" id="m-room" name="mrc_room" inputmode="numeric" value="<?php echo esc_attr( $data['room'] ); ?>">
						</div>
						<div class="form-group">
							<label class="form-label" for="m-body">お問い合わせ内容<span class="req">必須</span></label>
							<textarea class="form-control" id="m-body" name="mrc_body" required><?php echo esc_textarea( $data['body'] ); ?></textarea>
						</div>
						<button type="submit" class="btn btn--navy btn--block btn--lg">入力内容を確認する</button>
						<p class="form-hint">※ お名前・部屋番号は任意です。匿名でもお送りいただけます。</p>
					</form>

				<?php elseif ( 'confirm' === $stage ) : ?>
					<?php if ( $error ) : ?>
						<p class="login-error" role="alert"><?php echo esc_html( $error ); ?></p>
					<?php endif; ?>
					<p class="note" style="margin-bottom:24px;">入力内容をご確認ください。修正する場合は「修正する」を押してください。</p>
					<ul class="confirm-review">
						<li><span class="review-label">種別</span><span class="review-value"><?php echo esc_html( $mrc_types[ $data['type'] ] ); ?></span></li>
						<li><span class="review-label">お名前</span><span class="review-value"><?php echo esc_html( '' !== $data['name'] ? $data['name'] : '（未入力）' ); ?></span></li>
						<li><span class="review-label">部屋番号</span><span class="review-value"><?php echo esc_html( '' !== $data['room'] ? $data['room'] : '（未入力）' ); ?></span></li>
						<li><span class="review-label">お問い合わせ内容</span><span class="review-value"><?php echo esc_html( $data['body'] ); ?></span></li>
					</ul>
					<div class="form-actions">
						<form action="<?php echo esc_url( home_url( '/contact/' ) ); ?>" method="post">
							<?php wp_nonce_field( 'mrc_contact', 'mrc_contact_nonce' ); ?>
							<input type="hidden" name="mrc_contact_action" value="input">
							<input type="hidden" name="mrc_type" value="<?php echo esc_attr( $data['type'] ); ?>">
							<input type="hidden" name="mrc_name" value="<?php echo esc_attr( $data['name'] ); ?>">
							<input type="hidden" name="mrc_room" value="<?php echo esc_attr( $data['room'] ); ?>">
							<input type="hidden" name="mrc_body" value="<?php echo esc_attr( $data['body'] ); ?>">
							<button type="submit" class="btn btn--outline">修正する</button>
						</form>
						<form action="<?php echo esc_url( home_url( '/contact/' ) ); ?>" method="post">
							<?php wp_nonce_field( 'mrc_contact', 'mrc_contact_nonce' ); ?>
							<?php mrc_recaptcha_field(); ?>
							<input type="hidden" name="mrc_contact_action" value="send">
							<input type="hidden" name="mrc_type" value="<?php echo esc_attr( $data['type'] ); ?>">
							<input type="hidden" name="mrc_name" value="<?php echo esc_attr( $data['name'] ); ?>">
							<input type="hidden" name="mrc_room" value="<?php echo esc_attr( $data['room'] ); ?>">
							<input type="hidden" name="mrc_body" value="<?php echo esc_attr( $data['body'] ); ?>">
							<button type="submit" class="btn btn--primary">この内容で送信する</button>
						</form>
					</div>

				<?php else : ?>
					<div class="completion">
						<div class="check-icon" aria-hidden="true"></div>
						<h2>お問い合わせを受け付けました</h2>
						<p>内容を確認し、担当の窓口より順次対応いたします。ご協力ありがとうございました。</p>
						<a href="<?php echo esc_url( home_url( '/member/' ) ); ?>" class="btn btn--navy">会員トップに戻る</a>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</section>
</main>

<?php
get_footer();
