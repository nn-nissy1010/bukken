<?php
/**
 * 工事の計画（固定ページ / slug: plan）
 * 文言は専用フォーム（物件基本設定›ページ編集›工事の計画）で編集し、`mrc_get_plan_content()`
 * から取得。見た目（カード・工程ステップ）はテンプレートが担保する。
 * 説明会資料のダウンロード（機能部品）は常に表示。
 *
 * @package mrc-residents
 */

get_header();

$plan       = mrc_get_plan_content();
$mrc_icons  = array( 'purpose-icon--safe', 'purpose-icon--value', 'purpose-icon--home' );
?>

<div class="container container--narrow">
	<nav class="breadcrumb" aria-label="パンくずリスト">
		<ol>
			<li><a href="<?php echo esc_url( get_post_type_archive_link( 'news' ) ); ?>">お知らせ</a></li>
			<li aria-current="page">工事の計画</li>
		</ol>
	</nav>
</div>

<main>
	<article class="section" style="padding-top:8px;">
		<div class="container container--narrow">
			<div class="page-intro">
				<h1>工事の計画について</h1>
				<?php if ( '' !== trim( (string) $plan['lead'] ) ) : ?>
					<p class="lead"><?php echo nl2br( esc_html( $plan['lead'] ) ); ?></p>
				<?php endif; ?>
			</div>

			<section style="margin-bottom:48px;">
				<?php if ( '' !== trim( (string) $plan['intro_heading'] ) ) : ?>
					<div class="section-heading"><h2><?php echo esc_html( $plan['intro_heading'] ); ?></h2></div>
				<?php endif; ?>
				<?php if ( '' !== trim( (string) $plan['intro_body'] ) ) : ?>
					<p><?php echo nl2br( esc_html( $plan['intro_body'] ) ); ?></p>
				<?php endif; ?>

				<?php if ( ! empty( $plan['purposes'] ) ) : ?>
					<div class="grid grid--3" style="margin-top:28px;">
						<?php foreach ( $plan['purposes'] as $i => $p ) : ?>
							<div class="card purpose-card">
								<span class="purpose-icon <?php echo esc_attr( $mrc_icons[ $i % count( $mrc_icons ) ] ); ?>" aria-hidden="true"></span>
								<h3><?php echo esc_html( $p['title'] ); ?></h3>
								<p><?php echo nl2br( esc_html( $p['desc'] ) ); ?></p>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</section>

			<section style="margin-bottom:48px;">
				<?php if ( '' !== trim( (string) $plan['target_heading'] ) ) : ?>
					<div class="section-heading"><h2><?php echo esc_html( $plan['target_heading'] ); ?></h2></div>
				<?php endif; ?>
				<?php if ( '' !== trim( (string) $plan['target_intro'] ) ) : ?>
					<p style="margin-bottom:16px;"><?php echo nl2br( esc_html( $plan['target_intro'] ) ); ?></p>
				<?php endif; ?>
				<?php if ( ! empty( $plan['targets'] ) ) : ?>
					<ul class="spec-tags">
						<?php foreach ( $plan['targets'] as $t ) : ?>
							<li><?php echo esc_html( $t ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</section>

			<!-- 説明会の資料（機能部品・常時表示） -->
			<section style="margin-bottom:48px;">
				<div class="section-heading"><h2>くわしい内容は「説明会の資料」で</h2></div>
				<p style="margin-bottom:16px;">工事の対象箇所や具体的な内容は、調査・診断や説明会の資料にまとめています。下記からダウンロードいただけます（いずれもPDF）。</p>
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
							echo '<li class="doc-item"><span class="doc-name">資料はまだありません。</span></li>';
						endif;
						?>
					</ul>
				</div>
				<p style="margin-top:16px;"><a href="<?php echo esc_url( get_post_type_archive_link( 'document' ) ); ?>" class="cta-link">資料ダウンロード一覧へ</a></p>
			</section>

			<?php if ( ! empty( $plan['steps'] ) || '' !== trim( (string) $plan['flow_heading'] ) ) : ?>
			<section>
				<?php if ( '' !== trim( (string) $plan['flow_heading'] ) ) : ?>
					<div class="section-heading"><h2><?php echo esc_html( $plan['flow_heading'] ); ?></h2></div>
				<?php endif; ?>
				<?php if ( ! empty( $plan['steps'] ) ) : ?>
					<ol class="process-steps">
						<?php foreach ( $plan['steps'] as $i => $s ) : ?>
							<li class="process-step">
								<span class="process-step__num"><?php echo (int) ( $i + 1 ); ?></span>
								<div class="process-step__body">
									<h3 class="process-step__title"><?php echo esc_html( $s['title'] ); ?></h3>
									<p class="process-step__desc"><?php echo nl2br( esc_html( $s['desc'] ) ); ?></p>
								</div>
							</li>
						<?php endforeach; ?>
					</ol>
				<?php endif; ?>
				<?php if ( '' !== trim( (string) $plan['flow_note'] ) ) : ?>
					<p class="form-hint" style="margin-top:16px;"><?php echo esc_html( $plan['flow_note'] ); ?></p>
				<?php endif; ?>
			</section>
			<?php endif; ?>
		</div>
	</article>
</main>

<?php
get_footer();
