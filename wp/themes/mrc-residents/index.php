<?php
/**
 * 汎用テンプレート（フォールバック）
 *
 * @package mrc-residents
 */

get_header();
?>

<main>
	<section class="section">
		<div class="container container--narrow">
			<?php if ( have_posts() ) : ?>
				<div class="page-intro">
					<h1><?php echo esc_html( wp_get_document_title() ); ?></h1>
				</div>
				<div class="card" style="padding:8px 16px;">
					<ul class="post-list">
						<?php
						while ( have_posts() ) :
							the_post();
							?>
							<li class="post-item">
								<a href="<?php the_permalink(); ?>">
									<span class="post-date"><?php echo esc_html( get_the_date() ); ?></span>
									<span class="post-title"><?php the_title(); ?></span>
								</a>
							</li>
						<?php endwhile; ?>
					</ul>
				</div>
				<?php the_posts_pagination( array( 'mid_size' => 2 ) ); ?>
			<?php else : ?>
				<div class="page-intro">
					<h1>コンテンツがありません</h1>
					<p>まだ投稿がありません。</p>
				</div>
			<?php endif; ?>
		</div>
	</section>
</main>

<?php
get_footer();
