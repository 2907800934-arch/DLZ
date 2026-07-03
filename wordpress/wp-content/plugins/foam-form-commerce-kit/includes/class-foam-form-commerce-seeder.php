<?php
/**
 * Product seeding and page creation.
 *
 * @package FoamFormCommerceKit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Foam_Form_Commerce_Seeder {

	/**
	 * Singleton.
	 *
	 * @var Foam_Form_Commerce_Seeder|null
	 */
	protected static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return Foam_Form_Commerce_Seeder
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
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			$this->maybe_register_cli_commands();
		}
	}

	/**
	 * Resolve upload-based asset path.
	 *
	 * @param string $filename Upload filename.
	 * @return string
	 */
	protected function get_upload_asset_path( $filename ) {
		$uploads = wp_get_upload_dir();

		if ( empty( $uploads['basedir'] ) ) {
			return '';
		}

		return trailingslashit( $uploads['basedir'] ) . '2026/07/' . ltrim( $filename, '/' );
	}

	/**
	 * Ensure a file exists in the media library and return its attachment ID.
	 *
	 * @param string $filename Upload filename.
	 * @return int
	 */
	protected function ensure_product_attachment( $filename ) {
		$file_path = $this->get_upload_asset_path( $filename );

		if ( ! $file_path || ! file_exists( $file_path ) ) {
			return 0;
		}

		$attachment = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => 1,
				'meta_key'       => '_wp_attached_file',
				'meta_value'     => '2026/07/' . $filename,
				'fields'         => 'ids',
			)
		);

		if ( ! empty( $attachment ) ) {
			return (int) $attachment[0];
		}

		$filetype = wp_check_filetype( basename( $file_path ), null );
		$uploads  = wp_get_upload_dir();
		$relative = '2026/07/' . $filename;

		$attachment_id = wp_insert_attachment(
			array(
				'post_mime_type' => $filetype['type'],
				'post_title'     => sanitize_text_field( pathinfo( $filename, PATHINFO_FILENAME ) ),
				'post_content'   => '',
				'post_status'    => 'inherit',
				'guid'           => trailingslashit( $uploads['baseurl'] ) . $relative,
			),
			$file_path
		);

		if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
			return 0;
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';

		update_post_meta( $attachment_id, '_wp_attached_file', $relative );
		$metadata = wp_generate_attachment_metadata( $attachment_id, $file_path );

		if ( ! empty( $metadata ) ) {
			wp_update_attachment_metadata( $attachment_id, $metadata );
		}

		return (int) $attachment_id;
	}

	/**
	 * Register WP-CLI commands when available.
	 *
	 * @return void
	 */
	public function maybe_register_cli_commands() {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::add_command( 'foam-form seed', array( $this, 'seed_store' ) );
		}
	}

	/**
	 * Seed store pages, categories, shipping classes and sample products.
	 *
	 * @return void
	 */
	public function seed_store( $args = array(), $assoc_args = array() ) {
		if ( ! class_exists( 'WooCommerce' ) ) {
			if ( class_exists( 'WP_CLI' ) ) {
				WP_CLI::error( 'WooCommerce is required before seeding.' );
			}
			return;
		}

		$this->create_core_pages();
		$this->create_categories();
		$this->configure_shipping();
		$this->create_sample_products();

		if ( class_exists( 'WP_CLI' ) ) {
			WP_CLI::success( 'sonovafurn compressed sofa store created.' );
		}
	}

	/**
	 * Create core pages and assign WooCommerce pages.
	 *
	 * @return void
	 */
	protected function create_core_pages() {
		$pages = array(
			'Home'              => '[foam_form_home_sections]',
			'Shop'              => '[foam_form_shop_intro][products paginate="true" columns="3" limit="12"]',
			'About Us'          => '<h1>About sonovafurn</h1><p>sonovafurn is a minimalist furniture brand focused on compressed sofa design, sustainable comfort materials, and modern living solutions for smaller, brighter homes.</p><h2>Design Philosophy</h2><p>Our direction blends Japandi calm, Scandinavian function, and premium white-studio clarity. Every silhouette is designed to feel lighter in the room and easier to bring home.</p><h2>Sustainability</h2><p>We prioritize compact shipping, durable foam structures, and room-friendly modular thinking to reduce moving friction and extend product life.</p><h2>Factory & Technology</h2><p>Compression technology, foam recovery testing, and comfort-focused finishing are central to our product development process.</p>',
			'Blog'              => '<h1>Blog</h1><h2>Space Design</h2><p><a href="/small-apartment-living-guide/">Small Apartment Living Guide</a></p><p><a href="/minimalist-living-room-ideas/">Minimalist Living Room Ideas</a></p><h2>Product Comparison</h2><p><a href="/compressed-sofa-vs-traditional-sofa/">Compressed Sofa vs Traditional Sofa</a></p><p><a href="/best-sofa-beds-2026/">Best Sofa Beds 2026</a></p><h2>Buying Guides</h2><p><a href="/how-to-choose-a-sofa-for-small-space/">How to Choose a Sofa for Small Space</a></p>',
			'Contact'           => '<h1>Contact</h1><p>Email: support@sonovafurn.com</p><p>Support hours: Monday-Friday, 9am-6pm PT</p><p>Need help with shipping, returns, or choosing a compressed sofa? Send us a message below.</p>[contact-form-placeholder]',
			'FAQ'               => '<h1>FAQ</h1><h2>Shipping Time</h2><p>Most sofa orders dispatch within 1-2 business days, with delivery timing depending on destination.</p><h2>Return Policy</h2><p>Returns are accepted within 30 days in original condition.</p><h2>Compression Safety</h2><p>Our compressed shipping format is designed to protect foam structure during transit and expansion.</p><h2>Warranty</h2><p>Most compressed sofas include a 1-year limited warranty.</p>',
			'Shipping Policy'   => '<h1>Shipping &amp; Delivery</h1><p>We offer fast, affordable, flat-rate delivery on every order through standard carrier networks such as UPS and FedEx whenever possible.</p>',
			'Return Policy'     => '<h1>Return Policy</h1><p>Returns are accepted within 30 days of delivery for products in original condition. Please contact support@sonovafurn.com before returning a compressed sofa or mattress.</p>',
			'Privacy Policy'    => '<h1>Privacy Policy</h1><p>We only use customer information to support checkout, shipping communication, and customer care. This placeholder page is ready for your full production policy copy.</p>',
			'Terms of Service'  => '<h1>Terms of Service</h1><p>By purchasing from sonovafurn, customers agree to our policies around order fulfillment, returns, warranties, and safe product use. This page is ready for your legal copy.</p>',
		);

		$page_ids = array();
		foreach ( $pages as $title => $content ) {
			$existing = get_page_by_title( $title );

			if ( $existing ) {
				wp_update_post(
					array(
						'ID'           => $existing->ID,
						'post_content' => $content,
					)
				);
				$page_ids[ $title ] = $existing->ID;
				continue;
			}

			$page_ids[ $title ] = wp_insert_post(
				array(
					'post_title'   => $title,
					'post_content' => $content,
					'post_status'  => 'publish',
					'post_type'    => 'page',
				)
			);
		}

		if ( isset( $page_ids['Home'] ) ) {
			update_option( 'page_on_front', $page_ids['Home'] );
			update_option( 'show_on_front', 'page' );
		}

		if ( isset( $page_ids['Shop'] ) ) {
			update_option( 'woocommerce_shop_page_id', $page_ids['Shop'] );
		}
	}

	/**
	 * Create product categories.
	 *
	 * @return void
	 */
	protected function create_categories() {
		$categories = array(
			'compressed-sofa-beds'   => 'Compressed Sofa Beds',
			'space-saving-sofas'     => 'Space Saving Sofas',
			'modular-sofas'          => 'Modular Sofas',
			'sofa-in-a-box'          => 'Sofa in a Box',
			'convertible-sofa-beds'  => 'Convertible Sofa Beds',
			'mattresses'             => 'Mattresses',
		);

		foreach ( $categories as $slug => $name ) {
			if ( ! term_exists( $slug, 'product_cat' ) ) {
				wp_insert_term( $name, 'product_cat', array( 'slug' => $slug ) );
			}
		}

		if ( ! term_exists( 'best-seller', 'product_badge' ) ) {
			wp_insert_term( 'Best Seller', 'product_badge', array( 'slug' => 'best-seller' ) );
		}
	}

	/**
	 * Configure shipping methods.
	 *
	 * @return void
	 */
	protected function configure_shipping() {
		$zone_id = 0;
		foreach ( WC_Shipping_Zones::get_zones() as $zone_data ) {
			if ( isset( $zone_data['zone_name'] ) && 'United States' === $zone_data['zone_name'] ) {
				$zone_id = (int) $zone_data['id'];
				break;
			}
		}

		if ( ! $zone_id ) {
			$zone = new WC_Shipping_Zone();
			$zone->set_zone_name( 'United States' );
			$zone->set_zone_order( 0 );
			$zone->add_location( 'US', 'country' );
			$zone_id = $zone->save();
		}

		$zone    = new WC_Shipping_Zone( $zone_id );
		$methods = $zone->get_shipping_methods( true );

		if ( empty( $methods ) ) {
			$zone->add_shipping_method( 'free_shipping' );
			$zone->add_shipping_method( 'flat_rate' );
			$methods = $zone->get_shipping_methods( true );
		}

		foreach ( $methods as $method ) {
			if ( 'free_shipping' === $method->id ) {
				$method->instance_settings['requires']   = 'min_amount';
				$method->instance_settings['min_amount'] = '50';
				$method->instance_settings['title']      = 'Free shipping';
				$method->process_admin_options();
			}

			if ( 'flat_rate' === $method->id ) {
				$method->instance_settings['title'] = 'Standard shipping';
				$method->instance_settings['cost']  = '9.99';
				$method->process_admin_options();
			}
		}
	}

	/**
	 * Create sample WooCommerce products from JSON data.
	 *
	 * @return void
	 */
	protected function create_sample_products() {
		$data_file = FOAM_FORM_COMMERCE_PATH . 'data/sample-products.json';
		if ( ! file_exists( $data_file ) ) {
			return;
		}

		$products = json_decode( file_get_contents( $data_file ), true );
		if ( empty( $products ) || ! is_array( $products ) ) {
			return;
		}

		foreach ( $products as $item ) {
			$existing = isset( $item['slug'] ) ? get_page_by_path( $item['slug'], OBJECT, 'product' ) : null;
			$product  = null;

			if ( $existing ) {
				$product = wc_get_product( $existing->ID );
			}

			if ( ! $product ) {
				$product = new WC_Product_Simple();
			}

			$product->set_name( $item['name'] );
			$product->set_status( 'publish' );
			$product->set_catalog_visibility( 'visible' );
			$product->set_price( ! empty( $item['sale_price'] ) ? $item['sale_price'] : $item['price'] );
			$product->set_regular_price( $item['price'] );
			$product->set_sale_price( ! empty( $item['sale_price'] ) ? $item['sale_price'] : '' );
			$product->set_short_description( $item['short_description'] );
			$product->set_description( $item['description'] );
			$product->set_manage_stock( false );
			$product->set_reviews_allowed( true );

			if ( ! empty( $item['sku'] ) ) {
				$sku_owner_id = wc_get_product_id_by_sku( $item['sku'] );
				if ( ! $sku_owner_id || (int) $sku_owner_id === (int) $product->get_id() ) {
					$product->set_sku( $item['sku'] );
				}
			}

			if ( ! empty( $item['slug'] ) ) {
				$product->set_slug( $item['slug'] );
			}

			if ( isset( $item['menu_order'] ) ) {
				$product->set_menu_order( (int) $item['menu_order'] );
			}

			$product_id = $product->save();

			if ( ! empty( $item['categories'] ) ) {
				wp_set_object_terms( $product_id, $item['categories'], 'product_cat' );
			}

			if ( ! empty( $item['badges'] ) ) {
				wp_set_object_terms( $product_id, $item['badges'], 'product_badge' );
			}

			update_post_meta( $product_id, '_foam_density', $item['foam_density'] );
			update_post_meta( $product_id, '_foam_fabric', $item['fabric'] );
			update_post_meta( $product_id, '_foam_durability', $item['durability'] );
			update_post_meta( $product_id, '_foam_feature_bullets', $item['feature_bullets'] );
			update_post_meta( $product_id, '_foam_dimensions', $item['dimensions'] );
			update_post_meta( $product_id, '_foam_weight', $item['weight'] );
			update_post_meta( $product_id, '_foam_colors', $item['colors'] );
			update_post_meta( $product_id, '_foam_warranty', $item['warranty'] );
			update_post_meta( $product_id, '_foam_highlights', $item['highlights'] );
			update_post_meta( $product_id, '_foam_faq', isset( $item['faq'] ) ? $item['faq'] : array() );

			if ( ! empty( $item['image'] ) ) {
				$attachment_id = $this->ensure_product_attachment( $item['image'] );
				if ( $attachment_id ) {
					set_post_thumbnail( $product_id, $attachment_id );
					$product->set_image_id( $attachment_id );
					$product->save();
				}
			}
		}
	}
}
