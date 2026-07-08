<?php
/**
 * サンプルデータ投入スクリプト（開発用）
 * 実行: wp eval-file wp-content/themes/mrc-residents/dev/seed.php
 * 二重投入を防ぐため option 'mrc_seeded' で一度きりに制御。
 */

if ( get_option( 'mrc_seeded' ) ) {
	WP_CLI::log( 'すでにシード済みのためスキップしました。' );
	return;
}

/* 会員トップ固定ページ（slug: member） */
if ( ! get_page_by_path( 'member' ) ) {
	wp_insert_post(
		array(
			'post_title'   => '会員トップ',
			'post_name'    => 'member',
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => '',
		)
	);
	WP_CLI::log( '会員トップ固定ページを作成しました。' );
}

/* お知らせカテゴリー */
$cats = array(
	'news'     => 'お知らせ',
	'schedule' => 'スケジュール',
	'plan'     => '工事の計画',
);
$term_ids = array();
foreach ( $cats as $slug => $name ) {
	$existing = get_term_by( 'slug', $slug, 'news_category' );
	if ( $existing ) {
		$term_ids[ $slug ] = $existing->term_id;
	} else {
		$res = wp_insert_term( $name, 'news_category', array( 'slug' => $slug ) );
		if ( ! is_wp_error( $res ) ) {
			$term_ids[ $slug ] = $res['term_id'];
		}
	}
}

/* お知らせ投稿 */
$news_items = array(
	array( '第1回 住民説明会の開催について', 'news', '2026-07-15 10:00:00' ),
	array( '今後の予定を更新しました', 'schedule', '2026-07-10 10:00:00' ),
	array( '建物調査・診断の結果についてのご報告', 'plan', '2026-07-05 10:00:00' ),
	array( '当サイトの使い方について', 'news', '2026-06-28 10:00:00' ),
);
foreach ( $news_items as $item ) {
	$id = wp_insert_post(
		array(
			'post_title'   => $item[0],
			'post_content' => 'ここに本文が入ります。（サンプル）',
			'post_status'  => 'publish',
			'post_type'    => 'news',
			'post_date'    => $item[2],
		)
	);
	if ( $id && isset( $term_ids[ $item[1] ] ) ) {
		wp_set_object_terms( $id, (int) $term_ids[ $item[1] ], 'news_category' );
	}
}

/* 資料の種別 */
$doc_cats     = array(
	'survey'   => '調査・診断',
	'briefing' => '説明会資料',
	'plan'     => '計画・その他',
);
$doc_term_ids = array();
foreach ( $doc_cats as $slug => $name ) {
	$ex = get_term_by( 'slug', $slug, 'doc_category' );
	if ( $ex ) {
		$doc_term_ids[ $slug ] = $ex->term_id;
	} else {
		$r = wp_insert_term( $name, 'doc_category', array( 'slug' => $slug ) );
		if ( ! is_wp_error( $r ) ) {
			$doc_term_ids[ $slug ] = $r['term_id'];
		}
	}
}

/* 資料 */
$docs = array(
	array( '建物調査診断報告書', 'survey', '2026-07-12 10:00:00' ),
	array( '住民説明会資料', 'briefing', '2026-07-11 10:00:00' ),
	array( '長期修繕計画書（概要版）', 'plan', '2026-07-10 10:00:00' ),
);
foreach ( $docs as $d ) {
	$id = wp_insert_post(
		array(
			'post_title'  => $d[0],
			'post_status' => 'publish',
			'post_type'   => 'document',
			'post_date'   => $d[2],
		)
	);
	if ( $id && isset( $doc_term_ids[ $d[1] ] ) ) {
		wp_set_object_terms( $id, (int) $doc_term_ids[ $d[1] ], 'doc_category' );
	}
}

/* 動画（URLは _mrc_video_url メタに保存。サンプルは実在するYouTube動画） */
$videos = array(
	array( '第1回 住民説明会（録画）', 'https://youtu.be/aqz-KE-bpKQ' ),
	array( '工事内容の説明', 'https://youtu.be/jNQXAC9IVRw' ),
);
foreach ( $videos as $v ) {
	$vid = wp_insert_post(
		array(
			'post_title'  => $v[0],
			'post_status' => 'publish',
			'post_type'   => 'video',
			'post_date'   => '2026-07-20 10:00:00',
		)
	);
	if ( $vid && ! is_wp_error( $vid ) ) {
		update_post_meta( $vid, '_mrc_video_url', $v[1] );
	}
}

/* Q&A */
$qas = array(
	'建物調査時のバルコニー調査に立ち会えなかった場合、工事の対象外になりますか？' => '対象外にはなりません。建物調査でのバルコニー調査は、建物全体の劣化状況を把握し、工事内容を検討するために実施するものです。工事着工後には施工会社が全住戸を対象として改めて調査を実施し、その際に劣化状況を確認したうえで必要な補修を行いますので、ご安心ください。',
	'なぜ今、大規模修繕工事をするのですか？' => '建物の劣化は時間とともに進みます。調査・診断の結果をもとに、傷みが大きくなる前の適切な時期として計画しています。',
	'工事の費用はどうなりますか？'       => '原則として、毎月お積み立ていただいている修繕積立金から充当します。費用の詳細は総会でお諮りします。',
	'いつから工事が始まりますか？'       => '現在は計画段階です。着工の時期は、施工会社の選定・総会での決議を経て決まります。決まり次第お知らせします。',
);
$order = 1;
foreach ( $qas as $q => $a ) {
	wp_insert_post(
		array(
			'post_title'   => $q,
			'post_content' => $a,
			'post_status'  => 'publish',
			'post_type'    => 'qa',
			'menu_order'   => $order++,
		)
	);
}

/* テスト用 居住者アカウント（購読者） */
if ( ! username_exists( 'resident' ) ) {
	$uid = wp_create_user( 'resident', 'resident', 'resident@example.com' );
	if ( ! is_wp_error( $uid ) ) {
		$u = new WP_User( $uid );
		$u->set_role( 'subscriber' );
		wp_update_user( array( 'ID' => $uid, 'display_name' => '居住者テスト' ) );
		WP_CLI::log( '居住者アカウント resident / resident を作成しました。' );
	}
}

update_option( 'mrc_seeded', 1 );
WP_CLI::success( 'サンプルデータの投入が完了しました。' );
