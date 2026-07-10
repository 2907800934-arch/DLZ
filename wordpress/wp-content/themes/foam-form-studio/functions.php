<?php
/**
 * Theme bootstrap for sonovafurn Studio.
 *
 * @package FoamFormStudio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FOAM_FORM_THEME_VERSION', '1.1.0' );

/**
 * Determine whether the front-end design lab should render.
 *
 * @return bool
 */
function foam_form_should_load_design_lab() {
	return false;
}

/**
 * Build front-end design lab payload for the current page.
 *
 * @return array<string, mixed>
 */
function foam_form_get_design_lab_payload() {
	$page_id       = get_queried_object_id();
	$template_slug = '';
	$page_type     = 'generic';
	$page_title    = wp_get_document_title();
	$object        = get_queried_object();

	if ( $page_id ) {
		$template_slug = (string) get_page_template_slug( $page_id );
	}

	if ( $object instanceof WP_Post ) {
		$page_type  = $object->post_type;
		$page_title = get_the_title( $object ) ?: $page_title;
	} elseif ( $object instanceof WP_Term ) {
		$page_type  = $object->taxonomy;
		$page_title = $object->name ?: $page_title;
	}

	if ( is_front_page() || is_home() ) {
		$page_type = 'front-page';
	}

	if ( function_exists( 'is_shop' ) && is_shop() ) {
		$page_type = 'shop';
	}

	if ( function_exists( 'is_product' ) && is_product() ) {
		$page_type = 'single-product';
	}

	if ( function_exists( 'is_product_category' ) && is_product_category() ) {
		$page_type = 'product-category';
	}

	if ( empty( $template_slug ) ) {
		$template_slug = 'default';
	}

	return array(
		'pageId'      => $page_id ? (int) $page_id : 0,
		'pageKey'     => md5( home_url( add_query_arg( array(), $GLOBALS['wp']->request ?? '' ) ) ),
		'pageTitle'   => $page_title,
		'pageType'    => $page_type,
		'template'    => $template_slug,
		'path'        => wp_parse_url( home_url( add_query_arg( array(), $GLOBALS['wp']->request ?? '' ) ), PHP_URL_PATH ),
		'bodyClasses' => array_values( get_body_class() ),
		'storageKey'  => 'foamFormDesignLab.v1',
	);
}

/**
 * Keep the storefront source language in English.
 *
 * GTranslate then auto-switches the visible language based on the
 * visitor's browser or system language, while the underlying source
 * content stays consistent and avoids mixed default UI strings.
 *
 * @param string $locale Active locale.
 * @return string
 */
function foam_form_filter_frontend_locale( $locale ) {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || wp_is_json_request() ) {
		return $locale;
	}

	return 'en_US';
}
add_filter( 'locale', 'foam_form_filter_frontend_locale', 20 );

add_action(
	'wp_enqueue_scripts',
	function () {
		$font_query     = 'family=DM+Serif+Display:ital@0;1&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap';
		$theme_css_path = get_stylesheet_directory() . '/assets/css/site.css';
		$theme_js_path  = get_stylesheet_directory() . '/assets/js/site.js';

		wp_enqueue_style(
			'foam-form-fonts',
			'https://fonts.googleapis.com/css2?' . $font_query,
			array(),
			null
		);

		wp_enqueue_style(
			'foam-form-studio',
			get_stylesheet_directory_uri() . '/assets/css/site.css',
			array( 'astra-theme-css', 'foam-form-fonts' ),
			file_exists( $theme_css_path ) ? (string) filemtime( $theme_css_path ) : FOAM_FORM_THEME_VERSION
		);

		wp_enqueue_script(
			'foam-form-studio',
			get_stylesheet_directory_uri() . '/assets/js/site.js',
			array(),
			file_exists( $theme_js_path ) ? (string) filemtime( $theme_js_path ) : FOAM_FORM_THEME_VERSION,
			true
		);

		if ( foam_form_should_load_design_lab() ) {
			wp_localize_script(
				'foam-form-studio',
				'foamDesignLab',
				foam_form_get_design_lab_payload()
			);
		}

		wp_script_add_data( 'foam-form-studio', 'strategy', 'defer' );
		wp_script_add_data( 'foam-form-studio', 'defer', true );

		wp_dequeue_script( 'wp-embed' );

		if ( ! is_user_logged_in() ) {
			wp_deregister_style( 'dashicons' );
		}
	},
	20
);

add_filter(
	'wp_resource_hints',
	function ( $urls, $relation_type ) {
		if ( 'preconnect' === $relation_type ) {
			$urls[] = 'https://fonts.googleapis.com';
			$urls[] = array(
				'href'        => 'https://fonts.gstatic.com',
				'crossorigin' => 'anonymous',
			);
		}

		return $urls;
	},
	10,
	2
);

add_action(
	'init',
	function () {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
	}
);

/**
 * Build an editorial asset URL by filename.
 *
 * @param string $filename File inside uploads/2026/07.
 * @return string
 */
function foam_form_get_editorial_asset_url( $filename ) {
	$uploads = wp_get_upload_dir();

	if ( empty( $uploads['baseurl'] ) ) {
		return '';
	}

	return trailingslashit( $uploads['baseurl'] ) . '2026/07/' . ltrim( $filename, '/' );
}

add_action(
	'after_setup_theme',
	function () {
		load_child_theme_textdomain( 'foam-form-studio', get_stylesheet_directory() . '/languages' );
		add_theme_support( 'woocommerce' );
		add_theme_support( 'wc-product-gallery-zoom' );
		add_theme_support( 'wc-product-gallery-lightbox' );
		add_theme_support( 'wc-product-gallery-slider' );
	}
);

add_filter(
	'astra_page_layout',
	function ( $layout ) {
		if ( is_front_page() || is_home() || is_page( 'home' ) ) {
			return 'page-builder';
		}

		return 'no-sidebar';
	}
);

add_filter(
	'astra_get_content_layout',
	function ( $layout ) {
		if ( is_front_page() || is_home() || is_page( 'home' ) ) {
			return 'full-width-content';
		}

		return 'full-width-content';
	}
);

add_filter(
	'astra_the_title_enabled',
	function ( $enabled ) {
		if ( is_front_page() || is_home() || is_page( 'home' ) ) {
			return false;
		}

		return $enabled;
	}
);

/**
 * Build a fallback page URL by slug.
 *
 * @param string $slug Page slug.
 * @param string $fallback Fallback relative path.
 * @return string
 */
function foam_form_get_page_url( $slug, $fallback = '/' ) {
	$page = get_page_by_path( $slug );
	if ( $page instanceof WP_Post ) {
		return get_permalink( $page );
	}

	return home_url( $fallback );
}

/**
 * Build a product category URL by slug.
 *
 * @param string $slug Term slug.
 * @return string
 */
function foam_form_get_product_category_url( $slug ) {
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
 * Build a WooCommerce product search URL.
 *
 * @param string $query Search keywords.
 * @return string
 */
function foam_form_get_product_search_url( $query ) {
	return add_query_arg(
		array(
			's'         => $query,
			'post_type' => 'product',
		),
		home_url( '/' )
	);
}

/**
 * Build a post search URL for editorial and journal links.
 *
 * @param string $query Search keywords.
 * @return string
 */
function foam_form_get_post_search_url( $query ) {
	return add_query_arg(
		array(
			's'         => $query,
			'post_type' => 'post',
		),
		home_url( '/' )
	);
}

/**
 * Attach richer visual previews to header mega-menu cards.
 *
 * @param array  $nav_cards Menu configuration.
 * @param string $editorial_base Upload base URL for editorial assets.
 * @return array
 */
function foam_form_enrich_nav_cards( $nav_cards, $editorial_base ) {
	$preview_map = array(
		'SHOP'        => array(
			'All Products' => array(
				'image' => $editorial_base . 'sonovafurn-best-seller-02.png',
				'title' => __( 'Shop the Full Collection', 'foam-form-studio' ),
				'copy'  => __( 'A clear starting point for living-room seating, memory foam essentials, and everyday soft goods.', 'foam-form-studio' ),
			),
			'New Arrivals' => array(
				'image' => $editorial_base . 'sonovafurn-source-03.png',
				'title' => __( 'New Shapes for Softer Rooms', 'foam-form-studio' ),
				'copy'  => __( 'Fresh silhouettes and quieter textures prepared for modern apartments and guest-ready layouts.', 'foam-form-studio' ),
			),
			'Best Sellers' => array(
				'image' => $editorial_base . 'sonovafurn-best-seller-01.png',
				'title' => __( 'Best Sellers', 'foam-form-studio' ),
				'copy'  => __( 'The most considered Sonovafurn pieces for compact living, comfortable routines, and everyday ease.', 'foam-form-studio' ),
			),
			'Bundles'      => array(
				'image' => $editorial_base . 'sonovafurn-best-seller-03.png',
				'title' => __( 'Layered Sets', 'foam-form-studio' ),
				'copy'  => __( 'Complete seating and comfort combinations designed to make room planning feel simpler.', 'foam-form-studio' ),
			),
		),
		'SOFAS'       => array(
			'All Sofas'       => array(
				'image' => $editorial_base . 'sonovafurn-editorial-shop-night.jpg',
				'title' => __( 'All Sofas', 'foam-form-studio' ),
				'copy'  => __( 'Explore flexible seating built for smaller footprints and calmer interiors.', 'foam-form-studio' ),
			),
			'Boneless Sofas'  => array(
				'image' => $editorial_base . 'sonovafurn-ribbed-floor-lounger-gray.png',
				'title' => __( 'Boneless Sofa Forms', 'foam-form-studio' ),
				'copy'  => __( 'Relaxed, low-profile seating with a softer structure and lounge-first posture.', 'foam-form-studio' ),
			),
			'Sofa Beds'       => array(
				'image' => $editorial_base . 'sonovafurn-best-seller-02.png',
				'title' => __( 'Convertible Sofa Beds', 'foam-form-studio' ),
				'copy'  => __( 'Day-to-night comfort made for studio apartments, guest rooms, and flexible layouts.', 'foam-form-studio' ),
			),
			'Modular Sofas'   => array(
				'image' => $editorial_base . 'sonovafurn-grand-modular-sectional-ivory.png',
				'title' => __( 'Modular Seating', 'foam-form-studio' ),
				'copy'  => __( 'Configurable sections designed to scale with apartment living and room changes.', 'foam-form-studio' ),
			),
			'Floor Sofas'     => array(
				'image' => $editorial_base . 'sonovafurn-source-04.png',
				'title' => __( 'Floor Sofas', 'foam-form-studio' ),
				'copy'  => __( 'Grounded lounge pieces with a more casual silhouette and quiet, sculptural presence.', 'foam-form-studio' ),
			),
			'Loveseats'       => array(
				'image' => $editorial_base . 'sonovafurn-editorial-lifestyle-sofa.jpg',
				'title' => __( 'Compact Loveseats', 'foam-form-studio' ),
				'copy'  => __( 'Two-seat comfort balanced for reading corners, smaller living rooms, and shared spaces.', 'foam-form-studio' ),
			),
			'Sectionals'      => array(
				'image' => $editorial_base . 'sonovafurn-grand-modular-sectional-ivory.png',
				'title' => __( 'Sectional Layouts', 'foam-form-studio' ),
				'copy'  => __( 'Broader seating compositions prepared for larger living zones and hosting moments.', 'foam-form-studio' ),
			),
			'Ottomans'        => array(
				'image' => $editorial_base . 'sonovafurn-source-01.jpg',
				'title' => __( 'Ottomans & Soft Extensions', 'foam-form-studio' ),
				'copy'  => __( 'Soft add-ons that introduce leg support, extra seating, and quieter layering.', 'foam-form-studio' ),
			),
		),
		'MATTRESSES'  => array(
			'All Mattresses'   => array(
				'image' => $editorial_base . 'sonovafurn-editorial-hero-living.jpg',
				'title' => __( 'Mattress Collection', 'foam-form-studio' ),
				'copy'  => __( 'Sleep essentials designed for easier setup, easier transport, and a calmer bedroom visual language.', 'foam-form-studio' ),
			),
			'Memory Foam'      => array(
				'image' => $editorial_base . 'sonovafurn-best-seller-03.png',
				'title' => __( 'Memory Foam', 'foam-form-studio' ),
				'copy'  => __( 'Pressure-relieving layers shaped for supportive recovery and softer nighttime comfort.', 'foam-form-studio' ),
			),
			'Hybrid'           => array(
				'image' => $editorial_base . 'sonovafurn-source-02.jpg',
				'title' => __( 'Hybrid Construction', 'foam-form-studio' ),
				'copy'  => __( 'Balanced mattress structures combining softness, support, and more responsive lift.', 'foam-form-studio' ),
			),
			'Mattress Toppers' => array(
				'image' => $editorial_base . 'sonovafurn-editorial-white-sofa.jpg',
				'title' => __( 'Topper Layer', 'foam-form-studio' ),
				'copy'  => __( 'A lighter comfort layer for guest beds, dorm setups, and everyday sleep refreshes.', 'foam-form-studio' ),
			),
		),
		'PILLOWS'     => array(
			'All Pillows' => array(
				'image' => $editorial_base . 'sonovafurn-editorial-white-sofa.jpg',
				'title' => __( 'Pillow Collection', 'foam-form-studio' ),
				'copy'  => __( 'Soft support pieces designed for rest, recovery, and a cleaner sleep setup.', 'foam-form-studio' ),
			),
			'Memory Foam' => array(
				'image' => $editorial_base . 'sonovafurn-source-01.jpg',
				'title' => __( 'Memory Foam Support', 'foam-form-studio' ),
				'copy'  => __( 'Adaptive support shaped for nightly alignment and calmer pressure relief.', 'foam-form-studio' ),
			),
			'Cooling'     => array(
				'image' => $editorial_base . 'sonovafurn-source-02.jpg',
				'title' => __( 'Cooling Pillows', 'foam-form-studio' ),
				'copy'  => __( 'Breathable pillow formats prepared for warmer sleepers and lighter seasonal bedding.', 'foam-form-studio' ),
			),
			'Neck'        => array(
				'image' => $editorial_base . 'sonovafurn-reading-lounge-chair-rust.jpg',
				'title' => __( 'Neck Support', 'foam-form-studio' ),
				'copy'  => __( 'Targeted contours for seated rest, travel moments, and evening wind-down routines.', 'foam-form-studio' ),
			),
			'Body'        => array(
				'image' => $editorial_base . 'sonovafurn-editorial-lifestyle-sofa.jpg',
				'title' => __( 'Body Pillows', 'foam-form-studio' ),
				'copy'  => __( 'Long-form comfort for side sleepers, recovery routines, and softer lounging.', 'foam-form-studio' ),
			),
		),
		'PET'         => array(
			'Pet Stairs'   => array(
				'image' => $editorial_base . 'sonovafurn-source-04.png',
				'title' => __( 'Pet Stairs', 'foam-form-studio' ),
				'copy'  => __( 'Softer access support for smaller companions and elevated home routines.', 'foam-form-studio' ),
			),
			'Pet Ramps'    => array(
				'image' => $editorial_base . 'sonovafurn-source-03.png',
				'title' => __( 'Pet Ramps', 'foam-form-studio' ),
				'copy'  => __( 'Gentler transitions for sofas, beds, and everyday movement around the home.', 'foam-form-studio' ),
			),
			'Pet Beds'     => array(
				'image' => $editorial_base . 'sonovafurn-editorial-lifestyle-sofa.jpg',
				'title' => __( 'Pet Beds', 'foam-form-studio' ),
				'copy'  => __( 'Foam-based comfort for calmer rest, easier upkeep, and shared living spaces.', 'foam-form-studio' ),
			),
			'Pet Cushions' => array(
				'image' => $editorial_base . 'sonovafurn-source-01.jpg',
				'title' => __( 'Pet Cushions', 'foam-form-studio' ),
				'copy'  => __( 'Low-profile comfort layers that bring softness to crates, corners, and travel setups.', 'foam-form-studio' ),
			),
		),
		'ACCESSORIES' => array(
			'Covers'    => array(
				'image' => $editorial_base . 'sonovafurn-source-03.png',
				'title' => __( 'Protective Covers', 'foam-form-studio' ),
				'copy'  => __( 'Practical layers for easier upkeep, lighter care routines, and extended product life.', 'foam-form-studio' ),
			),
			'Cushions'  => array(
				'image' => $editorial_base . 'sonovafurn-source-04.png',
				'title' => __( 'Cushions', 'foam-form-studio' ),
				'copy'  => __( 'Soft accent pieces that complete a room without disturbing its visual calm.', 'foam-form-studio' ),
			),
			'Blankets'  => array(
				'image' => $editorial_base . 'sonovafurn-source-02.jpg',
				'title' => __( 'Blankets', 'foam-form-studio' ),
				'copy'  => __( 'Textural finishing layers for colder evenings, guest rooms, and everyday lounging.', 'foam-form-studio' ),
			),
		),
	);

	foreach ( $nav_cards as $section_label => $section ) {
		if ( empty( $section['cards'] ) || empty( $preview_map[ $section_label ] ) ) {
			continue;
		}

		foreach ( $section['cards'] as $index => $card ) {
			$card_label = isset( $card['title'] ) ? wp_strip_all_tags( $card['title'] ) : '';

			if ( empty( $preview_map[ $section_label ][ $card_label ] ) ) {
				continue;
			}

			$nav_cards[ $section_label ]['cards'][ $index ] = array_merge(
				$card,
				array(
					'image'         => $preview_map[ $section_label ][ $card_label ]['image'],
					'preview_title' => $preview_map[ $section_label ][ $card_label ]['title'],
					'preview_copy'  => $preview_map[ $section_label ][ $card_label ]['copy'],
				)
			);
		}
	}

	return $nav_cards;
}

add_filter( 'show_admin_bar', '__return_false' );

add_action(
	'init',
	function () {
		$settings = get_option( 'GTranslate', array() );

		if ( ! is_array( $settings ) ) {
			return;
		}

		$updated = false;

		if ( ! isset( $settings['detect_browser_language'] ) || (int) $settings['detect_browser_language'] !== 1 ) {
			$settings['detect_browser_language'] = 1;
			$updated                             = true;
		}

		if ( $updated ) {
			update_option( 'GTranslate', $settings );
		}
	},
	20
);

add_filter(
	'pre_get_document_title',
	function ( $title ) {
		if ( is_front_page() ) {
			return 'Sonovafurn';
		}

		return str_replace( 'sonovafurn', 'Sonovafurn', $title );
	}
);

add_filter(
	'option_blogname',
	function ( $value ) {
		return 'Sonovafurn';
	}
);

add_action(
	'wp_body_open',
	function () {
		$home_url          = home_url( '/' );
		$shop_url          = foam_form_get_page_url( 'shop', '/shop/' );
		$all_products_url  = $shop_url;
		$cart_url          = foam_form_get_page_url( 'cart', '/cart/' );
		$account_url       = foam_form_get_page_url( 'my-account', '/my-account/' );
		$about_url         = foam_form_get_page_url( 'about-us', '/about-us/' );
		$blog_url          = foam_form_get_page_url( 'blog', '/blog/' );
		$contact_url       = foam_form_get_page_url( 'contact', '/contact/' );
		$shipping_url      = foam_form_get_page_url( 'shipping-policy', '/shipping-policy/' );
		$best_seller_url   = home_url( '/#best-sellers' );
		$sofa_beds_url     = foam_form_get_product_category_url( 'compressed-sofa-beds' );
		$memory_foam_url   = foam_form_get_product_category_url( 'mattresses' );
		$lounge_url        = foam_form_get_product_category_url( 'space-saving-sofas' );
		$modular_url       = foam_form_get_product_category_url( 'modular-sofas' );
		$pillow_url        = foam_form_get_product_search_url( 'pillow' );
		$loveseat_url      = foam_form_get_product_search_url( 'loveseat' );
		$sale_url          = $shop_url;
		$new_arrivals_url  = add_query_arg( array( 'orderby' => 'date' ), $shop_url );
		$bundles_url       = foam_form_get_product_search_url( 'bundle' );
		$all_sofas_url     = foam_form_get_product_search_url( 'sofa' );
		$boneless_sofa_url = foam_form_get_product_search_url( 'boneless sofa' );
		$floor_sofa_url    = foam_form_get_product_search_url( 'floor sofa' );
		$sectional_url     = foam_form_get_product_search_url( 'sectional sofa' );
		$ottoman_url       = foam_form_get_product_search_url( 'ottoman' );
		$accent_chairs_url = foam_form_get_product_search_url( 'accent chair' );
		$side_tables_url   = foam_form_get_product_search_url( 'side table' );
		$coffee_tables_url = foam_form_get_product_search_url( 'coffee table' );
		$all_mattress_url  = foam_form_get_product_search_url( 'mattress' );
		$hybrid_url        = foam_form_get_product_search_url( 'hybrid mattress' );
		$topper_url        = foam_form_get_product_search_url( 'mattress topper' );
		$sleeper_sofas_url = foam_form_get_product_search_url( 'sleeper sofa' );
		$memory_pillow_url = foam_form_get_product_search_url( 'memory foam pillow' );
		$cooling_pillow_url = foam_form_get_product_search_url( 'cooling pillow' );
		$neck_pillow_url   = foam_form_get_product_search_url( 'neck pillow' );
		$body_pillow_url   = foam_form_get_product_search_url( 'body pillow' );
		$compact_sofas_url = foam_form_get_product_search_url( 'compact sofa' );
		$foldable_furniture_url = foam_form_get_product_search_url( 'foldable furniture' );
		$convertible_furniture_url = foam_form_get_product_search_url( 'convertible furniture' );
		$storage_solutions_url = foam_form_get_product_search_url( 'storage ottoman' );
		$pet_stairs_url    = foam_form_get_product_search_url( 'pet stairs' );
		$pet_ramps_url     = foam_form_get_product_search_url( 'pet ramp' );
		$pet_beds_url      = foam_form_get_product_search_url( 'pet bed' );
		$pet_sofas_url     = foam_form_get_product_search_url( 'pet sofa' );
		$pet_cushions_url  = foam_form_get_product_search_url( 'pet cushion' );
		$covers_url        = foam_form_get_product_search_url( 'cover' );
		$cushions_url      = foam_form_get_product_search_url( 'cushion' );
		$blankets_url      = foam_form_get_product_search_url( 'blanket' );
		$throw_blankets_url = foam_form_get_product_search_url( 'throw blanket' );
		$decor_pillows_url = foam_form_get_product_search_url( 'decor pillow' );
		$daybeds_url       = foam_form_get_product_search_url( 'daybed' );
		$chaise_lounges_url = foam_form_get_product_search_url( 'chaise lounge' );
		$floor_loungers_url = foam_form_get_product_search_url( 'floor lounger' );
		$living_room_sets_url = foam_form_get_product_search_url( 'living room set' );
		$shop_by_space_url = foam_form_get_post_search_url( 'shop by space' );
		$living_room_ideas_url = foam_form_get_post_search_url( 'living room ideas' );
		$small_living_spaces_url = foam_form_get_post_search_url( 'small living spaces' );
		$family_living_url = foam_form_get_post_search_url( 'family living' );
		$hosting_inspiration_url = foam_form_get_post_search_url( 'hosting inspiration' );
		$sleep_better_url  = foam_form_get_post_search_url( 'sleep better' );
		$guest_room_guide_url = foam_form_get_post_search_url( 'guest room guide' );
		$foam_technology_url = foam_form_get_post_search_url( 'foam technology' );
		$studio_apartments_url = foam_form_get_post_search_url( 'studio apartments' );
		$apartment_living_url = foam_form_get_post_search_url( 'apartment living' );
		$home_office_url   = foam_form_get_post_search_url( 'home office' );
		$small_space_guide_url = foam_form_get_post_search_url( 'small space guide' );
		$living_with_pets_url = foam_form_get_post_search_url( 'living with pets' );
		$cleaning_guide_url = foam_form_get_post_search_url( 'cleaning guide' );
		$pet_care_tips_url = foam_form_get_post_search_url( 'pet care tips' );
		$editors_picks_url = $best_seller_url;
		$apartment_collection_url = foam_form_get_product_search_url( 'apartment collection' );
		$minimal_living_url = foam_form_get_post_search_url( 'minimal living' );
		$guest_ready_url   = foam_form_get_post_search_url( 'guest ready' );
		$materials_url     = $about_url . '#materials';
		$sustainability_url = $about_url . '#sustainability';
		$living_ideas_url  = foam_form_get_post_search_url( 'living ideas' );
		$interior_styling_url = foam_form_get_post_search_url( 'interior styling' );
		$buying_guides_url = foam_form_get_post_search_url( 'buying guides' );
		$furniture_care_url = foam_form_get_post_search_url( 'furniture care' );
		$foam_education_url = foam_form_get_post_search_url( 'foam education' );
		$company_news_url  = foam_form_get_post_search_url( 'company news' );
		$corner_product = get_page_by_path( 'sonovafurn-corner-modular-sofa', OBJECT, 'product' );
		$corner_url        = $corner_product instanceof WP_Post ? get_permalink( $corner_product ) : $modular_url;
		$cart_count        = 0;
		$uploads           = wp_get_upload_dir();
		$editorial_base    = ! empty( $uploads['baseurl'] ) ? trailingslashit( $uploads['baseurl'] ) . '2026/07/' : '';
		$nav_cards         = array(
			'EVERYDAY LIVING' => array(
				'url'    => $all_sofas_url,
				'kicker' => __( 'Rooms for daily rituals', 'foam-form-studio' ),
				'groups' => array(
					array(
						'heading' => __( 'Seating', 'foam-form-studio' ),
						'links'   => array(
							array( 'title' => __( 'Sofas', 'foam-form-studio' ), 'url' => $all_sofas_url ),
							array( 'title' => __( 'Modular Seating', 'foam-form-studio' ), 'url' => $modular_url ),
							array( 'title' => __( 'Loveseats', 'foam-form-studio' ), 'url' => $loveseat_url ),
							array( 'title' => __( 'Accent Chairs', 'foam-form-studio' ), 'url' => $accent_chairs_url ),
							array( 'title' => __( 'Ottomans', 'foam-form-studio' ), 'url' => $ottoman_url ),
						),
					),
					array(
						'heading' => __( 'Convertible Comfort', 'foam-form-studio' ),
						'links'   => array(
							array( 'title' => __( 'Sofa Beds', 'foam-form-studio' ), 'url' => $sofa_beds_url ),
							array( 'title' => __( 'Daybeds', 'foam-form-studio' ), 'url' => $daybeds_url ),
							array( 'title' => __( 'Chaise Lounges', 'foam-form-studio' ), 'url' => $chaise_lounges_url ),
							array( 'title' => __( 'Floor Loungers', 'foam-form-studio' ), 'url' => $floor_loungers_url ),
						),
					),
					array(
						'heading' => __( 'Living Essentials', 'foam-form-studio' ),
						'links'   => array(
							array( 'title' => __( 'Cushions', 'foam-form-studio' ), 'url' => $cushions_url ),
							array( 'title' => __( 'Throws', 'foam-form-studio' ), 'url' => $throw_blankets_url ),
							array( 'title' => __( 'Coffee Tables', 'foam-form-studio' ), 'url' => $coffee_tables_url ),
							array( 'title' => __( 'Side Tables', 'foam-form-studio' ), 'url' => $side_tables_url ),
						),
					),
					array(
						'heading' => __( 'The Living Edit', 'foam-form-studio' ),
						'links'   => array(
							array( 'title' => __( 'New Arrivals', 'foam-form-studio' ), 'url' => $new_arrivals_url ),
							array( 'title' => __( 'Best Sellers', 'foam-form-studio' ), 'url' => $best_seller_url ),
							array( 'title' => __( 'Living Room Sets', 'foam-form-studio' ), 'url' => $living_room_sets_url ),
							array( 'title' => __( 'Shop by Space', 'foam-form-studio' ), 'url' => $shop_by_space_url ),
						),
					),
				),
				'feature' => array(
					'image'     => $editorial_base . 'sonovafurn-editorial-lifestyle-sofa.jpg',
					'position'  => 'center center',
					'meta'      => __( 'Everyday living', 'foam-form-studio' ),
					'title'     => __( 'Furniture arranged around the softer parts of the day', 'foam-form-studio' ),
					'copy'      => __( 'Spaces for gathering, stretching out, and moving naturally through the rituals that make a room feel lived in.', 'foam-form-studio' ),
					'cta_label' => __( 'Explore Living Spaces →', 'foam-form-studio' ),
					'cta_url'   => $all_sofas_url,
				),
			),
			'REST & SLEEP' => array(
				'url'    => $all_mattress_url,
				'kicker' => __( 'Sleep essentials', 'foam-form-studio' ),
				'groups' => array(
					array(
						'heading' => __( 'Products', 'foam-form-studio' ),
						'links'   => array(
							array( 'title' => __( 'Mattresses', 'foam-form-studio' ), 'url' => $all_mattress_url ),
							array( 'title' => __( 'Memory Foam Mattresses', 'foam-form-studio' ), 'url' => $memory_foam_url ),
							array( 'title' => __( 'Pillows', 'foam-form-studio' ), 'url' => $pillow_url ),
							array( 'title' => __( 'Mattress Toppers', 'foam-form-studio' ), 'url' => $topper_url ),
							array( 'title' => __( 'Sleeper Sofas', 'foam-form-studio' ), 'url' => $sleeper_sofas_url ),
						),
					),
					array(
						'heading' => __( 'Lifestyle Collections', 'foam-form-studio' ),
						'links'   => array(
							array( 'title' => __( 'Guest Ready', 'foam-form-studio' ), 'url' => $guest_ready_url ),
							array( 'title' => __( 'Small Bedrooms', 'foam-form-studio' ), 'url' => $small_space_guide_url ),
							array( 'title' => __( 'Overnight Hosting', 'foam-form-studio' ), 'url' => $hosting_inspiration_url ),
						),
					),
					array(
						'heading' => __( 'Editorial', 'foam-form-studio' ),
						'links'   => array(
							array( 'title' => __( 'Sleep Better', 'foam-form-studio' ), 'url' => $sleep_better_url ),
							array( 'title' => __( 'Guest Room Guide', 'foam-form-studio' ), 'url' => $guest_room_guide_url ),
							array( 'title' => __( 'Foam Technology', 'foam-form-studio' ), 'url' => $foam_technology_url ),
						),
					),
				),
				'feature' => array(
					'image'     => $editorial_base . 'sonovafurn-editorial-hero-living.jpg',
					'position'  => 'center center',
					'meta'      => __( 'Rest & sleep', 'foam-form-studio' ),
					'title'     => __( 'Sleep pieces made to feel lighter, calmer, and easier to live with', 'foam-form-studio' ),
					'copy'      => __( 'From guest-ready sofa beds to softer mattress layers, each format is designed for easier setup and quieter bedrooms.', 'foam-form-studio' ),
					'cta_label' => __( 'Explore Sleep Essentials →', 'foam-form-studio' ),
					'cta_url'   => $all_mattress_url,
				),
			),
			'SMALL HOME' => array(
				'url'    => $compact_sofas_url,
				'kicker' => __( 'Smaller footprints', 'foam-form-studio' ),
				'groups' => array(
					array(
						'heading' => __( 'Products', 'foam-form-studio' ),
						'links'   => array(
							array( 'title' => __( 'Compact Sofas', 'foam-form-studio' ), 'url' => $compact_sofas_url ),
							array( 'title' => __( 'Foldable Furniture', 'foam-form-studio' ), 'url' => $foldable_furniture_url ),
							array( 'title' => __( 'Convertible Furniture', 'foam-form-studio' ), 'url' => $convertible_furniture_url ),
							array( 'title' => __( 'Storage Solutions', 'foam-form-studio' ), 'url' => $storage_solutions_url ),
						),
					),
					array(
						'heading' => __( 'Lifestyle Collections', 'foam-form-studio' ),
						'links'   => array(
							array( 'title' => __( 'Studio Apartments', 'foam-form-studio' ), 'url' => $studio_apartments_url ),
							array( 'title' => __( 'Apartment Living', 'foam-form-studio' ), 'url' => $apartment_living_url ),
							array( 'title' => __( 'Home Office', 'foam-form-studio' ), 'url' => $home_office_url ),
						),
					),
					array(
						'heading' => __( 'Guides', 'foam-form-studio' ),
						'links'   => array(
							array( 'title' => __( 'Small Space Guide', 'foam-form-studio' ), 'url' => $small_space_guide_url ),
							array( 'title' => __( 'Living Room Ideas', 'foam-form-studio' ), 'url' => $living_room_ideas_url ),
							array( 'title' => __( 'Minimal Living', 'foam-form-studio' ), 'url' => $minimal_living_url ),
						),
					),
				),
				'feature' => array(
					'image'     => $editorial_base . 'sonovafurn-editorial-shop-night.jpg',
					'position'  => 'center center',
					'meta'      => __( 'Small home', 'foam-form-studio' ),
					'title'     => __( 'Pieces that move easily through tighter plans and changing routines', 'foam-form-studio' ),
					'copy'      => __( 'Designed for elevators, corners, and multipurpose rooms without losing the sense of comfort a home needs every day.', 'foam-form-studio' ),
					'cta_label' => __( 'Explore Small Home →', 'foam-form-studio' ),
					'cta_url'   => $compact_sofas_url,
				),
			),
			'PET COMFORT' => array(
				'url'    => $pet_beds_url,
				'kicker' => __( 'Shared living', 'foam-form-studio' ),
				'groups' => array(
					array(
						'heading' => __( 'Products', 'foam-form-studio' ),
						'links'   => array(
							array( 'title' => __( 'Pet Beds', 'foam-form-studio' ), 'url' => $pet_beds_url ),
							array( 'title' => __( 'Pet Sofas', 'foam-form-studio' ), 'url' => $pet_sofas_url ),
							array( 'title' => __( 'Pet Ramps', 'foam-form-studio' ), 'url' => $pet_ramps_url ),
							array( 'title' => __( 'Pet Stairs', 'foam-form-studio' ), 'url' => $pet_stairs_url ),
						),
					),
					array(
						'heading' => __( 'Lifestyle Collections', 'foam-form-studio' ),
						'links'   => array(
							array( 'title' => __( 'Living With Pets', 'foam-form-studio' ), 'url' => $living_with_pets_url ),
							array( 'title' => __( 'Apartment Pets', 'foam-form-studio' ), 'url' => $apartment_living_url ),
							array( 'title' => __( 'Softer Landings', 'foam-form-studio' ), 'url' => $pet_ramps_url ),
						),
					),
					array(
						'heading' => __( 'Editorial', 'foam-form-studio' ),
						'links'   => array(
							array( 'title' => __( 'Living With Pets', 'foam-form-studio' ), 'url' => $living_with_pets_url ),
							array( 'title' => __( 'Cleaning Guide', 'foam-form-studio' ), 'url' => $cleaning_guide_url ),
							array( 'title' => __( 'Pet Care Tips', 'foam-form-studio' ), 'url' => $pet_care_tips_url ),
						),
					),
				),
				'feature' => array(
					'image'     => $editorial_base . 'sonovafurn-editorial-lifestyle-sofa.jpg',
					'position'  => 'center center',
					'meta'      => __( 'Pet comfort', 'foam-form-studio' ),
					'title'     => __( 'Foam pieces that keep shared spaces softer and easier to move through', 'foam-form-studio' ),
					'copy'      => __( 'For companions at home, access pieces and rest layers are designed to feel gentle without crowding the room around them.', 'foam-form-studio' ),
					'cta_label' => __( 'Explore Pet Comfort →', 'foam-form-studio' ),
					'cta_url'   => $pet_beds_url,
				),
			),
			'FEATURED COLLECTIONS' => array(
				'url'    => $best_seller_url,
				'kicker' => __( 'Curated edits', 'foam-form-studio' ),
				'groups' => array(
					array(
						'heading' => __( 'Product Categories', 'foam-form-studio' ),
						'links'   => array(
							array( 'title' => __( 'Editor\'s Picks', 'foam-form-studio' ), 'url' => $editors_picks_url ),
							array( 'title' => __( 'New Arrivals', 'foam-form-studio' ), 'url' => $new_arrivals_url ),
							array( 'title' => __( 'Best Sellers', 'foam-form-studio' ), 'url' => $best_seller_url ),
						),
					),
					array(
						'heading' => __( 'Lifestyle Collections', 'foam-form-studio' ),
						'links'   => array(
							array( 'title' => __( 'Apartment Collection', 'foam-form-studio' ), 'url' => $apartment_collection_url ),
							array( 'title' => __( 'Minimal Living', 'foam-form-studio' ), 'url' => $minimal_living_url ),
							array( 'title' => __( 'Guest Ready', 'foam-form-studio' ), 'url' => $guest_ready_url ),
						),
					),
					array(
						'heading' => __( 'Editorial Links', 'foam-form-studio' ),
						'links'   => array(
							array( 'title' => __( 'Comfort Journey', 'foam-form-studio' ), 'url' => home_url( '/#comfort-journey' ) ),
							array( 'title' => __( 'Living Ideas', 'foam-form-studio' ), 'url' => $living_ideas_url ),
							array( 'title' => __( 'Buying Guides', 'foam-form-studio' ), 'url' => $buying_guides_url ),
						),
					),
				),
				'feature' => array(
					'image'     => $editorial_base . 'sonovafurn-best-seller-01.png',
					'position'  => 'center center',
					'meta'      => __( 'Featured collections', 'foam-form-studio' ),
					'title'     => __( 'A calmer way to navigate the pieces that define the brand', 'foam-form-studio' ),
					'copy'      => __( 'Curated edits bring together softer silhouettes, practical layers, and the rooms they were made to support.', 'foam-form-studio' ),
					'cta_label' => __( 'Explore Collection →', 'foam-form-studio' ),
					'cta_url'   => $best_seller_url,
				),
			),
			'ABOUT' => array(
				'url'    => $about_url,
				'kicker' => __( 'Brand & materials', 'foam-form-studio' ),
				'groups' => array(
					array(
						'heading' => __( 'Brand', 'foam-form-studio' ),
						'links'   => array(
							array( 'title' => __( 'Our Story', 'foam-form-studio' ), 'url' => $about_url ),
							array( 'title' => __( 'Materials', 'foam-form-studio' ), 'url' => $materials_url ),
							array( 'title' => __( 'Foam Technology', 'foam-form-studio' ), 'url' => $foam_technology_url ),
						),
					),
					array(
						'heading' => __( 'Values', 'foam-form-studio' ),
						'links'   => array(
							array( 'title' => __( 'Sustainability', 'foam-form-studio' ), 'url' => $sustainability_url ),
							array( 'title' => __( 'Small Home Living', 'foam-form-studio' ), 'url' => $small_space_guide_url ),
						),
					),
					array(
						'heading' => __( 'Support', 'foam-form-studio' ),
						'links'   => array(
							array( 'title' => __( 'Shipping', 'foam-form-studio' ), 'url' => $shipping_url ),
							array( 'title' => __( 'Contact', 'foam-form-studio' ), 'url' => $contact_url ),
							array( 'title' => __( 'Journal', 'foam-form-studio' ), 'url' => $blog_url ),
						),
					),
				),
				'feature' => array(
					'image'     => $editorial_base . 'sonovafurn-editorial-white-sofa.jpg',
					'position'  => 'center center',
					'meta'      => __( 'About Sonovafurn', 'foam-form-studio' ),
					'title'     => __( 'Designing softer rooms around smaller homes and everyday use', 'foam-form-studio' ),
					'copy'      => __( 'The brand story begins with compressed delivery, practical materials, and a quieter approach to modern furniture.', 'foam-form-studio' ),
					'cta_label' => __( 'Read Our Story →', 'foam-form-studio' ),
					'cta_url'   => $about_url,
				),
			),
			'JOURNAL' => array(
				'url'    => $blog_url,
				'kicker' => __( 'Editorial reading', 'foam-form-studio' ),
				'groups' => array(
					array(
						'heading' => __( 'Topics', 'foam-form-studio' ),
						'links'   => array(
							array( 'title' => __( 'Living Ideas', 'foam-form-studio' ), 'url' => $living_ideas_url ),
							array( 'title' => __( 'Interior Styling', 'foam-form-studio' ), 'url' => $interior_styling_url ),
							array( 'title' => __( 'Buying Guides', 'foam-form-studio' ), 'url' => $buying_guides_url ),
						),
					),
					array(
						'heading' => __( 'Care & Materials', 'foam-form-studio' ),
						'links'   => array(
							array( 'title' => __( 'Furniture Care', 'foam-form-studio' ), 'url' => $furniture_care_url ),
							array( 'title' => __( 'Foam Education', 'foam-form-studio' ), 'url' => $foam_education_url ),
						),
					),
					array(
						'heading' => __( 'From the Brand', 'foam-form-studio' ),
						'links'   => array(
							array( 'title' => __( 'Company News', 'foam-form-studio' ), 'url' => $company_news_url ),
							array( 'title' => __( 'Comfort Journey', 'foam-form-studio' ), 'url' => home_url( '/#comfort-journey' ) ),
						),
					),
				),
				'feature' => array(
					'image'     => $editorial_base . 'sonovafurn-source-02.jpg',
					'position'  => 'center center',
					'meta'      => __( 'Journal', 'foam-form-studio' ),
					'title'     => __( 'Stories for arranging calmer rooms and softer routines', 'foam-form-studio' ),
					'copy'      => __( 'Interior notes, buying guides, and material details written to feel more like a magazine than a product catalog.', 'foam-form-studio' ),
					'cta_label' => __( 'Read the Journal →', 'foam-form-studio' ),
					'cta_url'   => $blog_url,
				),
			),
		);

		if ( class_exists( 'WooCommerce' ) && function_exists( 'WC' ) && WC()->cart ) {
			$cart_count = (int) WC()->cart->get_cart_contents_count();
		}
		?>
		<header class="foam-site-header" aria-label="<?php esc_attr_e( 'Primary', 'foam-form-studio' ); ?>">
			<div class="foam-site-header__inner">
				<a class="foam-site-brand" href="<?php echo esc_url( $home_url ); ?>">
					<span class="foam-site-brand__mark" aria-hidden="true">
						<svg viewBox="0 0 48 48" focusable="false">
							<path d="M33.5 12.5C31.76 10.92 29.3 10 26.32 10C20.08 10 15.92 13.58 15.92 18.74C15.92 28.3 32.08 24.84 32.08 33.52C32.08 37.06 29.18 39.5 24.24 39.5C20.96 39.5 18.12 38.48 15.78 36.42" />
							<path d="M14.5 14.3V10.5H18.3" />
							<path d="M33.5 33.7V37.5H29.7" />
						</svg>
					</span>
					<span class="foam-site-brand__wordmark">Sonovafurn</span>
				</a>

				<nav class="foam-site-nav" id="foam-site-nav-main" aria-label="<?php esc_attr_e( 'Main navigation', 'foam-form-studio' ); ?>">
					<?php foreach ( $nav_cards as $label => $item ) : ?>
						<?php $has_panel = ! empty( $item['groups'] ); ?>
						<div class="foam-site-nav-item<?php echo $has_panel ? '' : ' foam-site-nav-item--simple'; ?>" data-nav-item="<?php echo esc_attr( sanitize_title( $label ) ); ?>">
							<a class="foam-site-nav__trigger" href="<?php echo esc_url( $item['url'] ); ?>">
								<span class="foam-site-nav__label-group">
									<strong><?php echo esc_html( $label ); ?></strong>
								</span>
							</a>
							<?php if ( $has_panel ) : ?>
								<?php
								$feature         = ! empty( $item['feature'] ) ? $item['feature'] : array();
								$panel_thumb     = ! empty( $feature['image'] ) ? $feature['image'] : '';
								$panel_title     = ! empty( $feature['title'] ) ? $feature['title'] : $label;
								$panel_copy      = ! empty( $feature['copy'] ) ? $feature['copy'] : $item['kicker'];
								$panel_meta      = ! empty( $feature['meta'] ) ? $feature['meta'] : $label;
								$panel_cta_label = ! empty( $feature['cta_label'] ) ? $feature['cta_label'] : __( 'Explore Collection →', 'foam-form-studio' );
								$panel_cta_url   = ! empty( $feature['cta_url'] ) ? $feature['cta_url'] : $item['url'];
								$feature_style   = '';

								if ( ! empty( $panel_thumb ) ) {
									$feature_style = "background-image: linear-gradient(180deg, rgba(255,255,255,0.02), rgba(17,17,17,0.08) 48%, rgba(17,17,17,0.22) 100%), url('" . esc_url( $panel_thumb ) . "');";

									if ( ! empty( $feature['position'] ) ) {
										$feature_style .= ' background-position: ' . sanitize_text_field( $feature['position'] ) . ';';
									}
								}

								$item_slug = sanitize_title( $label );
								?>
								<div class="foam-site-nav-panel">
									<div class="foam-site-nav-panel__grid">
										<div class="foam-site-nav-columns">
											<div class="foam-site-nav-subnav" aria-label="<?php echo esc_attr( $label ); ?>">
												<?php foreach ( $item['groups'] as $group_index => $group ) : ?>
													<?php $group_id = $item_slug . '-group-' . $group_index; ?>
													<button
														class="foam-site-nav-subnav__button<?php echo 0 === $group_index ? ' is-active' : ''; ?>"
														type="button"
														data-group-target="<?php echo esc_attr( $group_id ); ?>"
														aria-controls="<?php echo esc_attr( $group_id ); ?>"
														aria-pressed="<?php echo 0 === $group_index ? 'true' : 'false'; ?>"
													>
														<span><?php echo esc_html( $group['heading'] ); ?></span>
													</button>
												<?php endforeach; ?>
											</div>
											<div class="foam-site-nav-stage">
												<?php foreach ( $item['groups'] as $group_index => $group ) : ?>
													<?php $group_id = $item_slug . '-group-' . $group_index; ?>
													<div
														class="foam-site-nav-stage__panel<?php echo 0 === $group_index ? ' is-active' : ''; ?>"
														data-group-panel="<?php echo esc_attr( $group_id ); ?>"
														id="<?php echo esc_attr( $group_id ); ?>"
														<?php echo 0 === $group_index ? '' : ' hidden'; ?>
													>
														<span class="foam-site-nav-stage__eyebrow"><?php echo esc_html( $group['heading'] ); ?></span>
														<div class="foam-site-nav-menu__list">
															<?php foreach ( $group['links'] as $link_index => $card ) : ?>
																<a
																	class="foam-site-nav-menu__link"
																	href="<?php echo esc_url( $card['url'] ); ?>"
																	data-preview-image="<?php echo esc_attr( ! empty( $card['preview_image'] ) ? $card['preview_image'] : $panel_thumb ); ?>"
																	data-preview-title="<?php echo esc_attr( ! empty( $card['preview_title'] ) ? $card['preview_title'] : $card['title'] ); ?>"
																	data-preview-copy="<?php echo esc_attr( ! empty( $card['preview_copy'] ) ? $card['preview_copy'] : $panel_copy ); ?>"
																	data-preview-meta="<?php echo esc_attr( ! empty( $card['preview_meta'] ) ? $card['preview_meta'] : $group['heading'] ); ?>"
																	data-preview-url="<?php echo esc_attr( $card['url'] ); ?>"
																	data-group-parent="<?php echo esc_attr( $group_id ); ?>"
																	<?php echo ( 0 === $group_index && 0 === $link_index ) ? ' data-preview-default="true"' : ''; ?>
																	<?php echo 0 === $link_index ? ' data-group-default="true"' : ''; ?>
																>
																	<strong><?php echo esc_html( $card['title'] ); ?></strong>
																</a>
															<?php endforeach; ?>
														</div>
													</div>
												<?php endforeach; ?>
											</div>
										</div>
										<div
											class="foam-site-nav-feature"
											data-default-image="<?php echo esc_attr( $panel_thumb ); ?>"
											data-default-title="<?php echo esc_attr( $panel_title ); ?>"
											data-default-copy="<?php echo esc_attr( $panel_copy ); ?>"
											data-default-meta="<?php echo esc_attr( $panel_meta ); ?>"
											data-default-url="<?php echo esc_attr( $panel_cta_url ); ?>"
											<?php echo $feature_style ? ' style="' . esc_attr( $feature_style ) . '"' : ''; ?>
										>
											<div class="foam-site-nav-feature__body">
												<span class="foam-site-nav-feature__meta"><?php echo esc_html( $panel_meta ); ?></span>
												<strong><?php echo esc_html( $panel_title ); ?></strong>
												<em><?php echo esc_html( $panel_copy ); ?></em>
												<a class="foam-site-nav-feature__cta" href="<?php echo esc_url( $panel_cta_url ); ?>"><?php echo esc_html( $panel_cta_label ); ?></a>
											</div>
										</div>
									</div>
								</div>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
					<div class="foam-site-nav-mobile-links">
						<a class="foam-site-nav-mobile-link" href="<?php echo esc_url( $account_url ); ?>">
							<?php esc_html_e( 'My Account', 'foam-form-studio' ); ?>
						</a>
					</div>
				</nav>

				<div class="foam-site-actions">
					<button class="foam-icon-button foam-search-toggle foam-search-pill" type="button" aria-expanded="false" aria-controls="foam-search-panel" aria-label="<?php esc_attr_e( 'Open search', 'foam-form-studio' ); ?>">
						<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
							<path d="M10.5 4a6.5 6.5 0 1 0 4.02 11.61l4.94 4.95 1.06-1.06-4.95-4.94A6.5 6.5 0 0 0 10.5 4Zm0 1.5a5 5 0 1 1 0 10 5 5 0 0 1 0-10Z" fill="currentColor"/>
						</svg>
						<span><?php esc_html_e( 'Search', 'foam-form-studio' ); ?></span>
					</button>
					<a class="foam-icon-button foam-cart-button" href="<?php echo esc_url( $cart_url ); ?>" aria-label="<?php esc_attr_e( 'View cart', 'foam-form-studio' ); ?>">
						<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
							<path d="M7 6.5h10l1.55 10.25a1 1 0 0 1-.99 1.15H6.44a1 1 0 0 1-.99-1.15L7 6.5Zm1.28 1.5-.93 8.4h9.3L15.72 8H8.28ZM9.75 5a2.25 2.25 0 0 1 4.5 0h-1.5a.75.75 0 0 0-1.5 0h-1.5Z" fill="currentColor"/>
						</svg>
						<span class="foam-cart-count"><?php echo esc_html( (string) $cart_count ); ?></span>
					</a>
					<a class="foam-primary-cta foam-account-pill" href="<?php echo esc_url( $account_url ); ?>">
						<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
							<path d="M12 12a3.75 3.75 0 1 0-3.75-3.75A3.75 3.75 0 0 0 12 12Zm0 1.5c-3.27 0-6.75 1.67-6.75 4.75 0 .41.34.75.75.75h12c.41 0 .75-.34.75-.75 0-3.08-3.48-4.75-6.75-4.75Z" fill="currentColor"/>
						</svg>
						<span><?php esc_html_e( 'Account', 'foam-form-studio' ); ?></span>
					</a>
					<button class="foam-icon-button foam-menu-toggle" type="button" aria-expanded="false" aria-controls="foam-site-nav-main" aria-label="<?php esc_attr_e( 'Open menu', 'foam-form-studio' ); ?>">
						<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
							<path d="M5 7.5h14" />
							<path d="M5 12h14" />
							<path d="M5 16.5h14" />
						</svg>
					</button>
				</div>
			</div>
		</header>
		<div class="foam-search-panel" id="foam-search-panel" hidden>
			<div class="foam-search-panel__backdrop"></div>
			<div class="foam-search-panel__dialog" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Site search', 'foam-form-studio' ); ?>">
				<button class="foam-search-panel__close" type="button" aria-label="<?php esc_attr_e( 'Close search', 'foam-form-studio' ); ?>">&times;</button>
				<form class="foam-search-form" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
					<label class="screen-reader-text" for="foam-search-field"><?php esc_html_e( 'Search products', 'foam-form-studio' ); ?></label>
					<input id="foam-search-field" type="search" name="s" placeholder="<?php esc_attr_e( 'Search sofas, fabrics, and foam essentials', 'foam-form-studio' ); ?>">
					<input type="hidden" name="post_type" value="product">
					<button class="foam-primary-cta foam-primary-cta--search" type="submit"><?php esc_html_e( 'Search', 'foam-form-studio' ); ?></button>
				</form>
			</div>
		</div>
		<?php
	},
	5
);

add_action(
	'wp_footer',
	function () {
		$faq_url      = foam_form_get_page_url( 'faq', '/faq/' );
		$about_url    = foam_form_get_page_url( 'about-us', '/about-us/' );
		$account_url  = foam_form_get_page_url( 'my-account', '/my-account/' );
		$contact_url  = foam_form_get_page_url( 'contact', '/contact/' );
		$shipping_url = foam_form_get_page_url( 'shipping-policy', '/shipping-policy/' );
		$return_url   = foam_form_get_page_url( 'return-policy', '/return-policy/' );
		$privacy_url  = foam_form_get_page_url( 'privacy-policy-2', '/privacy-policy-2/' );
		$terms_url    = foam_form_get_page_url( 'terms-of-service', '/terms-of-service/' );
		$shop_url     = foam_form_get_page_url( 'shop', '/shop/' );
		$blog_url     = foam_form_get_page_url( 'blog', '/blog/' );
		?>
		<section class="foam-footer-shell" aria-label="<?php esc_attr_e( 'Footer', 'foam-form-studio' ); ?>">
			<div class="foam-footer-shell__inner">
				<div class="foam-footer-subscribe">
					<div class="foam-footer-subscribe__content">
						<p class="foam-footer-subscribe__eyebrow"><?php esc_html_e( 'Stay in touch', 'foam-form-studio' ); ?></p>
						<h2><?php esc_html_e( 'Subscribe for launch notes, room ideas, and service updates', 'foam-form-studio' ); ?></h2>
						<form class="foam-footer-subscribe-form">
							<input type="email" name="email" placeholder="<?php esc_attr_e( 'Email address', 'foam-form-studio' ); ?>" aria-label="<?php esc_attr_e( 'Email address', 'foam-form-studio' ); ?>">
							<input type="tel" name="phone" placeholder="<?php esc_attr_e( 'Phone number', 'foam-form-studio' ); ?>" aria-label="<?php esc_attr_e( 'Phone number', 'foam-form-studio' ); ?>">
							<button type="submit" class="foam-footer-subscribe-form__button"><?php esc_html_e( 'Subscribe', 'foam-form-studio' ); ?></button>
						</form>
						<p class="foam-footer-subscribe__note"><?php esc_html_e( 'By sharing your email or phone number, you agree to receive occasional product launches, room-planning notes, and service follow-ups. Consent is not required for purchase.', 'foam-form-studio' ); ?></p>
						<p class="foam-footer-subscribe__legal">
							<a href="<?php echo esc_url( $terms_url ); ?>"><?php esc_html_e( 'Terms & Conditions', 'foam-form-studio' ); ?></a>
							<span>&amp;</span>
							<a href="<?php echo esc_url( $privacy_url ); ?>"><?php esc_html_e( 'Privacy Policy', 'foam-form-studio' ); ?></a>
						</p>
					</div>
					<div class="foam-footer-social" aria-label="<?php esc_attr_e( 'Social links', 'foam-form-studio' ); ?>">
						<a href="#" aria-label="<?php esc_attr_e( 'Facebook', 'foam-form-studio' ); ?>">
							<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
								<path d="M13.5 21v-7h2.35l.4-3h-2.75V9.28c0-.87.24-1.47 1.5-1.47H16.4V5.13c-.25-.03-1.1-.1-2.09-.1-2.07 0-3.49 1.26-3.49 3.58V11H8.5v3h2.32v7h2.68Z" fill="currentColor"/>
							</svg>
						</a>
						<a href="#" aria-label="<?php esc_attr_e( 'Instagram', 'foam-form-studio' ); ?>">
							<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
								<path d="M7.5 3h9A4.5 4.5 0 0 1 21 7.5v9a4.5 4.5 0 0 1-4.5 4.5h-9A4.5 4.5 0 0 1 3 16.5v-9A4.5 4.5 0 0 1 7.5 3Zm0 1.5A3 3 0 0 0 4.5 7.5v9a3 3 0 0 0 3 3h9a3 3 0 0 0 3-3v-9a3 3 0 0 0-3-3h-9Zm9.75 1.12a.88.88 0 1 1 0 1.76.88.88 0 0 1 0-1.76ZM12 7a5 5 0 1 1 0 10 5 5 0 0 1 0-10Zm0 1.5A3.5 3.5 0 1 0 12 15.5 3.5 3.5 0 0 0 12 8.5Z" fill="currentColor"/>
							</svg>
						</a>
						<a href="#" aria-label="<?php esc_attr_e( 'TikTok', 'foam-form-studio' ); ?>">
							<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
								<path d="M14.66 4c.39 1.83 1.47 3.08 3.34 3.2v2.16c-1.12.11-2.12-.27-3.12-.86v5.02c0 3.15-1.9 5.48-5.2 5.48A4.8 4.8 0 0 1 5 14.2a4.92 4.92 0 0 1 5.77-4.82v2.22a2.62 2.62 0 0 0-1.05-.05 2.46 2.46 0 0 0-2.01 2.57 2.38 2.38 0 0 0 2.53 2.45c1.66 0 2.35-1.17 2.35-2.73V4h2.07Z" fill="currentColor"/>
							</svg>
						</a>
					</div>
				</div>

				<div class="foam-footer-links">
					<div>
						<h3><?php esc_html_e( 'Client Services', 'foam-form-studio' ); ?></h3>
						<a href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Design Consultation', 'foam-form-studio' ); ?></a>
						<a href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Room Fit Guide', 'foam-form-studio' ); ?></a>
						<a href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Trade & Hospitality', 'foam-form-studio' ); ?></a>
					</div>
					<div>
						<h3><?php esc_html_e( 'About', 'foam-form-studio' ); ?></h3>
						<a href="<?php echo esc_url( $about_url ); ?>"><?php esc_html_e( 'Our Story', 'foam-form-studio' ); ?></a>
						<a href="<?php echo esc_url( $blog_url ); ?>"><?php esc_html_e( 'Journal', 'foam-form-studio' ); ?></a>
						<a href="<?php echo esc_url( $about_url ); ?>#materials"><?php esc_html_e( 'Materials', 'foam-form-studio' ); ?></a>
						<a href="<?php echo esc_url( $about_url ); ?>#technology"><?php esc_html_e( 'Compression Technology', 'foam-form-studio' ); ?></a>
					</div>
					<div>
						<h3><?php esc_html_e( 'Resources', 'foam-form-studio' ); ?></h3>
						<a href="<?php echo esc_url( $shipping_url ); ?>"><?php esc_html_e( 'Shipping & Delivery', 'foam-form-studio' ); ?></a>
						<a href="<?php echo esc_url( $return_url ); ?>"><?php esc_html_e( 'Return & Refund Policy', 'foam-form-studio' ); ?></a>
						<a href="<?php echo esc_url( $faq_url ); ?>"><?php esc_html_e( 'FAQ', 'foam-form-studio' ); ?></a>
						<a href="<?php echo esc_url( $contact_url ); ?>"><?php esc_html_e( 'Care & Support', 'foam-form-studio' ); ?></a>
					</div>
					<div>
						<h3><?php esc_html_e( 'Collections', 'foam-form-studio' ); ?></h3>
						<a href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Compressed Sofa Beds', 'foam-form-studio' ); ?></a>
						<a href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Memory Foam', 'foam-form-studio' ); ?></a>
						<a href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Modular Sofas', 'foam-form-studio' ); ?></a>
						<a href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Guest Room Essentials', 'foam-form-studio' ); ?></a>
					</div>
					<div>
						<h3><?php esc_html_e( 'Customer Care', 'foam-form-studio' ); ?></h3>
						<p><?php esc_html_e( 'Email: support@sonovafurn.com', 'foam-form-studio' ); ?></p>
						<p><?php esc_html_e( 'Hours:', 'foam-form-studio' ); ?></p>
						<p><?php esc_html_e( 'Monday to Friday: 10a - 6p EST', 'foam-form-studio' ); ?></p>
						<p><?php esc_html_e( 'Saturday to Sunday: 10a - 2p EST', 'foam-form-studio' ); ?></p>
					</div>
				</div>

				<div class="foam-footer-meta">
					<p><?php esc_html_e( 'Copyright 2026 Sonovafurn', 'foam-form-studio' ); ?></p>
					<p><?php esc_html_e( 'UNIT A53M116 29/F, LEGEND TOWER 7 SHING YIP STREET, KWUN TONG, KL', 'foam-form-studio' ); ?></p>
					<div class="foam-footer-meta__links">
						<a href="<?php echo esc_url( $terms_url ); ?>"><?php esc_html_e( 'Terms & Conditions', 'foam-form-studio' ); ?></a>
						<a href="<?php echo esc_url( $privacy_url ); ?>"><?php esc_html_e( 'Privacy Policy', 'foam-form-studio' ); ?></a>
						<a href="<?php echo esc_url( $shipping_url ); ?>"><?php esc_html_e( 'Shipping & Delivery', 'foam-form-studio' ); ?></a>
						<a href="<?php echo esc_url( $return_url ); ?>"><?php esc_html_e( 'Return & Refund Policy', 'foam-form-studio' ); ?></a>
					</div>
				</div>
			</div>
		</section>
		<?php
	},
	5
);

add_action(
	'wp_footer',
	function () {
		if ( ! foam_form_should_load_design_lab() ) {
			return;
		}
		?>
		<div class="foam-design-lab" data-foam-design-lab>
			<button class="foam-design-lab__toggle" type="button" data-foam-design-lab-toggle aria-expanded="false">
				<span><?php esc_html_e( 'Design Lab', 'foam-form-studio' ); ?></span>
			</button>
			<div class="foam-design-lab__panel" data-foam-design-lab-panel hidden>
				<div class="foam-design-lab__header">
					<div>
						<p class="foam-design-lab__eyebrow"><?php esc_html_e( 'Live style controls', 'foam-form-studio' ); ?></p>
						<h3><?php esc_html_e( 'Page Design Toolbar', 'foam-form-studio' ); ?></h3>
					</div>
					<button class="foam-design-lab__close" type="button" data-foam-design-lab-close aria-label="<?php esc_attr_e( 'Close design toolbar', 'foam-form-studio' ); ?>"></button>
				</div>

				<div class="foam-design-lab__meta">
					<div class="foam-design-lab__meta-card">
						<span><?php esc_html_e( 'Page', 'foam-form-studio' ); ?></span>
						<strong data-foam-design-page-title></strong>
					</div>
					<div class="foam-design-lab__meta-grid">
						<div class="foam-design-lab__meta-card">
							<span><?php esc_html_e( 'Template', 'foam-form-studio' ); ?></span>
							<strong data-foam-design-template></strong>
						</div>
						<div class="foam-design-lab__meta-card">
							<span><?php esc_html_e( 'Type', 'foam-form-studio' ); ?></span>
							<strong data-foam-design-page-type></strong>
						</div>
						<div class="foam-design-lab__meta-card">
							<span><?php esc_html_e( 'Page ID', 'foam-form-studio' ); ?></span>
							<strong data-foam-design-page-id></strong>
						</div>
						<div class="foam-design-lab__meta-card">
							<span><?php esc_html_e( 'Path', 'foam-form-studio' ); ?></span>
							<strong data-foam-design-page-path></strong>
						</div>
					</div>
					<p class="foam-design-lab__classes" data-foam-design-body-classes></p>
				</div>

				<div class="foam-design-lab__section">
					<div class="foam-design-lab__section-heading">
						<h4><?php esc_html_e( 'Typography', 'foam-form-studio' ); ?></h4>
					</div>
					<div class="foam-design-lab__controls">
						<label class="foam-design-lab__control">
							<span><?php esc_html_e( 'Hero font', 'foam-form-studio' ); ?></span>
							<input type="text" data-foam-design-control data-css-var="--foam-font-hero">
						</label>
						<label class="foam-design-lab__control">
							<span><?php esc_html_e( 'Section heading font', 'foam-form-studio' ); ?></span>
							<input type="text" data-foam-design-control data-css-var="--foam-font-heading">
						</label>
						<label class="foam-design-lab__control">
							<span><?php esc_html_e( 'Body font', 'foam-form-studio' ); ?></span>
							<input type="text" data-foam-design-control data-css-var="--foam-font-body">
						</label>
						<label class="foam-design-lab__control">
							<span><?php esc_html_e( 'Navigation font', 'foam-form-studio' ); ?></span>
							<input type="text" data-foam-design-control data-css-var="--foam-font-nav">
						</label>
						<label class="foam-design-lab__control">
							<span><?php esc_html_e( 'Button font', 'foam-form-studio' ); ?></span>
							<input type="text" data-foam-design-control data-css-var="--foam-font-button">
						</label>
						<label class="foam-design-lab__control">
							<span><?php esc_html_e( 'Specs font', 'foam-form-studio' ); ?></span>
							<input type="text" data-foam-design-control data-css-var="--foam-font-spec">
						</label>
					</div>
				</div>

				<div class="foam-design-lab__section">
					<div class="foam-design-lab__section-heading">
						<h4><?php esc_html_e( 'Color', 'foam-form-studio' ); ?></h4>
					</div>
					<div class="foam-design-lab__controls foam-design-lab__controls--compact">
						<label class="foam-design-lab__control">
							<span><?php esc_html_e( 'Page background', 'foam-form-studio' ); ?></span>
							<input type="color" data-foam-design-control data-css-var="--foam-color-bg">
						</label>
						<label class="foam-design-lab__control">
							<span><?php esc_html_e( 'Surface', 'foam-form-studio' ); ?></span>
							<input type="color" data-foam-design-control data-css-var="--foam-color-surface">
						</label>
						<label class="foam-design-lab__control">
							<span><?php esc_html_e( 'Text', 'foam-form-studio' ); ?></span>
							<input type="color" data-foam-design-control data-css-var="--foam-color-text">
						</label>
					</div>
				</div>

				<div class="foam-design-lab__section">
					<div class="foam-design-lab__section-heading">
						<h4><?php esc_html_e( 'Spacing', 'foam-form-studio' ); ?></h4>
					</div>
					<div class="foam-design-lab__controls">
						<label class="foam-design-lab__control">
							<span><?php esc_html_e( 'Body letter spacing', 'foam-form-studio' ); ?></span>
							<div class="foam-design-lab__range-row">
								<input type="range" min="-0.02" max="0.04" step="0.001" data-unit="em" data-foam-design-control data-css-var="--foam-letter-body">
								<output data-foam-design-value></output>
							</div>
						</label>
						<label class="foam-design-lab__control">
							<span><?php esc_html_e( 'Heading letter spacing', 'foam-form-studio' ); ?></span>
							<div class="foam-design-lab__range-row">
								<input type="range" min="-0.04" max="0.04" step="0.001" data-unit="em" data-foam-design-control data-css-var="--foam-letter-heading">
								<output data-foam-design-value></output>
							</div>
						</label>
						<label class="foam-design-lab__control">
							<span><?php esc_html_e( 'Display letter spacing', 'foam-form-studio' ); ?></span>
							<div class="foam-design-lab__range-row">
								<input type="range" min="-0.05" max="0.04" step="0.001" data-unit="em" data-foam-design-control data-css-var="--foam-letter-display">
								<output data-foam-design-value></output>
							</div>
						</label>
						<label class="foam-design-lab__control">
							<span><?php esc_html_e( 'UI letter spacing', 'foam-form-studio' ); ?></span>
							<div class="foam-design-lab__range-row">
								<input type="range" min="-0.03" max="0.04" step="0.001" data-unit="em" data-foam-design-control data-css-var="--foam-letter-ui-title">
								<output data-foam-design-value></output>
							</div>
						</label>
						<label class="foam-design-lab__control">
							<span><?php esc_html_e( 'Body line height', 'foam-form-studio' ); ?></span>
							<div class="foam-design-lab__range-row">
								<input type="range" min="1.3" max="2.1" step="0.01" data-foam-design-control data-css-var="--foam-line-body">
								<output data-foam-design-value></output>
							</div>
						</label>
						<label class="foam-design-lab__control">
							<span><?php esc_html_e( 'Display line height', 'foam-form-studio' ); ?></span>
							<div class="foam-design-lab__range-row">
								<input type="range" min="0.9" max="1.4" step="0.01" data-foam-design-control data-css-var="--foam-line-display">
								<output data-foam-design-value></output>
							</div>
						</label>
					</div>
				</div>

				<div class="foam-design-lab__actions">
					<button class="foam-design-lab__action" type="button" data-foam-design-copy><?php esc_html_e( 'Copy CSS', 'foam-form-studio' ); ?></button>
					<button class="foam-design-lab__action foam-design-lab__action--ghost" type="button" data-foam-design-reset><?php esc_html_e( 'Reset', 'foam-form-studio' ); ?></button>
				</div>

				<pre class="foam-design-lab__export" data-foam-design-export></pre>
			</div>
		</div>
		<?php
	},
	45
);

add_action(
	'wp_footer',
	function () {
		if ( is_admin() || wp_doing_ajax() || ! shortcode_exists( 'gtranslate' ) ) {
			return;
		}

		$settings = get_option( 'GTranslate', array() );
		$languages = array( 'en', 'es', 'fr', 'de', 'it', 'pt', 'nl', 'ru', 'ja', 'ko', 'ar', 'zh-CN', 'zh-TW' );

		if ( ! empty( $settings['incl_langs'] ) && is_array( $settings['incl_langs'] ) ) {
			$languages = array_values( array_unique( array_map( 'strval', $settings['incl_langs'] ) ) );
		}
		?>
		<div class="foam-auto-language-bootstrap" aria-hidden="true" hidden>
			<?php echo do_shortcode( '[gtranslate widget_look="lang_names"]' ); ?>
		</div>
		<script>
		(function () {
			var allowedLanguages = <?php echo wp_json_encode( array_values( $languages ) ); ?>;
			var defaultLanguage = 'en';

			function normalizeLanguage(language) {
				var value = (language || '').toLowerCase();
				if (!value) {
					return defaultLanguage;
				}

				if (value === 'zh' || value === 'zh-cn' || value.indexOf('zh-hans') === 0) {
					return 'zh-CN';
				}

				if (value === 'zh-tw' || value === 'zh-hk' || value.indexOf('zh-hant') === 0) {
					return 'zh-TW';
				}

				if (value === 'he') {
					return 'iw';
				}

				return value.slice(0, 2);
			}

			function applyPreferredLanguage() {
				if (typeof window.doGTranslate !== 'function') {
					return false;
				}

				var preferred = normalizeLanguage(window.navigator.language || window.navigator.userLanguage || defaultLanguage);
				var target = allowedLanguages.indexOf(preferred) !== -1 ? preferred : defaultLanguage;

				try {
					window.localStorage.removeItem('gt_autoswitch');
				} catch (error) {}

				window.doGTranslate(defaultLanguage + '|' + target);
				return true;
			}

			var attempts = 0;
			var timer = window.setInterval(function () {
				attempts += 1;

				if (applyPreferredLanguage() || attempts > 20) {
					window.clearInterval(timer);
				}
			}, 250);
		}());
		</script>
		<?php
	},
	40
);

add_action(
	'init',
	function () {
		register_nav_menus(
			array(
				'primary-sales' => __( 'Primary Sales Menu', 'foam-form-studio' ),
				'footer-sales'  => __( 'Footer Sales Menu', 'foam-form-studio' ),
			)
		);
	}
);

add_action(
	'customize_register',
	function ( $wp_customize ) {
		$wp_customize->add_section(
			'foam_form_branding',
			array(
				'title'    => __( 'sonovafurn Branding', 'foam-form-studio' ),
				'priority' => 35,
			)
		);

		$settings = array(
			'foam_form_tagline' => array(
				'label'   => __( 'Hero Tagline', 'foam-form-studio' ),
				'default' => __( 'Compressed comfort for modern American living.', 'foam-form-studio' ),
			),
			'foam_form_cta_url' => array(
				'label'   => __( 'Primary CTA URL', 'foam-form-studio' ),
				'default' => '/shop',
			),
		);

		foreach ( $settings as $id => $config ) {
			$wp_customize->add_setting(
				$id,
				array(
					'default'           => $config['default'],
					'sanitize_callback' => 'sanitize_text_field',
				)
			);

			$wp_customize->add_control(
				$id,
				array(
					'label'   => $config['label'],
					'section' => 'foam_form_branding',
					'type'    => 'text',
				)
			);
		}
	}
);

add_action(
	'wp_enqueue_scripts',
	function () {
		wp_dequeue_style( 'astra-theme-css' );
	},
	99
);

add_filter(
	'wp_nav_menu_args',
	function ( $args ) {
		if ( empty( $args['fallback_cb'] ) ) {
			$args['fallback_cb'] = '__return_empty_string';
		}

		return $args;
	},
	20
);

