<?php
/**
 * デモ用 追加お知らせ投入スクリプト（掲示板のにぎわい演出用）
 * 実行例:
 *   wp eval-file wp-content/themes/mrc-residents/dev/seed-demo-news.php --url=localhost:8888/house1
 * 同一タイトルが既にあればスキップするので繰り返し実行しても安全。
 */

// 追加するお知らせ（タイトル, カテゴリーslug, 投稿日, 本文）
$items = array(
	array(
		'第2回 住民説明会の日程が決まりました',
		'schedule',
		'2026-07-06 10:00:00',
		"第2回の住民説明会を下記のとおり開催いたします。\n\n日時：2026年8月2日（土）14:00〜15:30\n場所：管理棟 集会室\n\n工事の進め方や今後のスケジュールについてご説明します。ご都合のつかない方には後日、資料と動画を当サイトで公開いたします。",
	),
	array(
		'足場設置に伴う駐輪場の一時移動について',
		'news',
		'2026-07-03 10:00:00',
		"外壁工事の足場設置に伴い、A棟東側の駐輪場を下記期間、南側臨時スペースへ移動いたします。\n\n期間：2026年8月10日〜9月30日（予定）\n\nご不便をおかけしますが、ご協力をお願いいたします。詳細は掲示板および各戸配布のお知らせをご確認ください。",
	),
	array(
		'外壁塗装の色見本を掲示しています',
		'plan',
		'2026-07-01 10:00:00',
		"外壁塗装の色候補について、管理棟エントランスに実物大の色見本を掲示しています。\n\nご意見のある方は「ご意見の窓口」よりお寄せください。最終的な配色は管理組合理事会で決定いたします。",
	),
	array(
		'バルコニー使用制限期間のお知らせ',
		'news',
		'2026-06-29 10:00:00',
		"防水・塗装工事の期間中、各住戸のバルコニーの使用を一時的に制限させていただきます。\n\n洗濯物の外干し、植木鉢等の設置ができない期間があります。対象期間は住戸ごとに順次ご案内いたします。ご協力をお願いいたします。",
	),
);

$created = 0;
foreach ( $items as $item ) {
	list( $title, $cat_slug, $date, $content ) = $item;

	// 同名タイトルが既にあればスキップ
	$existing = get_page_by_title( $title, OBJECT, 'news' );
	if ( $existing ) {
		WP_CLI::log( "スキップ（既存）: {$title}" );
		continue;
	}

	$id = wp_insert_post(
		array(
			'post_title'   => $title,
			'post_content' => $content,
			'post_status'  => 'publish',
			'post_type'    => 'news',
			'post_date'    => $date,
		)
	);

	if ( $id && ! is_wp_error( $id ) ) {
		$term = get_term_by( 'slug', $cat_slug, 'news_category' );
		if ( $term ) {
			wp_set_object_terms( $id, (int) $term->term_id, 'news_category' );
		}
		$created++;
		WP_CLI::log( "作成: {$title}（{$cat_slug}）" );
	}
}

WP_CLI::success( "デモ用お知らせを {$created} 件追加しました。" );
