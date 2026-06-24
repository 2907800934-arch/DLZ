<?php
/**
 * Plugin Name: sonovafurn Commerce Kit
 * Description: Conversion-focused WooCommerce enhancements for the sonovafurn compressed sofa and mattress storefront.
 * Version: 1.1.0
 * Author: Codex
 * Text Domain: foam-form-commerce-kit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FOAM_FORM_COMMERCE_VERSION', '1.0.0' );
define( 'FOAM_FORM_COMMERCE_FILE', __FILE__ );
define( 'FOAM_FORM_COMMERCE_PATH', plugin_dir_path( __FILE__ ) );
define( 'FOAM_FORM_COMMERCE_URL', plugin_dir_url( __FILE__ ) );

require_once FOAM_FORM_COMMERCE_PATH . 'includes/class-foam-form-commerce-kit.php';

Foam_Form_Commerce_Kit::instance();
