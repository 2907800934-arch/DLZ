<?php
/**
 * Main plugin bootstrap.
 *
 * @package FoamFormCommerceKit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once FOAM_FORM_COMMERCE_PATH . 'includes/class-foam-form-commerce-setup.php';
require_once FOAM_FORM_COMMERCE_PATH . 'includes/class-foam-form-commerce-product-page.php';
require_once FOAM_FORM_COMMERCE_PATH . 'includes/class-foam-form-commerce-seeder.php';
require_once FOAM_FORM_COMMERCE_PATH . 'includes/class-foam-form-commerce-elementor-docs.php';

class Foam_Form_Commerce_Kit {

	/**
	 * Singleton instance.
	 *
	 * @var Foam_Form_Commerce_Kit|null
	 */
	protected static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return Foam_Form_Commerce_Kit
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
		add_action( 'plugins_loaded', array( $this, 'bootstrap' ) );
		register_activation_hook( FOAM_FORM_COMMERCE_FILE, array( 'Foam_Form_Commerce_Setup', 'activate' ) );
	}

	/**
	 * Boot feature classes.
	 *
	 * @return void
	 */
	public function bootstrap() {
		load_plugin_textdomain( 'foam-form-commerce-kit', false, dirname( plugin_basename( FOAM_FORM_COMMERCE_FILE ) ) . '/languages' );

		Foam_Form_Commerce_Setup::instance();
		Foam_Form_Commerce_Product_Page::instance();
		Foam_Form_Commerce_Seeder::instance();
		Foam_Form_Commerce_Elementor_Docs::instance();
	}
}
