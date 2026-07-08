<?php
/**
 * カスタム投稿タイプ／タクソノミーの登録
 * functions.php から読み込まれる機能モジュール。
 *
 * @package mrc-residents
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * カスタム投稿タイプ・タクソノミーの登録
 * （お知らせ／資料／動画／Q&A）
 */
function mrc_register_post_types() {

	// お知らせ（＝お知らせ／スケジュール／工事の計画をカテゴリーで分類）
	register_post_type(
		'news',
		array(
			'labels'        => array(
				'name'          => 'お知らせ',
				'singular_name' => 'お知らせ',
				'add_new'       => '新規追加',
				'add_new_item'  => 'お知らせを追加',
				'edit_item'     => 'お知らせを編集',
				'all_items'     => 'お知らせ一覧',
			),
			'public'        => true,
			'has_archive'   => true,
			'menu_icon'     => 'dashicons-megaphone',
			'menu_position' => 5,
			'supports'      => array( 'title', 'editor', 'thumbnail' ),
			'rewrite'       => array( 'slug' => 'news' ),
			'show_in_rest'  => true,
		)
	);
	register_taxonomy(
		'news_category',
		'news',
		array(
			'labels'       => array(
				'name'          => 'カテゴリー',
				'singular_name' => 'カテゴリー',
			),
			'hierarchical' => true,
			'public'       => true,
			'show_in_rest' => true,
			'rewrite'      => array( 'slug' => 'news-category' ),
		)
	);
	register_taxonomy(
		'doc_category',
		'document',
		array(
			'labels'       => array(
				'name'          => '種別',
				'singular_name' => '種別',
			),
			'hierarchical' => true,
			'public'       => true,
			'show_in_rest' => true,
			'rewrite'      => array( 'slug' => 'document-category' ),
		)
	);

	// 資料（PDF等）
	register_post_type(
		'document',
		array(
			'labels'        => array(
				'name'          => '資料',
				'singular_name' => '資料',
				'add_new_item'  => '資料を追加',
				'edit_item'     => '資料を編集',
				'all_items'     => '資料一覧',
			),
			'public'        => true,
			'has_archive'   => true,
			'menu_icon'     => 'dashicons-media-document',
			'menu_position' => 6,
			'supports'      => array( 'title' ),
			'rewrite'       => array( 'slug' => 'documents' ),
			'show_in_rest'  => true,
		)
	);

	// 動画（外部埋め込み）
	register_post_type(
		'video',
		array(
			'labels'        => array(
				'name'          => '動画',
				'singular_name' => '動画',
				'add_new_item'  => '動画を追加',
				'edit_item'     => '動画を編集',
				'all_items'     => '動画一覧',
			),
			'public'        => true,
			'has_archive'   => true,
			'menu_icon'     => 'dashicons-video-alt3',
			'menu_position' => 7,
			'supports'      => array( 'title', 'thumbnail' ),
			'rewrite'       => array( 'slug' => 'videos' ),
			'show_in_rest'  => true,
		)
	);

	// Q&A
	register_post_type(
		'qa',
		array(
			'labels'        => array(
				'name'          => 'Q&A',
				'singular_name' => 'Q&A',
				'add_new_item'  => '質問を追加',
				'edit_item'     => '質問を編集',
				'all_items'     => 'Q&A一覧',
			),
			'public'        => true,
			'has_archive'   => true,
			'menu_icon'     => 'dashicons-editor-help',
			'menu_position' => 8,
			'supports'      => array( 'title', 'editor', 'page-attributes' ),
			'rewrite'       => array( 'slug' => 'qa' ),
			'show_in_rest'  => true,
		)
	);
}
add_action( 'init', 'mrc_register_post_types' );
