<?php
/**
 * 資料一覧（アーカイブ）
 *
 * @package mrc-residents
 */

get_header();
?>

<div class="container container--narrow">
	<nav class="breadcrumb" aria-label="パンくずリスト">
		<ol>
			<li><a href="<?php echo esc_url( home_url( '/member/' ) ); ?>">会員トップ</a></li>
			<li aria-current="page">資料一覧</li>
		</ol>
	</nav>
</div>

<main>
	<section class="section" style="padding-top:8px;">
		<div class="container container--narrow">
			<div class="page-intro">
				<h1>資料一覧</h1>
				<p>説明会資料や計画書などをダウンロードいただけます。</p>
			</div>

			<?php $doc_terms = get_terms( array( 'taxonomy' => 'doc_category', 'hide_empty' => false ) ); ?>
			<?php if ( ! is_wp_error( $doc_terms ) && ! empty( $doc_terms ) ) : ?>
				<div class="chip-filter" data-filter-group data-filter-target="#doc-archive" style="margin-bottom:20px;" role="tablist" aria-label="種別で絞り込み">
					<button class="chip is-active" data-filter="all" aria-pressed="true">すべて</button>
					<?php foreach ( $doc_terms as $term ) : ?>
						<button class="chip" data-filter="<?php echo esc_attr( $term->slug ); ?>" aria-pressed="false"><?php echo esc_html( $term->name ); ?></button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<div class="card" style="padding:8px 16px;">
				<ul class="doc-list" id="doc-archive">
					<?php if ( have_posts() ) : ?>
						<?php
						while ( have_posts() ) :
							the_post();
							$dterms = get_the_terms( get_the_ID(), 'doc_category' );
							$dterm  = ( $dterms && ! is_wp_error( $dterms ) ) ? $dterms[0] : null;
							?>
							<li class="doc-item" data-filter-item data-category="<?php echo esc_attr( $dterm ? $dterm->slug : '' ); ?>">
								<span class="badge badge--file">PDF</span>
								<span class="doc-name"><?php the_title(); ?></span>
								<?php if ( $dterm ) : ?>
									<span class="doc-cat"><?php echo esc_html( $dterm->name ); ?></span>
								<?php endif; ?>
								<span class="doc-meta"><?php echo esc_html( get_the_date( 'Y.m.d' ) ); ?></span>
								<a href="<?php the_permalink(); ?>" class="btn btn--outline btn--sm">詳細</a>
								<a href="<?php echo esc_url( mrc_doc_download_url() ); ?>" class="btn btn--navy btn--sm" download>ダウンロード</a>
							</li>
						<?php endwhile; ?>
					<?php else : ?>
						<li class="doc-item"><span class="doc-name">資料はまだありません。</span></li>
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
