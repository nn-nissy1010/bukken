<?php
/**
 * Q&A 詳細（単一の質問と回答）
 *
 * @package mrc-residents
 */

get_header();

while ( have_posts() ) :
	the_post();
	?>

	<div class="container container--narrow">
		<nav class="breadcrumb" aria-label="パンくずリスト">
			<ol>
				<li><a href="<?php echo esc_url( home_url( '/member/' ) ); ?>">会員トップ</a></li>
				<li><a href="<?php echo esc_url( get_post_type_archive_link( 'qa' ) ); ?>">Q&amp;A</a></li>
				<li aria-current="page"><?php the_title(); ?></li>
			</ol>
		</nav>
	</div>

	<main>
		<article class="section" style="padding-top:8px;">
			<div class="container container--narrow">
				<header class="page-intro">
					<h1>Q. <?php the_title(); ?></h1>
				</header>
				<div class="card card--pad-lg entry-content" style="font-size:16px;line-height:1.9;">
					<?php the_content(); ?>
				</div>
				<p style="margin-top:32px;"><a href="<?php echo esc_url( get_post_type_archive_link( 'qa' ) ); ?>" class="cta-link">Q&amp;A一覧へ戻る</a></p>
			</div>
		</article>
	</main>

	<?php
endwhile;

get_footer();
