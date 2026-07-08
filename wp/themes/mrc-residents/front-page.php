<?php
/**
 * ログイン前トップ（公開ゾーン）
 *
 * @package mrc-residents
 */

get_header();

// ルート（メインサイト）は「雛形／受付」。住民向けログインは表示しない。
if ( is_main_site() ) :
	?>
	<main>
		<section class="section">
			<div class="container container--narrow" style="padding-top:32px;">
				<div class="cta-band">
					<h2>居住者専用サイト ― 雛形（見本）</h2>
					<p>この画面は各物件サイトの<strong>複製元（雛形）</strong>です。実際の居住者向けサイトは物件ごとの個別URLでご利用ください。</p>
					<?php if ( current_user_can( 'manage_network' ) ) : ?>
						<a href="<?php echo esc_url( network_admin_url() ); ?>" class="btn btn--primary">ネットワーク管理へ</a>
					<?php endif; ?>
				</div>
				<?php if ( current_user_can( 'manage_network' ) ) : ?>
					<div class="card card--pad-lg" style="margin-top:24px;">
						<div class="section-heading" style="margin-bottom:12px;"><h2 style="font-size:20px;">物件サイト一覧（管理者向け）</h2></div>
						<ul class="post-list">
							<?php
							$sites = get_sites( array( 'number' => 50 ) );
							foreach ( $sites as $site ) :
								if ( (int) $site->blog_id === (int) get_main_site_id() ) {
									continue;
								}
								switch_to_blog( $site->blog_id );
								$name = get_bloginfo( 'name' );
								$home = home_url( '/' );
								restore_current_blog();
								?>
								<li class="post-item"><a href="<?php echo esc_url( $home ); ?>">
									<span class="post-title"><?php echo esc_html( $name ); ?></span>
									<span class="post-date" style="min-width:auto;"><?php echo esc_html( untrailingslashit( str_replace( home_url(), '', $home ) ) . '/' ); ?></span>
								</a></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>
			</div>
		</section>
	</main>
	<?php
	get_footer();
	return;
endif;
?>

<main>
	<!-- ヒーロー：メインビジュアル + ログインボックス -->
	<section class="hero" id="login">
		<div class="hero__inner">
			<div class="hero__visual" style="background-image:url('<?php echo esc_url( mrc_hero_image_url() ); ?>')" role="img" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?> メインビジュアル"></div>

			<div class="login-box">
				<h2>居住者専用ログイン</h2>
				<p class="login-sub">配布されたID・パスワードでログインしてください。</p>
				<?php if ( ! empty( $GLOBALS['mrc_login_error'] ) ) : ?>
					<p class="login-error" role="alert"><?php echo esc_html( $GLOBALS['mrc_login_error'] ); ?></p>
				<?php endif; ?>
				<form action="<?php echo esc_url( home_url( '/' ) ); ?>" method="post">
					<?php wp_nonce_field( 'mrc_login', 'mrc_login_nonce' ); ?>
					<input type="hidden" name="mrc_login" value="1">
					<div class="form-group">
						<label class="form-label" for="user_login">ID</label>
						<input class="form-control" type="text" id="user_login" name="log" autocomplete="username" value="<?php echo isset( $_POST['log'] ) ? esc_attr( wp_unslash( $_POST['log'] ) ) : ''; ?>">
					</div>
					<div class="form-group">
						<label class="form-label" for="user_pass">パスワード</label>
						<input class="form-control" type="password" id="user_pass" name="pwd" autocomplete="current-password">
					</div>
					<button type="submit" class="btn btn--primary btn--block">ログイン</button>
				</form>
				<p class="form-hint">ID・パスワードは、書面（またはQRコード）でお配りしています。</p>
			</div>
		</div>
	</section>

	<!-- サイトについて（キャッチコピー＋説明） -->
	<section class="section" id="about">
		<div class="container container--narrow">
			<div class="page-intro" style="margin-bottom:0;">
				<p class="about-kicker">居住者専用ポータル</p>
				<h1>大規模修繕工事の計画状況を、いつでもご確認いただけます</h1>
				<p>このサイトは、<?php bloginfo( 'name' ); ?>にお住まいの皆さまへ、大規模修繕工事の計画に関するお知らせ・スケジュール・資料などをお届けする、居住者専用のサイトです。掲示板を見に行かなくても、スマートフォンやパソコンからいつでもご確認いただけます。</p>
			</div>
		</div>
	</section>

	<!-- はじめての方へ（アコーディオン） -->
	<section class="section section--alt" id="first">
		<div class="container container--narrow">
			<div class="section-heading">
				<h2>はじめての方へ</h2>
			</div>

			<?php
			$faqs = array(
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
			foreach ( $faqs as $i => $faq ) :
				$open  = ( 0 === $i );
				$panel = 'faq-first-' . ( $i + 1 );
				?>
				<div class="accordion">
					<h3>
						<button class="accordion__trigger" data-accordion-trigger aria-expanded="<?php echo $open ? 'true' : 'false'; ?>" aria-controls="<?php echo esc_attr( $panel ); ?>">
							<?php echo esc_html( $faq['q'] ); ?>
							<span class="accordion__icon" aria-hidden="true"></span>
						</button>
					</h3>
					<div class="accordion__panel" id="<?php echo esc_attr( $panel ); ?>" role="region">
						<div class="accordion__panel-inner">
							<p><?php echo esc_html( $faq['a'] ); ?></p>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</section>

	<!-- お問い合わせCTA -->
	<section class="section section--home-cta">
		<div class="container container--narrow">
			<div class="cta-band">
				<h2>ログイン・IDについてのお問い合わせ</h2>
				<p>ログインやIDについてのお問い合わせはこちら。<br>工事内容など計画に関するご質問は、ログイン後の「ご意見の窓口」からお願いします。</p>
				<a href="<?php echo esc_url( home_url( '/contact-public/' ) ); ?>" class="btn btn--primary btn--lg">お問い合わせフォームへ</a>
			</div>
		</div>
	</section>
</main>

<?php
get_footer();
