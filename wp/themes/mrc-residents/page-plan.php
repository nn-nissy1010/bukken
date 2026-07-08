<?php
/**
 * 工事の計画（固定ページ / slug: plan）
 * 全物件共通の定型説明＋説明会資料のダウンロード。
 *
 * @package mrc-residents
 */

get_header();
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
				<p class="lead"><?php bloginfo( 'name' ); ?>で予定している大規模修繕工事について、目的や進め方をかんたんにご案内します。工事の詳しい内容は、住民説明会で使用した資料（PDF）をご覧ください。</p>
			</div>

			<section style="margin-bottom:48px;">
				<div class="section-heading"><h2>大規模修繕工事とは（かんたんに）</h2></div>
				<p>マンションは、およそ12〜15年ごとに、外壁・防水・鉄部などをまとめて直す大規模修繕工事を行います。建物を長く安全に使い、資産としての価値を守るための工事です。専門家（設計監理者）が調査・診断し、住民説明会と総会での合意を経て進めます。</p>

				<div class="grid grid--3" style="margin-top:28px;">
					<div class="card purpose-card">
						<span class="purpose-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 3v5c0 4.6-3 7.7-7 9-4-1.3-7-4.4-7-9V6l7-3z"/><path d="M9 12l2 2 4-4"/></svg></span>
						<h3>建物を長く安全に</h3>
						<p>外壁や防水の劣化を放置せず、雨漏りや事故を防いで、安心して暮らせる状態を保ちます。</p>
					</div>
					<div class="card purpose-card">
						<span class="purpose-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17l6-6 4 4 8-8"/><path d="M15 7h6v6"/></svg></span>
						<h3>資産価値を守る</h3>
						<p>計画的に修繕することで、マンションの資産としての価値が下がるのを防ぎます。</p>
					</div>
					<div class="card purpose-card">
						<span class="purpose-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 11l8-6 8 6"/><path d="M6 10v9h12v-9"/><path d="M10 19v-5h4v5"/></svg></span>
						<h3>快適な住環境</h3>
						<p>美観や住み心地を維持し、これからも気持ちよく暮らせる環境を整えます。</p>
					</div>
				</div>
			</section>

			<section style="margin-bottom:48px;">
				<div class="section-heading"><h2>主な工事の対象</h2></div>
				<p style="margin-bottom:16px;">大規模修繕では、主に次のような箇所をまとめて点検・修繕します。（対象箇所は建物により異なります）</p>
				<ul class="spec-tags">
					<li>外壁塗装</li>
					<li>防水工事（屋上・バルコニー）</li>
					<li>鉄部塗装</li>
					<li>タイル補修</li>
					<li>シーリング打ち替え</li>
					<li>給排水設備</li>
				</ul>
			</section>

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

			<section>
				<div class="section-heading"><h2>今後の流れ</h2></div>
				<ol class="process-steps">
					<li class="process-step">
						<span class="process-step__num">1</span>
						<div class="process-step__body">
							<h3 class="process-step__title">調査・診断</h3>
							<p class="process-step__desc">専門家（設計監理者）が建物の状態を詳しく調べ、劣化の程度を診断します。</p>
						</div>
					</li>
					<li class="process-step">
						<span class="process-step__num">2</span>
						<div class="process-step__body">
							<h3 class="process-step__title">住民説明会</h3>
							<p class="process-step__desc">調査の結果や工事の進め方を、居住者の皆さまにわかりやすくご説明します。</p>
						</div>
					</li>
					<li class="process-step">
						<span class="process-step__num">3</span>
						<div class="process-step__body">
							<h3 class="process-step__title">施工会社の選定</h3>
							<p class="process-step__desc">複数の会社を比較・検討し、工事を担当する施工会社を選びます。</p>
						</div>
					</li>
					<li class="process-step">
						<span class="process-step__num">4</span>
						<div class="process-step__body">
							<h3 class="process-step__title">総会での決議</h3>
							<p class="process-step__desc">工事請負契約の承認を総会で決議し、工事が正式に決まります。</p>
						</div>
					</li>
					<li class="process-step">
						<span class="process-step__num">5</span>
						<div class="process-step__body">
							<h3 class="process-step__title">着工</h3>
							<p class="process-step__desc">準備が整い次第、工事を開始します。工程はお知らせと掲示板でご案内します。</p>
						</div>
					</li>
				</ol>
				<p class="form-hint" style="margin-top:16px;">※ 着工の時期は決まり次第お知らせします。</p>
			</section>
		</div>
	</article>
</main>

<?php
get_footer();
