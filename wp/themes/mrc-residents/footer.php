<?php
/**
 * 共通フッター
 *
 * @package mrc-residents
 */
?>
<footer class="site-footer">
	<div class="site-footer__inner">
		<p class="footer-brand"><?php bloginfo( 'name' ); ?></p>
		<nav class="footer-links" aria-label="フッターリンク">
			<a href="<?php echo esc_url( function_exists( 'mrc_privacy_url' ) ? mrc_privacy_url() : home_url( '/privacy/' ) ); ?>">プライバシーポリシー</a>
		</nav>
		<p class="copyright">
			運営：株式会社MRC ｜ &copy; <?php echo esc_html( date_i18n( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>
		</p>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
