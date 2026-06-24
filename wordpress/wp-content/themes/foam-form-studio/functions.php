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

add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_style(
			'foam-form-studio',
			get_stylesheet_directory_uri() . '/assets/css/site.css',
			array( 'astra-theme-css' ),
			FOAM_FORM_THEME_VERSION
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
		$blog_url       = foam_form_get_page_url( 'blog', '/blog/' );
		$about_url      = foam_form_get_page_url( 'about-us', '/about-us/' );
		$faq_url        = foam_form_get_page_url( 'faq', '/faq/' );
		$account_url    = foam_form_get_page_url( 'my-account', '/my-account/' );
		$shop_url       = foam_form_get_page_url( 'shop', '/shop/' );
		$contact_url    = foam_form_get_page_url( 'contact', '/contact/' );
		$shipping_url   = foam_form_get_page_url( 'shipping-policy', '/shipping-policy/' );
		$return_url     = foam_form_get_page_url( 'return-policy', '/return-policy/' );
		$privacy_url    = foam_form_get_page_url( 'privacy-policy-2', '/privacy-policy-2/' );
		$terms_url      = foam_form_get_page_url( 'terms-of-service', '/terms-of-service/' );
		$sofa_beds_url  = foam_form_get_product_category_url( 'compressed-sofa-beds' );
		$memory_url     = foam_form_get_product_category_url( 'mattresses' );
		$space_url      = foam_form_get_product_category_url( 'space-saving-sofas' );
		$modular_url    = foam_form_get_product_category_url( 'modular-sofas' );
		$boxed_url      = foam_form_get_product_category_url( 'sofa-in-a-box' );
		$convert_url    = foam_form_get_product_category_url( 'convertible-sofa-beds' );
		?>
		<header class="foam-site-header" aria-label="<?php esc_attr_e( 'Primary', 'foam-form-studio' ); ?>">
			<div class="foam-site-header__inner">
				<a class="foam-site-brand" href="<?php echo esc_url( $home_url ); ?>">
					<span class="foam-site-brand__mark">S</span>
					<span class="foam-site-brand__wordmark">Sonovafurn</span>
				</a>

				<nav class="foam-site-nav" aria-label="<?php esc_attr_e( 'Main navigation', 'foam-form-studio' ); ?>">
					<a href="<?php echo esc_url( $home_url ); ?>"><?php esc_html_e( 'Home', 'foam-form-studio' ); ?></a>
					<a href="<?php echo esc_url( $blog_url ); ?>"><?php esc_html_e( 'Blog', 'foam-form-studio' ); ?></a>
					<a href="<?php echo esc_url( $about_url ); ?>"><?php esc_html_e( 'About Us', 'foam-form-studio' ); ?></a>
					<a href="<?php echo esc_url( $faq_url ); ?>"><?php esc_html_e( 'FAQ', 'foam-form-studio' ); ?></a>
				</nav>

				<div class="foam-site-actions">
					<div class="foam-nav-group">
						<button class="foam-nav-group__toggle" type="button">
							<?php esc_html_e( 'Collections', 'foam-form-studio' ); ?>
						</button>
						<div class="foam-nav-panel">
							<div class="foam-nav-panel__column">
								<span class="foam-nav-panel__label"><?php esc_html_e( 'Living', 'foam-form-studio' ); ?></span>
								<a href="<?php echo esc_url( $sofa_beds_url ); ?>"><?php esc_html_e( 'Compressed Sofa Beds', 'foam-form-studio' ); ?></a>
								<a href="<?php echo esc_url( $space_url ); ?>"><?php esc_html_e( 'Space Saving Sofas', 'foam-form-studio' ); ?></a>
								<a href="<?php echo esc_url( $modular_url ); ?>"><?php esc_html_e( 'Modular Sofas', 'foam-form-studio' ); ?></a>
							</div>
							<div class="foam-nav-panel__column">
								<span class="foam-nav-panel__label"><?php esc_html_e( 'Formats', 'foam-form-studio' ); ?></span>
								<a href="<?php echo esc_url( $boxed_url ); ?>"><?php esc_html_e( 'Sofa in a Box', 'foam-form-studio' ); ?></a>
								<a href="<?php echo esc_url( $convert_url ); ?>"><?php esc_html_e( 'Convertible Sofa Beds', 'foam-form-studio' ); ?></a>
								<a href="<?php echo esc_url( $memory_url ); ?>"><?php esc_html_e( 'Memory Foam', 'foam-form-studio' ); ?></a>
							</div>
							<div class="foam-nav-panel__column">
								<span class="foam-nav-panel__label"><?php esc_html_e( 'Browse', 'foam-form-studio' ); ?></span>
								<a href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Shop All', 'foam-form-studio' ); ?></a>
								<a href="<?php echo esc_url( $blog_url ); ?>"><?php esc_html_e( 'Buying Guides', 'foam-form-studio' ); ?></a>
							</div>
						</div>
					</div>

					<div class="foam-nav-group">
						<button class="foam-nav-group__toggle" type="button">
							<?php esc_html_e( 'Support', 'foam-form-studio' ); ?>
						</button>
						<div class="foam-nav-panel foam-nav-panel--compact">
							<div class="foam-nav-panel__column">
								<span class="foam-nav-panel__label"><?php esc_html_e( 'Policies', 'foam-form-studio' ); ?></span>
								<a href="<?php echo esc_url( $shipping_url ); ?>"><?php esc_html_e( 'Shipping Policy', 'foam-form-studio' ); ?></a>
								<a href="<?php echo esc_url( $return_url ); ?>"><?php esc_html_e( 'Return Policy', 'foam-form-studio' ); ?></a>
								<a href="<?php echo esc_url( $privacy_url ); ?>"><?php esc_html_e( 'Privacy Policy', 'foam-form-studio' ); ?></a>
								<a href="<?php echo esc_url( $terms_url ); ?>"><?php esc_html_e( 'Terms of Service', 'foam-form-studio' ); ?></a>
								<a href="<?php echo esc_url( $contact_url ); ?>"><?php esc_html_e( 'Contact', 'foam-form-studio' ); ?></a>
							</div>
						</div>
					</div>

					<a class="foam-account-pill" href="<?php echo esc_url( $account_url ); ?>" aria-label="<?php esc_attr_e( 'My account', 'foam-form-studio' ); ?>">
						<span class="foam-account-pill__avatar"></span>
						<span class="foam-account-pill__label"><?php esc_html_e( 'My Account', 'foam-form-studio' ); ?></span>
					</a>
				</div>
			</div>
		</header>
		<?php
	},
	5
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
