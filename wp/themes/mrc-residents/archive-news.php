<?php
/**
 * お知らせ一覧（アーカイブ）
 *
 * @package mrc-residents
 */

get_header();

if ( ! function_exists( 'mrc_news_badge_class' ) ) {
	function mrc_news_badge_class( $slug ) {
		$map = array(
			'news'     => 'badge--news',
			'schedule' => 'badge--schedule',
			'plan'     => 'badge--plan',
		);
		return isset( $map[ $slug ] ) ? $map[ $slug ] : 'badge--news';
	}
}
?>

<div class="container container--narrow">
	<nav class="breadcrumb" aria-label="パンくずリスト">
		<ol>
			<li><a href="<?php echo esc_url( home_url( '/member/' ) ); ?>">会員トップ</a></li>
			<li aria-current="page">お知らせ一覧</li>
		</ol>
	</nav>
</div>

<main>
	<section class="section" style="padding-top:8px;">
		<div class="container container--narrow">
			<div class="page-intro">
				<h1>お知らせ一覧</h1>
			</div>

			<?php $news_terms = get_terms( array( 'taxonomy' => 'news_category', 'hide_empty' => false ) ); ?>
			<div class="chip-filter" data-filter-group data-filter-target="#news-archive" style="margin-bottom:20px;" role="tablist" aria-label="カテゴリ絞り込み">
				<button class="chip is-active" data-filter="all" aria-pressed="true">すべて</button>
				<?php if ( ! is_wp_error( $news_terms ) ) : ?>
					<?php foreach ( $news_terms as $term ) : ?>
						<button class="chip" data-filter="<?php echo esc_attr( $term->slug ); ?>" aria-pressed="false"><?php echo esc_html( $term->name ); ?></button>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>

			<div class="card" style="padding:8px 16px;">
				<ul class="post-list" id="news-archive">
					<?php if ( have_posts() ) : ?>
						<?php
						while ( have_posts() ) :
							the_post();
							$terms = get_the_terms( get_the_ID(), 'news_category' );
							$term  = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0] : null;
							?>
							<li class="post-item" data-filter-item data-category="<?php echo esc_attr( $term ? $term->slug : '' ); ?>">
								<a href="<?php the_permalink(); ?>">
									<span class="post-date"><?php echo esc_html( get_the_date( 'Y.m.d' ) ); ?></span>
									<?php if ( $term ) : ?>
										<span class="badge <?php echo esc_attr( mrc_news_badge_class( $term->slug ) ); ?>"><?php echo esc_html( $term->name ); ?></span>
									<?php endif; ?>
									<span class="post-title"><?php the_title(); ?></span>
								</a>
							</li>
						<?php endwhile; ?>
					<?php else : ?>
						<li class="post-item"><a href="#"><span class="post-title">お知らせはまだありません。</span></a></li>
					<?php endif; ?>
				</ul>
			</div>

			<?php
			$links = paginate_links( array( 'type' => 'array', 'prev_text' => '‹', 'next_text' => '›', 'mid_size' => 2 ) );
			if ( $links ) {
				echo '<nav class="pagination" aria-label="ページ送り">';
				foreach ( $links as $link ) {
					echo wp_kses_post( $link );
				}
				echo '</nav>';
			}
			?>
		</div>
	</section>
</main>

<?php
get_footer();
