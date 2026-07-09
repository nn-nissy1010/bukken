<?php
/**
 * サンプルデータ投入スクリプト（開発用・単独で完結する物件ビルダー）
 * 実行: wp eval-file wp-content/themes/mrc-residents/dev/seed.php --url=localhost:8888/<slug>
 *
 * このスクリプト1本で、まっさらなサブサイトを「デモとして完成した物件」に仕上げる：
 *   物件名/キャッチ → 必須固定ページ → タクソノミー → お知らせ/資料/動画/Q&A → 居住者アカウント。
 * すべて冪等（存在チェック付き）なので、再実行しても重複しない。
 * 二重投入を防ぐ option 'mrc_seeded' は最後に立てるが、途中の各処理も個別に冪等。
 */

if ( get_option( 'mrc_seeded' ) ) {
	WP_CLI::log( 'すでにシード済みのためスキップしました。' );
	return;
}

/* パーマリンクを /%postname%/ に統一（新規サイト自動設定が未適用でも動くように保険） */
if ( '/%postname%/' !== get_option( 'permalink_structure' ) ) {
	update_option( 'permalink_structure', '/%postname%/' );
	flush_rewrite_rules( false );
}

/* 物件名・キャッチ（デモ用。実物件では管理画面から各自設定） */
update_option( 'blogname', '桜台レジデンス' );
update_option( 'blogdescription', '大規模修繕工事 居住者専用サイト' );

/*
 * 必須固定ページ（slug でテンプレートが決まる）。
 * 本文はテンプレート（page-*.php）側に持つため、ページ自体は空でよい。
 * 本番は雛形サイトを NS Cloner 複製するため既に存在するが、素のサブサイト用に自前でも作る。
 */
$pages = array(
	'member'         => '会員トップ',
	'plan'           => '工事の計画',
	'contact'        => 'ご意見の窓口',
	'contact-public' => 'お問い合わせ',
);
foreach ( $pages as $slug => $title ) {
	if ( ! get_page_by_path( $slug ) ) {
		wp_insert_post(
			array(
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => '',
			)
		);
		WP_CLI::log( "固定ページを作成: {$title}（{$slug}）" );
	}
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

/*
 * 投稿日は「現在からの相対過去日」で与える。
 * 固定の未来日付だと WordPress が予約投稿(future)扱いにして公開されず、
 * いつ seed しても一覧が欠ける（＝再現性が崩れる）ため、必ず過去にする。
 */
$days_ago = static function ( $n ) {
	return gmdate( 'Y-m-d H:i:s', time() - ( (int) $n * DAY_IN_SECONDS ) );
};

/* お知らせ投稿（タイトル, カテゴリーslug, 何日前, 本文） */
$news_items = array(
	array( '第1回 住民説明会の開催について', 'news', 1, 'ここに本文が入ります。（サンプル）' ),
	array( '今後の予定を更新しました', 'schedule', 3, 'ここに本文が入ります。（サンプル）' ),
	array( '第2回 住民説明会の日程が決まりました', 'schedule', 5, "第2回の住民説明会を下記のとおり開催いたします。\n\n日時：来月上旬（土）14:00〜15:30\n場所：管理棟 集会室\n\n工事の進め方や今後のスケジュールについてご説明します。ご都合のつかない方には後日、資料と動画を当サイトで公開いたします。" ),
	array( '建物調査・診断の結果についてのご報告', 'plan', 7, 'ここに本文が入ります。（サンプル）' ),
	array( '足場設置に伴う駐輪場の一時移動について', 'news', 9, "外壁工事の足場設置に伴い、A棟東側の駐輪場を一定期間、南側臨時スペースへ移動いたします。\n\nご不便をおかけしますが、ご協力をお願いいたします。詳細は掲示板および各戸配布のお知らせをご確認ください。" ),
	array( '外壁塗装の色見本を掲示しています', 'plan', 11, "外壁塗装の色候補について、管理棟エントランスに実物大の色見本を掲示しています。\n\nご意見のある方は「ご意見の窓口」よりお寄せください。最終的な配色は管理組合理事会で決定いたします。" ),
	array( 'バルコニー使用制限期間のお知らせ', 'news', 13, "防水・塗装工事の期間中、各住戸のバルコニーの使用を一時的に制限させていただきます。\n\n洗濯物の外干し、植木鉢等の設置ができない期間があります。対象期間は住戸ごとに順次ご案内いたします。ご協力をお願いいたします。" ),
	array( '当サイトの使い方について', 'news', 15, 'ここに本文が入ります。（サンプル）' ),
);
foreach ( $news_items as $item ) {
	// 同名タイトルが既にあればスキップ（再実行安全）
	if ( get_posts( array( 'post_type' => 'news', 'title' => $item[0], 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids' ) ) ) {
		continue;
	}
	$id = wp_insert_post(
		array(
			'post_title'   => $item[0],
			'post_content' => $item[3],
			'post_status'  => 'publish',
			'post_type'    => 'news',
			'post_date'    => $days_ago( $item[2] ),
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

/* 資料（タイトル, 種別slug, 何日前） */
$docs = array(
	array( '建物調査診断報告書', 'survey', 2 ),
	array( '住民説明会資料', 'briefing', 4 ),
	array( '長期修繕計画書（概要版）', 'plan', 6 ),
);
foreach ( $docs as $d ) {
	if ( get_posts( array( 'post_type' => 'document', 'title' => $d[0], 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids' ) ) ) {
		continue;
	}
	$id = wp_insert_post(
		array(
			'post_title'  => $d[0],
			'post_status' => 'publish',
			'post_type'   => 'document',
			'post_date'   => $days_ago( $d[2] ),
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
	if ( get_posts( array( 'post_type' => 'video', 'title' => $v[0], 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids' ) ) ) {
		continue;
	}
	$vid = wp_insert_post(
		array(
			'post_title'  => $v[0],
			'post_status' => 'publish',
			'post_type'   => 'video',
			'post_date'   => $days_ago( 8 ),
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
	if ( get_posts( array( 'post_type' => 'qa', 'title' => $q, 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids' ) ) ) {
		$order++;
		continue;
	}
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

/*
 * デモ居住者アカウント（購読者）。ID: resident / PW: resident1234
 * マルチサイトでは username_exists はネットワーク全体を見るため、
 * 既存ユーザーでも「この物件サイトの登録者」にしないとログインできない
 * （access.php の is_user_member_of_blog チェック）。両ケースを吸収する。
 */
$resident_id = username_exists( 'resident' );
if ( ! $resident_id ) {
	$resident_id = wp_create_user( 'resident', 'resident1234', 'resident@example.com' );
	if ( ! is_wp_error( $resident_id ) ) {
		wp_update_user( array( 'ID' => $resident_id, 'display_name' => '居住者テスト' ) );
		WP_CLI::log( '居住者アカウント resident / resident1234 を作成しました。' );
	}
} else {
	// 既存（別物件の seed で作成済み等）でも、記載どおりのパスワードで必ずログインできるよう揃える。
	wp_set_password( 'resident1234', (int) $resident_id );
	WP_CLI::log( '既存の居住者アカウント resident のパスワードを resident1234 に設定しました。' );
}
if ( $resident_id && ! is_wp_error( $resident_id ) ) {
	// この物件サイトの購読者として所属させる（未所属なら追加）
	add_user_to_blog( get_current_blog_id(), (int) $resident_id, 'subscriber' );
}

update_option( 'mrc_seeded', 1 );
WP_CLI::success( 'サンプルデータの投入が完了しました。物件名・ページ・お知らせ/資料/動画/Q&A・居住者アカウントを用意しました。' );
