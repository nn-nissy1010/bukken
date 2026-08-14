<?php
/**
 * 各ページ（工事の計画/会員トップ/ご意見の窓口/お問い合わせ）の本文が空なら、
 * 標準説明を「編集しやすい本文」として初期投入する。
 *
 * 冪等：本文が既に入っているページは変更しない（誤って上書きしない）。
 * 標準文の内容は共通テーマの mrc_standard_page_content() が単一の情報源。
 *
 * 使い方（対象サイトごとに）:
 *   wp eval-file deploy/seed-standard-page-content.php --url=https://mrc-archi.site/house1/
 *
 * @package mrc-residents-ops
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'mrc_standard_page_content' ) ) {
	echo "エラー: 共通テーマが有効化されていません（mrc_standard_page_content 未定義）。\n";
	return;
}

$slugs = array( 'plan', 'member', 'contact', 'contact-public' );

foreach ( $slugs as $slug ) {
	$page = get_page_by_path( $slug );
	if ( ! $page ) {
		echo "  {$slug}: ページ無し（スキップ）\n";
		continue;
	}
	if ( '' !== trim( (string) $page->post_content ) ) {
		echo "  {$slug}: 本文あり（変更しません）\n";
		continue;
	}
	$content = mrc_standard_page_content( $slug );
	if ( '' === $content ) {
		echo "  {$slug}: 標準文の定義なし（スキップ）\n";
		continue;
	}
	wp_update_post(
		array(
			'ID'           => $page->ID,
			'post_content' => $content,
		)
	);
	echo "  {$slug}: 標準説明を本文に投入しました\n";
}

echo "完了。\n";
