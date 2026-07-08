<?php
/**
 * お知らせ 詳細（単一記事）
 *
 * @package mrc-residents
 */

get_header();

if ( ! function_exists( 'mrc_news_badge_class' ) ) {
	function mrc_news_badge_class( $slug ) {
		$map = array( 'news' => 'badge--news', 'schedule' => 'badge--schedule', 'plan' => 'badge--plan' );
		return isset( $map[ $slug ] ) ? $map[ $slug ] : 'badge--news';
	}
}

while ( have_posts() ) :
	the_post();
	$terms = get_the_terms( get_the_ID(), 'news_category' );
	$term  = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0] : null;
	?>

	<div class="container container--narrow">
		<nav class="breadcrumb" aria-label="パンくずリスト">
			<ol>
				<li><a href="<?php echo esc_url( home_url( '/member/' ) ); ?>">会員トップ</a></li>
				<li><a href="<?php echo esc_url( get_post_type_archive_link( 'news' ) ); ?>">お知らせ</a></li>
				<li aria-current="page"><?php the_title(); ?></li>
			</ol>
		</nav>
	</div>

	<main>
		<article class="section" style="padding-top:8px;">
			<div class="container container--narrow">
				<header class="page-intro">
					<p style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
						<span class="post-date" style="min-width:auto;"><?php echo esc_html( get_the_date( 'Y.m.d' ) ); ?></span>
						<?php if ( $term ) : ?>
							<span class="badge <?php echo esc_attr( mrc_news_badge_class( $term->slug ) ); ?>"><?php echo esc_html( $term->name ); ?></span>
						<?php endif; ?>
					</p>
					<h1><?php the_title(); ?></h1>
				</header>

				<?php if ( has_post_thumbnail() ) : ?>
					<figure style="margin:0 0 24px;border-radius:var(--radius-lg);overflow:hidden;">
						<?php the_post_thumbnail( 'large', array( 'style' => 'width:100%;height:auto;display:block;' ) ); ?>
					</figure>
				<?php endif; ?>

				<div class="entry-content" style="font-size:16px;line-height:1.9;">
					<?php the_content(); ?>
				</div>

				<p style="margin-top:40px;"><a href="<?php echo esc_url( get_post_type_archive_link( 'news' ) ); ?>" class="cta-link">お知らせ一覧へ戻る</a></p>
			</div>
		</article>
	</main>

	<?php
endwhile;

get_footer();
