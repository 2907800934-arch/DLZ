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
		$theme_css_path = get_stylesheet_directory() . '/assets/css/site.css';
		$theme_js_path  = get_stylesheet_directory() . '/assets/js/site.js';

		wp_enqueue_style(
			'foam-form-studio',
			get_stylesheet_directory_uri() . '/assets/css/site.css',
			array( 'astra-theme-css' ),
			file_exists( $theme_css_path ) ? (string) filemtime( $theme_css_path ) : FOAM_FORM_THEME_VERSION
		);

		wp_enqueue_script(
			'foam-form-studio',
			get_stylesheet_directory_uri() . '/assets/js/site.js',
			array(),
			file_exists( $theme_js_path ) ? (string) filemtime( $theme_js_path ) : FOAM_FORM_THEME_VERSION,
			true
		);
	},
	20
);

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

		return $layout;
	}
);

add_filter(
	'astra_get_content_layout',
	function ( $layout ) {
		if ( is_front_page() || is_home() || is_page( 'home' ) ) {
			return 'full-width-content';
		}

		return $layout;
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
		$home_url       = home_url( '/' );
		$shop_url       = foam_form_get_page_url( 'shop', '/shop/' );
		$cart_url       = foam_form_get_page_url( 'cart', '/cart/' );
		$account_url    = foam_form_get_page_url( 'my-account', '/my-account/' );
		$about_url      = foam_form_get_page_url( 'about-us', '/about-us/' );
		$blog_url       = foam_form_get_page_url( 'blog', '/blog/' );
		$best_seller_url = home_url( '/#best-sellers' );
		$technology_url = home_url( '/#technology' );
		$materials_url  = trailingslashit( $about_url ) . '#materials';
		$reviews_url    = home_url( '/#reviews' );
		$sofa_beds_url  = foam_form_get_product_category_url( 'compressed-sofa-beds' );
		$memory_foam_url = foam_form_get_product_category_url( 'mattresses' );
		$lounge_url     = foam_form_get_product_category_url( 'space-saving-sofas' );
		$modular_url    = foam_form_get_product_category_url( 'modular-sofas' );
		$pillow_url     = home_url( '/?s=pillow&post_type=product' );
		$corner_product = get_page_by_path( 'sonovafurn-corner-modular-sofa', OBJECT, 'product' );
		$corner_url     = $corner_product instanceof WP_Post ? get_permalink( $corner_product ) : $modular_url;
		$cart_count     = 0;
		$uploads        = wp_get_upload_dir();
		$editorial_base = ! empty( $uploads['baseurl'] ) ? trailingslashit( $uploads['baseurl'] ) . '2026/07/' : '';
		$nav_cards      = array(
			'Best Seller' => array(
				'url'   => $best_seller_url,
				'kicker' => __( 'Curated edit', 'foam-form-studio' ),
				'thumb' => $editorial_base . 'sonovafurn-editorial-hero-living.jpg',
				'panel_title' => __( 'Best Seller Edit', 'foam-form-studio' ),
				'panel_copy'  => __( 'A calmer shortlist of the collection, selected for smaller spaces, easier comparison, and everyday use.', 'foam-form-studio' ),
				'cards' => array(
					array(
						'title' => __( 'Top sofa beds', 'foam-form-studio' ),
						'copy'  => __( 'Convertible forms selected for guest-ready rooms, smaller layouts, and calmer daily use.', 'foam-form-studio' ),
						'url'   => $best_seller_url,
						'image' => $editorial_base . 'sonovafurn-editorial-hero-living.jpg',
					),
					array(
						'title' => __( 'Most considered pieces', 'foam-form-studio' ),
						'copy'  => __( 'A quieter shortlist of the collection with clearer proportions, softer materials, and easier comparison.', 'foam-form-studio' ),
						'url'   => $best_seller_url,
						'image' => $editorial_base . 'sonovafurn-editorial-lifestyle-sofa.jpg',
					),
				),
			),
			'Compressed Sofa' => array(
				'url'   => $sofa_beds_url,
				'kicker' => __( 'Core collection', 'foam-form-studio' ),
				'thumb' => $editorial_base . 'sonovafurn-editorial-shop-night.jpg',
				'panel_title' => __( 'Compressed Sofa Beds', 'foam-form-studio' ),
				'panel_copy'  => __( 'Compressed seating developed for easier delivery, cleaner silhouettes, and calmer apartment living.', 'foam-form-studio' ),
				'cards' => array(
					array(
						'title' => __( 'Sofa Beds', 'foam-form-studio' ),
						'copy'  => __( 'Compressed seating designed for easier delivery, smaller rooms, and quieter living layouts.', 'foam-form-studio' ),
						'url'   => $sofa_beds_url,
						'image' => $editorial_base . 'sonovafurn-editorial-shop-night.jpg',
					),
					array(
						'title' => __( 'Compact Forms', 'foam-form-studio' ),
						'copy'  => __( 'Low-stress silhouettes that move more naturally through apartments, stairs, and tighter plans.', 'foam-form-studio' ),
						'url'   => $lounge_url,
						'image' => $editorial_base . 'sonovafurn-editorial-hero-living.jpg',
					),
				),
			),
			'Pillow' => array(
				'url'   => $pillow_url,
				'kicker' => __( 'Soft accents', 'foam-form-studio' ),
				'thumb' => $editorial_base . 'sonovafurn-editorial-white-sofa.jpg',
				'panel_title' => __( 'Pillow & Cushion Layer', 'foam-form-studio' ),
				'panel_copy'  => __( 'A softer future product line prepared for tonal accents, layered comfort, and quieter styling.', 'foam-form-studio' ),
				'cards' => array(
					array(
						'title' => __( 'Accent Pillows', 'foam-form-studio' ),
						'copy'  => __( 'Prepared as a softer product lane for tonal accents, layered comfort, and future textile add-ons.', 'foam-form-studio' ),
						'url'   => $pillow_url,
						'image' => $editorial_base . 'sonovafurn-editorial-white-sofa.jpg',
					),
					array(
						'title' => __( 'Cushion Layer', 'foam-form-studio' ),
						'copy'  => __( 'A clean placeholder entry for pillows and cushions without crowding the current main sofa story.', 'foam-form-studio' ),
						'url'   => $pillow_url,
						'image' => $editorial_base . 'sonovafurn-editorial-lifestyle-sofa.jpg',
					),
				),
			),
			'Mattress' => array(
				'url'   => $memory_foam_url,
				'kicker' => __( 'Sleep support', 'foam-form-studio' ),
				'thumb' => $editorial_base . 'sonovafurn-editorial-white-sofa.jpg',
				'panel_title' => __( 'Mattress Collection', 'foam-form-studio' ),
				'panel_copy'  => __( 'Rolled and boxed memory foam essentials with quieter forms, practical comfort, and cleaner bedrooms.', 'foam-form-studio' ),
				'cards' => array(
					array(
						'title' => __( 'Memory Foam', 'foam-form-studio' ),
						'copy'  => __( 'Rolled and boxed sleep essentials with cleaner forms and practical pressure relief.', 'foam-form-studio' ),
						'url'   => $memory_foam_url,
						'image' => $editorial_base . 'sonovafurn-editorial-white-sofa.jpg',
					),
					array(
						'title' => __( 'Bedroom Edit', 'foam-form-studio' ),
						'copy'  => __( 'A quieter mattress direction prepared for primary rooms, guest rooms, and smaller apartments.', 'foam-form-studio' ),
						'url'   => $memory_foam_url,
						'image' => $editorial_base . 'sonovafurn-editorial-hero-living.jpg',
					),
				),
			),
			'Corner' => array(
				'url'   => $corner_url,
				'kicker' => __( 'L-shaped living', 'foam-form-studio' ),
				'thumb' => $editorial_base . 'sonovafurn-source-04.png',
				'panel_title' => __( 'Corner Sofa Edit', 'foam-form-studio' ),
				'panel_copy'  => __( 'Broader modular seating intended for open plans that still benefit from compressed delivery and softer visual weight.', 'foam-form-studio' ),
				'cards' => array(
					array(
						'title' => __( 'Corner Modular', 'foam-form-studio' ),
						'copy'  => __( 'Broader corner seating for open plans that still need compressed delivery efficiency.', 'foam-form-studio' ),
						'url'   => $corner_url,
						'image' => $editorial_base . 'sonovafurn-source-04.png',
					),
					array(
						'title' => __( 'Modular Angle', 'foam-form-studio' ),
						'copy'  => __( 'A more architectural sofa direction for layouts that need longer seating and softer presence.', 'foam-form-studio' ),
						'url'   => $modular_url,
						'image' => $editorial_base . 'sonovafurn-source-03.png',
					),
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

				<nav class="foam-site-nav" aria-label="<?php esc_attr_e( 'Main navigation', 'foam-form-studio' ); ?>">
					<?php foreach ( $nav_cards as $label => $item ) : ?>
						<div class="foam-site-nav-item">
							<a class="foam-site-nav__trigger" href="<?php echo esc_url( $item['url'] ); ?>">
								<span class="foam-site-nav__label-group">
									<strong><?php echo esc_html( $label ); ?></strong>
								</span>
							</a>
							<?php
							$panel_thumb = ! empty( $item['thumb'] ) ? $item['thumb'] : '';

							if ( empty( $panel_thumb ) ) {
								foreach ( $item['cards'] as $card ) {
									if ( ! empty( $card['image'] ) ) {
										$panel_thumb = $card['image'];
										break;
									}
								}
							}

							$panel_title = ! empty( $item['panel_title'] ) ? $item['panel_title'] : $label;
							$panel_copy  = ! empty( $item['panel_copy'] ) ? $item['panel_copy'] : $item['kicker'];
							?>
							<div class="foam-site-nav-panel">
								<div class="foam-site-nav-panel__grid">
									<div class="foam-site-nav-menu">
										<span class="foam-site-nav-menu__eyebrow"><?php echo esc_html( $item['kicker'] ); ?></span>
										<div class="foam-site-nav-menu__list">
											<?php foreach ( $item['cards'] as $card ) : ?>
												<a class="foam-site-nav-menu__link" href="<?php echo esc_url( $card['url'] ); ?>">
													<strong><?php echo esc_html( $card['title'] ); ?></strong>
												</a>
											<?php endforeach; ?>
										</div>
									</div>
									<?php
									$feature_classes = 'foam-site-nav-feature';
									$feature_style   = '';

									if ( ! empty( $panel_thumb ) ) {
										$feature_style = "background-image: linear-gradient(180deg, rgba(17, 17, 17, 0.03), rgba(17, 17, 17, 0.26)), url('" . esc_url( $panel_thumb ) . "');";
									} else {
										$feature_classes .= ' foam-site-nav-feature--blank';
									}
									?>
									<a class="<?php echo esc_attr( $feature_classes ); ?>" href="<?php echo esc_url( $item['url'] ); ?>"<?php echo $feature_style ? ' style="' . esc_attr( $feature_style ) . '"' : ''; ?>>
										<span class="foam-site-nav-feature__meta"><?php echo esc_html( $label ); ?></span>
										<strong><?php echo esc_html( $panel_title ); ?></strong>
										<em><?php echo esc_html( $panel_copy ); ?></em>
									</a>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
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

