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
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'body_class', array( $this, 'body_classes' ) );
		add_filter( 'woocommerce_currency', array( $this, 'force_store_currency' ) );
		add_filter( 'woocommerce_product_tabs', array( $this, 'adjust_product_tabs' ), 98 );
		add_filter( 'woocommerce_checkout_fields', array( $this, 'streamline_checkout_fields' ) );
		add_filter( 'woocommerce_states', array( $this, 'filter_states' ) );
		add_filter( 'woocommerce_available_payment_gateways', array( $this, 'mark_future_gateways' ) );
		add_filter( 'get_block_templates', array( $this, 'disable_woo_block_templates' ), 100, 3 );
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

		wp_enqueue_style(
			'foam-form-commerce-kit',
			FOAM_FORM_COMMERCE_URL . 'assets/css/commerce-kit.css',
			array(),
			FOAM_FORM_COMMERCE_VERSION
		);

		wp_enqueue_script(
			'foam-form-commerce-kit',
			FOAM_FORM_COMMERCE_URL . 'assets/js/commerce-kit.js',
			array( 'jquery' ),
			FOAM_FORM_COMMERCE_VERSION,
			true
		);

		wp_localize_script(
			'foam-form-commerce-kit',
			'foamFormCommerce',
			array(
				'currencySymbol'   => get_woocommerce_currency_symbol(),
				'stickyCartText'   => __( 'Add to Cart', 'foam-form-commerce-kit' ),
				'popupSuccessText' => __( 'Thanks. Your 10% welcome offer is ready for Klaviyo sync.', 'foam-form-commerce-kit' ),
			)
		);
	}

	/**
	 * Add body classes.
	 *
	 * @param array $classes Body classes.
	 * @return array
	 */
	public function body_classes( $classes ) {
		$classes[] = 'foam-form-storefront';
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
	 * Register custom image sizes.
	 *
	 * @return void
	 */
	public function register_image_sizes() {
		add_image_size( 'foam_form_lifestyle', 1440, 1080, true );
		add_image_size( 'foam_form_product_story', 1440, 1080, true );
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
		$shop_url                 = home_url( '/shop/' );
		$about_url                = home_url( '/about-us/' );
		$compressed_sofa_beds_url = $this->get_product_category_link( 'compressed-sofa-beds' );
		$mattresses_url           = $this->get_product_category_link( 'mattresses' );
		$space_saving_sofas_url   = $this->get_product_category_link( 'space-saving-sofas' );
		$modular_sofas_url        = $this->get_product_category_link( 'modular-sofas' );

		ob_start();
		?>
		<section class="foam-hero-shell">
			<div class="foam-hero-grid">
				<div class="foam-hero-copy">
					<div>
						<span class="foam-hero-eyebrow"><?php esc_html_e( 'Affordable luxury for modern apartments', 'foam-form-commerce-kit' ); ?></span>
						<h1><?php esc_html_e( 'Transform Any Room Into Comfort', 'foam-form-commerce-kit' ); ?></h1>
						<p><?php esc_html_e( 'Premium sofa beds and memory foam furniture designed for modern living.', 'foam-form-commerce-kit' ); ?></p>
						<div class="foam-hero-actions">
							<a class="foam-button" href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Shop Best Sellers', 'foam-form-commerce-kit' ); ?></a>
							<a class="foam-button foam-button--secondary" href="#collections"><?php esc_html_e( 'Explore Collections', 'foam-form-commerce-kit' ); ?></a>
						</div>
					</div>
					<div class="foam-hero-metrics">
						<div><strong>4.8/5</strong><span><?php esc_html_e( 'Average rating', 'foam-form-commerce-kit' ); ?></span></div>
						<div><strong>10,000+</strong><span><?php esc_html_e( 'Happy customers', 'foam-form-commerce-kit' ); ?></span></div>
						<div><strong>Fast</strong><span><?php esc_html_e( 'US shipping and no-assembly comfort', 'foam-form-commerce-kit' ); ?></span></div>
					</div>
				</div>
				<div class="foam-hero-visual">
					<div class="foam-floating-card">
						<span><?php esc_html_e( 'Best Seller', 'foam-form-commerce-kit' ); ?></span>
						<h3><?php esc_html_e( 'Black corduroy sofa bed, elevated for real homes', 'foam-form-commerce-kit' ); ?></h3>
						<p><?php esc_html_e( 'Premium texture, small-space logic, and editorial styling that feels more West Elm than marketplace clutter.', 'foam-form-commerce-kit' ); ?></p>
					</div>
				</div>
			</div>
		</section>

		<section class="foam-home-features" aria-label="<?php esc_attr_e( 'Core selling points', 'foam-form-commerce-kit' ); ?>">
			<div class="foam-home-feature">
				<span class="foam-home-feature__icon"><?php esc_html_e( 'FS', 'foam-form-commerce-kit' ); ?></span>
				<div><strong><?php esc_html_e( 'Free Shipping', 'foam-form-commerce-kit' ); ?></strong><span><?php esc_html_e( 'US-wide on orders over $50', 'foam-form-commerce-kit' ); ?></span></div>
			</div>
			<div class="foam-home-feature">
				<span class="foam-home-feature__icon"><?php esc_html_e( 'TR', 'foam-form-commerce-kit' ); ?></span>
				<div><strong><?php esc_html_e( '30 Night Trial', 'foam-form-commerce-kit' ); ?></strong><span><?php esc_html_e( 'Low-risk comfort testing at home', 'foam-form-commerce-kit' ); ?></span></div>
			</div>
			<div class="foam-home-feature">
				<span class="foam-home-feature__icon"><?php esc_html_e( 'HF', 'foam-form-commerce-kit' ); ?></span>
				<div><strong><?php esc_html_e( 'High Density Foam', 'foam-form-commerce-kit' ); ?></strong><span><?php esc_html_e( 'Supportive layers built for daily use', 'foam-form-commerce-kit' ); ?></span></div>
			</div>
			<div class="foam-home-feature">
				<span class="foam-home-feature__icon"><?php esc_html_e( 'NA', 'foam-form-commerce-kit' ); ?></span>
				<div><strong><?php esc_html_e( 'No Assembly Required', 'foam-form-commerce-kit' ); ?></strong><span><?php esc_html_e( 'Unbox, expand, and style in minutes', 'foam-form-commerce-kit' ); ?></span></div>
			</div>
		</section>

		<section class="foam-home-section" id="collections">
			<div class="foam-section-heading">
				<p class="foam-kicker"><?php esc_html_e( 'Shop by room and product type', 'foam-form-commerce-kit' ); ?></p>
				<h2><?php esc_html_e( 'Collections designed like a premium furniture brand, not a dropshipping grid', 'foam-form-commerce-kit' ); ?></h2>
			</div>
			<div class="foam-collection-grid">
				<a class="foam-collection-card foam-collection-card--sofabed" href="<?php echo esc_url( $compressed_sofa_beds_url ); ?>">
					<span><?php esc_html_e( 'Sofa Beds', 'foam-form-commerce-kit' ); ?></span>
					<strong><?php esc_html_e( 'Convertible comfort for apartments and guest rooms', 'foam-form-commerce-kit' ); ?></strong>
					<em><?php esc_html_e( 'Shop Collection', 'foam-form-commerce-kit' ); ?></em>
				</a>
				<a class="foam-collection-card foam-collection-card--memory" href="<?php echo esc_url( $mattresses_url ); ?>">
					<span><?php esc_html_e( 'Memory Foam', 'foam-form-commerce-kit' ); ?></span>
					<strong><?php esc_html_e( 'Supportive foam layers with a softer luxury feel', 'foam-form-commerce-kit' ); ?></strong>
					<em><?php esc_html_e( 'Shop Collection', 'foam-form-commerce-kit' ); ?></em>
				</a>
				<a class="foam-collection-card foam-collection-card--loveseat" href="<?php echo esc_url( $space_saving_sofas_url ); ?>">
					<span><?php esc_html_e( 'Loveseats', 'foam-form-commerce-kit' ); ?></span>
					<strong><?php esc_html_e( 'Small-space seating with premium upholstery presence', 'foam-form-commerce-kit' ); ?></strong>
					<em><?php esc_html_e( 'Shop Collection', 'foam-form-commerce-kit' ); ?></em>
				</a>
				<a class="foam-collection-card foam-collection-card--living" href="<?php echo esc_url( $modular_sofas_url ); ?>">
					<span><?php esc_html_e( 'Living Room', 'foam-form-commerce-kit' ); ?></span>
					<strong><?php esc_html_e( 'Modular layouts for modern homes and Airbnb styling', 'foam-form-commerce-kit' ); ?></strong>
					<em><?php esc_html_e( 'Shop Collection', 'foam-form-commerce-kit' ); ?></em>
				</a>
			</div>
		</section>

		<section class="foam-home-section foam-home-section--product-showcase">
			<div class="foam-section-heading">
				<p class="foam-kicker"><?php esc_html_e( 'Best sellers', 'foam-form-commerce-kit' ); ?></p>
				<h2><?php esc_html_e( 'Best-selling pieces shoppers trust first', 'foam-form-commerce-kit' ); ?></h2>
			</div>
			<?php echo do_shortcode( '[products ids="35,53,54,37" columns="4" orderby="post__in"]' ); ?>
		</section>

		<section class="foam-home-section">
			<div class="foam-section-heading">
				<p class="foam-kicker"><?php esc_html_e( 'Lifestyle gallery', 'foam-form-commerce-kit' ); ?></p>
				<h2><?php esc_html_e( 'Furniture shown where people actually imagine using it', 'foam-form-commerce-kit' ); ?></h2>
				<p><?php esc_html_e( 'Small apartments, reading corners, guest rooms, and modern living rooms all create emotional buying cues that increase conversion.', 'foam-form-commerce-kit' ); ?></p>
			</div>
			<div class="foam-lifestyle-gallery">
				<article class="foam-lifestyle-tile foam-lifestyle-tile--apartment"><div><span><?php esc_html_e( 'Small Apartments', 'foam-form-commerce-kit' ); ?></span><strong><?php esc_html_e( 'Light-filled layouts that still feel spacious', 'foam-form-commerce-kit' ); ?></strong></div></article>
				<article class="foam-lifestyle-tile foam-lifestyle-tile--living"><div><span><?php esc_html_e( 'Modern Living Rooms', 'foam-form-commerce-kit' ); ?></span><strong><?php esc_html_e( 'Editorial styling with affordable-luxury energy', 'foam-form-commerce-kit' ); ?></strong></div></article>
				<article class="foam-lifestyle-tile foam-lifestyle-tile--reading"><div><span><?php esc_html_e( 'Reading Corners', 'foam-form-commerce-kit' ); ?></span><strong><?php esc_html_e( 'Quiet texture, high comfort, easy maintenance', 'foam-form-commerce-kit' ); ?></strong></div></article>
				<article class="foam-lifestyle-tile foam-lifestyle-tile--guest"><div><span><?php esc_html_e( 'Guest Rooms', 'foam-form-commerce-kit' ); ?></span><strong><?php esc_html_e( 'From small sofa to sleep-ready setup in minutes', 'foam-form-commerce-kit' ); ?></strong></div></article>
			</div>
		</section>

		<section class="foam-home-section foam-home-section--reviews">
			<div class="foam-section-heading">
				<p class="foam-kicker"><?php esc_html_e( 'Social proof', 'foam-form-commerce-kit' ); ?></p>
				<h2><?php esc_html_e( '4.8/5 average rating from shoppers who want more than a cheap boxed sofa', 'foam-form-commerce-kit' ); ?></h2>
			</div>
			<div class="foam-review-summary">
				<div><strong>4.8/5</strong><span><?php esc_html_e( 'Average Rating', 'foam-form-commerce-kit' ); ?></span></div>
				<div><strong>10,000+</strong><span><?php esc_html_e( 'Happy Customers', 'foam-form-commerce-kit' ); ?></span></div>
				<div><strong>UGC</strong><span><?php esc_html_e( 'Customer photos and real-room reviews', 'foam-form-commerce-kit' ); ?></span></div>
			</div>
			<div class="foam-review-grid">
				<div class="foam-review-card"><h3><?php esc_html_e( 'Looks like a designer piece, ships like a practical one', 'foam-form-commerce-kit' ); ?></h3><p><?php esc_html_e( 'The black corduroy finish gave our apartment the premium look we wanted without the usual delivery headache.', 'foam-form-commerce-kit' ); ?></p><strong><?php esc_html_e( 'Maya, Chicago', 'foam-form-commerce-kit' ); ?></strong></div>
				<div class="foam-review-card"><h3><?php esc_html_e( 'Way more polished than most boxed furniture', 'foam-form-commerce-kit' ); ?></h3><p><?php esc_html_e( 'It feels closer to West Elm than a marketplace impulse buy. The setup was exactly as easy as promised.', 'foam-form-commerce-kit' ); ?></p><strong><?php esc_html_e( 'Derek, Austin', 'foam-form-commerce-kit' ); ?></strong></div>
				<div class="foam-review-card"><h3><?php esc_html_e( 'Perfect for Airbnb styling', 'foam-form-commerce-kit' ); ?></h3><p><?php esc_html_e( 'Guests comment on the sofa every stay. It photographs beautifully and is easy to keep clean between turnovers.', 'foam-form-commerce-kit' ); ?></p><strong><?php esc_html_e( 'Lena, San Diego', 'foam-form-commerce-kit' ); ?></strong></div>
			</div>
		</section>

		<section class="foam-home-section foam-home-section--trust">
			<div class="foam-section-heading">
				<p class="foam-kicker"><?php esc_html_e( 'Brand trust', 'foam-form-commerce-kit' ); ?></p>
				<h2><?php esc_html_e( 'The trust signals premium shoppers look for before they add to cart', 'foam-form-commerce-kit' ); ?></h2>
			</div>
			<div class="foam-badge-grid">
				<div class="foam-icon-card"><h3><?php esc_html_e( 'American Designed', 'foam-form-commerce-kit' ); ?></h3><p><?php esc_html_e( 'A clean small-apartment point of view for US renters, hosts, and first-home buyers.', 'foam-form-commerce-kit' ); ?></p></div>
				<div class="foam-icon-card"><h3><?php esc_html_e( 'Premium Materials', 'foam-form-commerce-kit' ); ?></h3><p><?php esc_html_e( 'Corduroy, pet-friendly surfaces, and high-density foam designed for everyday durability.', 'foam-form-commerce-kit' ); ?></p></div>
				<div class="foam-icon-card"><h3><?php esc_html_e( 'Safe Certified Foam', 'foam-form-commerce-kit' ); ?></h3><p><?php esc_html_e( 'Low-odor supportive materials with a stronger quality story than generic boxed furniture.', 'foam-form-commerce-kit' ); ?></p></div>
				<div class="foam-icon-card"><h3><?php esc_html_e( 'Fast Delivery', 'foam-form-commerce-kit' ); ?></h3><p><?php esc_html_e( 'Delivery-friendly packaging that gets large comfort pieces into smaller homes faster.', 'foam-form-commerce-kit' ); ?></p></div>
			</div>
		</section>

		<section class="foam-home-section foam-home-section--cta">
			<div class="foam-cta-panel">
				<div>
					<p class="foam-kicker"><?php esc_html_e( 'Premium, trustworthy, conversion-focused', 'foam-form-commerce-kit' ); ?></p>
					<h2><?php esc_html_e( 'A furniture brand website designed to feel elevated from the first scroll.', 'foam-form-commerce-kit' ); ?></h2>
				</div>
				<div class="foam-final-cta__actions">
					<a class="foam-button" href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Shop Best Sellers', 'foam-form-commerce-kit' ); ?></a>
					<a class="foam-button foam-button--secondary" href="<?php echo esc_url( $about_url ); ?>"><?php esc_html_e( 'Learn About sonovafurn', 'foam-form-commerce-kit' ); ?></a>
				</div>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Shop intro shortcode.
	 *
	 * @return string
	 */
	public function render_shop_intro() {
		$compressed_sofa_beds_url = $this->get_product_category_link( 'compressed-sofa-beds' );
		$mattresses_url           = $this->get_product_category_link( 'mattresses' );
		$space_saving_sofas_url   = $this->get_product_category_link( 'space-saving-sofas' );
		$modular_sofas_url        = $this->get_product_category_link( 'modular-sofas' );

		ob_start();
		?>
		<div class="foam-section-card foam-shop-toolbar">
			<div class="foam-section-heading">
				<p class="foam-kicker"><?php esc_html_e( 'Affordable luxury furniture', 'foam-form-commerce-kit' ); ?></p>
				<h1><?php esc_html_e( 'Space-saving sofas and foam products for modern American living', 'foam-form-commerce-kit' ); ?></h1>
				<p><?php esc_html_e( 'Designed for apartment renters, young professionals, Airbnb hosts, and small-home owners who want something that looks premium and lives practically.', 'foam-form-commerce-kit' ); ?></p>
			</div>
			<div class="foam-filter-pills">
				<a href="<?php echo esc_url( $compressed_sofa_beds_url ); ?>"><?php esc_html_e( 'Sofa Beds', 'foam-form-commerce-kit' ); ?></a>
				<a href="<?php echo esc_url( $mattresses_url ); ?>"><?php esc_html_e( 'Memory Foam', 'foam-form-commerce-kit' ); ?></a>
				<a href="<?php echo esc_url( $space_saving_sofas_url ); ?>"><?php esc_html_e( 'Loveseats', 'foam-form-commerce-kit' ); ?></a>
				<a href="<?php echo esc_url( $modular_sofas_url ); ?>"><?php esc_html_e( 'Living Room', 'foam-form-commerce-kit' ); ?></a>
			</div>
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

		echo '<div class="foam-breadcrumb-shell">';
		woocommerce_breadcrumb();
		echo '</div>';
	}

	/**
	 * Render shop intro on archive templates.
	 *
	 * @return void
	 */
	public function render_shop_intro_hook() {
		if ( function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() ) ) {
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
			<h2><?php esc_html_e( 'Send us a message', 'foam-form-commerce-kit' ); ?></h2>
			<p><?php esc_html_e( 'This form is ready to be connected to Elementor Forms, WPForms, or a Klaviyo service flow.', 'foam-form-commerce-kit' ); ?></p>
			<p><label><?php esc_html_e( 'Name', 'foam-form-commerce-kit' ); ?><br><input type="text"></label></p>
			<p><label><?php esc_html_e( 'Email', 'foam-form-commerce-kit' ); ?><br><input type="email"></label></p>
			<p><label><?php esc_html_e( 'Message', 'foam-form-commerce-kit' ); ?><br><textarea rows="5"></textarea></label></p>
			<p><button type="button" class="foam-button"><?php esc_html_e( 'Send Message', 'foam-form-commerce-kit' ); ?></button></p>
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
			<div class="foam-exit-popup__dialog" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Welcome offer signup', 'foam-form-commerce-kit' ); ?>">
				<button type="button" class="foam-exit-popup__close" aria-label="<?php esc_attr_e( 'Close popup', 'foam-form-commerce-kit' ); ?>">&times;</button>
				<p class="foam-kicker"><?php esc_html_e( 'Before you go', 'foam-form-commerce-kit' ); ?></p>
				<h2><?php esc_html_e( 'Get 10% Off Your First Order', 'foam-form-commerce-kit' ); ?></h2>
				<p><?php esc_html_e( 'This signup form is prepped for Klaviyo welcome flows and cart-abandonment automation.', 'foam-form-commerce-kit' ); ?></p>
				<form class="foam-exit-popup__form foam-klaviyo-form" data-klaviyo-list="sonovafurn-welcome">
					<input type="email" name="email" placeholder="<?php esc_attr_e( 'Enter your email', 'foam-form-commerce-kit' ); ?>" required>
					<button type="submit" class="foam-button"><?php esc_html_e( 'Unlock 10% Off', 'foam-form-commerce-kit' ); ?></button>
				</form>
				<small><?php esc_html_e( 'Klaviyo placeholder ready for email marketing and abandoned-cart flows.', 'foam-form-commerce-kit' ); ?></small>
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

		return '<div class="foam-section-card foam-ai-shell"><h2>Recommended for you</h2><p>Behavior-based recommendation module ready for future AI scoring and Klaviyo segmentation.</p>' . do_shortcode( '[products ids="' . esc_attr( implode( ',', $ids ) ) . '" columns="4" orderby="post__in"]' ) . '</div>';
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

