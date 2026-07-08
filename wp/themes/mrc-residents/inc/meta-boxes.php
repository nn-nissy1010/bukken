<?php
/**
 * 資料/動画のメタボックス・保存処理・表示ヘルパー
 * functions.php から読み込まれる機能モジュール。
 *
 * @package mrc-residents
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ============================================================
   資料PDF・動画URL（カスタムフィールド）と表示補助
   ============================================================ */

/** メタボックス登録 */
function mrc_add_meta_boxes() {
	add_meta_box( 'mrc_doc_file', '資料ファイル（PDF）', 'mrc_doc_file_box', 'document', 'normal', 'high' );
	add_meta_box( 'mrc_video_url_box', '動画URL（YouTube / Vimeo）', 'mrc_video_url_box', 'video', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'mrc_add_meta_boxes' );

function mrc_doc_file_box( $post ) {
	wp_nonce_field( 'mrc_doc_file', 'mrc_doc_file_nonce' );
	$url = get_post_meta( $post->ID, '_mrc_doc_url', true );
	?>
	<p style="margin-top:4px;">住民にダウンロードさせる <strong>PDFファイル</strong> をここで選びます（アップロード or メディアから選択）。</p>
	<input type="hidden" id="mrc_doc_url" name="mrc_doc_url" value="<?php echo esc_attr( $url ); ?>">
	<p style="margin:12px 0;">
		<button type="button" class="button button-primary button-large" id="mrc_doc_select">
			<span class="dashicons dashicons-media-document" style="vertical-align:text-top;"></span>
			<?php echo $url ? 'PDFを変更する' : 'PDFファイルを選択'; ?>
		</button>
		<button type="button" class="button button-link-delete" id="mrc_doc_clear" style="<?php echo $url ? 'margin-left:8px;' : 'display:none;'; ?>">削除</button>
	</p>
	<p id="mrc_doc_name" style="word-break:break-all;font-size:13px;">
		<?php if ( $url ) : ?>
			<span class="dashicons dashicons-yes" style="color:#00a32a;"></span>
			選択中：<strong><?php echo esc_html( wp_basename( $url ) ); ?></strong>
		<?php else : ?>
			<span style="color:#b32d2e;">まだファイルが選ばれていません。</span>
		<?php endif; ?>
	</p>
	<?php
}

function mrc_video_url_box( $post ) {
	wp_nonce_field( 'mrc_video_url', 'mrc_video_url_nonce' );
	$url = get_post_meta( $post->ID, '_mrc_video_url', true );
	?>
	<p class="description">YouTube（限定公開）や Vimeo の動画URLを貼り付けてください。</p>
	<input type="url" class="widefat" name="mrc_video_url" value="<?php echo esc_attr( $url ); ?>" placeholder="https://youtu.be/xxxxxxxx">
	<?php
}

/** 保存 */
function mrc_save_meta( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( isset( $_POST['mrc_doc_file_nonce'] ) && wp_verify_nonce( $_POST['mrc_doc_file_nonce'], 'mrc_doc_file' ) ) {
		update_post_meta( $post_id, '_mrc_doc_url', esc_url_raw( wp_unslash( $_POST['mrc_doc_url'] ?? '' ) ) );
	}
	if ( isset( $_POST['mrc_video_url_nonce'] ) && wp_verify_nonce( $_POST['mrc_video_url_nonce'], 'mrc_video_url' ) ) {
		update_post_meta( $post_id, '_mrc_video_url', esc_url_raw( wp_unslash( $_POST['mrc_video_url'] ?? '' ) ) );
	}
}
add_action( 'save_post', 'mrc_save_meta' );

/** 資料編集画面にメディアアップローダーを読み込む */
function mrc_admin_media_assets( $hook ) {
	if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen || 'document' !== $screen->post_type ) {
		return;
	}
	wp_enqueue_media();
	wp_add_inline_script(
		'jquery-core',
		"jQuery(function($){var f;\$('#mrc_doc_select').on('click',function(e){e.preventDefault();if(f){f.open();return;}f=wp.media({title:'資料ファイルを選択',library:{type:'application/pdf'},button:{text:'選択'},multiple:false});f.on('select',function(){var a=f.state().get('selection').first().toJSON();\$('#mrc_doc_url').val(a.url);\$('#mrc_doc_name').text(a.filename||a.url);\$('#mrc_doc_clear').show();});f.open();});\$('#mrc_doc_clear').on('click',function(e){e.preventDefault();\$('#mrc_doc_url').val('');\$('#mrc_doc_name').text('');\$(this).hide();});});"
	);
}
add_action( 'admin_enqueue_scripts', 'mrc_admin_media_assets' );

/** 補助：資料のダウンロードURL（ファイル未設定なら詳細ページ） */
function mrc_doc_download_url( $post_id = null ) {
	$id  = $post_id ? $post_id : get_the_ID();
	$url = get_post_meta( $id, '_mrc_doc_url', true );
	return $url ? $url : get_permalink( $id );
}
/** 補助：動画の埋め込みHTML（oEmbed）。YouTubeはプレイヤーのパラメータを最適化。 */
function mrc_video_embed( $post_id = null ) {
	$id  = $post_id ? $post_id : get_the_ID();
	$url = get_post_meta( $id, '_mrc_video_url', true );
	if ( ! $url ) {
		return '';
	}
	$embed = wp_oembed_get( $url );
	if ( ! $embed ) {
		return '';
	}
	// YouTube 埋め込みのURLに、見やすさのためのパラメータを付与
	// rel=0（関連動画は同チャンネル）／modestbranding=1（ロゴ控えめ）／controls=1（コントロール表示）
	$embed = preg_replace_callback(
		'~src="(https://www\.youtube\.com/embed/[^"]*)"~',
		function ( $m ) {
			$src = html_entity_decode( $m[1] );
			$sep = ( false !== strpos( $src, '?' ) ) ? '&' : '?';
			return 'src="' . esc_url( $src . $sep . 'rel=0&modestbranding=1&controls=1' ) . '"';
		},
		$embed
	);
	return $embed;
}

/** 動画URLから YouTube 動画IDを抽出 */
function mrc_youtube_id( $url ) {
	if ( $url && preg_match( '~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|v/|shorts/))([A-Za-z0-9_-]{11})~', $url, $m ) ) {
		return $m[1];
	}
	return '';
}

/**
 * 動画の表紙（サムネイル）URL。
 * 優先: アイキャッチ画像 → YouTubeサムネイル → 空（プレースホルダー表示）
 */
function mrc_video_thumb_url( $post_id = null ) {
	$id = $post_id ? $post_id : get_the_ID();
	if ( has_post_thumbnail( $id ) ) {
		return get_the_post_thumbnail_url( $id, 'large' );
	}
	$yt = mrc_youtube_id( get_post_meta( $id, '_mrc_video_url', true ) );
	if ( $yt ) {
		return 'https://img.youtube.com/vi/' . $yt . '/hqdefault.jpg';
	}
	return '';
}

/**
 * 資料・動画はブロックエディタを使わずクラシック編集にする。
 * （メタボックス「PDFファイル」「動画URL」をタイトル直下に大きく見せ、迷わない編集画面に）
 */
function mrc_disable_block_editor( $use, $post_type ) {
	if ( in_array( $post_type, array( 'document', 'video' ), true ) ) {
		return false;
	}
	return $use;
}
add_filter( 'use_block_editor_for_post_type', 'mrc_disable_block_editor', 10, 2 );
