<?php
/**
 * Store configuration bootstrap.
 *
 * @package FoamFormCommerceKit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Foam_Form_Commerce_Setup {

	/**
	 * Singleton.
	 *
	 * @var Foam_Form_Commerce_Setup|null
	 */
	protected static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return Foam_Form_Commerce_Setup
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
		add_action( 'init', array( $this, 'register_product_badge_taxonomy' ) );
		add_action( 'init', array( $this, 'maybe_sync_brand_pages' ), 25 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp', array( $this, 'normalize_woocommerce_archive_hooks' ), 20 );
		add_filter( 'body_class', array( $this, 'body_classes' ) );
		add_filter( 'the_content', array( $this, 'render_editorial_content_pages' ), 20 );
		add_filter( 'the_title', array( $this, 'filter_editorial_page_title' ), 20, 2 );
		add_filter( 'pre_get_document_title', array( $this, 'filter_pre_document_title' ) );
		add_filter( 'document_title_parts', array( $this, 'filter_document_title_parts' ) );
		add_filter( 'wp_list_pages', array( $this, 'filter_page_list_titles' ) );
		add_filter( 'woocommerce_currency', array( $this, 'force_store_currency' ) );
		add_filter( 'woocommerce_product_tabs', array( $this, 'adjust_product_tabs' ), 98 );
		add_filter( 'woocommerce_checkout_fields', array( $this, 'streamline_checkout_fields' ) );
		add_filter( 'woocommerce_states', array( $this, 'filter_states' ) );
		add_filter( 'woocommerce_available_payment_gateways', array( $this, 'mark_future_gateways' ) );
		add_filter( 'woocommerce_loop_add_to_cart_link', array( $this, 'refine_loop_add_to_cart_link' ), 10, 3 );
		add_filter( 'woocommerce_default_catalog_orderby', array( $this, 'set_catalog_orderby' ) );
		add_filter( 'woocommerce_get_catalog_ordering_args', array( $this, 'set_catalog_ordering_args' ), 20 );
		add_filter( 'get_block_templates', array( $this, 'disable_woo_block_templates' ), 100, 3 );
		add_filter( 'get_the_archive_description', array( $this, 'filter_store_archive_description' ), 20 );
		add_filter( 'wp_get_attachment_image_attributes', array( $this, 'optimize_attachment_image_attributes' ), 20, 3 );
		add_action( 'wp_head', array( $this, 'inject_performance_hints' ), 2 );
		add_action( 'wp_head', array( $this, 'inject_brand_meta' ) );
		add_action( 'after_setup_theme', array( $this, 'register_image_sizes' ) );
		add_shortcode( 'foam_form_home_sections', array( $this, 'render_home_sections' ) );
		add_shortcode( 'foam_form_shop_intro', array( $this, 'render_shop_intro' ) );
		add_shortcode( 'contact-form-placeholder', array( $this, 'render_contact_form_placeholder' ) );
		add_shortcode( 'foam_form_ai_recommendations', array( $this, 'render_ai_recommendations' ) );
		add_action( 'template_redirect', array( $this, 'capture_recently_viewed_products' ) );
		add_action( 'woocommerce_before_main_content', array( $this, 'render_store_breadcrumbs' ), 6 );
		add_action( 'woocommerce_before_main_content', array( $this, 'render_shop_intro_hook' ), 8 );
		add_action( 'wp_footer', array( $this, 'render_exit_popup' ) );
	}

	/**
	 * Prevent duplicate WooCommerce archive chrome.
	 *
	 * @return void
	 */
	public function normalize_woocommerce_archive_hooks() {
		if ( ! function_exists( 'is_shop' ) ) {
			return;
		}

		if ( is_shop() || is_product() || is_product_taxonomy() ) {
			remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
			remove_action( 'woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10 );
			remove_action( 'woocommerce_archive_description', 'woocommerce_product_archive_description', 10 );
		}
	}

	/**
	 * Filter branded editorial page titles on the front end.
	 *
	 * @param string $title   Page title.
	 * @param int    $post_id Post ID.
	 * @return string
	 */
	public function filter_editorial_page_title( $title, $post_id = 0 ) {
		if ( is_admin() || ! $post_id ) {
			return $title;
		}

		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post || 'page' !== $post->post_type ) {
			return $title;
		}

		if ( 'shipping-policy' === $post->post_name ) {
			return 'Shipping & Delivery';
		}

		if ( 'terms-of-service' === $post->post_name ) {
			return 'Terms & Conditions';
		}

		return $title;
	}

	/**
	 * Filter the browser title for curated policy pages.
	 *
	 * @param array $title_parts Title parts.
	 * @return array
	 */
	public function filter_document_title_parts( $title_parts ) {
		if ( is_page( 'shipping-policy' ) ) {
			$title_parts['title'] = 'Shipping & Delivery';
		}

		if ( is_page( 'terms-of-service' ) ) {
			$title_parts['title'] = 'Terms & Conditions';
		}

		return $title_parts;
	}

	/**
	 * Filter the full browser title when SEO plugins do not use title parts.
	 *
	 * @param string $title Title value.
	 * @return string
	 */
	public function filter_pre_document_title( $title ) {
		if ( is_page( 'shipping-policy' ) ) {
			return 'Shipping & Delivery - Sonovafurn';
		}

		if ( is_page( 'terms-of-service' ) ) {
			return 'Terms & Conditions - Sonovafurn';
		}

		return $title;
	}

	/**
	 * Normalize fallback page-list labels.
	 *
	 * @param string $output Page-list HTML.
	 * @return string
	 */
	public function filter_page_list_titles( $output ) {
		if ( false !== strpos( $output, 'Shipping Policy' ) ) {
			$output = str_replace( 'Shipping Policy', 'Shipping &amp; Delivery', $output );
		}

		if ( false !== strpos( $output, 'Terms and Conditions' ) ) {
			$output = str_replace( 'Terms and Conditions', 'Terms &amp; Conditions', $output );
		}

		return $output;
	}

	/**
	 * Ensure essential branded pages exist after migrations or redeployments.
	 *
	 * @return void
	 */
	public function maybe_sync_brand_pages() {
		if ( ! function_exists( 'wp_insert_post' ) ) {
			return;
		}

		$sync_version = '2026-07-03-home-legal-sync';
		if ( get_option( 'foam_form_core_pages_version' ) === $sync_version ) {
			return;
		}

		$pages = array(
			'home' => array(
				'title'   => 'Home',
				'content' => '[foam_form_home_sections]',
			),
			'about-us' => array(
				'title'   => 'About Us',
				'content' => '<h1>About sonovafurn</h1><p>sonovafurn is a minimalist furniture brand focused on compressed sofa design, sustainable comfort materials, and modern living solutions for smaller, brighter homes.</p><h2>Design Philosophy</h2><p>Our direction blends Japandi calm, Scandinavian function, and premium white-studio clarity. Every silhouette is designed to feel lighter in the room and easier to bring home.</p><h2>Sustainability</h2><p>We prioritize compact shipping, durable foam structures, and room-friendly modular thinking to reduce moving friction and extend product life.</p><h2>Factory & Technology</h2><p>Compression technology, foam recovery testing, and comfort-focused finishing are central to our product development process.</p>',
			),
			'blog' => array(
				'title'   => 'Blog',
				'content' => '<h1>Blog</h1><p>Editorial notes on room planning, compressed furniture, and more practical buying decisions.</p>',
			),
			'contact' => array(
				'title'   => 'Contact',
				'content' => '<h1>Contact</h1><p>Email: support@sonovafurn.com</p><p>Support hours: Monday-Friday, 9am-6pm PT</p><p>Need help with shipping, returns, or choosing a compressed sofa? Send us a message below.</p>[contact-form-placeholder]',
			),
			'faq' => array(
				'title'   => 'FAQ',
				'content' => '<h1>FAQ</h1><p>Shipping, setup, compression safety, and returns explained simply.</p>',
			),
		);

		$home_page_id = 0;

		foreach ( $pages as $slug => $page_data ) {
			$page = get_page_by_path( $slug );
			if ( ! $page ) {
				$page = get_page_by_title( $page_data['title'] );
			}

			$postarr = array(
				'post_title'   => $page_data['title'],
				'post_content' => $page_data['content'],
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_name'    => $slug,
			);

			if ( $page instanceof WP_Post ) {
				$postarr['ID'] = $page->ID;
				$page_id       = wp_update_post( $postarr, true );
			} else {
				$page_id = wp_insert_post( $postarr, true );
			}

			if ( ! is_wp_error( $page_id ) && 'home' === $slug ) {
				$home_page_id = (int) $page_id;
			}
		}

		if ( $home_page_id > 0 ) {
			update_option( 'show_on_front', 'page' );
			update_option( 'page_on_front', $home_page_id );
		}

		update_option( 'foam_form_core_pages_version', $sync_version );
	}

	/**
	 * Resolve a product category archive URL from slug.
	 *
	 * @param string $slug Product category slug.
	 * @return string
	 */
	protected function get_product_category_link( $slug ) {
		$term = get_term_by( 'slug', $slug, 'product_cat' );
		if ( $term && ! is_wp_error( $term ) ) {
			$link = get_term_link( $term, 'product_cat' );
			if ( ! is_wp_error( $link ) ) {
				return $link;
			}
		}

		return home_url( '/shop/' );
	}

	/**
	 * Get Sonovafurn editorial asset URL.
	 *
	 * @param string $filename File name inside uploads/2026/07.
	 * @return string
	 */
	protected function get_editorial_asset_url( $filename ) {
		$uploads = wp_get_upload_dir();

		if ( empty( $uploads['baseurl'] ) ) {
			return '';
		}

		return trailingslashit( $uploads['baseurl'] ) . '2026/07/' . ltrim( $filename, '/' );
	}

	/**
	 * Get curated shop intro filter metadata.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	protected function get_shop_intro_filters() {
		return array(
			'compressed-sofa-beds' => array(
				'label'       => __( 'Sofa Beds', 'foam-form-commerce-kit' ),
				'description' => __( 'Convertible seating for guest-ready rooms and smaller apartments.', 'foam-form-commerce-kit' ),
				'keywords'    => array( 'sofa', 'sofa bed', 'sleeper', 'convertible', 'guest' ),
			),
			'mattresses'           => array(
				'label'       => __( 'Memory Foam', 'foam-form-commerce-kit' ),
				'description' => __( 'Layered support pieces shaped around everyday rest and pressure relief.', 'foam-form-commerce-kit' ),
				'keywords'    => array( 'mattress', 'memory foam', 'foam', 'sleep', 'bed' ),
			),
			'space-saving-sofas'   => array(
				'label'       => __( 'Small-Space Seating', 'foam-form-commerce-kit' ),
				'description' => __( 'Compact silhouettes planned for circulation, entries, and tighter layouts.', 'foam-form-commerce-kit' ),
				'keywords'    => array( 'small', 'space', 'compact', 'apartment', 'loveseat', 'chair' ),
			),
			'modular-sofas'        => array(
				'label'       => __( 'Living Room Systems', 'foam-form-commerce-kit' ),
				'description' => __( 'More flexible formats for rooms that need to change use over time.', 'foam-form-commerce-kit' ),
				'keywords'    => array( 'modular', 'sectional', 'living room', 'ottoman', 'system' ),
			),
		);
	}

	/**
	 * Check whether the current request is a product search context.
	 *
	 * @return bool
	 */
	protected function is_product_search_context() {
		if ( ! is_search() ) {
			return false;
		}

		$post_type = get_query_var( 'post_type' );

		if ( empty( $post_type ) && isset( $_GET['post_type'] ) ) {
			$post_type = wp_unslash( $_GET['post_type'] );
		}

		if ( is_array( $post_type ) ) {
			return in_array( 'product', $post_type, true );
		}

		return 'product' === $post_type;
	}

	/**
	 * Match the current archive or search context to a shop intro filter.
	 *
	 * @param string $value   Source value to inspect.
	 * @param array  $filters Available filter metadata.
	 * @return string
	 */
	protected function match_shop_intro_filter( $value, $filters ) {
		$value      = trim( wp_strip_all_tags( (string) $value ) );
		$raw_value  = strtolower( $value );
		$slug_value = sanitize_title( $value );

		if ( '' === $raw_value && '' === $slug_value ) {
			return '';
		}

		foreach ( $filters as $slug => $filter ) {
			$candidates = array( $slug );

			if ( ! empty( $filter['label'] ) ) {
				$candidates[] = $filter['label'];
			}

			if ( ! empty( $filter['keywords'] ) && is_array( $filter['keywords'] ) ) {
				$candidates = array_merge( $candidates, $filter['keywords'] );
			}

			foreach ( $candidates as $candidate ) {
				$candidate      = wp_strip_all_tags( (string) $candidate );
				$candidate_raw  = strtolower( $candidate );
				$candidate_slug = sanitize_title( $candidate );

				if ( '' !== $candidate_raw && false !== strpos( $raw_value, $candidate_raw ) ) {
					return $slug;
				}

				if ( '' !== $candidate_slug && false !== strpos( $slug_value, $candidate_slug ) ) {
					return $slug;
				}
			}
		}

		return '';
	}

	/**
	 * Resolve the current shop intro label and active filter.
	 *
	 * @param array $filters Available filter metadata.
	 * @return array<string, string>
	 */
	protected function get_shop_intro_context( $filters ) {
		$active_slug = 'compressed-sofa-beds';

		if ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) {
			$term = get_queried_object();

			if ( $term instanceof WP_Term ) {
				if ( isset( $filters[ $term->slug ] ) ) {
					$active_slug = $term->slug;
				} else {
					$matched_slug = $this->match_shop_intro_filter( $term->slug . ' ' . $term->name, $filters );

					if ( '' !== $matched_slug ) {
						$active_slug = $matched_slug;
					}
				}
			}
		} elseif ( $this->is_product_search_context() ) {
			$matched_slug = $this->match_shop_intro_filter( get_search_query(), $filters );

			if ( '' !== $matched_slug ) {
				$active_slug = $matched_slug;
			}
		}

		$label = isset( $filters[ $active_slug ]['label'] ) ? (string) $filters[ $active_slug ]['label'] : __( 'Sofa Beds', 'foam-form-commerce-kit' );

		return array(
			'active_slug' => $active_slug,
			'kicker'      => $label,
		);
	}

	/**
	 * Activation routine.
	 *
	 * @return void
	 */
	public static function activate() {
		update_option( 'woocommerce_currency', 'USD' );
		update_option( 'woocommerce_calc_taxes', 'no' );
		update_option( 'woocommerce_default_country', 'US:CA' );
		update_option( 'woocommerce_weight_unit', 'lb' );
		update_option( 'woocommerce_dimension_unit', 'in' );
		update_option( 'woocommerce_enable_reviews', 'yes' );
		update_option( 'woocommerce_enable_review_rating', 'yes' );
		update_option( 'woocommerce_review_rating_required', 'yes' );
		update_option( 'woocommerce_enable_ajax_add_to_cart', 'yes' );
		update_option( 'blogname', 'sonovafurn' );
		update_option( 'blogdescription', 'Affordable luxury sofa beds and memory foam furniture for modern small-space living.' );
		update_option( 'timezone_string', 'America/Los_Angeles' );
		update_option( 'permalink_structure', '/%postname%/' );
		update_option( 'show_on_front', 'page' );
		update_option(
			'woocommerce_permalinks',
			array(
				'product_base'           => '/product',
				'category_base'          => '/category',
				'tag_base'               => '/tag',
				'attribute_base'         => '/filter',
				'use_verbose_page_rules' => false,
			)
		);

		$instance = static::instance();
		$instance->register_product_badge_taxonomy();
		flush_rewrite_rules();
	}

	/**
	 * Register custom product badge taxonomy.
	 *
	 * @return void
	 */
	public function register_product_badge_taxonomy() {
		register_taxonomy(
			'product_badge',
			'product',
			array(
				'label'             => __( 'Product Badges', 'foam-form-commerce-kit' ),
				'public'            => false,
				'show_ui'           => true,
				'show_admin_column' => true,
				'hierarchical'      => false,
				'show_in_rest'      => true,
			)
		);
	}

	/**
	 * Enqueue shared assets.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		$style_path  = FOAM_FORM_COMMERCE_PATH . 'assets/css/commerce-kit.css';
		$script_path = FOAM_FORM_COMMERCE_PATH . 'assets/js/commerce-kit.js';

		wp_enqueue_style(
			'foam-form-commerce-kit',
			FOAM_FORM_COMMERCE_URL . 'assets/css/commerce-kit.css',
			array(),
			file_exists( $style_path ) ? (string) filemtime( $style_path ) : FOAM_FORM_COMMERCE_VERSION
		);

		wp_enqueue_script(
			'foam-form-commerce-kit',
			FOAM_FORM_COMMERCE_URL . 'assets/js/commerce-kit.js',
			array(),
			file_exists( $script_path ) ? (string) filemtime( $script_path ) : FOAM_FORM_COMMERCE_VERSION,
			true
		);

		wp_script_add_data( 'foam-form-commerce-kit', 'strategy', 'defer' );
		wp_script_add_data( 'foam-form-commerce-kit', 'defer', true );

		wp_localize_script(
			'foam-form-commerce-kit',
			'foamFormCommerce',
			array(
				'currencySymbol'   => get_woocommerce_currency_symbol(),
				'stickyCartText'   => __( 'Review options', 'foam-form-commerce-kit' ),
				'popupSuccessText' => __( 'Thank you. Your note has been saved for future email updates.', 'foam-form-commerce-kit' ),
			)
		);
	}

	/**
	 * Tune attachment loading for product-first templates.
	 *
	 * @param array        $attr       Image attributes.
	 * @param WP_Post      $attachment Attachment object.
	 * @param string|array $size       Requested size.
	 * @return array
	 */
	public function optimize_attachment_image_attributes( $attr, $attachment, $size ) {
		if ( is_admin() ) {
			return $attr;
		}

		$attr['decoding'] = 'async';

		if ( function_exists( 'is_product' ) && is_product() ) {
			static $boosted_product_image = false;
			$class = isset( $attr['class'] ) ? (string) $attr['class'] : '';

			if ( ! $boosted_product_image && false !== strpos( $class, 'wp-post-image' ) ) {
				$attr['loading']       = 'eager';
				$attr['fetchpriority'] = 'high';
				$boosted_product_image = true;
			}
		}

		return $attr;
	}

	/**
	 * Add body classes.
	 *
	 * @param array $classes Body classes.
	 * @return array
	 */
	public function body_classes( $classes ) {
		$classes[] = 'foam-form-storefront';

		if ( is_page() ) {
			$page = get_queried_object();
			if ( $page instanceof WP_Post ) {
				$policy_pages = array(
					'shipping-policy',
					'return-policy',
					'privacy-policy-2',
					'terms-of-service',
				);

				$editorial_pages = array(
					'about-us',
					'blog',
					'faq',
					'contact',
				);

				$editorial_pages = array_merge( $editorial_pages, $policy_pages );

				if ( in_array( $page->post_name, $editorial_pages, true ) ) {
					$classes[] = 'foam-editorial-template';
				}

				if ( in_array( $page->post_name, $policy_pages, true ) ) {
					$classes[] = 'foam-policy-template';
				}

				if ( 'shipping-policy' === $page->post_name ) {
					$classes[] = 'foam-policy-shipping';
				}
			}
		}

		return $classes;
	}

	/**
	 * Force USD store currency.
	 *
	 * @param string $currency Currency code.
	 * @return string
	 */
	public function force_store_currency( $currency ) {
		return 'USD';
	}

	/**
	 * Adjust product tabs.
	 *
	 * @param array $tabs Product tabs.
	 * @return array
	 */
	public function adjust_product_tabs( $tabs ) {
		if ( isset( $tabs['additional_information'] ) ) {
			$tabs['additional_information']['title'] = __( 'Specifications', 'foam-form-commerce-kit' );
		}

		return $tabs;
	}

	/**
	 * Simplify checkout fields for mobile-first UX.
	 *
	 * @param array $fields Checkout fields.
	 * @return array
	 */
	public function streamline_checkout_fields( $fields ) {
		if ( isset( $fields['billing']['billing_company'] ) ) {
			unset( $fields['billing']['billing_company'] );
		}

		if ( isset( $fields['order']['order_comments'] ) ) {
			$fields['order']['order_comments']['placeholder'] = __( 'Delivery notes or building access details', 'foam-form-commerce-kit' );
		}

		return $fields;
	}

	/**
	 * Limit state listings to US only when store base country is US.
	 *
	 * @param array $states States list.
	 * @return array
	 */
	public function filter_states( $states ) {
		if ( isset( $states['US'] ) ) {
			return array( 'US' => $states['US'] );
		}

		return $states;
	}

	/**
	 * Add descriptive titles to placeholder gateways.
	 *
	 * @param array $gateways Gateways.
	 * @return array
	 */
	public function mark_future_gateways( $gateways ) {
		foreach ( $gateways as $gateway ) {
			if ( is_object( $gateway ) && in_array( $gateway->id, array( 'stripe', 'paypal' ), true ) ) {
				$gateway->description .= ' ' . __( 'Configured for future live payments.', 'foam-form-commerce-kit' );
			}
		}

		return $gateways;
	}

	/**
	 * Replace homepage loop add-to-cart buttons with quieter product links.
	 *
	 * @param string     $html Original link HTML.
	 * @param WC_Product $product Product object.
	 * @param array      $args Link args.
	 * @return string
	 */
	public function refine_loop_add_to_cart_link( $html, $product, $args ) {
		if ( ! ( is_front_page() || is_home() || is_shop() || is_product_taxonomy() ) ) {
			return $html;
		}

		if ( ! $product instanceof WC_Product ) {
			return $html;
		}

		return sprintf(
			'<a class="foam-product-link" href="%1$s">%2$s</a>',
			esc_url( get_permalink( $product->get_id() ) ),
			esc_html__( 'View details', 'foam-form-commerce-kit' )
		);
	}

	/**
	 * Use manual catalog ordering by default.
	 *
	 * @param string $orderby Current default order.
	 * @return string
	 */
	public function set_catalog_orderby( $orderby ) {
		return 'menu_order';
	}

	/**
	 * Force product archives to respect manual sequence.
	 *
	 * @param array $args Catalog ordering args.
	 * @return array
	 */
	public function set_catalog_ordering_args( $args ) {
		$args['orderby'] = 'menu_order title';
		$args['order']   = 'ASC';

		if ( empty( $args['meta_key'] ) ) {
			$args['meta_key'] = '';
		}

		return $args;
	}

	/**
	 * Resolve curated best seller products for the homepage edit.
	 *
	 * @param int $limit Number of products to load.
	 * @return WC_Product[]
	 */
	protected function get_best_seller_products( $limit = 3 ) {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return array();
		}

		$limit       = max( 1, (int) $limit );
		$curated_slugs = array(
			'sonovafurn-compression-sleeper-sofa',
			'sonovafurn-modular-compression-sofa',
			'sonovafurn-corner-modular-sofa',
		);
		$fallback_ids = array( 35, 53, 54 );
		$product_ids  = array();

		foreach ( $curated_slugs as $slug ) {
			$product_post = get_page_by_path( $slug, OBJECT, 'product' );

			if ( $product_post instanceof WP_Post && 'publish' === $product_post->post_status ) {
				$product_ids[] = (int) $product_post->ID;
			}
		}

		if ( count( $product_ids ) < $limit ) {
			$product_ids = array_merge(
				$product_ids,
				get_posts(
					array(
						'post_type'           => 'product',
						'post_status'         => 'publish',
						'posts_per_page'      => $limit,
						'orderby'             => array(
							'menu_order' => 'ASC',
							'title'      => 'ASC',
						),
						'tax_query'           => array(
							array(
								'taxonomy' => 'product_badge',
								'field'    => 'slug',
								'terms'    => array( 'best-seller' ),
							),
						),
						'fields'              => 'ids',
						'ignore_sticky_posts' => true,
						'no_found_rows'       => true,
					)
				)
			);
		}

		if ( count( $product_ids ) < $limit ) {
			$product_ids = array_values( array_unique( array_merge( $product_ids, $fallback_ids ) ) );
		}

		$product_ids = array_slice( $product_ids, 0, $limit );
		$products    = array();

		foreach ( $product_ids as $product_id ) {
			$product = wc_get_product( $product_id );

			if ( $product instanceof WC_Product && 'publish' === get_post_status( $product_id ) ) {
				$products[] = $product;
			}
		}

		return $products;
	}

	/**
	 * Resolve image pair for the homepage best seller hover effect.
	 *
	 * @param WC_Product $product Product object.
	 * @return array<string, string>
	 */
	protected function get_best_seller_media_urls( WC_Product $product ) {
		$product_id    = $product->get_id();
		$product_slug  = (string) $product->get_slug();
		$primary_url   = (string) get_the_post_thumbnail_url( $product_id, 'large' );
		$secondary_url = '';
		$gallery_ids   = array_values( array_filter( array_map( 'intval', (array) $product->get_gallery_image_ids() ) ) );

		if ( ! empty( $gallery_ids ) ) {
			$secondary_url = (string) wp_get_attachment_image_url( $gallery_ids[0], 'large' );
		}

		$best_seller_one   = $this->get_editorial_asset_url( 'sonovafurn-best-seller-01.png' );
		$best_seller_two   = $this->get_editorial_asset_url( 'sonovafurn-best-seller-02.png' );
		$best_seller_three = $this->get_editorial_asset_url( 'sonovafurn-best-seller-03.png' );

		$fallbacks = array(
			'default' => array(
				'primary'   => $best_seller_one,
				'secondary' => $best_seller_two,
			),
			'compression-sleeper-sofa' => array(
				'primary'   => $best_seller_one,
				'secondary' => $best_seller_two,
			),
			'modular' => array(
				'primary'   => $best_seller_two,
				'secondary' => $best_seller_three,
			),
			'corner' => array(
				'primary'   => $best_seller_three,
				'secondary' => $best_seller_one,
			),
			'curved' => array(
				'primary'   => $best_seller_three,
				'secondary' => $best_seller_one,
			),
			'sleeper' => array(
				'primary'   => $best_seller_one,
				'secondary' => $best_seller_two,
			),
			'sectional' => array(
				'primary'   => $best_seller_three,
				'secondary' => $best_seller_one,
			),
			'lounger' => array(
				'primary'   => $best_seller_two,
				'secondary' => $best_seller_three,
			),
			'mattress' => array(
				'primary'   => $best_seller_one,
				'secondary' => $best_seller_two,
			),
		);

		$fallback_key = 'default';

		foreach ( array_keys( $fallbacks ) as $candidate ) {
			if ( 'default' !== $candidate && false !== strpos( $product_slug, $candidate ) ) {
				$fallback_key = $candidate;
				break;
			}
		}

		if ( empty( $primary_url ) ) {
			$primary_url = $fallbacks[ $fallback_key ]['primary'];
		}

		if ( empty( $secondary_url ) ) {
			$secondary_url = $fallbacks[ $fallback_key ]['secondary'];
		}

		if ( empty( $secondary_url ) ) {
			$secondary_url = $primary_url;
		}

		return array(
			'primary'   => (string) $primary_url,
			'secondary' => (string) $secondary_url,
		);
	}

	/**
	 * Return a shorter editorial card title for homepage best sellers.
	 *
	 * @param WC_Product $product Product object.
	 * @return string
	 */
	protected function get_best_seller_display_title( WC_Product $product ) {
		$product_slug = (string) $product->get_slug();
		$titles       = array(
			'sonovafurn-compression-sleeper-sofa' => __( 'Sculpted Sofa', 'foam-form-commerce-kit' ),
			'sonovafurn-modular-compression-sofa' => __( 'Light Modular', 'foam-form-commerce-kit' ),
			'sonovafurn-corner-modular-sofa'      => __( 'Gallery Sectional', 'foam-form-commerce-kit' ),
			'sonovafurn-black-corduroy-sofa-bed'  => __( 'Corduroy Sleeper', 'foam-form-commerce-kit' ),
		);

		if ( isset( $titles[ $product_slug ] ) ) {
			return $titles[ $product_slug ];
		}

		if ( false !== strpos( $product_slug, 'corduroy' ) ) {
			return __( 'Corduroy Sleeper', 'foam-form-commerce-kit' );
		}

		return (string) $product->get_name();
	}

	/**
	 * Render the homepage best seller grid.
	 *
	 * @param int $limit Number of products to render.
	 * @return string
	 */
	protected function render_best_seller_grid( $limit = 3 ) {
		$products = $this->get_best_seller_products( $limit );

		if ( empty( $products ) ) {
			return '';
		}

		ob_start();
		?>
		<div class="foam-best-seller-grid">
			<?php foreach ( $products as $product ) : ?>
				<?php
				$product_url  = get_permalink( $product->get_id() );
				$product_name = $product->get_name();
				$card_title   = $this->get_best_seller_display_title( $product );
				$media_urls   = $this->get_best_seller_media_urls( $product );
				?>
				<article class="foam-best-seller-card">
					<a class="foam-best-seller-card__panel" href="<?php echo esc_url( $product_url ); ?>">
						<span class="foam-best-seller-card__eyebrow"><?php esc_html_e( 'Best Seller', 'foam-form-commerce-kit' ); ?></span>
						<span class="foam-best-seller-card__media">
							<span class="foam-best-seller-card__image-layer foam-best-seller-card__image-layer--primary">
								<img src="<?php echo esc_url( $media_urls['primary'] ); ?>" alt="<?php echo esc_attr( $product_name ); ?>" loading="lazy" decoding="async" />
							</span>
							<span class="foam-best-seller-card__image-layer foam-best-seller-card__image-layer--secondary" aria-hidden="true">
								<img src="<?php echo esc_url( $media_urls['secondary'] ); ?>" alt="" loading="lazy" decoding="async" />
							</span>
						</span>
						<span class="foam-best-seller-card__body">
							<span class="foam-best-seller-card__title"><?php echo esc_html( $card_title ); ?></span>
						</span>
					</a>
				</article>
			<?php endforeach; ?>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Render the homepage category grid.
	 *
	 * @return string
	 */
	protected function render_home_category_grid() {
		$categories = array(
			array(
				'modifier' => 'sofabed',
				'eyebrow'  => '',
				'title'    => __( 'Sofa Beds', 'foam-form-commerce-kit' ),
				'link'     => $this->get_product_category_link( 'compressed-sofa-beds' ),
				'cta'      => __( 'Explore the collection', 'foam-form-commerce-kit' ),
			),
			array(
				'modifier' => 'loveseat',
				'eyebrow'  => '',
				'title'    => __( 'Small-Space Sofas', 'foam-form-commerce-kit' ),
				'link'     => $this->get_product_category_link( 'space-saving-sofas' ),
				'cta'      => __( 'View apartment-ready seating', 'foam-form-commerce-kit' ),
			),
			array(
				'modifier' => 'memory',
				'eyebrow'  => '',
				'title'    => __( 'Mattresses', 'foam-form-commerce-kit' ),
				'link'     => $this->get_product_category_link( 'mattresses' ),
				'cta'      => __( 'See sleep essentials', 'foam-form-commerce-kit' ),
			),
			array(
				'modifier' => 'living',
				'eyebrow'  => '',
				'title'    => __( 'Modular Seating', 'foam-form-commerce-kit' ),
				'link'     => $this->get_product_category_link( 'modular-sofas' ),
				'cta'      => __( 'Browse flexible layouts', 'foam-form-commerce-kit' ),
			),
		);

		ob_start();
		?>
		<div class="foam-collection-grid">
			<?php foreach ( $categories as $category ) : ?>
				<a class="foam-collection-card foam-collection-card--<?php echo esc_attr( $category['modifier'] ); ?>" href="<?php echo esc_url( $category['link'] ); ?>">
					<?php if ( ! empty( $category['eyebrow'] ) ) : ?>
						<span><?php echo esc_html( $category['eyebrow'] ); ?></span>
					<?php endif; ?>
					<strong><?php echo esc_html( $category['title'] ); ?></strong>
					<em><?php echo esc_html( $category['cta'] ); ?></em>
				</a>
			<?php endforeach; ?>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Render the lifestyle carousel section on the homepage.
	 *
	 * @return string
	 */
	protected function render_life_with_sonovafurn_section() {
		$slides = array(
			array(
				'label' => __( 'Morning Coffee', 'foam-form-commerce-kit' ),
				'title' => __( 'Start the day somewhere soft.', 'foam-form-commerce-kit' ),
				'copy'  => __( 'A softer place to ease into the day, with warm light, a quiet cup, and comfort that never feels overstated.', 'foam-form-commerce-kit' ),
				'image' => $this->get_editorial_asset_url( 'sonovafurn-editorial-lifestyle-sofa.jpg' ),
				'alt'   => __( 'Cream Sonovafurn sofa styled for a warm morning living room scene', 'foam-form-commerce-kit' ),
			),
			array(
				'label' => __( 'Weekend Reading', 'foam-form-commerce-kit' ),
				'title' => __( 'Slow mornings begin here.', 'foam-form-commerce-kit' ),
				'copy'  => __( 'A calm corner for longer pages, softer textures, and the kind of comfort that makes it easy to stay a little longer.', 'foam-form-commerce-kit' ),
				'image' => $this->get_editorial_asset_url( 'sonovafurn-reading-lounge-chair-rust.jpg' ),
				'alt'   => __( 'Reading lounge chair beside a bookshelf and side table', 'foam-form-commerce-kit' ),
			),
			array(
				'label' => __( 'Guest Ready', 'foam-form-commerce-kit' ),
				'title' => __( 'Comfort whenever someone stays over.', 'foam-form-commerce-kit' ),
				'copy'  => __( 'Designed to shift naturally from daily living to overnight use, without asking the room to feel temporary.', 'foam-form-commerce-kit' ),
				'image' => $this->get_editorial_asset_url( 'sonovafurn-scene-guest-room.png' ),
				'alt'   => __( 'Black compressed sofa bed styled in a minimal guest room', 'foam-form-commerce-kit' ),
			),
			array(
				'label' => __( 'Movie Night', 'foam-form-commerce-kit' ),
				'title' => __( 'Made for evenings that last longer.', 'foam-form-commerce-kit' ),
				'copy'  => __( 'A lower, warmer atmosphere for longer conversations, softer lighting, and evenings that naturally stretch on.', 'foam-form-commerce-kit' ),
				'image' => $this->get_editorial_asset_url( 'sonovafurn-editorial-shop-night.jpg' ),
				'alt'   => __( 'Dark modular Sonovafurn sofa in a warm evening living room', 'foam-form-commerce-kit' ),
			),
		);

		ob_start();
		?>
		<section class="foam-home-section foam-home-section--life" id="life-with-sonovafurn">
			<div class="foam-section-heading">
				<p class="foam-kicker"><?php esc_html_e( 'Life With Sonovafurn', 'foam-form-commerce-kit' ); ?></p>
				<h2><?php esc_html_e( 'Comfort, seen in everyday rituals.', 'foam-form-commerce-kit' ); ?></h2>
				<p><?php esc_html_e( 'From quiet mornings to overnight stays, each piece is designed to move naturally through the softer rhythms of home.', 'foam-form-commerce-kit' ); ?></p>
			</div>
			<div class="foam-life-carousel" data-foam-hero-carousel>
				<div class="foam-life-carousel__viewport">
					<?php foreach ( $slides as $index => $slide ) : ?>
						<article class="foam-life-slide<?php echo 0 === $index ? ' is-active' : ''; ?>" data-foam-hero-slide aria-hidden="<?php echo 0 === $index ? 'false' : 'true'; ?>">
							<div class="foam-life-slide__media">
								<img src="<?php echo esc_url( $slide['image'] ); ?>" alt="<?php echo esc_attr( $slide['alt'] ); ?>" loading="lazy" decoding="async" />
							</div>
							<div class="foam-life-slide__copy">
								<span class="foam-life-slide__label"><?php echo esc_html( $slide['label'] ); ?></span>
								<h3><?php echo esc_html( $slide['title'] ); ?></h3>
								<p><?php echo esc_html( $slide['copy'] ); ?></p>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
				<div class="foam-life-carousel__controls" aria-label="<?php esc_attr_e( 'Life with Sonovafurn carousel controls', 'foam-form-commerce-kit' ); ?>">
					<button class="foam-life-carousel__arrow" type="button" data-foam-hero-prev aria-label="<?php esc_attr_e( 'Previous slide', 'foam-form-commerce-kit' ); ?>">
						<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M14.5 5.5 8 12l6.5 6.5"></path></svg>
					</button>
					<div class="foam-life-carousel__dots" aria-label="<?php esc_attr_e( 'Select lifestyle slide', 'foam-form-commerce-kit' ); ?>">
						<?php foreach ( $slides as $index => $slide ) : ?>
							<button class="foam-life-carousel__dot<?php echo 0 === $index ? ' is-active' : ''; ?>" type="button" data-foam-hero-dot aria-label="<?php echo esc_attr( $slide['label'] ); ?>" aria-pressed="<?php echo 0 === $index ? 'true' : 'false'; ?>"></button>
						<?php endforeach; ?>
					</div>
					<button class="foam-life-carousel__arrow" type="button" data-foam-hero-next aria-label="<?php esc_attr_e( 'Next slide', 'foam-form-commerce-kit' ); ?>">
						<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M9.5 5.5 16 12l-6.5 6.5"></path></svg>
					</button>
				</div>
			</div>
		</section>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Render reordered homepage sections.
	 *
	 * @return string
	 */
	protected function render_reordered_home_sections() {
		$shop_url                 = home_url( '/shop/' );
		$about_url                = home_url( '/about-us/' );
		$faq_url                  = home_url( '/faq/' );
		$compressed_sofa_beds_url = $this->get_product_category_link( 'compressed-sofa-beds' );
		$mattresses_url           = $this->get_product_category_link( 'mattresses' );
		$space_saving_sofas_url   = $this->get_product_category_link( 'space-saving-sofas' );
		$modular_sofas_url        = $this->get_product_category_link( 'modular-sofas' );
		$white_sofa_url           = $this->get_editorial_asset_url( 'sonovafurn-editorial-white-sofa.jpg' );
		$shop_night_url           = $this->get_editorial_asset_url( 'sonovafurn-editorial-shop-night.jpg' );
		$corner_sofa_url          = $this->get_editorial_asset_url( 'sonovafurn-source-04.png' );
		$materials_texture_url    = $this->get_editorial_asset_url( 'sonovafurn-source-03.png' );
		$materials_support_url    = $this->get_editorial_asset_url( 'sonovafurn-grand-modular-sectional-ivory.png' );
		$materials_lifestyle_url  = $this->get_editorial_asset_url( 'sonovafurn-source-01.jpg' );
		$materials_trust_url      = $this->get_editorial_asset_url( 'sonovafurn-source-02.jpg' );
		$materials_visual_url     = $this->get_editorial_asset_url( 'sonovafurn-grand-modular-sectional-ivory.png' );
		$materials_visual_alt_url = $this->get_editorial_asset_url( 'sonovafurn-best-seller-02.png' );
		$materials_visual_base_url = $this->get_editorial_asset_url( 'sonovafurn-best-seller-03.png' );
		$hero_slides              = array(
			array(
				'eyebrow'   => __( 'Comfort, Compressed', 'foam-form-commerce-kit' ),
				'title'     => __( 'Inside Sonovafurn', 'foam-form-commerce-kit' ),
				'copy'      => __( 'Thoughtfully designed sofas and sleep solutions that arrive compressed, expand beautifully, and bring effortless comfort to modern homes.', 'foam-form-commerce-kit' ),
				'image'     => $white_sofa_url,
				'primary'   => array(
					'label' => __( 'Shop Sofas', 'foam-form-commerce-kit' ),
					'url'   => $shop_url,
				),
				'secondary' => array(
					'label' => __( 'Explore Best Sellers', 'foam-form-commerce-kit' ),
					'url'   => $compressed_sofa_beds_url,
				),
				'facts'     => array(),
			),
			array(
				'eyebrow'   => __( 'Convertible Seating', 'foam-form-commerce-kit' ),
				'title'     => __( 'One Sofa. More Possibilities.', 'foam-form-commerce-kit' ),
				'copy'      => __( 'From everyday lounging to overnight guests, our convertible sofa beds adapt effortlessly to the way you live.', 'foam-form-commerce-kit' ),
				'image'     => $shop_night_url,
				'primary'   => array(
					'label' => __( 'Shop Sofa Beds', 'foam-form-commerce-kit' ),
					'url'   => $compressed_sofa_beds_url,
				),
				'secondary' => array(
					'label' => __( 'Explore Convertible Seating', 'foam-form-commerce-kit' ),
					'url'   => $space_saving_sofas_url,
				),
				'facts'     => array(),
			),
			array(
				'eyebrow'   => __( 'Small-Space Living', 'foam-form-commerce-kit' ),
				'title'     => __( 'More Comfort. Less Compromise.', 'foam-form-commerce-kit' ),
				'copy'      => __( 'Designed to move easily through apartments, elevators, and narrow hallways without sacrificing everyday comfort.', 'foam-form-commerce-kit' ),
				'image'     => $corner_sofa_url ? $corner_sofa_url : $white_sofa_url,
				'primary'   => array(
					'label' => __( 'Shop Small-Space Sofas', 'foam-form-commerce-kit' ),
					'url'   => $modular_sofas_url,
				),
				'secondary' => array(
					'label' => __( 'Learn About Compressed Delivery', 'foam-form-commerce-kit' ),
					'url'   => $mattresses_url,
				),
				'facts'     => array(),
			),
		);

		ob_start();
		?>
		<div class="foam-homepage-shell">
			<section class="foam-hero-shell">
				<div class="foam-hero-grid">
					<h1 class="screen-reader-text"><?php esc_html_e( 'Sonovafurn compressed sofa beds and foam furniture', 'foam-form-commerce-kit' ); ?></h1>
					<div class="foam-hero-carousel" data-foam-hero-carousel>
						<div class="foam-hero-carousel__viewport">
							<?php foreach ( $hero_slides as $index => $slide ) : ?>
								<article class="foam-hero-slide<?php echo 0 === $index ? ' is-active' : ''; ?>" data-foam-hero-slide style="background-image: linear-gradient(180deg, rgba(16, 14, 11, 0.14), rgba(16, 14, 11, 0.48)), url('<?php echo esc_url( $slide['image'] ); ?>');" aria-hidden="<?php echo 0 === $index ? 'false' : 'true'; ?>">
									<div class="foam-hero-slide__inner">
										<span class="foam-hero-slide__eyebrow"><?php echo esc_html( $slide['eyebrow'] ); ?></span>
										<h2 class="foam-hero-slide__title"><?php echo esc_html( $slide['title'] ); ?></h2>
										<p class="foam-hero-slide__copy"><?php echo esc_html( $slide['copy'] ); ?></p>
										<div class="foam-hero-slide__actions">
											<a class="foam-hero-slide__primary" href="<?php echo esc_url( $slide['primary']['url'] ); ?>"><?php echo esc_html( $slide['primary']['label'] ); ?></a>
											<a class="foam-hero-slide__secondary" href="<?php echo esc_url( $slide['secondary']['url'] ); ?>"><?php echo esc_html( $slide['secondary']['label'] ); ?></a>
										</div>
										<?php if ( ! empty( $slide['facts'] ) ) : ?>
											<div class="foam-hero-slide__facts">
												<?php foreach ( $slide['facts'] as $fact ) : ?>
													<div>
														<strong><?php echo esc_html( $fact['value'] ); ?></strong>
														<span><?php echo esc_html( $fact['label'] ); ?></span>
													</div>
												<?php endforeach; ?>
											</div>
										<?php endif; ?>
									</div>
								</article>
							<?php endforeach; ?>
						</div>
						<div class="foam-hero-carousel__controls" aria-label="<?php esc_attr_e( 'Homepage hero controls', 'foam-form-commerce-kit' ); ?>">
							<button class="foam-hero-carousel__arrow" type="button" data-foam-hero-prev aria-label="<?php esc_attr_e( 'Previous slide', 'foam-form-commerce-kit' ); ?>">
								<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
									<path d="M14.5 6.5 9 12l5.5 5.5" />
								</svg>
							</button>
							<div class="foam-hero-carousel__dots">
								<?php foreach ( $hero_slides as $index => $slide ) : ?>
									<button class="foam-hero-carousel__dot<?php echo 0 === $index ? ' is-active' : ''; ?>" type="button" data-foam-hero-dot="<?php echo esc_attr( (string) $index ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Go to slide %d', 'foam-form-commerce-kit' ), $index + 1 ) ); ?>"></button>
								<?php endforeach; ?>
							</div>
							<button class="foam-hero-carousel__arrow" type="button" data-foam-hero-next aria-label="<?php esc_attr_e( 'Next slide', 'foam-form-commerce-kit' ); ?>">
								<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
									<path d="M9.5 6.5 15 12l-5.5 5.5" />
								</svg>
							</button>
						</div>
					</div>
				</div>
			</section>

			<section class="foam-home-section foam-home-section--comfort-journey" id="comfort-journey">
				<div class="foam-inside-story">
					<div class="foam-inside-story__copy">
						<p class="foam-kicker"><?php esc_html_e( 'Designed From the Inside Out', 'foam-form-commerce-kit' ); ?></p>
						<h2><?php esc_html_e( 'Comfort begins long before you sit down.', 'foam-form-commerce-kit' ); ?></h2>
						<p><?php esc_html_e( 'Every layer is thoughtfully selected, every detail intentionally designed. From premium fabrics to supportive foam and individually wrapped pocket springs, each component works together to create lasting comfort for everyday living.', 'foam-form-commerce-kit' ); ?></p>
						<div class="foam-link-row foam-link-row--section foam-link-row--inside-story">
							<a class="foam-text-link" href="<?php echo esc_url( $about_url ); ?>"><?php esc_html_e( 'Explore Our Materials', 'foam-form-commerce-kit' ); ?></a>
						</div>
					</div>
					<div class="foam-inside-story__visual">
						<div class="foam-inside-stack is-fabric" data-foam-material-story>
							<div class="foam-inside-stack__story-card">
								<div class="foam-inside-stack__controls" role="tablist" aria-label="<?php esc_attr_e( 'Material story navigation', 'foam-form-commerce-kit' ); ?>">
									<button class="foam-inside-stack__label foam-inside-stack__label--fabric is-active" type="button" data-foam-material-trigger="fabric" data-foam-material-image="<?php echo esc_url( $materials_visual_url ); ?>" aria-pressed="true"><?php esc_html_e( 'Soft-touch upholstery', 'foam-form-commerce-kit' ); ?></button>
									<button class="foam-inside-stack__label foam-inside-stack__label--comfort" type="button" data-foam-material-trigger="support" data-foam-material-image="<?php echo esc_url( $materials_visual_alt_url ); ?>" aria-pressed="false"><?php esc_html_e( 'Support layer', 'foam-form-commerce-kit' ); ?></button>
									<button class="foam-inside-stack__label foam-inside-stack__label--support" type="button" data-foam-material-trigger="springs" data-foam-material-image="<?php echo esc_url( $materials_visual_base_url ); ?>" aria-pressed="false"><?php esc_html_e( 'Pocket springs', 'foam-form-commerce-kit' ); ?></button>
								</div>
								<div class="foam-inside-stack__story-panel is-active" data-foam-material-panel="fabric">
									<span><?php esc_html_e( 'Soft-touch upholstery', 'foam-form-commerce-kit' ); ?></span>
									<h3><?php esc_html_e( 'Softness you notice first', 'foam-form-commerce-kit' ); ?></h3>
									<p><?php esc_html_e( 'A softly textured finish selected to feel inviting from the first touch while keeping the room calm, warm, and easy to live with.', 'foam-form-commerce-kit' ); ?></p>
								</div>
								<div class="foam-inside-stack__story-panel" data-foam-material-panel="support">
									<span><?php esc_html_e( 'Support layer', 'foam-form-commerce-kit' ); ?></span>
									<h3><?php esc_html_e( 'Balanced comfort underneath', 'foam-form-commerce-kit' ); ?></h3>
									<p><?php esc_html_e( 'Resilient comfort layers and supportive foam help the seat feel balanced, easy, and welcoming through everyday lounging.', 'foam-form-commerce-kit' ); ?></p>
								</div>
								<div class="foam-inside-stack__story-panel" data-foam-material-panel="springs">
									<span><?php esc_html_e( 'Pocket springs', 'foam-form-commerce-kit' ); ?></span>
									<h3><?php esc_html_e( 'Quiet support that stays comfortable', 'foam-form-commerce-kit' ); ?></h3>
									<p><?php esc_html_e( 'Individually wrapped pocket springs help distribute weight more evenly, adding a quieter sense of support beneath the softness.', 'foam-form-commerce-kit' ); ?></p>
								</div>
							</div>
							<div class="foam-inside-stack__layer foam-inside-stack__layer--top" data-foam-material-layer="fabric"></div>
							<div class="foam-inside-stack__layer foam-inside-stack__layer--mid" data-foam-material-layer="support"></div>
							<div class="foam-inside-stack__layer foam-inside-stack__layer--base" data-foam-material-layer="springs"></div>
							<div class="foam-inside-stack__sofa">
								<img src="<?php echo esc_url( $materials_visual_url ); ?>" alt="" loading="lazy" decoding="async" data-foam-material-image-view />
							</div>
						</div>
					</div>
				</div>
				<div class="foam-material-story-grid">
					<article class="foam-material-story-card">
						<figure class="foam-material-story-card__media">
							<img src="<?php echo esc_url( $materials_texture_url ); ?>" alt="<?php esc_attr_e( 'Soft textured upholstery detail', 'foam-form-commerce-kit' ); ?>" loading="lazy" decoding="async" />
						</figure>
						<div class="foam-material-story-card__copy">
							<h3><?php esc_html_e( 'Soft Begins Here', 'foam-form-commerce-kit' ); ?></h3>
							<p><?php esc_html_e( 'Soft-touch upholstery selected for everyday comfort, inviting texture, and a relaxed feel from the very first touch.', 'foam-form-commerce-kit' ); ?></p>
						</div>
					</article>
					<article class="foam-material-story-card">
						<figure class="foam-material-story-card__media">
							<img src="<?php echo esc_url( $materials_support_url ); ?>" alt="<?php esc_attr_e( 'Layered sofa composition with balanced support', 'foam-form-commerce-kit' ); ?>" loading="lazy" decoding="async" />
						</figure>
						<div class="foam-material-story-card__copy">
							<h3><?php esc_html_e( 'Balanced Support', 'foam-form-commerce-kit' ); ?></h3>
							<p><?php esc_html_e( 'Layered comfort fiber, high-resilience foam, and individually wrapped pocket springs work together to provide balanced support without sacrificing softness.', 'foam-form-commerce-kit' ); ?></p>
						</div>
					</article>
					<article class="foam-material-story-card">
						<figure class="foam-material-story-card__media">
							<img src="<?php echo esc_url( $materials_lifestyle_url ); ?>" alt="<?php esc_attr_e( 'Lifestyle sofa scene designed for everyday relaxation', 'foam-form-commerce-kit' ); ?>" loading="lazy" decoding="async" />
						</figure>
						<div class="foam-material-story-card__copy">
							<h3><?php esc_html_e( 'Comfort That Lasts', 'foam-form-commerce-kit' ); ?></h3>
							<p><?php esc_html_e( 'Responsive materials recover naturally after everyday use, helping every seat stay inviting through years of relaxing moments.', 'foam-form-commerce-kit' ); ?></p>
						</div>
					</article>
					<article class="foam-material-story-card">
						<figure class="foam-material-story-card__media">
							<img src="<?php echo esc_url( $materials_trust_url ); ?>" alt="<?php esc_attr_e( 'Material details selected for comfortable everyday living', 'foam-form-commerce-kit' ); ?>" loading="lazy" decoding="async" />
						</figure>
						<div class="foam-material-story-card__copy">
							<h3><?php esc_html_e( 'Materials You Can Trust', 'foam-form-commerce-kit' ); ?></h3>
							<p><?php esc_html_e( 'Carefully selected materials that support cleaner, more comfortable everyday living, with certification details shared only where officially applicable.', 'foam-form-commerce-kit' ); ?></p>
						</div>
					</article>
				</div>
				<div class="foam-inside-story__statement">
					<h3><?php esc_html_e( 'Built for the moments that matter most.', 'foam-form-commerce-kit' ); ?></h3>
					<p><?php esc_html_e( 'Great comfort is not created by a single material. It is the result of thoughtful design, carefully selected components, and craftsmanship that puts everyday living first.', 'foam-form-commerce-kit' ); ?></p>
				</div>
			</section>

			<section class="foam-home-section foam-home-section--product-showcase" id="featured-collection">
				<div class="foam-section-heading">
					<p class="foam-kicker"><?php esc_html_e( 'Featured Collection', 'foam-form-commerce-kit' ); ?></p>
					<h2><?php esc_html_e( 'Elevated Comfort. Every Day.', 'foam-form-commerce-kit' ); ?></h2>
					<p><?php esc_html_e( 'Sofas, sleepers, and foam-backed essentials crafted for compact spaces and fast, easy delivery. It\'s the support you need and the style you want, ready to enjoy the moment it arrives.', 'foam-form-commerce-kit' ); ?></p>
				</div>
				<?php echo $this->render_best_seller_grid( 4 ); ?>
				<div class="foam-link-row foam-link-row--section foam-link-row--best-sellers">
					<a class="foam-text-link" href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Browse the full collection', 'foam-form-commerce-kit' ); ?></a>
					<a class="foam-text-link" href="<?php echo esc_url( $compressed_sofa_beds_url ); ?>"><?php esc_html_e( 'Explore featured sofa beds', 'foam-form-commerce-kit' ); ?></a>
				</div>
			</section>

			<section class="foam-home-section foam-home-section--categories" id="shop-by-category">
				<div class="foam-section-heading">
					<p class="foam-kicker"><?php esc_html_e( 'Comfort For Every Space', 'foam-form-commerce-kit' ); ?></p>
					<h2><?php esc_html_e( 'Find comfort that fits your home.', 'foam-form-commerce-kit' ); ?></h2>
					<p><?php esc_html_e( 'Discover thoughtfully designed furniture collections created to fit modern homes, flexible routines, and the way you live every day.', 'foam-form-commerce-kit' ); ?></p>
				</div>
				<?php echo $this->render_home_category_grid(); ?>
			</section>

			<?php echo $this->render_life_with_sonovafurn_section(); ?>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Register custom image sizes.
	 *
	 * @return void
	 */
	public function register_image_sizes() {
		add_image_size( 'foam_form_lifestyle', 1440, 1080, true );
		add_image_size( 'foam_form_product_story', 1440, 1080, true );
	}

	/**
	 * Inject lightweight preload hints for the current template's LCP media.
	 *
	 * @return void
	 */
	public function inject_performance_hints() {
		if ( is_admin() ) {
			return;
		}

		$preload_images = array();

		if ( is_front_page() || is_home() || is_page( 'home' ) ) {
			$preload_images[] = $this->get_editorial_asset_url( 'sonovafurn-editorial-white-sofa.jpg' );
		} elseif ( function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() ) ) {
			$preload_images[] = $this->get_editorial_asset_url( 'sonovafurn-editorial-shop-night.jpg' );
		} elseif ( is_page( 'about-us' ) || is_page( 'blog' ) ) {
			$preload_images[] = $this->get_editorial_asset_url( 'sonovafurn-editorial-hero-living.jpg' );
		} elseif ( is_page( 'shipping-policy' ) ) {
			$preload_images[] = $this->get_editorial_asset_url( 'sonovafurn-shipping-delivery.jpg' );
		} elseif ( function_exists( 'is_product' ) && is_product() ) {
			$product_image_id = get_post_thumbnail_id( get_the_ID() );
			$product_image    = $product_image_id ? wp_get_attachment_image_url( $product_image_id, 'large' ) : '';

			if ( ! empty( $product_image ) ) {
				$preload_images[] = $product_image;
			}
		}

		$preload_images = array_values(
			array_filter(
				array_unique(
					array_map( 'esc_url_raw', $preload_images )
				)
			)
		);

		foreach ( $preload_images as $image_url ) {
			echo '<link rel="preload" as="image" href="' . esc_url( $image_url ) . '" fetchpriority="high">' . "\n";
		}
	}

	/**
	 * Output brand meta for SEO.
	 *
	 * @return void
	 */
	public function inject_brand_meta() {
		echo '<meta name="description" content="' . esc_attr( get_bloginfo( 'description' ) ) . '">' . "\n";
		echo '<meta property="og:site_name" content="sonovafurn">' . "\n";
		echo '<meta property="og:type" content="website">' . "\n";

		$website_schema = array(
			'@context' => 'https://schema.org',
			'@type'    => 'WebSite',
			'name'     => 'sonovafurn',
			'url'      => home_url( '/' ),
			'description' => get_bloginfo( 'description' ),
		);

		echo '<script type="application/ld+json">' . wp_json_encode( $website_schema ) . '</script>' . "\n";

		if ( is_singular( 'post' ) ) {
			$article_schema = array(
				'@context'        => 'https://schema.org',
				'@type'           => 'Article',
				'headline'        => get_the_title(),
				'datePublished'   => get_the_date( DATE_W3C ),
				'dateModified'    => get_the_modified_date( DATE_W3C ),
				'author'          => array(
					'@type' => 'Organization',
					'name'  => 'sonovafurn',
				),
				'publisher'       => array(
					'@type' => 'Organization',
					'name'  => 'sonovafurn',
				),
				'mainEntityOfPage' => get_permalink(),
			);

			echo '<script type="application/ld+json">' . wp_json_encode( $article_schema ) . '</script>' . "\n";
		}
	}

	/**
	 * Homepage sections shortcode.
	 *
	 * @return string
	 */
	public function render_home_sections() {
		return $this->render_reordered_home_sections();

		$shop_url                 = home_url( '/shop/' );
		$about_url                = home_url( '/about-us/' );
		$faq_url                  = home_url( '/faq/' );
		$compressed_sofa_beds_url = $this->get_product_category_link( 'compressed-sofa-beds' );
		$mattresses_url           = $this->get_product_category_link( 'mattresses' );
		$space_saving_sofas_url   = $this->get_product_category_link( 'space-saving-sofas' );
		$modular_sofas_url        = $this->get_product_category_link( 'modular-sofas' );
		$white_sofa_url           = $this->get_editorial_asset_url( 'sonovafurn-editorial-white-sofa.jpg' );
		$lifestyle_sofa_url       = $this->get_editorial_asset_url( 'sonovafurn-editorial-lifestyle-sofa.jpg' );
		$hero_logo_url            = $this->get_editorial_asset_url( 'sonovafurn-hero-logo-editorial.png' );
		$shop_night_url           = $this->get_editorial_asset_url( 'sonovafurn-editorial-shop-night.jpg' );
		$corner_sofa_url          = $this->get_editorial_asset_url( 'sonovafurn-source-04.png' );
		$hero_slides              = array(
			array(
				'eyebrow'   => __( 'Comfort, Compressed', 'foam-form-commerce-kit' ),
				'title'     => __( 'A Softer Way To Live', 'foam-form-commerce-kit' ),
				'copy'      => __( 'Thoughtfully designed sofas and sleep solutions that arrive compressed, expand beautifully, and bring effortless comfort to modern homes.', 'foam-form-commerce-kit' ),
				'image'     => $white_sofa_url,
				'primary'   => array(
					'label' => __( 'Shop Sofas', 'foam-form-commerce-kit' ),
					'url'   => $shop_url,
				),
				'secondary' => array(
					'label' => __( 'Explore Best Sellers', 'foam-form-commerce-kit' ),
					'url'   => $compressed_sofa_beds_url,
				),
				'facts'     => array(),
			),
			array(
				'eyebrow'   => __( 'Convertible Seating', 'foam-form-commerce-kit' ),
				'title'     => __( 'One Sofa. More Possibilities.', 'foam-form-commerce-kit' ),
				'copy'      => __( 'From everyday lounging to overnight guests, our convertible sofa beds adapt effortlessly to the way you live.', 'foam-form-commerce-kit' ),
				'image'     => $shop_night_url,
				'primary'   => array(
					'label' => __( 'Shop Sofa Beds', 'foam-form-commerce-kit' ),
					'url'   => $compressed_sofa_beds_url,
				),
				'secondary' => array(
					'label' => __( 'Explore Convertible Seating', 'foam-form-commerce-kit' ),
					'url'   => $space_saving_sofas_url,
				),
				'facts'     => array(),
			),
			array(
				'eyebrow'   => __( 'Small-Space Living', 'foam-form-commerce-kit' ),
				'title'     => __( 'More Comfort. Less Compromise.', 'foam-form-commerce-kit' ),
				'copy'      => __( 'Designed to move easily through apartments, elevators, and narrow hallways without sacrificing everyday comfort.', 'foam-form-commerce-kit' ),
				'image'     => $corner_sofa_url ? $corner_sofa_url : $white_sofa_url,
				'primary'   => array(
					'label' => __( 'Shop Small-Space Sofas', 'foam-form-commerce-kit' ),
					'url'   => $modular_sofas_url,
				),
				'secondary' => array(
					'label' => __( 'Learn About Compressed Delivery', 'foam-form-commerce-kit' ),
					'url'   => $mattresses_url,
				),
				'facts'     => array(),
			),
		);

		ob_start();
		?>
		<div class="foam-homepage-shell">
		<section class="foam-hero-shell">
			<div class="foam-hero-grid">
				<h1 class="screen-reader-text"><?php esc_html_e( 'Sonovafurn compressed sofa beds and foam furniture', 'foam-form-commerce-kit' ); ?></h1>
				<div class="foam-hero-carousel" data-foam-hero-carousel>
					<div class="foam-hero-carousel__viewport">
						<?php foreach ( $hero_slides as $index => $slide ) : ?>
							<article class="foam-hero-slide<?php echo 0 === $index ? ' is-active' : ''; ?>" data-foam-hero-slide style="background-image: linear-gradient(180deg, rgba(16, 14, 11, 0.14), rgba(16, 14, 11, 0.48)), url('<?php echo esc_url( $slide['image'] ); ?>');" aria-hidden="<?php echo 0 === $index ? 'false' : 'true'; ?>">
								<div class="foam-hero-slide__inner">
									<span class="foam-hero-slide__eyebrow"><?php echo esc_html( $slide['eyebrow'] ); ?></span>
									<h2 class="foam-hero-slide__title"><?php echo esc_html( $slide['title'] ); ?></h2>
									<p class="foam-hero-slide__copy"><?php echo esc_html( $slide['copy'] ); ?></p>
									<div class="foam-hero-slide__actions">
										<a class="foam-hero-slide__primary" href="<?php echo esc_url( $slide['primary']['url'] ); ?>"><?php echo esc_html( $slide['primary']['label'] ); ?></a>
										<a class="foam-hero-slide__secondary" href="<?php echo esc_url( $slide['secondary']['url'] ); ?>"><?php echo esc_html( $slide['secondary']['label'] ); ?></a>
									</div>
									<?php if ( ! empty( $slide['facts'] ) ) : ?>
										<div class="foam-hero-slide__facts">
											<?php foreach ( $slide['facts'] as $fact ) : ?>
												<div>
													<strong><?php echo esc_html( $fact['value'] ); ?></strong>
													<span><?php echo esc_html( $fact['label'] ); ?></span>
												</div>
											<?php endforeach; ?>
										</div>
									<?php endif; ?>
								</div>
							</article>
						<?php endforeach; ?>
					</div>
					<div class="foam-hero-carousel__controls" aria-label="<?php esc_attr_e( 'Homepage hero controls', 'foam-form-commerce-kit' ); ?>">
						<button class="foam-hero-carousel__arrow" type="button" data-foam-hero-prev aria-label="<?php esc_attr_e( 'Previous slide', 'foam-form-commerce-kit' ); ?>">
							<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
								<path d="M14.5 6.5 9 12l5.5 5.5" />
							</svg>
						</button>
						<div class="foam-hero-carousel__dots">
							<?php foreach ( $hero_slides as $index => $slide ) : ?>
								<button class="foam-hero-carousel__dot<?php echo 0 === $index ? ' is-active' : ''; ?>" type="button" data-foam-hero-dot="<?php echo esc_attr( (string) $index ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Go to slide %d', 'foam-form-commerce-kit' ), $index + 1 ) ); ?>"></button>
							<?php endforeach; ?>
						</div>
						<button class="foam-hero-carousel__arrow" type="button" data-foam-hero-next aria-label="<?php esc_attr_e( 'Next slide', 'foam-form-commerce-kit' ); ?>">
							<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
								<path d="M9.5 6.5 15 12l-5.5 5.5" />
							</svg>
						</button>
					</div>
				</div>
			</div>
		</section>

		<section class="foam-home-features" aria-label="<?php esc_attr_e( 'Functional highlights', 'foam-form-commerce-kit' ); ?>">
			<div class="foam-home-feature">
				<span class="foam-home-feature__icon" aria-hidden="true">
					<svg viewBox="0 0 24 24" focusable="false">
						<path d="M4.75 10.25 12 4.75l7.25 5.5v8a1 1 0 0 1-1 1h-4.5v-5.25h-3.5v5.25h-4.5a1 1 0 0 1-1-1Z" />
					</svg>
				</span>
				<div><strong><?php esc_html_e( 'Small-space ready', 'foam-form-commerce-kit' ); ?></strong><span><?php esc_html_e( 'Scaled for apartments, studios, and guest rooms without overwhelming the room.', 'foam-form-commerce-kit' ); ?></span></div>
			</div>
			<div class="foam-home-feature">
				<span class="foam-home-feature__icon" aria-hidden="true">
					<svg viewBox="0 0 24 24" focusable="false">
						<path d="M12 3.75 19 7.75v8.5l-7 4-7-4v-8.5Z" />
						<path d="M12 3.75v8m0 0 7-4m-7 4-7-4" />
					</svg>
				</span>
				<div><strong><?php esc_html_e( 'Compressed delivery', 'foam-form-commerce-kit' ); ?></strong><span><?php esc_html_e( 'Ships compact for easier receiving through elevators, hallways, and narrow entries.', 'foam-form-commerce-kit' ); ?></span></div>
			</div>
			<div class="foam-home-feature">
				<span class="foam-home-feature__icon" aria-hidden="true">
					<svg viewBox="0 0 24 24" focusable="false">
						<circle cx="12" cy="12" r="7.75" />
						<path d="m9.4 12.1 1.8 1.8 3.6-3.8" />
					</svg>
				</span>
				<div><strong><?php esc_html_e( 'Quiet materials', 'foam-form-commerce-kit' ); ?></strong><span><?php esc_html_e( 'Layered foam, soft-touch fabrics, and supportive construction made for everyday living.', 'foam-form-commerce-kit' ); ?></span></div>
			</div>
		</section>

		<section class="foam-home-section foam-home-section--product-showcase" id="best-sellers">
			<div class="foam-section-heading foam-section-heading--split">
				<div>
					<p class="foam-kicker"><?php esc_html_e( 'Best sellers', 'foam-form-commerce-kit' ); ?></p>
					<h2><?php esc_html_e( 'Elevated Comfort. Every Day.', 'foam-form-commerce-kit' ); ?></h2>
				</div>
				<p><?php esc_html_e( 'Sofas, sleepers, and foam-backed essentials crafted for compact spaces and fast, easy delivery. It’s the support you need and the style you want—ready to enjoy the moment it arrives.', 'foam-form-commerce-kit' ); ?></p>
			</div>
			<?php echo $this->render_best_seller_grid( 3 ); ?>
			<div class="foam-link-row foam-link-row--section foam-link-row--best-sellers">
				<a class="foam-text-link" href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Browse the full collection', 'foam-form-commerce-kit' ); ?></a>
				<a class="foam-text-link" href="<?php echo esc_url( $compressed_sofa_beds_url ); ?>"><?php esc_html_e( 'Explore sofa bed best sellers', 'foam-form-commerce-kit' ); ?></a>
			</div>
		</section>

		<section class="foam-home-section foam-home-section--technology" id="technology">
			<div class="foam-section-heading foam-section-heading--split">
				<div>
					<p class="foam-kicker"><?php esc_html_e( 'Technology', 'foam-form-commerce-kit' ); ?></p>
					<h2><?php esc_html_e( 'Material choices explained with clarity rather than sales pressure', 'foam-form-commerce-kit' ); ?></h2>
				</div>
				<p><?php esc_html_e( 'The core idea is simple: easier delivery, practical setup, and layered foam structures that hold up to everyday sitting, hosting, and repeat use.', 'foam-form-commerce-kit' ); ?></p>
			</div>
			<div class="foam-technology-grid">
				<article class="foam-technology-card">
					<h3><?php esc_html_e( 'Compressed packaging', 'foam-form-commerce-kit' ); ?></h3>
					<p><?php esc_html_e( 'Large furniture pieces are prepared for delivery in a more compact format, which is especially useful in apartment buildings and narrower entries.', 'foam-form-commerce-kit' ); ?></p>
				</article>
				<article class="foam-technology-card">
					<h3><?php esc_html_e( 'Layered support foam', 'foam-form-commerce-kit' ); ?></h3>
					<p><?php esc_html_e( 'Different foam layers are used to balance structure, softness, and recovery after repeated use.', 'foam-form-commerce-kit' ); ?></p>
				</article>
				<article class="foam-technology-card">
					<h3><?php esc_html_e( 'No tool setup', 'foam-form-commerce-kit' ); ?></h3>
					<p><?php esc_html_e( 'The intended experience is straightforward: unpack, allow the form to settle, and place it directly in the room.', 'foam-form-commerce-kit' ); ?></p>
				</article>
			</div>
			<div class="foam-link-row foam-link-row--section">
				<a class="foam-text-link" href="<?php echo esc_url( $faq_url ); ?>"><?php esc_html_e( 'Read setup and shipping questions', 'foam-form-commerce-kit' ); ?></a>
			</div>
		</section>

		<section class="foam-home-section">
			<div class="foam-section-heading">
				<p class="foam-kicker"><?php esc_html_e( 'Lifestyle', 'foam-form-commerce-kit' ); ?></p>
				<h2><?php esc_html_e( 'Shown inside rooms that feel calm, practical, and lived in', 'foam-form-commerce-kit' ); ?></h2>
				<p><?php esc_html_e( 'The imagery is meant to clarify proportion and mood rather than over-style the room. Natural light, quieter layouts, and softer edges remain the focus.', 'foam-form-commerce-kit' ); ?></p>
			</div>
			<div class="foam-lifestyle-gallery">
				<article class="foam-lifestyle-tile foam-lifestyle-tile--apartment"><div><span><?php esc_html_e( 'Small Apartments', 'foam-form-commerce-kit' ); ?></span><strong><?php esc_html_e( 'Open floor plans with softer edges and cleaner circulation', 'foam-form-commerce-kit' ); ?></strong></div></article>
				<article class="foam-lifestyle-tile foam-lifestyle-tile--living"><div><span><?php esc_html_e( 'Living Rooms', 'foam-form-commerce-kit' ); ?></span><strong><?php esc_html_e( 'Quiet arrangements for sitting, reading, and regular daily use', 'foam-form-commerce-kit' ); ?></strong></div></article>
				<article class="foam-lifestyle-tile foam-lifestyle-tile--reading"><div><span><?php esc_html_e( 'Reading Corners', 'foam-form-commerce-kit' ); ?></span><strong><?php esc_html_e( 'Compact seating that does not visually overload a room', 'foam-form-commerce-kit' ); ?></strong></div></article>
				<article class="foam-lifestyle-tile foam-lifestyle-tile--guest"><div><span><?php esc_html_e( 'Guest Rooms', 'foam-form-commerce-kit' ); ?></span><strong><?php esc_html_e( 'Flexible pieces that move between occasional hosting and everyday use', 'foam-form-commerce-kit' ); ?></strong></div></article>
			</div>
		</section>

		<section class="foam-home-section foam-home-section--editorial-gallery">
			<div class="foam-section-heading foam-section-heading--split">
				<div>
					<p class="foam-kicker"><?php esc_html_e( 'Editorial view', 'foam-form-commerce-kit' ); ?></p>
					<h2><?php esc_html_e( 'A lighter domestic palette for customers who want softness without visual heaviness', 'foam-form-commerce-kit' ); ?></h2>
				</div>
				<p><?php esc_html_e( 'These scenes broaden the brand world beyond darker upholstery, showing how the collection also settles naturally into brighter interiors with quieter contrast and more air around the furniture.', 'foam-form-commerce-kit' ); ?></p>
			</div>
			<div class="foam-home-editorial-grid">
				<figure class="foam-home-editorial-media">
					<img src="<?php echo esc_url( $white_sofa_url ); ?>" alt="<?php esc_attr_e( 'White modular foam sofa in a bright minimalist interior', 'foam-form-commerce-kit' ); ?>" loading="lazy" decoding="async" />
				</figure>
				<article class="foam-home-editorial-copy">
					<span><?php esc_html_e( 'Light-filled rooms', 'foam-form-commerce-kit' ); ?></span>
					<h3><?php esc_html_e( 'Minimal silhouettes, plant accents, and a quieter domestic mood', 'foam-form-commerce-kit' ); ?></h3>
					<p><?php esc_html_e( 'For customers comparing styles, this helps clarify that the collection can sit inside both darker editorial apartments and brighter Scandinavian-inspired rooms without breaking the overall brand restraint.', 'foam-form-commerce-kit' ); ?></p>
					<a class="foam-text-link" href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Browse adaptable seating', 'foam-form-commerce-kit' ); ?></a>
				</article>
				<figure class="foam-home-editorial-media foam-home-editorial-media--warm">
					<img src="<?php echo esc_url( $lifestyle_sofa_url ); ?>" alt="<?php esc_attr_e( 'Soft foam sofa styled with warm cushions and natural light', 'foam-form-commerce-kit' ); ?>" loading="lazy" decoding="async" />
				</figure>
			</div>
		</section>

		<section class="foam-home-section foam-home-section--reviews">
			<div class="foam-section-heading">
				<p class="foam-kicker"><?php esc_html_e( 'Reviews', 'foam-form-commerce-kit' ); ?></p>
				<h2><?php esc_html_e( 'Practical reassurance, kept quiet and believable', 'foam-form-commerce-kit' ); ?></h2>
				<p><?php esc_html_e( 'Delivery clarity, room fit, and day-to-day usefulness do more to reduce uncertainty than louder claims. These notes stay close to that reality.', 'foam-form-commerce-kit' ); ?></p>
			</div>
			<div class="foam-review-summary">
				<div><strong><?php esc_html_e( '4.8/5', 'foam-form-commerce-kit' ); ?></strong><span><?php esc_html_e( 'Average rating across reviewed items', 'foam-form-commerce-kit' ); ?></span></div>
				<div><strong><?php esc_html_e( '10,000+', 'foam-form-commerce-kit' ); ?></strong><span><?php esc_html_e( 'Households served across living, guest, and small-space setups', 'foam-form-commerce-kit' ); ?></span></div>
				<div><strong><?php esc_html_e( '3-5 min', 'foam-form-commerce-kit' ); ?></strong><span><?php esc_html_e( 'Typical setup window once packaging is opened', 'foam-form-commerce-kit' ); ?></span></div>
			</div>
			<div class="foam-review-grid">
				<div class="foam-review-card"><h3><?php esc_html_e( 'Worked well in a narrow apartment entry', 'foam-form-commerce-kit' ); ?></h3><p><?php esc_html_e( 'The compact delivery format mattered more than expected. It reduced the usual concern around stairs and hallway clearance.', 'foam-form-commerce-kit' ); ?></p><strong><?php esc_html_e( 'Maya, Chicago', 'foam-form-commerce-kit' ); ?></strong></div>
				<div class="foam-review-card"><h3><?php esc_html_e( 'The room still feels calm after adding it', 'foam-form-commerce-kit' ); ?></h3><p><?php esc_html_e( 'The size feels considered, not oversized. It gives the room function without making the layout feel crowded.', 'foam-form-commerce-kit' ); ?></p><strong><?php esc_html_e( 'Derek, Austin', 'foam-form-commerce-kit' ); ?></strong></div>
				<div class="foam-review-card"><h3><?php esc_html_e( 'Useful for guest stays without changing the room permanently', 'foam-form-commerce-kit' ); ?></h3><p><?php esc_html_e( 'It is easy to keep the room as a daily living space and still make it ready for visitors when needed.', 'foam-form-commerce-kit' ); ?></p><strong><?php esc_html_e( 'Lena, San Diego', 'foam-form-commerce-kit' ); ?></strong></div>
			</div>
		</section>

		<section class="foam-home-section foam-home-section--cta">
			<div class="foam-cta-panel">
				<div>
					<p class="foam-kicker"><?php esc_html_e( 'Continue browsing', 'foam-form-commerce-kit' ); ?></p>
					<h2><?php esc_html_e( 'Browse the collection once the room mood, material logic, and delivery approach feel understood.', 'foam-form-commerce-kit' ); ?></h2>
				</div>
				<div class="foam-link-row foam-link-row--section">
					<a class="foam-text-link" href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Browse the full collection', 'foam-form-commerce-kit' ); ?></a>
					<a class="foam-text-link" href="<?php echo esc_url( $about_url ); ?>"><?php esc_html_e( 'Read the brand and material approach', 'foam-form-commerce-kit' ); ?></a>
				</div>
			</div>
		</section>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Shop intro shortcode.
	 *
	 * @return string
	 */
	public function render_shop_intro() {
		$filters        = $this->get_shop_intro_filters();
		$context        = $this->get_shop_intro_context( $filters );
		$night_sofa_url = $this->get_editorial_asset_url( 'sonovafurn-editorial-shop-night.jpg' );

		ob_start();
		?>
		<div class="foam-section-card foam-shop-toolbar">
			<div class="foam-section-heading foam-section-heading--shop-center">
				<div class="foam-shop-toolbar__intro">
					<p class="foam-kicker"><?php echo esc_html( $context['kicker'] ); ?></p>
					<h1 class="foam-heading--shop-two-line">
						<span><?php esc_html_e( 'A quieter product edit', 'foam-form-commerce-kit' ); ?></span>
						<span><?php esc_html_e( 'for compact rooms and flexible living', 'foam-form-commerce-kit' ); ?></span>
					</h1>
				</div>
				<p class="foam-shop-toolbar__summary"><?php esc_html_e( 'Browse by use case first: living, hosting, sleeping, and small-space flexibility. The shop is structured to feel more like an edited collection than a crowded catalog.', 'foam-form-commerce-kit' ); ?></p>
			</div>
			<div class="foam-shop-promo">
				<div class="foam-shop-promo__media">
					<img src="<?php echo esc_url( $night_sofa_url ); ?>" alt="<?php esc_attr_e( 'Dark modular sofa in a modern evening apartment interior', 'foam-form-commerce-kit' ); ?>" loading="eager" fetchpriority="high" decoding="async" />
				</div>
				<div class="foam-shop-promo__copy">
					<span class="foam-shop-promo__eyebrow"><?php esc_html_e( 'Editorial note', 'foam-form-commerce-kit' ); ?></span>
					<h2 class="foam-shop-promo__title">
						<span class="foam-shop-promo__title-line"><?php esc_html_e( 'Furniture that helps a room shift', 'foam-form-commerce-kit' ); ?></span>
						<span class="foam-shop-promo__title-line"><?php esc_html_e( 'between comfort and hosting', 'foam-form-commerce-kit' ); ?></span>
					</h2>
					<p><?php esc_html_e( 'Compressed formats, layered foam support, and practical silhouettes are presented with enough context to compare calmly before moving into product detail.', 'foam-form-commerce-kit' ); ?></p>
				</div>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Render editorial content shells for core brand pages.
	 *
	 * @param string $content Original page content.
	 * @return string
	 */
	public function render_editorial_content_pages( $content ) {
		if ( is_admin() || ! is_main_query() || ! in_the_loop() || ! is_page() ) {
			return $content;
		}

		$page = get_queried_object();
		if ( ! $page instanceof WP_Post ) {
			return $content;
		}

		if ( 'about-us' === $page->post_name ) {
			return $this->render_about_page();
		}

		if ( 'blog' === $page->post_name ) {
			return $this->render_blog_page();
		}

		if ( 'contact' === $page->post_name ) {
			return $this->render_contact_page();
		}

		if ( 'faq' === $page->post_name ) {
			return $this->render_faq_page();
		}

		if ( 'shipping-policy' === $page->post_name ) {
			return $this->render_shipping_delivery_page();
		}

		if ( 'return-policy' === $page->post_name ) {
			return $this->render_legal_policy_page(
				'return-policy',
				__( 'Returns', 'foam-form-commerce-kit' ),
				__( 'Return & Refund Policy', 'foam-form-commerce-kit' ),
				__( 'A clearer outline of eligibility, product condition, foam recovery expectations, and how support reviews return-related cases.', 'foam-form-commerce-kit' )
			);
		}

		if ( 'privacy-policy-2' === $page->post_name ) {
			return $this->render_legal_policy_page(
				'privacy-policy-2',
				__( 'Privacy', 'foam-form-commerce-kit' ),
				__( 'Privacy Policy', 'foam-form-commerce-kit' ),
				__( 'A straightforward overview of how Sonovafurn collects, uses, stores, and protects customer information across browsing, checkout, and support.', 'foam-form-commerce-kit' )
			);
		}

		if ( 'terms-of-service' === $page->post_name ) {
			return $this->render_legal_policy_page(
				'terms-of-service',
				__( 'Legal', 'foam-form-commerce-kit' ),
				__( 'Terms & Conditions', 'foam-form-commerce-kit' ),
				__( 'These terms explain the operating rules, purchase conditions, product notices, and policy structure that apply when using sonovafurn.com.', 'foam-form-commerce-kit' )
			);
		}

		return $content;
	}

	/**
	 * Render about page.
	 *
	 * @return string
	 */
	protected function render_about_page() {
		$hero_living_url = $this->get_editorial_asset_url( 'sonovafurn-editorial-hero-living.jpg' );
		$white_sofa_url  = $this->get_editorial_asset_url( 'sonovafurn-editorial-white-sofa.jpg' );

		ob_start();
		?>
		<div class="foam-editorial-page foam-editorial-page--about">
			<div class="foam-editorial-shell foam-editorial-shell--about">
			<section class="foam-editorial-hero foam-editorial-hero--about">
				<div class="foam-section-heading foam-section-heading--center">
					<div>
						<p class="foam-kicker"><?php esc_html_e( 'About', 'foam-form-commerce-kit' ); ?></p>
						<h1><?php esc_html_e( 'Foam furniture designed for smaller homes that still want visual calm', 'foam-form-commerce-kit' ); ?></h1>
					</div>
					<p><?php esc_html_e( 'Sonovafurn is built around compressed sofa beds, layered foam structures, and room-friendly proportions for apartments, guest rooms, and more flexible domestic layouts.', 'foam-form-commerce-kit' ); ?></p>
				</div>
			</section>

			<section class="foam-editorial-feature foam-editorial-feature--about">
				<figure class="foam-editorial-feature__media">
					<img src="<?php echo esc_url( $hero_living_url ); ?>" alt="<?php esc_attr_e( 'Soft living room with neutral sofa and plant accents', 'foam-form-commerce-kit' ); ?>" loading="eager" fetchpriority="high" decoding="async" />
				</figure>
				<div class="foam-editorial-feature__copy">
					<span><?php esc_html_e( 'Brand approach', 'foam-form-commerce-kit' ); ?></span>
					<h2><?php esc_html_e( 'Comfort is treated as room behavior, not only product softness', 'foam-form-commerce-kit' ); ?></h2>
					<p><?php esc_html_e( 'A sofa should enter the building more easily, sit more quietly in the room, and remain useful when the space shifts between work, rest, and hosting. That practical sequence shapes the collection from the beginning.', 'foam-form-commerce-kit' ); ?></p>
				</div>
			</section>

			<section class="foam-editorial-grid">
				<article class="foam-editorial-block">
					<span><?php esc_html_e( 'Design philosophy', 'foam-form-commerce-kit' ); ?></span>
					<h2><?php esc_html_e( 'A quieter visual language', 'foam-form-commerce-kit' ); ?></h2>
					<p><?php esc_html_e( 'The goal is not showroom excess. It is a lighter domestic feeling shaped by cleaner silhouettes, soft neutral materials, and layouts that leave the room breathable.', 'foam-form-commerce-kit' ); ?></p>
				</article>
				<article class="foam-editorial-block">
					<span><?php esc_html_e( 'Material approach', 'foam-form-commerce-kit' ); ?></span>
					<h2><?php esc_html_e( 'Support, recovery, and practical use', 'foam-form-commerce-kit' ); ?></h2>
					<p><?php esc_html_e( 'Foam density, surface texture, and compressed transport are treated as part of the product story from the beginning, because these choices shape how a piece feels over time.', 'foam-form-commerce-kit' ); ?></p>
				</article>
				<article class="foam-editorial-block">
					<span><?php esc_html_e( 'Small-space logic', 'foam-form-commerce-kit' ); ?></span>
					<h2><?php esc_html_e( 'Made for buildings and rooms with limits', 'foam-form-commerce-kit' ); ?></h2>
					<p><?php esc_html_e( 'Elevators, tighter entries, studio layouts, guest rooms, and multi-use spaces all influence the way these products are planned, packed, and placed.', 'foam-form-commerce-kit' ); ?></p>
				</article>
			</section>

			<section class="foam-editorial-gallery foam-editorial-gallery--about">
				<article class="foam-editorial-gallery__card">
					<img src="<?php echo esc_url( $white_sofa_url ); ?>" alt="<?php esc_attr_e( 'Bright white sofa in a softly lit room', 'foam-form-commerce-kit' ); ?>" loading="lazy" decoding="async" />
					<div>
						<span><?php esc_html_e( 'Visual tone', 'foam-form-commerce-kit' ); ?></span>
						<h3><?php esc_html_e( 'Lighter surfaces help larger pieces stay calmer in compact rooms', 'foam-form-commerce-kit' ); ?></h3>
					</div>
				</article>
				<article class="foam-editorial-gallery__card foam-editorial-gallery__card--text">
					<div>
						<span><?php esc_html_e( 'Operating principles', 'foam-form-commerce-kit' ); ?></span>
						<h3><?php esc_html_e( 'Useful proportions, restrained materials, and simpler setup stay at the center of every product decision', 'foam-form-commerce-kit' ); ?></h3>
						<p><?php esc_html_e( 'The result should feel calmer than fast furniture and more accessible than traditional showroom pricing.', 'foam-form-commerce-kit' ); ?></p>
					</div>
				</article>
			</section>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Render blog page.
	 *
	 * @return string
	 */
	protected function render_blog_page() {
		$hero_living_url = $this->get_editorial_asset_url( 'sonovafurn-editorial-hero-living.jpg' );
		$white_sofa_url  = $this->get_editorial_asset_url( 'sonovafurn-editorial-white-sofa.jpg' );
		$warm_sofa_url   = $this->get_editorial_asset_url( 'sonovafurn-editorial-lifestyle-sofa.jpg' );
		$posts = get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => 3,
			)
		);

		ob_start();
		?>
		<div class="foam-editorial-page foam-editorial-page--blog">
			<section class="foam-editorial-hero">
				<div class="foam-section-heading foam-section-heading--split">
					<div>
						<p class="foam-kicker"><?php esc_html_e( 'Journal', 'foam-form-commerce-kit' ); ?></p>
						<h1><?php esc_html_e( 'Notes on room planning, furniture comparison, and more practical buying decisions', 'foam-form-commerce-kit' ); ?></h1>
					</div>
					<p><?php esc_html_e( 'This section supports slower product decisions with clearer context: how a sofa fits in a studio, how compressed delivery changes setup, and how foam structure affects regular use.', 'foam-form-commerce-kit' ); ?></p>
				</div>
			</section>

			<section class="foam-editorial-feature">
				<figure class="foam-editorial-feature__media">
					<img src="<?php echo esc_url( $hero_living_url ); ?>" alt="<?php esc_attr_e( 'Editorial living room with a cream sofa and plant accents', 'foam-form-commerce-kit' ); ?>" loading="eager" fetchpriority="high" decoding="async" />
				</figure>
				<div class="foam-editorial-feature__copy">
					<span><?php esc_html_e( 'Editorial study', 'foam-form-commerce-kit' ); ?></span>
					<h2><?php esc_html_e( 'How softer materials and lighter tones change the way a room feels at apartment scale', 'foam-form-commerce-kit' ); ?></h2>
					<p><?php esc_html_e( 'Furniture decisions are not only about dimensions. Light reflection, surface texture, circulation, and visual weight all affect whether a compact room feels open or crowded.', 'foam-form-commerce-kit' ); ?></p>
				</div>
			</section>

			<section class="foam-editorial-grid foam-editorial-grid--blog">
				<article class="foam-editorial-block" id="space-planning">
					<span><?php esc_html_e( 'Space planning', 'foam-form-commerce-kit' ); ?></span>
					<h2><?php esc_html_e( 'Small Apartment Living Guide', 'foam-form-commerce-kit' ); ?></h2>
					<p><?php esc_html_e( 'How to use proportion, convertible furniture, and circulation to keep a smaller room functional and visually calm.', 'foam-form-commerce-kit' ); ?></p>
				</article>
				<article class="foam-editorial-block" id="comparison">
					<span><?php esc_html_e( 'Comparison', 'foam-form-commerce-kit' ); ?></span>
					<h2><?php esc_html_e( 'Compressed Sofa vs Traditional Sofa', 'foam-form-commerce-kit' ); ?></h2>
					<p><?php esc_html_e( 'A more useful comparison based on entry access, room fit, setup time, and everyday use rather than only headline pricing.', 'foam-form-commerce-kit' ); ?></p>
				</article>
				<article class="foam-editorial-block" id="buying-notes">
					<span><?php esc_html_e( 'Buying notes', 'foam-form-commerce-kit' ); ?></span>
					<h2><?php esc_html_e( 'How to choose a sofa for small space living', 'foam-form-commerce-kit' ); ?></h2>
					<p><?php esc_html_e( 'A straightforward checklist for scale, seat depth, sleeping flexibility, fabric, and how often the room needs to change role.', 'foam-form-commerce-kit' ); ?></p>
				</article>
			</section>

			<section class="foam-editorial-topic-row">
				<a class="foam-editorial-topic" href="<?php echo esc_url( home_url( '/blog/#space-planning' ) ); ?>">
					<span><?php esc_html_e( 'Guide', 'foam-form-commerce-kit' ); ?></span>
					<strong><?php esc_html_e( 'Small apartment planning', 'foam-form-commerce-kit' ); ?></strong>
				</a>
				<a class="foam-editorial-topic" href="<?php echo esc_url( home_url( '/blog/#comparison' ) ); ?>">
					<span><?php esc_html_e( 'Comparison', 'foam-form-commerce-kit' ); ?></span>
					<strong><?php esc_html_e( 'Compressed versus traditional sofas', 'foam-form-commerce-kit' ); ?></strong>
				</a>
				<a class="foam-editorial-topic" href="<?php echo esc_url( home_url( '/blog/#buying-notes' ) ); ?>">
					<span><?php esc_html_e( 'Checklist', 'foam-form-commerce-kit' ); ?></span>
					<strong><?php esc_html_e( 'Choosing for multi-use rooms', 'foam-form-commerce-kit' ); ?></strong>
				</a>
			</section>

			<section class="foam-editorial-gallery">
				<article class="foam-editorial-gallery__card">
					<img src="<?php echo esc_url( $white_sofa_url ); ?>" alt="<?php esc_attr_e( 'White foam sofa in a bright modern room', 'foam-form-commerce-kit' ); ?>" loading="lazy" decoding="async" />
					<div>
						<span><?php esc_html_e( 'Material mood', 'foam-form-commerce-kit' ); ?></span>
						<h3><?php esc_html_e( 'When boucle textures and pale upholstery keep larger pieces visually lighter', 'foam-form-commerce-kit' ); ?></h3>
					</div>
				</article>
				<article class="foam-editorial-gallery__card">
					<img src="<?php echo esc_url( $warm_sofa_url ); ?>" alt="<?php esc_attr_e( 'Lifestyle sofa scene with warm styling and indoor plants', 'foam-form-commerce-kit' ); ?>" loading="lazy" decoding="async" />
					<div>
						<span><?php esc_html_e( 'Lifestyle contrast', 'foam-form-commerce-kit' ); ?></span>
						<h3><?php esc_html_e( 'How warmer accessories can soften a minimalist shell without making it feel busy', 'foam-form-commerce-kit' ); ?></h3>
					</div>
				</article>
			</section>

			<?php if ( ! empty( $posts ) ) : ?>
				<section class="foam-editorial-posts">
					<div class="foam-section-heading">
						<p class="foam-kicker"><?php esc_html_e( 'Latest posts', 'foam-form-commerce-kit' ); ?></p>
						<h2><?php esc_html_e( 'Published articles', 'foam-form-commerce-kit' ); ?></h2>
					</div>
					<div class="foam-editorial-grid foam-editorial-grid--posts">
						<?php foreach ( $posts as $post ) : ?>
							<article class="foam-editorial-block">
								<span><?php echo esc_html( get_the_date( 'F j, Y', $post ) ); ?></span>
								<h2><?php echo esc_html( get_the_title( $post ) ); ?></h2>
								<p><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $post->post_content ), 22 ) ); ?></p>
								<a class="foam-text-link" href="<?php echo esc_url( get_permalink( $post ) ); ?>"><?php esc_html_e( 'Read article', 'foam-form-commerce-kit' ); ?></a>
							</article>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Render contact page.
	 *
	 * @return string
	 */
	protected function render_contact_page() {
		$faq_url             = home_url( '/faq/' );
		$shipping_policy_url = home_url( '/shipping-policy/' );
		$returns_policy_url  = home_url( '/return-policy/' );

		ob_start();
		?>
		<div class="foam-editorial-page foam-editorial-page--contact">
			<section class="foam-editorial-hero">
				<div class="foam-section-heading foam-section-heading--split">
					<div>
						<p class="foam-kicker"><?php esc_html_e( 'Contact', 'foam-form-commerce-kit' ); ?></p>
						<h1><?php esc_html_e( 'Support for product fit, delivery timing, and after-purchase questions', 'foam-form-commerce-kit' ); ?></h1>
					</div>
					<p><?php esc_html_e( 'Reach out for help with room fit, material questions, shipping expectations, or returns. The support flow is designed to stay calm, clear, and useful before and after purchase.', 'foam-form-commerce-kit' ); ?></p>
				</div>
			</section>

			<section class="foam-editorial-feature foam-editorial-feature--contact">
				<div class="foam-contact-card">
					<div class="foam-contact-card__group">
						<span><?php esc_html_e( 'Email', 'foam-form-commerce-kit' ); ?></span>
						<h2><?php esc_html_e( 'sonovafurnservice@outlook.com', 'foam-form-commerce-kit' ); ?></h2>
						<p><?php esc_html_e( 'The best channel for order questions, product fit notes, or return-related follow-up.', 'foam-form-commerce-kit' ); ?></p>
					</div>
					<div class="foam-contact-card__group">
						<span><?php esc_html_e( 'Support hours', 'foam-form-commerce-kit' ); ?></span>
						<h3><?php esc_html_e( '10:00 AM to 16:00 PM', 'foam-form-commerce-kit' ); ?></h3>
						<p><?php esc_html_e( 'We aim to keep responses clear and practical, with help centered on delivery, setup, and product guidance.', 'foam-form-commerce-kit' ); ?></p>
					</div>
					<div class="foam-contact-card__group">
						<span><?php esc_html_e( 'Address', 'foam-form-commerce-kit' ); ?></span>
						<h3><?php esc_html_e( 'UNIT A53M116 29/F, LEGEND TOWER 7 SHING YIP STREET, KWUN TONG, KL', 'foam-form-commerce-kit' ); ?></h3>
					</div>
					<div class="foam-contact-card__group">
						<span><?php esc_html_e( 'Helpful links', 'foam-form-commerce-kit' ); ?></span>
						<div class="foam-contact-card__link-grid">
							<a class="foam-contact-card__link" href="<?php echo esc_url( $faq_url ); ?>">
								<strong><?php esc_html_e( 'FAQ', 'foam-form-commerce-kit' ); ?></strong>
								<p><?php esc_html_e( 'Shipping, compression safety, setup, and warranty basics.', 'foam-form-commerce-kit' ); ?></p>
							</a>
							<a class="foam-contact-card__link" href="<?php echo esc_url( $shipping_policy_url ); ?>">
								<strong><?php esc_html_e( 'Shipping & Delivery', 'foam-form-commerce-kit' ); ?></strong>
								<p><?php esc_html_e( 'Delivery timing, shipping structure, and collection-level details.', 'foam-form-commerce-kit' ); ?></p>
							</a>
							<a class="foam-contact-card__link" href="<?php echo esc_url( $returns_policy_url ); ?>">
								<strong><?php esc_html_e( 'Return & Refund Policy', 'foam-form-commerce-kit' ); ?></strong>
								<p><?php esc_html_e( 'Eligibility, condition expectations, and return handling notes.', 'foam-form-commerce-kit' ); ?></p>
							</a>
						</div>
					</div>
				</div>

				<div class="foam-contact-form-shell">
					<?php echo $this->render_contact_form_placeholder(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</section>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Render FAQ page.
	 *
	 * @return string
	 */
	protected function render_faq_page() {
		$shipping_policy_url = home_url( '/shipping-policy/' );
		$returns_policy_url  = home_url( '/return-policy/' );
		$privacy_policy_url  = home_url( '/privacy-policy-2/' );
		$terms_policy_url    = home_url( '/terms-of-service/' );
		$contact_url         = home_url( '/contact/' );

		$faqs = array(
			array(
				'q' => __( 'How long does shipping take?', 'foam-form-commerce-kit' ),
				'a' => __( 'Most in-stock sofa orders dispatch within one to two business days. Final timing depends on destination and local carrier scheduling.', 'foam-form-commerce-kit' ),
			),
			array(
				'q' => __( 'Is compressed shipping safe for foam furniture?', 'foam-form-commerce-kit' ),
				'a' => __( 'Yes. The compressed format is intended to help with transport and access. Products are designed to recover shape after unpacking and settling.', 'foam-form-commerce-kit' ),
			),
			array(
				'q' => __( 'Does setup require tools or assembly?', 'foam-form-commerce-kit' ),
				'a' => __( 'Most formats are designed for simple unboxing and expansion rather than a separate assembly process.', 'foam-form-commerce-kit' ),
			),
			array(
				'q' => __( 'What is the return policy?', 'foam-form-commerce-kit' ),
				'a' => __( 'Returns are accepted within the stated policy window when items remain in original condition. Full details are listed on the policy pages.', 'foam-form-commerce-kit' ),
			),
			array(
				'q' => __( 'Is there a warranty?', 'foam-form-commerce-kit' ),
				'a' => __( 'Most compressed sofas include a limited warranty. Coverage details vary by product and are listed on the product page or policy documents.', 'foam-form-commerce-kit' ),
			),
		);

		ob_start();
		?>
		<div class="foam-editorial-page foam-editorial-page--faq">
			<section class="foam-editorial-hero">
				<div class="foam-section-heading foam-section-heading--split">
					<div>
						<p class="foam-kicker"><?php esc_html_e( 'FAQ', 'foam-form-commerce-kit' ); ?></p>
						<h1><?php esc_html_e( 'Shipping, setup, compression safety, and returns explained simply', 'foam-form-commerce-kit' ); ?></h1>
					</div>
					<p><?php esc_html_e( 'The aim is to reduce uncertainty before purchase by keeping the most common questions close to product, delivery, and room-fit decisions.', 'foam-form-commerce-kit' ); ?></p>
				</div>
			</section>

			<section class="foam-editorial-faq">
				<?php foreach ( $faqs as $faq ) : ?>
					<details class="foam-editorial-faq__item">
						<summary><?php echo esc_html( $faq['q'] ); ?></summary>
						<p><?php echo esc_html( $faq['a'] ); ?></p>
					</details>
				<?php endforeach; ?>
			</section>

			<section class="foam-editorial-policy-map">
				<div class="foam-section-heading">
					<p class="foam-kicker"><?php esc_html_e( 'Policy map', 'foam-form-commerce-kit' ); ?></p>
					<h2><?php esc_html_e( 'If a question needs more detail, move directly into the relevant policy page', 'foam-form-commerce-kit' ); ?></h2>
				</div>
				<div class="foam-editorial-topic-row foam-editorial-topic-row--policies">
					<a class="foam-editorial-topic" href="<?php echo esc_url( $shipping_policy_url ); ?>">
						<span><?php esc_html_e( 'Delivery', 'foam-form-commerce-kit' ); ?></span>
						<strong><?php esc_html_e( 'Shipping & Delivery', 'foam-form-commerce-kit' ); ?></strong>
					</a>
					<a class="foam-editorial-topic" href="<?php echo esc_url( $returns_policy_url ); ?>">
						<span><?php esc_html_e( 'Returns', 'foam-form-commerce-kit' ); ?></span>
						<strong><?php esc_html_e( 'Return & Refund Policy', 'foam-form-commerce-kit' ); ?></strong>
					</a>
					<a class="foam-editorial-topic" href="<?php echo esc_url( $privacy_policy_url ); ?>">
						<span><?php esc_html_e( 'Privacy', 'foam-form-commerce-kit' ); ?></span>
						<strong><?php esc_html_e( 'Privacy Policy', 'foam-form-commerce-kit' ); ?></strong>
					</a>
					<a class="foam-editorial-topic" href="<?php echo esc_url( $terms_policy_url ); ?>">
						<span><?php esc_html_e( 'Legal', 'foam-form-commerce-kit' ); ?></span>
						<strong><?php esc_html_e( 'Terms & Conditions', 'foam-form-commerce-kit' ); ?></strong>
					</a>
					<a class="foam-editorial-topic" href="<?php echo esc_url( $contact_url ); ?>">
						<span><?php esc_html_e( 'Support', 'foam-form-commerce-kit' ); ?></span>
						<strong><?php esc_html_e( 'Contact Sonovafurn', 'foam-form-commerce-kit' ); ?></strong>
					</a>
				</div>
			</section>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Render shipping and delivery page.
	 *
	 * @return string
	 */
	protected function render_shipping_delivery_page() {
		$delivery_image_url = $this->get_editorial_asset_url( 'sonovafurn-shipping-delivery.jpg' );
		$shipping_rates = array(
			__( 'Orders over $1,000 ship at a flat rate of $69.', 'foam-form-commerce-kit' ),
			__( 'Orders between $500 and $1,000 ship at a flat rate of $49.', 'foam-form-commerce-kit' ),
			__( 'Orders between $100 and $500 ship at a flat rate of $39.', 'foam-form-commerce-kit' ),
			__( 'Orders under $100 ship free.', 'foam-form-commerce-kit' ),
		);

		$delivery_groups = array(
			array(
				'title' => __( 'Nomad Seating Collection', 'foam-form-commerce-kit' ),
				'items' => array(
					__( 'You will generally receive one more box than the number of seats you ordered, plus additional boxes for pieces like an ottoman or chaise.', 'foam-form-commerce-kit' ),
					__( 'A standard 3-seat sofa with an ottoman usually arrives in 5 boxes.', 'foam-form-commerce-kit' ),
					__( 'Each box weighs around 40 pounds and fits through just about any door.', 'foam-form-commerce-kit' ),
				),
			),
			array(
				'title' => __( 'OVER Seating Collection', 'foam-form-commerce-kit' ),
				'items' => array(
					__( 'The OVER Collection ships directly from our warehouse to your door.', 'foam-form-commerce-kit' ),
					__( 'You will generally receive one box for each seat, plus additional boxes for pieces like an ottoman or table.', 'foam-form-commerce-kit' ),
					__( 'All orders ship with a separate leg box.', 'foam-form-commerce-kit' ),
					__( 'Each seat box weighs around 40 pounds and fits through just about any door.', 'foam-form-commerce-kit' ),
					__( 'All other boxes typically weigh between 10 and 20 pounds each.', 'foam-form-commerce-kit' ),
				),
			),
			array(
				'title' => __( 'Coffee and side tables', 'foam-form-commerce-kit' ),
				'items' => array(
					__( 'You will receive 1 to 2 boxes for each table.', 'foam-form-commerce-kit' ),
				),
			),
			array(
				'title' => __( 'Rugs and rug pads', 'foam-form-commerce-kit' ),
				'items' => array(
					__( 'Rugs and rug pads ship from our warehouse in New Jersey.', 'foam-form-commerce-kit' ),
					__( 'Your rug will arrive rolled in protective packaging and may take some time to lie completely flat.', 'foam-form-commerce-kit' ),
				),
			),
			array(
				'title' => __( 'Credenzas and benches', 'foam-form-commerce-kit' ),
				'items' => array(
					__( 'Your credenza or bench will arrive in 1 to 2 boxes.', 'foam-form-commerce-kit' ),
					__( 'Like our modular seating system, setup is designed to take just a few minutes without tools.', 'foam-form-commerce-kit' ),
				),
			),
			array(
				'title' => __( 'Shelves', 'foam-form-commerce-kit' ),
				'items' => array(
					__( 'Each shelving unit arrives in a separate box.', 'foam-form-commerce-kit' ),
				),
			),
			array(
				'title' => __( 'Accessories', 'foam-form-commerce-kit' ),
				'items' => array(
					__( 'Accessories such as accent pillows and throw blankets ship through a mix of standard delivery carriers.', 'foam-form-commerce-kit' ),
				),
			),
			array(
				'title' => __( 'Carts and trays', 'foam-form-commerce-kit' ),
				'items' => array(
					__( 'Delivery windows can vary by region.', 'foam-form-commerce-kit' ),
				),
			),
			array(
				'title' => __( 'Field Seating Collection', 'foam-form-commerce-kit' ),
				'items' => array(
					__( 'The Field Collection ships directly from our warehouse to your door.', 'foam-form-commerce-kit' ),
					__( 'You will generally receive one box for each seat, plus additional boxes for pieces like an ottoman.', 'foam-form-commerce-kit' ),
					__( 'All orders ship with a separate leg box.', 'foam-form-commerce-kit' ),
					__( 'Each seat box weighs around 40 pounds and fits through just about any door.', 'foam-form-commerce-kit' ),
					__( 'All other boxes typically weigh between 10 and 20 pounds each.', 'foam-form-commerce-kit' ),
				),
			),
		);

		ob_start();
		?>
		<div class="foam-editorial-page foam-editorial-page--shipping">
			<section class="foam-editorial-hero">
				<div class="foam-section-heading foam-section-heading--split">
					<div>
						<p class="foam-kicker"><?php esc_html_e( 'Shipping', 'foam-form-commerce-kit' ); ?></p>
						<h1><?php esc_html_e( 'Shipping & Delivery', 'foam-form-commerce-kit' ); ?></h1>
					</div>
					<p><?php esc_html_e( 'We use parcel-friendly packaging and standard carrier networks to keep delivery more efficient, more predictable, and easier to manage for modern furniture orders.', 'foam-form-commerce-kit' ); ?></p>
				</div>
			</section>

			<div class="foam-legal-page">
				<div class="foam-legal-intro-wrap">
					<div class="foam-legal-intro-copy">
						<p class="foam-legal-intro"><?php esc_html_e( 'We offer fast, affordable, flat-rate delivery on every order. Because our modular formats fit into standard shipping boxes, many items can travel through normal delivery networks such as UPS and FedEx rather than special freight services.', 'foam-form-commerce-kit' ); ?></p>
					</div>
					<?php if ( $delivery_image_url ) : ?>
						<figure class="foam-legal-intro-media">
					<img src="<?php echo esc_url( $delivery_image_url ); ?>" alt="<?php esc_attr_e( 'Sonovafurn delivery box arriving at a front door', 'foam-form-commerce-kit' ); ?>" loading="eager" fetchpriority="high" decoding="async" />
						</figure>
					<?php endif; ?>
				</div>

				<h2><?php esc_html_e( 'Shipping rates', 'foam-form-commerce-kit' ); ?></h2>
				<ul class="foam-legal-list">
					<?php foreach ( $shipping_rates as $rate ) : ?>
						<li><?php echo esc_html( $rate ); ?></li>
					<?php endforeach; ?>
				</ul>

				<h2><?php esc_html_e( 'How long will it take?', 'foam-form-commerce-kit' ); ?></h2>
				<p><?php esc_html_e( 'We have always focused on some of the fastest delivery timelines in the category. For the most accurate estimate, please check the product-level shipping message located right below the Add to Cart area on each product page.', 'foam-form-commerce-kit' ); ?></p>
				<p><?php esc_html_e( 'You can also browse our Ready to Ship selection for items that are currently in stock and expected to arrive within three weeks.', 'foam-form-commerce-kit' ); ?></p>

				<h2><?php esc_html_e( 'Delivery details by collection', 'foam-form-commerce-kit' ); ?></h2>
				<ul class="foam-legal-list foam-legal-policy-list">
					<?php foreach ( $delivery_groups as $group ) : ?>
						<li>
							<strong><?php echo esc_html( $group['title'] ); ?></strong>
							<ul class="foam-legal-sublist">
								<?php foreach ( $group['items'] as $item ) : ?>
									<li><?php echo esc_html( $item ); ?></li>
								<?php endforeach; ?>
							</ul>
						</li>
					<?php endforeach; ?>
				</ul>

				<h2><?php esc_html_e( 'Backordered items', 'foam-form-commerce-kit' ); ?></h2>
				<p><?php esc_html_e( 'Some of our most popular styles are occasionally backordered. If your order includes a backordered item, we will ship that item as soon as it becomes available again.', 'foam-form-commerce-kit' ); ?></p>

				<h2><?php esc_html_e( 'Trade & commercial orders', 'foam-form-commerce-kit' ); ?></h2>
				<p><?php esc_html_e( 'Large trade or commercial orders are handled differently from standard residential orders. Lead times may be longer depending on order size, product mix, and delivery location. For the most accurate estimate, please contact your Trade Program representative.', 'foam-form-commerce-kit' ); ?></p>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Render a legal policy page with a unified editorial shell.
	 *
	 * @param string $slug   Page slug.
	 * @param string $kicker Hero kicker.
	 * @param string $title  Hero title.
	 * @param string $intro  Hero intro copy.
	 * @return string
	 */
	protected function render_legal_policy_page( $slug, $kicker, $title, $intro ) {
		$page = get_page_by_path( $slug );
		if ( ! $page instanceof WP_Post ) {
			return '';
		}

		$content = do_shortcode( (string) $page->post_content );

		ob_start();
		?>
		<div class="foam-editorial-page foam-editorial-page--policy">
			<section class="foam-editorial-hero">
				<div class="foam-section-heading foam-section-heading--split">
					<div>
						<p class="foam-kicker"><?php echo esc_html( $kicker ); ?></p>
						<h1><?php echo esc_html( $title ); ?></h1>
					</div>
					<p><?php echo esc_html( $intro ); ?></p>
				</div>
			</section>

			<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Render breadcrumbs wrapper.
	 *
	 * @return void
	 */
	public function render_store_breadcrumbs() {
		if ( ! function_exists( 'woocommerce_breadcrumb' ) ) {
			return;
		}

		if ( ! is_shop() && ! is_product() && ! is_product_taxonomy() ) {
			return;
		}

		if ( is_shop() || is_product_taxonomy() || $this->is_product_search_context() ) {
			return;
		}

		echo '<div class="foam-breadcrumb-shell">';
		woocommerce_breadcrumb();
		echo '</div>';
	}

	/**
	 * Remove native archive descriptions so curated intro blocks can render cleanly.
	 *
	 * @param string $description Archive description.
	 * @return string
	 */
	public function filter_store_archive_description( $description ) {
		if ( function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() ) ) {
			return '';
		}

		return $description;
	}

	/**
	 * Render shop intro on archive templates.
	 *
	 * @return void
	 */
	public function render_shop_intro_hook() {
		if ( ! function_exists( 'is_shop' ) ) {
			return;
		}

		if ( is_shop() || is_product_taxonomy() ) {
			echo wp_kses_post( $this->render_shop_intro() );
		}
	}

	/**
	 * Render contact form placeholder.
	 *
	 * @return string
	 */
	public function render_contact_form_placeholder() {
		ob_start();
		?>
		<form class="foam-section-card foam-contact-form">
			<p class="foam-kicker"><?php esc_html_e( 'Contact', 'foam-form-commerce-kit' ); ?></p>
			<h2><?php esc_html_e( 'Send a note about product fit, materials, or delivery questions', 'foam-form-commerce-kit' ); ?></h2>
			<p><?php esc_html_e( 'This form is prepared for a quieter support flow and can later connect to Elementor Forms, WPForms, or Klaviyo support routing.', 'foam-form-commerce-kit' ); ?></p>
			<p><label><?php esc_html_e( 'Name', 'foam-form-commerce-kit' ); ?><br><input type="text"></label></p>
			<p><label><?php esc_html_e( 'Email', 'foam-form-commerce-kit' ); ?><br><input type="email"></label></p>
			<p><label><?php esc_html_e( 'Message', 'foam-form-commerce-kit' ); ?><br><textarea rows="5"></textarea></label></p>
			<p><button type="button" class="foam-button"><?php esc_html_e( 'Send note', 'foam-form-commerce-kit' ); ?></button></p>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Render exit-intent popup.
	 *
	 * @return void
	 */
	public function render_exit_popup() {
		?>
		<div class="foam-exit-popup" aria-hidden="true">
			<div class="foam-exit-popup__backdrop"></div>
			<div class="foam-exit-popup__dialog" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Email updates signup', 'foam-form-commerce-kit' ); ?>">
				<button type="button" class="foam-exit-popup__close" aria-label="<?php esc_attr_e( 'Close popup', 'foam-form-commerce-kit' ); ?>">&times;</button>
				<p class="foam-kicker"><?php esc_html_e( 'Stay in touch', 'foam-form-commerce-kit' ); ?></p>
				<h2><?php esc_html_e( 'Receive product notes, room ideas, and occasional launch updates', 'foam-form-commerce-kit' ); ?></h2>
				<p><?php esc_html_e( 'Prepared for a slower email cadence focused on launches, room planning notes, and relevant follow-up rather than frequent promotions.', 'foam-form-commerce-kit' ); ?></p>
				<form class="foam-exit-popup__form foam-klaviyo-form" data-klaviyo-list="sonovafurn-welcome">
					<input type="email" name="email" placeholder="<?php esc_attr_e( 'Enter your email', 'foam-form-commerce-kit' ); ?>" required>
					<button type="submit" class="foam-button"><?php esc_html_e( 'Join the list', 'foam-form-commerce-kit' ); ?></button>
				</form>
				<small><?php esc_html_e( 'Prepared for a quieter email flow with launch notes and occasional reminders.', 'foam-form-commerce-kit' ); ?></small>
			</div>
		</div>
		<?php
	}

	/**
	 * Track viewed products for recommendation experiments.
	 *
	 * @return void
	 */
	public function capture_recently_viewed_products() {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}

		$product_id = get_the_ID();
		if ( ! $product_id ) {
			return;
		}

		$viewed = isset( $_COOKIE['foam_form_viewed_products'] ) ? explode( ',', sanitize_text_field( wp_unslash( $_COOKIE['foam_form_viewed_products'] ) ) ) : array();
		$viewed = array_filter( array_map( 'absint', $viewed ) );
		array_unshift( $viewed, (int) $product_id );
		$viewed = array_values( array_unique( $viewed ) );
		$viewed = array_slice( $viewed, 0, 8 );

		setcookie( 'foam_form_viewed_products', implode( ',', $viewed ), time() + MONTH_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/' );
	}

	/**
	 * Render a simple behavior-based recommendation block.
	 *
	 * @return string
	 */
	public function render_ai_recommendations() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return '';
		}

		$viewed = isset( $_COOKIE['foam_form_viewed_products'] ) ? explode( ',', sanitize_text_field( wp_unslash( $_COOKIE['foam_form_viewed_products'] ) ) ) : array();
		$viewed = array_filter( array_map( 'absint', $viewed ) );

		if ( empty( $viewed ) ) {
			$products = wc_get_products(
				array(
					'limit'    => 4,
					'status'   => 'publish',
					'orderby'  => 'date',
					'order'    => 'DESC',
					'category' => array( 'compressed-sofa-beds', 'space-saving-sofas', 'modular-sofas' ),
				)
			);
			$ids = wp_list_pluck( $products, 'id' );
		} else {
			$source_product = wc_get_product( $viewed[0] );
			$ids            = $source_product ? wc_get_related_products( $source_product->get_id(), 4 ) : array();
		}

		if ( empty( $ids ) ) {
			return '';
		}

		return '<div class="foam-section-card foam-ai-shell"><p class="foam-kicker">' . esc_html__( 'Related viewing', 'foam-form-commerce-kit' ) . '</p><h2>' . esc_html__( 'Additional pieces that follow a similar room story', 'foam-form-commerce-kit' ) . '</h2><p>' . esc_html__( 'This recommendation area is prepared for future behavior-based refinement and quieter follow-up through Klaviyo segmentation.', 'foam-form-commerce-kit' ) . '</p>' . do_shortcode( '[products ids="' . esc_attr( implode( ',', $ids ) ) . '" columns="4" orderby="post__in"]' ) . '</div>';
	}

	/**
	 * Force classic WooCommerce templates for shop and single product.
	 *
	 * @param array  $templates     Block templates.
	 * @param array  $query         Template query.
	 * @param string $template_type Template type.
	 * @return array
	 */
	public function disable_woo_block_templates( $templates, $query, $template_type ) {
		if ( 'wp_template' !== $template_type || empty( $templates ) || wp_is_block_theme() ) {
			return $templates;
		}

		$slugs = isset( $query['slug__in'] ) && is_array( $query['slug__in'] ) ? $query['slug__in'] : array();
		if ( empty( array_intersect( $slugs, array( 'single-product', 'archive-product' ) ) ) ) {
			return $templates;
		}

		return array_values(
			array_filter(
				$templates,
				static function ( $template ) {
					return ! (
						isset( $template->plugin, $template->slug ) &&
						'woocommerce' === $template->plugin &&
						in_array( $template->slug, array( 'single-product', 'archive-product' ), true )
					);
				}
			)
		);
	}
}

