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
 * お知らせ・資料・動画・Q&Aはブロックエディタを使わずクラシック編集にする。
 * （資料/動画はメタボックスをタイトル直下に大きく見せ、Q&Aは本文欄の上に
 *   「回答（A）」ラベルを差し込むため。お知らせは種別を単一選択ラジオにするため
 *   ＝ブロックエディタだと meta_box_cb が無視される。いずれも迷わない編集画面にする）
 */
function mrc_disable_block_editor( $use, $post_type ) {
	if ( in_array( $post_type, array( 'news', 'document', 'video', 'qa' ), true ) ) {
		return false;
	}
	return $use;
}
add_filter( 'use_block_editor_for_post_type', 'mrc_disable_block_editor', 10, 2 );

/* ============================================================
   Q&A 編集画面：タイトル＝質問(Q)／本文＝回答(A) を明示化
   （データ構造は変えず、記入欄が何を書く場所か一目で分かるようにする）
   ============================================================ */

/** タイトル欄のプレースホルダーを「質問（Q）」に差し替える。 */
function mrc_qa_title_placeholder( $text, $post ) {
	if ( $post instanceof WP_Post && 'qa' === $post->post_type ) {
		return '質問（Q）を入力　例：工事の費用はどうなりますか？';
	}
	return $text;
}
add_filter( 'enter_title_here', 'mrc_qa_title_placeholder', 10, 2 );

/** タイトル欄の上に、この画面の入力ルールを案内する。 */
function mrc_qa_edit_guide( $post ) {
	if ( ! ( $post instanceof WP_Post ) || 'qa' !== $post->post_type ) {
		return;
	}
	?>
	<div style="margin:12px 0;padding:12px 16px;border-left:4px solid #2271b1;background:#f0f6fc;font-size:14px;line-height:1.7;">
		この画面は <strong>Q&amp;Aの1問</strong> です。<br>
		<span style="display:inline-block;min-width:5.5em;font-weight:600;color:#1d4ed8;">■ 質問（Q）</span>… 下の<strong>タイトル欄</strong>に入力<br>
		<span style="display:inline-block;min-width:5.5em;font-weight:600;color:#b45309;">■ 回答（A）</span>… さらに下の<strong>本文欄</strong>に入力
	</div>
	<?php
}
add_action( 'edit_form_top', 'mrc_qa_edit_guide' );

/** 本文（回答）欄の直上に「回答（A）」の見出しを差し込む。 */
function mrc_qa_answer_label( $post ) {
	if ( ! ( $post instanceof WP_Post ) || 'qa' !== $post->post_type ) {
		return;
	}
	?>
	<h2 style="margin:8px 0 4px;font-size:15px;">回答（A）<span style="color:#b32d2e;">*</span></h2>
	<p class="description" style="margin:0 0 8px;">この質問に対する回答を、下の本文欄に入力してください。</p>
	<?php
}
add_action( 'edit_form_after_title', 'mrc_qa_answer_label' );

/**
 * Q&A編集画面ではエディター拡張（editor-expand：追従ツールバー＋自動リサイズ）を無効化する。
 * 拡張が有効だとツールバー分の高さを予約するため、ツールバー帯を隠すと予約分が空白として残る。
 * 無効化すると本文欄は素直な固定高になり、余分な空きが出ない。
 */
function mrc_qa_disable_editor_expand( $expand, $post_type ) {
	return ( 'qa' === $post_type ) ? false : $expand;
}
add_filter( 'wp_editor_expand', 'mrc_qa_disable_editor_expand', 10, 2 );

/**
 * Q&A編集画面では本文欄上部のツールバー帯（メディアを追加＋Visual/Textタブ）を隠す。
 * このエディタはタブが空のため、帯だけ残ると不自然な余白になる。帯ごと非表示にし、
 * 見出し（回答A）の直下に本文欄が来るようにする。書式ボタンはエディタ本体側なので残る。
 */
function mrc_qa_hide_media_button() {
	$screen = get_current_screen();
	if ( ! $screen || 'qa' !== $screen->post_type ) {
		return;
	}
	echo '<style>'
		. '#wp-content-editor-tools{display:none;}'      // メディア追加＋空タブの帯
		. '#titlediv{margin-bottom:0;}'                   // タイトル欄下の余白
		. '#titlediv .inside{margin-top:0;}'              // 新規時は空のパーマリンク枠の余白
		. '#edit-slug-box{margin-top:0;}'                 // 同上（枠内側）
		. '</style>';
}
add_action( 'admin_head', 'mrc_qa_hide_media_button' );

/* ============================================================
   種別（カテゴリー）を「単一選択（ラジオ）」にするメタボックス
   news_category / doc_category は meta_box_cb でこれを使う。
   ============================================================ */

/** ラジオボタンで1つだけ選べる種別メタボックス。 */
function mrc_radio_tax_meta_box( $post, $box ) {
	$taxonomy = isset( $box['args']['taxonomy'] ) ? $box['args']['taxonomy'] : '';
	$tax      = get_taxonomy( $taxonomy );
	if ( ! $tax ) {
		return;
	}
	$terms   = get_terms(
		array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
		)
	);
	$assigned = wp_get_object_terms( $post->ID, $taxonomy, array( 'fields' => 'ids' ) );
	$current  = ( ! is_wp_error( $assigned ) && ! empty( $assigned ) ) ? (int) $assigned[0] : 0;
	wp_nonce_field( 'mrc_radio_tax_' . $taxonomy, 'mrc_radio_tax_nonce_' . $taxonomy );
	echo '<ul class="mrc-radio-tax" style="margin:6px 0; max-height:220px; overflow:auto;">';
	printf(
		'<li><label><input type="radio" name="mrc_radio_tax[%1$s]" value="0" %2$s> （未選択）</label></li>',
		esc_attr( $taxonomy ),
		checked( 0, $current, false )
	);
	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term ) {
			printf(
				'<li><label><input type="radio" name="mrc_radio_tax[%1$s]" value="%2$d" %3$s> %4$s</label></li>',
				esc_attr( $taxonomy ),
				(int) $term->term_id,
				checked( (int) $term->term_id, $current, false ),
				esc_html( $term->name )
			);
		}
	}
	echo '</ul>';
	if ( current_user_can( $tax->cap->manage_terms ) ) {
		printf(
			'<p style="margin:8px 0 0;"><a href="%s">%s の追加・編集</a></p>',
			esc_url( admin_url( 'edit-tags.php?taxonomy=' . $taxonomy . '&post_type=' . $post->post_type ) ),
			esc_html( $tax->labels->name )
		);
	}
}

/** 単一選択の種別を保存（選択1つ、または未選択で解除）。 */
function mrc_save_radio_tax( $post_id ) {
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! isset( $_POST['mrc_radio_tax'] ) || ! is_array( $_POST['mrc_radio_tax'] ) ) {
		return;
	}
	foreach ( wp_unslash( $_POST['mrc_radio_tax'] ) as $taxonomy => $term_id ) {
		$taxonomy = sanitize_key( $taxonomy );
		$nonce    = isset( $_POST[ 'mrc_radio_tax_nonce_' . $taxonomy ] ) ? $_POST[ 'mrc_radio_tax_nonce_' . $taxonomy ] : '';
		if ( ! wp_verify_nonce( $nonce, 'mrc_radio_tax_' . $taxonomy ) || ! current_user_can( 'edit_post', $post_id ) || ! taxonomy_exists( $taxonomy ) ) {
			continue;
		}
		$term_id = (int) $term_id;
		wp_set_object_terms( $post_id, $term_id > 0 ? array( $term_id ) : array(), $taxonomy, false );
	}
}
add_action( 'save_post', 'mrc_save_radio_tax' );
