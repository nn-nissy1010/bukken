<?php
/**
 * 動画 詳細（埋め込み再生）
 *
 * @package mrc-residents
 */

get_header();

while ( have_posts() ) :
	the_post();
	$embed = mrc_video_embed( get_the_ID() );
	$thumb = mrc_video_thumb_url( get_the_ID() );
	?>

	<div class="container container--narrow">
		<nav class="breadcrumb" aria-label="パンくずリスト">
			<ol>
				<li><a href="<?php echo esc_url( home_url( '/member/' ) ); ?>">会員トップ</a></li>
				<li><a href="<?php echo esc_url( get_post_type_archive_link( 'video' ) ); ?>">動画</a></li>
				<li aria-current="page"><?php the_title(); ?></li>
			</ol>
		</nav>
	</div>

	<main>
		<article class="section" style="padding-top:8px;">
			<div class="container container--narrow">
				<header class="page-intro">
					<h1><?php the_title(); ?></h1>
					<p><?php echo esc_html( get_the_date( 'Y.m.d' ) ); ?></p>
				</header>

				<?php if ( $embed ) : ?>
					<div class="video-player" data-video-player style="margin-bottom:12px;">
						<button type="button" class="video-facade" data-video-facade<?php echo $thumb ? ' style="background-image:url(\'' . esc_url( $thumb ) . '\')"' : ''; ?> aria-label="<?php echo esc_attr( get_the_title() ); ?> を再生する">
							<span class="video-facade__play" aria-hidden="true"></span>
						</button>
						<div class="video-player__frame is-hidden" data-video-frame>
							<?php echo $embed; ?>
						</div>
					</div>
					<?php $mrc_vurl = get_post_meta( get_the_ID(), '_mrc_video_url', true ); ?>
					<?php if ( $mrc_vurl ) : ?>
						<p class="form-hint" style="margin-bottom:24px;">うまく再生できない場合は <a href="<?php echo esc_url( $mrc_vurl ); ?>" target="_blank" rel="noopener">動画のページを開く</a> からご覧ください。</p>
					<?php endif; ?>
				<?php else : ?>
					<div class="card card--pad-lg" style="margin-bottom:24px;">
						<p class="form-hint">※ 動画URLが設定されていないか、埋め込みに対応していないURLです（管理画面で YouTube/Vimeo のURLをご確認ください）。</p>
					</div>
				<?php endif; ?>

				<?php if ( trim( wp_strip_all_tags( get_the_content() ) ) !== '' ) : ?>
					<div class="entry-content" style="font-size:16px;line-height:1.9;">
						<?php the_content(); ?>
					</div>
				<?php endif; ?>

				<p style="margin-top:32px;"><a href="<?php echo esc_url( get_post_type_archive_link( 'video' ) ); ?>" class="cta-link">動画一覧へ戻る</a></p>
			</div>
		</article>
	</main>

	<?php
endwhile;

get_footer();
