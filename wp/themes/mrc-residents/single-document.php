<?php
/**
 * 資料 詳細（PDFダウンロード）
 *
 * @package mrc-residents
 */

get_header();

while ( have_posts() ) :
	the_post();
	$file = get_post_meta( get_the_ID(), '_mrc_doc_url', true );
	?>

	<div class="container container--narrow">
		<nav class="breadcrumb" aria-label="パンくずリスト">
			<ol>
				<li><a href="<?php echo esc_url( home_url( '/member/' ) ); ?>">会員トップ</a></li>
				<li><a href="<?php echo esc_url( get_post_type_archive_link( 'document' ) ); ?>">資料</a></li>
				<li aria-current="page"><?php the_title(); ?></li>
			</ol>
		</nav>
	</div>

	<main>
		<article class="section" style="padding-top:8px;">
			<div class="container container--narrow">
				<header class="page-intro" style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;">
					<div>
						<h1><?php the_title(); ?></h1>
						<p><?php echo esc_html( get_the_date( 'Y.m.d' ) ); ?> 更新</p>
					</div>
					<?php if ( $file ) : ?>
						<a href="<?php echo esc_url( $file ); ?>" class="btn btn--primary" download style="flex-shrink:0;">PDFをダウンロード</a>
					<?php endif; ?>
				</header>

				<?php if ( trim( wp_strip_all_tags( get_the_content() ) ) !== '' ) : ?>
					<div class="entry-content" style="font-size:16px;line-height:1.9;margin-bottom:28px;">
						<?php the_content(); ?>
					</div>
				<?php endif; ?>

				<?php if ( $file ) : ?>
					<div class="doc-preview">
						<iframe class="doc-preview__embed" src="<?php echo esc_url( $file ); ?>#view=FitH&navpanes=0" title="<?php echo esc_attr( get_the_title() ); ?> のプレビュー" loading="lazy"></iframe>
					</div>
				<?php elseif ( mrc_is_staff() ) : ?>
					<p class="note">※ この資料にはPDFが未設定です（管理画面の「資料ファイル（PDF）」で選択してください）。この案内は管理者にのみ表示されます。</p>
				<?php endif; ?>

				<p style="margin-top:32px;"><a href="<?php echo esc_url( get_post_type_archive_link( 'document' ) ); ?>" class="cta-link">資料一覧へ戻る</a></p>
			</div>
		</article>
	</main>

	<?php
endwhile;

get_footer();
