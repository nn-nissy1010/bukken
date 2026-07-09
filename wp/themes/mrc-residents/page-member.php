<?php
/**
 * 会員トップ（ログイン後・住民専用）
 * slug "member" の固定ページで使用されます。
 *
 * @package mrc-residents
 */

get_header();

/** カテゴリー色分け用のクラスマップ */
function mrc_news_badge_class( $slug ) {
	$map = array(
		'news'     => 'badge--news',
		'schedule' => 'badge--schedule',
		'plan'     => 'badge--plan',
	);
	return isset( $map[ $slug ] ) ? $map[ $slug ] : 'badge--news';
}
?>

<main>
	<!-- 固定サマリー -->
	<?php if ( mrc_page_is_public( 'plan' ) ) : ?>
	<section class="section section--tight">
		<div class="container">
			<div class="pinned-card">
				<span class="pin-label">固定</span>
				<h2>工事の計画 ― なぜ大規模修繕工事を行うのか</h2>
				<p>工事の目的や進め方をご案内します。くわしい内容は説明会の資料でご確認いただけます。</p>
				<a href="<?php echo esc_url( home_url( '/plan/' ) ); ?>" class="btn btn--primary">詳しくはこちら</a>
			</div>
		</div>
	</section>
	<?php endif; ?>

	<!-- 新着（掲示板） -->
	<?php if ( mrc_page_is_public( 'news' ) ) : ?>
	<section class="section section--tight">
		<div class="container">
			<div class="section-heading">
				<h2>新着（掲示板）</h2>
			</div>

			<?php $news_terms = get_terms( array( 'taxonomy' => 'news_category', 'hide_empty' => false ) ); ?>
			<div class="chip-filter" data-filter-group data-filter-target="#member-posts" style="margin-bottom:8px;" role="tablist" aria-label="カテゴリ絞り込み">
				<button class="chip is-active" data-filter="all" aria-pressed="true">すべて</button>
				<?php if ( ! is_wp_error( $news_terms ) ) : ?>
					<?php foreach ( $news_terms as $term ) : ?>
						<button class="chip" data-filter="<?php echo esc_attr( $term->slug ); ?>" aria-pressed="false"><?php echo esc_html( $term->name ); ?></button>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>

			<div class="card" style="padding:8px 16px;">
				<ul class="post-list" id="member-posts">
					<?php
					$news = new WP_Query(
						array(
							'post_type'      => 'news',
							'posts_per_page' => 4,
						)
					);
					if ( $news->have_posts() ) :
						while ( $news->have_posts() ) :
							$news->the_post();
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
							<?php
						endwhile;
						wp_reset_postdata();
					else :
						echo '<li class="post-item"><a href="#"><span class="post-title">まだお知らせはありません。</span></a></li>';
					endif;
					?>
				</ul>
			</div>
			<p style="margin-top:16px;"><a href="<?php echo esc_url( get_post_type_archive_link( 'news' ) ); ?>" class="cta-link">お知らせ一覧をすべて見る</a></p>
		</div>
	</section>
	<?php endif; ?>

	<!-- 最近の資料 -->
	<?php if ( mrc_page_is_public( 'document' ) ) : ?>
	<section class="section section--tight section--alt">
		<div class="container">
			<div class="section-heading">
				<h2>最近の資料</h2>
			</div>
			<div class="card" style="padding:8px 16px;">
				<ul class="doc-list">
					<?php
					$docs = new WP_Query(
						array(
							'post_type'      => 'document',
							'posts_per_page' => 3,
						)
					);
					if ( $docs->have_posts() ) :
						while ( $docs->have_posts() ) :
							$docs->the_post();
							?>
							<li class="doc-item">
								<span class="badge badge--file">PDF</span>
								<span class="doc-name"><?php the_title(); ?></span>
								<a href="<?php the_permalink(); ?>" class="btn btn--outline btn--sm">詳細</a>
								<a href="<?php echo esc_url( mrc_doc_download_url() ); ?>" class="btn btn--navy btn--sm" download>ダウンロード</a>
							</li>
							<?php
						endwhile;
						wp_reset_postdata();
					else :
						echo '<li class="doc-item"><span class="doc-name">まだ資料はありません。</span></li>';
					endif;
					?>
				</ul>
			</div>
			<p style="margin-top:16px;"><a href="<?php echo esc_url( get_post_type_archive_link( 'document' ) ); ?>" class="cta-link">資料一覧を見る</a></p>
		</div>
	</section>
	<?php endif; ?>

	<!-- 動画アーカイブ -->
	<?php if ( mrc_page_is_public( 'video' ) ) : ?>
	<section class="section section--tight">
		<div class="container">
			<div class="section-heading">
				<h2>動画アーカイブ</h2>
			</div>
			<div class="video-grid">
				<?php
				$videos = new WP_Query(
					array(
						'post_type'      => 'video',
						'posts_per_page' => 2,
					)
				);
				if ( $videos->have_posts() ) :
					while ( $videos->have_posts() ) :
						$videos->the_post();
						?>
						<?php $thumb = mrc_video_thumb_url(); ?>
						<a class="video-card" href="<?php the_permalink(); ?>">
							<div class="video-thumb<?php echo $thumb ? ' video-thumb--img' : ''; ?>"<?php echo $thumb ? ' style="background-image:url(\'' . esc_url( $thumb ) . '\')"' : ''; ?> aria-hidden="true"></div>
							<div class="video-body">
								<p class="video-title"><?php the_title(); ?></p>
								<p class="video-date"><?php echo esc_html( get_the_date( 'Y.m.d' ) ); ?></p>
							</div>
						</a>
						<?php
					endwhile;
					wp_reset_postdata();
				else :
					echo '<p>まだ動画はありません。</p>';
				endif;
				?>
			</div>
			<p style="margin-top:16px;"><a href="<?php echo esc_url( get_post_type_archive_link( 'video' ) ); ?>" class="cta-link">動画一覧を見る</a></p>
		</div>
	</section>
	<?php endif; ?>

	<!-- Q&A / ご意見の窓口 -->
	<?php if ( mrc_page_is_public( 'qa' ) || mrc_page_is_public( 'contact' ) ) : ?>
	<section class="section section--tight section--alt">
		<div class="container">
			<div class="grid grid--2">
				<?php if ( mrc_page_is_public( 'qa' ) ) : ?>
				<div class="card card--pad-lg">
					<div class="section-heading" style="margin-bottom:12px;">
						<h2 style="font-size:22px;">よくあるご質問</h2>
					</div>
					<p style="color:var(--color-text-muted); margin-bottom:20px;">費用・スケジュール・生活への影響など、よくあるご質問への先回り回答をまとめています。</p>
					<a href="<?php echo esc_url( get_post_type_archive_link( 'qa' ) ); ?>" class="cta-link">よくある質問を見る</a>
				</div>
				<?php endif; ?>
				<?php if ( mrc_page_is_public( 'contact' ) ) : ?>
				<div class="card card--pad-lg">
					<div class="section-heading" style="margin-bottom:12px;">
						<h2 style="font-size:22px;">ご意見の窓口</h2>
					</div>
					<p style="color:var(--color-text-muted); margin-bottom:20px;">ご質問・ご意見をお送りいただけます。内容の「種別」に応じて担当窓口へお届けします。</p>
					<a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" class="btn btn--primary">ご意見・お問い合わせフォームへ</a>
				</div>
				<?php endif; ?>
			</div>
		</div>
	</section>
	<?php endif; ?>
</main>

<?php
get_footer();
