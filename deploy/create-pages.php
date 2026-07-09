<?php
/**
 * 本番安全版・必須固定ページ作成スクリプト（デモデータは入れない）。
 * 実行: wp eval-file deploy/create-pages.php --url=https://example.com
 *
 * dev/seed.php はデモ用（サンプル記事＋弱いデモアカウント resident を作る）ため
 * 本番では使わない。本番は「テンプレートに必要な空ページ」だけをここで用意し、
 * 記事・資料・居住者アカウントは MRC が実データで登録する。
 * 冪等（既存slugはスキップ）。
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	echo "WP-CLI 経由で実行してください。\n";
	return;
}

$pages = array(
	'member'         => '会員トップ',
	'plan'           => '工事の計画',
	'contact'        => 'ご意見の窓口',
	'contact-public' => 'お問い合わせ',
);

foreach ( $pages as $slug => $title ) {
	if ( get_page_by_path( $slug ) ) {
		WP_CLI::log( "既存のためスキップ: {$title}（{$slug}）" );
		continue;
	}
	$id = wp_insert_post(
		array(
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => '',
		)
	);
	if ( $id && ! is_wp_error( $id ) ) {
		WP_CLI::log( "作成: {$title}（{$slug}）" );
	}
}

// パーマリンク統一・検索避け（居住者専用）
update_option( 'permalink_structure', '/%postname%/' );
update_option( 'blog_public', 0 );
flush_rewrite_rules( false );

WP_CLI::success( '必須固定ページの用意が完了しました（本番安全版・デモデータなし）。' );
