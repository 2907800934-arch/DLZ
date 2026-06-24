<?php
/**
 * Admin helper for setup guidance.
 *
 * @package FoamFormCommerceKit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Foam_Form_Commerce_Elementor_Docs {

	/**
	 * Singleton.
	 *
	 * @var Foam_Form_Commerce_Elementor_Docs|null
	 */
	protected static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return Foam_Form_Commerce_Elementor_Docs
	 */
	public static function instance() {
		if ( null === static::$instance ) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * Constructor.
	 */
	protected function __construct() {
		add_action( 'admin_menu', array( $this, 'register_admin_page' ) );
	}

	/**
	 * Register guide page under Appearance.
	 *
	 * @return void
	 */
	public function register_admin_page() {
		add_theme_page(
			__( 'Foam & Form Setup', 'foam-form-commerce-kit' ),
			__( 'Foam & Form Setup', 'foam-form-commerce-kit' ),
			'manage_options',
			'foam-form-setup',
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Render admin helper page.
	 *
	 * @return void
	 */
	public function render_admin_page() {
		$docs_url = content_url( 'plugins/foam-form-commerce-kit/docs/elementor-layout-guide.md' );
		echo '<div class="wrap"><h1>Foam & Form Setup</h1>';
		echo '<p>Use the Elementor layout guide and JSON sample data packaged with this plugin to finish the storefront quickly.</p>';
		echo '<p><a class="button button-primary" href="' . esc_url( $docs_url ) . '" target="_blank">Open Elementor Layout Guide</a></p>';
		echo '<p>Seed sample products via WP-CLI: <code>php wp-cli.phar foam-form seed --path=D:\\wordpress-7.0-zh_CN\\wordpress</code></p>';
		echo '</div>';
	}
}
