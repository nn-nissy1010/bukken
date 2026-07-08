<?php
/**
 * 共通ヘッダー
 *
 * @package mrc-residents
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header">
	<div class="site-header__inner">
		<p class="site-title">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<span class="tenant-name"><?php bloginfo( 'name' ); ?></span>
				<?php if ( get_bloginfo( 'description' ) ) : ?>
					<span class="site-subtitle"><?php bloginfo( 'description' ); ?></span>
				<?php endif; ?>
			</a>
		</p>

		<button class="nav-toggle" data-nav-toggle aria-label="メニューを開く" aria-expanded="false" aria-controls="global-nav">
			<span></span><span></span><span></span>
		</button>

		<nav class="global-nav" id="global-nav" data-global-nav aria-label="メインメニュー">
			<?php // ログイン前トップ（front-page）では常に公開メニューを表示する ?>
			<?php $mrc_public_zone = is_front_page() || is_page( 'contact-public' ); ?>
				<?php if ( is_user_logged_in() && ! $mrc_public_zone ) : ?>
				<?php
				if ( has_nav_menu( 'member' ) ) {
					wp_nav_menu(
						array(
							'theme_location' => 'member',
							'container'      => false,
							'fallback_cb'    => false,
						)
					);
				} else {
					?>
					<ul>
						<?php if ( mrc_page_menu_visible( 'news' ) ) : ?>
							<li><a href="<?php echo esc_url( get_post_type_archive_link( 'news' ) ); ?>">お知らせ</a></li>
						<?php endif; ?>
						<?php if ( mrc_page_menu_visible( 'plan' ) ) : ?>
							<li><a href="<?php echo esc_url( home_url( '/plan/' ) ); ?>">工事の計画</a></li>
						<?php endif; ?>
						<?php if ( mrc_page_menu_visible( 'document' ) ) : ?>
							<li><a href="<?php echo esc_url( get_post_type_archive_link( 'document' ) ); ?>">資料</a></li>
						<?php endif; ?>
						<?php if ( mrc_page_menu_visible( 'video' ) ) : ?>
							<li><a href="<?php echo esc_url( get_post_type_archive_link( 'video' ) ); ?>">動画</a></li>
						<?php endif; ?>
						<?php if ( mrc_page_menu_visible( 'qa' ) ) : ?>
							<li><a href="<?php echo esc_url( get_post_type_archive_link( 'qa' ) ); ?>">Q&amp;A</a></li>
						<?php endif; ?>
						<?php if ( mrc_page_menu_visible( 'contact' ) ) : ?>
							<li><a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">ご意見の窓口</a></li>
						<?php endif; ?>
						<li class="nav-logout"><a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>">ログアウト</a></li>
					</ul>
					<?php
				}
				?>
			<?php else : ?>
				<?php
				if ( has_nav_menu( 'public' ) ) {
					wp_nav_menu(
						array(
							'theme_location' => 'public',
							'container'      => false,
							'fallback_cb'    => false,
						)
					);
				} else {
					?>
					<ul>
						<li><a href="<?php echo esc_url( home_url( '/#first' ) ); ?>">はじめての方へ</a></li>
						<li><a href="<?php echo esc_url( home_url( '/#login' ) ); ?>">ログイン</a></li>
						<li><a href="<?php echo esc_url( home_url( '/contact-public/' ) ); ?>">お問い合わせ</a></li>
					</ul>
					<?php
				}
				?>
			<?php endif; ?>
		</nav>
	</div>
</header>
