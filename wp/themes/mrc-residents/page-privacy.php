<?php
/**
 * プライバシーポリシー（全物件共通・仮想ページ /privacy/）
 * 本文はネットワーク共通設定（site_option: mrc_privacy_policy）から取得する。
 * 公開ページ・会員ページのどちらのフッターからもアクセスできる。
 *
 * @package mrc-residents
 */

get_header();

$mrc_privacy_content = function_exists( 'mrc_get_privacy_policy' ) ? mrc_get_privacy_policy() : '';
?>

<div class="container container--narrow">
	<nav class="breadcrumb" aria-label="パンくずリスト">
		<ol>
			<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">ホーム</a></li>
			<li aria-current="page">プライバシーポリシー</li>
		</ol>
	</nav>
</div>

<main>
	<article class="section" style="padding-top:8px;">
		<div class="container container--narrow">
			<div class="page-intro">
				<h1>プライバシーポリシー</h1>
				<p class="lead"><?php bloginfo( 'name' ); ?>における個人情報の取り扱いについてご案内します。</p>
			</div>

			<div class="entry-content" style="font-size:16px; line-height:1.9;">
				<?php echo wp_kses_post( $mrc_privacy_content ); ?>
			</div>

			<p style="margin-top:32px;"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="cta-link">ホームへ戻る</a></p>
		</div>
	</article>
</main>

<?php
get_footer();
