<?php
/**
 * Q&A（アーカイブ・アコーディオン）
 *
 * @package mrc-residents
 */

get_header();
?>

<div class="container container--narrow">
	<nav class="breadcrumb" aria-label="パンくずリスト">
		<ol>
			<li><a href="<?php echo esc_url( home_url( '/member/' ) ); ?>">会員トップ</a></li>
			<li aria-current="page">Q&amp;A</li>
		</ol>
	</nav>
</div>

<main>
	<section class="section" style="padding-top:8px;">
		<div class="container container--narrow">
			<div class="page-intro">
				<h1>よくあるご質問（Q&amp;A）</h1>
				<p>計画期間中によくいただくご質問をまとめました。解決しない場合は「ご意見の窓口」からお問い合わせください。</p>
			</div>

			<?php if ( have_posts() ) : ?>
				<?php
				$i = 0;
				while ( have_posts() ) :
					the_post();
					$i++;
					$panel = 'qa-' . get_the_ID();
					?>
					<div class="accordion">
						<h2>
							<button class="accordion__trigger" data-accordion-trigger aria-expanded="false" aria-controls="<?php echo esc_attr( $panel ); ?>">
								Q. <?php the_title(); ?>
								<span class="accordion__icon" aria-hidden="true"></span>
							</button>
						</h2>
						<div class="accordion__panel" id="<?php echo esc_attr( $panel ); ?>" role="region">
							<div class="accordion__panel-inner">
								<?php the_content(); ?>
							</div>
						</div>
					</div>
				<?php endwhile; ?>
			<?php else : ?>
				<p>質問はまだ登録されていません。</p>
			<?php endif; ?>
		</div>
	</section>
</main>

<?php
get_footer();
