<?php
/**
 * 動画一覧（アーカイブ）
 *
 * @package mrc-residents
 */

get_header();
?>

<div class="container container--narrow">
	<nav class="breadcrumb" aria-label="パンくずリスト">
		<ol>
			<li><a href="<?php echo esc_url( home_url( '/member/' ) ); ?>">会員トップ</a></li>
			<li aria-current="page">動画アーカイブ</li>
		</ol>
	</nav>
</div>

<main>
	<section class="section" style="padding-top:8px;">
		<div class="container container--narrow">
			<div class="page-intro">
				<h1>動画アーカイブ</h1>
			</div>

			<div class="video-grid">
				<?php if ( have_posts() ) : ?>
					<?php
					while ( have_posts() ) :
						the_post();
						?>
						<?php $thumb = mrc_video_thumb_url(); ?>
						<a class="video-card" href="<?php the_permalink(); ?>">
							<div class="video-thumb<?php echo $thumb ? ' video-thumb--img' : ''; ?>"<?php echo $thumb ? ' style="background-image:url(\'' . esc_url( $thumb ) . '\')"' : ''; ?> aria-hidden="true"></div>
							<div class="video-body">
								<p class="video-title"><?php the_title(); ?></p>
								<p class="video-date"><?php echo esc_html( get_the_date( 'Y.m.d' ) ); ?></p>
							</div>
						</a>
					<?php endwhile; ?>
				<?php else : ?>
					<p>動画はまだありません。</p>
				<?php endif; ?>
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
