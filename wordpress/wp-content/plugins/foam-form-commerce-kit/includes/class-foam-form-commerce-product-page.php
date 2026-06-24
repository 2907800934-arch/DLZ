<?php
/**
 * Single product conversion modules.
 *
 * @package FoamFormCommerceKit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Foam_Form_Commerce_Product_Page {

	/**
	 * Singleton.
	 *
	 * @var Foam_Form_Commerce_Product_Page|null
	 */
	protected static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return Foam_Form_Commerce_Product_Page
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
		add_action( 'woocommerce_before_single_product_summary', array( $this, 'render_media_toolbar' ), 5 );
		add_action( 'woocommerce_single_product_summary', array( $this, 'render_best_seller_flag' ), 4 );
		add_action( 'woocommerce_single_product_summary', array( $this, 'render_social_proof_bar' ), 6 );
		add_action( 'woocommerce_single_product_summary', array( $this, 'render_offer_banner' ), 7 );
		add_action( 'woocommerce_single_product_summary', array( $this, 'render_financing_option' ), 11 );
		add_action( 'woocommerce_single_product_summary', array( $this, 'render_color_selector' ), 23 );
		add_action( 'woocommerce_single_product_summary', array( $this, 'render_feature_bullets' ), 21 );
		add_action( 'woocommerce_single_product_summary', array( $this, 'render_materials_snapshot' ), 26 );
		add_action( 'woocommerce_single_product_summary', array( $this, 'render_trust_row' ), 31 );
		add_action( 'woocommerce_single_product_summary', array( $this, 'render_buy_now_button' ), 35 );
		add_action( 'woocommerce_after_single_product_summary', array( $this, 'render_why_customers_love_section' ), 10 );
		add_action( 'woocommerce_after_single_product_summary', array( $this, 'render_before_after_section' ), 12 );
		add_action( 'woocommerce_after_single_product_summary', array( $this, 'render_specifications_section' ), 14 );
		add_action( 'woocommerce_after_single_product_summary', array( $this, 'render_material_layers_section' ), 16 );
		add_action( 'woocommerce_after_single_product_summary', array( $this, 'render_real_life_scenes_section' ), 18 );
		add_action( 'woocommerce_after_single_product_summary', array( $this, 'render_faq_section' ), 20 );
		add_action( 'woocommerce_after_single_product_summary', array( $this, 'render_frequently_bought_together' ), 22 );
		add_action( 'woocommerce_after_single_product_summary', array( $this, 'render_final_cta_section' ), 24 );
		add_action( 'wp_footer', array( $this, 'render_mobile_sticky_cart' ) );
		add_action( 'wp_head', array( $this, 'render_faq_schema' ) );
		add_filter( 'woocommerce_get_price_html', array( $this, 'append_offer_hint' ), 20, 2 );
		add_filter( 'woocommerce_structured_data_product', array( $this, 'enhance_structured_data' ), 20, 2 );
		add_action( 'woocommerce_before_shop_loop_item_title', array( $this, 'render_archive_badges' ), 8 );
	}

	/**
	 * Render media toolbar.
	 *
	 * @return void
	 */
	public function render_media_toolbar() {
		if ( ! is_product() ) {
			return;
		}

		echo '<div class="foam-media-toolbar">';
		echo '<span>' . esc_html__( 'Image Gallery', 'foam-form-commerce-kit' ) . '</span>';
		echo '<span>' . esc_html__( 'Video Preview', 'foam-form-commerce-kit' ) . '</span>';
		echo '<span>' . esc_html__( '360 Room View', 'foam-form-commerce-kit' ) . '</span>';
		echo '</div>';
	}

	/**
	 * Render best seller flag when tagged.
	 *
	 * @return void
	 */
	public function render_best_seller_flag() {
		global $product;

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		if ( has_term( 'best-seller', 'product_badge', $product->get_id() ) ) {
			echo '<span class="foam-best-seller-flag">' . esc_html__( 'Best Seller', 'foam-form-commerce-kit' ) . '</span>';
		}
	}

	/**
	 * Render social proof bar.
	 *
	 * @return void
	 */
	public function render_social_proof_bar() {
		global $product;

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$view_count = 120 + ( $product->get_id() % 29 );
		$sold_count = 12 + ( $product->get_id() % 11 );

		echo '<div class="foam-social-proof">';
		echo '<span>' . esc_html( sprintf( __( '%d people viewed this today', 'foam-form-commerce-kit' ), $view_count ) ) . '</span>';
		echo '<span>' . esc_html( sprintf( __( '%d sold in the last 24 hours', 'foam-form-commerce-kit' ), $sold_count ) ) . '</span>';
		echo '</div>';
	}

	/**
	 * Render sale banner.
	 *
	 * @return void
	 */
	public function render_offer_banner() {
		global $product;

		if ( ! $product instanceof WC_Product || ! $product->is_on_sale() ) {
			return;
		}

		echo '<div class="foam-offer-banner">' . esc_html__( 'Limited-time best seller pricing is live now.', 'foam-form-commerce-kit' ) . '</div>';
	}

	/**
	 * Render financing option.
	 *
	 * @return void
	 */
	public function render_financing_option() {
		global $product;

		if ( ! $product instanceof WC_Product || ! $product->get_price() ) {
			return;
		}

		$installment = number_format( (float) $product->get_price() / 4, 2 );
		echo '<p class="foam-financing-option">' . esc_html( sprintf( __( 'or 4 interest-free payments of $%s with future financing integration', 'foam-form-commerce-kit' ), $installment ) ) . '</p>';
	}

	/**
	 * Render color selector.
	 *
	 * @return void
	 */
	public function render_color_selector() {
		global $product;

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$colors = get_post_meta( $product->get_id(), '_foam_colors', true );
		if ( empty( $colors ) ) {
			return;
		}

		$list = array_filter( array_map( 'trim', explode( ',', $colors ) ) );
		if ( empty( $list ) ) {
			return;
		}

		echo '<div class="foam-color-selector"><strong>' . esc_html__( 'Color', 'foam-form-commerce-kit' ) . '</strong><div class="foam-color-selector__swatches">';
		foreach ( $list as $color ) {
			$class = 'foam-swatch';
			if ( false !== stripos( $color, 'black' ) ) {
				$class .= ' foam-swatch--black';
			} elseif ( false !== stripos( $color, 'green' ) ) {
				$class .= ' foam-swatch--green';
			} elseif ( false !== stripos( $color, 'ivory' ) || false !== stripos( $color, 'white' ) || false !== stripos( $color, 'cream' ) ) {
				$class .= ' foam-swatch--ivory';
			}

			echo '<span class="' . esc_attr( $class ) . '">' . esc_html( $color ) . '</span>';
		}
		echo '</div></div>';
	}

	/**
	 * Render short conversion bullets.
	 *
	 * @return void
	 */
	public function render_feature_bullets() {
		global $product;

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$bullets = get_post_meta( $product->get_id(), '_foam_feature_bullets', true );
		$bullets = is_array( $bullets ) ? $bullets : array(
			__( 'Convertible sofa bed format for modern small spaces', 'foam-form-commerce-kit' ),
			__( 'No assembly required, just unbox and style', 'foam-form-commerce-kit' ),
			__( 'Pet-friendly, easy-maintenance upholstery story', 'foam-form-commerce-kit' ),
		);

		echo '<div class="foam-feature-bullets"><h3>' . esc_html__( 'Why customers love it', 'foam-form-commerce-kit' ) . '</h3><ul>';
		foreach ( $bullets as $bullet ) {
			echo '<li>' . esc_html( $bullet ) . '</li>';
		}
		echo '</ul></div>';
	}

	/**
	 * Render materials snapshot.
	 *
	 * @return void
	 */
	public function render_materials_snapshot() {
		global $product;

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$density    = get_post_meta( $product->get_id(), '_foam_density', true );
		$fabric     = get_post_meta( $product->get_id(), '_foam_fabric', true );
		$durability = get_post_meta( $product->get_id(), '_foam_durability', true );

		echo '<div class="foam-section-card foam-materials-card">';
		echo '<h3>' . esc_html__( 'Quick product highlights', 'foam-form-commerce-kit' ) . '</h3>';
		echo '<p><strong>' . esc_html__( 'High Density Foam', 'foam-form-commerce-kit' ) . '</strong><br>' . esc_html( $density ? $density : __( 'Supportive comfort foam for daily use.', 'foam-form-commerce-kit' ) ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Premium Upholstery', 'foam-form-commerce-kit' ) . '</strong><br>' . esc_html( $fabric ? $fabric : __( 'Pet-friendly fabric with easy-care texture.', 'foam-form-commerce-kit' ) ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Built for real life', 'foam-form-commerce-kit' ) . '</strong><br>' . esc_html( $durability ? $durability : __( 'Convertible, durable, and small-space ready.', 'foam-form-commerce-kit' ) ) . '</p>';
		echo '</div>';
	}

	/**
	 * Render trust badges.
	 *
	 * @return void
	 */
	public function render_trust_row() {
		echo '<div class="foam-trust-row foam-section-card">';
		echo '<span>' . esc_html__( 'Free shipping', 'foam-form-commerce-kit' ) . '</span>';
		echo '<span>' . esc_html__( '30 night trial', 'foam-form-commerce-kit' ) . '</span>';
		echo '<span>' . esc_html__( 'Pet friendly fabric', 'foam-form-commerce-kit' ) . '</span>';
		echo '<span>' . esc_html__( 'No assembly required', 'foam-form-commerce-kit' ) . '</span>';
		echo '</div>';
	}

	/**
	 * Render buy now button.
	 *
	 * @return void
	 */
	public function render_buy_now_button() {
		if ( ! is_product() ) {
			return;
		}

		echo '<div class="foam-buy-now-row"><a class="foam-button foam-button--secondary foam-scroll-cart" href="#cart">' . esc_html__( 'Buy Now', 'foam-form-commerce-kit' ) . '</a></div>';
	}

	/**
	 * Render why-customers-love section.
	 *
	 * @return void
	 */
	public function render_why_customers_love_section() {
		if ( ! is_product() ) {
			return;
		}

		echo '<section class="foam-section-card foam-benefits-shell">';
		echo '<div class="foam-section-heading"><p class="foam-kicker">' . esc_html__( 'Section 2', 'foam-form-commerce-kit' ) . '</p><h2>' . esc_html__( 'Why Customers Love It', 'foam-form-commerce-kit' ) . '</h2></div>';
		echo '<div class="foam-benefits-grid">';
		echo '<div class="foam-icon-card"><h3>' . esc_html__( 'High Density Foam', 'foam-form-commerce-kit' ) . '</h3><p>' . esc_html__( 'Supportive comfort that feels premium from the first sit.', 'foam-form-commerce-kit' ) . '</p></div>';
		echo '<div class="foam-icon-card"><h3>' . esc_html__( 'Convertible Design', 'foam-form-commerce-kit' ) . '</h3><p>' . esc_html__( 'A smarter layout for lounge mode and guest-sleep flexibility.', 'foam-form-commerce-kit' ) . '</p></div>';
		echo '<div class="foam-icon-card"><h3>' . esc_html__( 'Pet Friendly', 'foam-form-commerce-kit' ) . '</h3><p>' . esc_html__( 'A fabric story built for easier upkeep in everyday homes.', 'foam-form-commerce-kit' ) . '</p></div>';
		echo '<div class="foam-icon-card"><h3>' . esc_html__( 'Easy Cleaning', 'foam-form-commerce-kit' ) . '</h3><p>' . esc_html__( 'Simple spot cleaning and lower-maintenance textures help keep it looking elevated.', 'foam-form-commerce-kit' ) . '</p></div>';
		echo '</div>';
		echo '</section>';
	}

	/**
	 * Render before/after section.
	 *
	 * @return void
	 */
	public function render_before_after_section() {
		if ( ! is_product() ) {
			return;
		}

		echo '<section class="foam-section-card foam-before-after-shell">';
		echo '<div class="foam-section-heading"><p class="foam-kicker">' . esc_html__( 'Section 3', 'foam-form-commerce-kit' ) . '</p><h2>' . esc_html__( 'Before vs After', 'foam-form-commerce-kit' ) . '</h2></div>';
		echo '<div class="foam-before-after-grid">';
		echo '<div class="foam-before-after-card"><span>' . esc_html__( 'Before', 'foam-form-commerce-kit' ) . '</span><strong>' . esc_html__( 'Small Sofa', 'foam-form-commerce-kit' ) . '</strong><p>' . esc_html__( 'Compact enough for apartment entries and smaller rooms.', 'foam-form-commerce-kit' ) . '</p></div>';
		echo '<div class="foam-before-after-arrow">â†?/div>';
		echo '<div class="foam-before-after-card"><span>' . esc_html__( 'After', 'foam-form-commerce-kit' ) . '</span><strong>' . esc_html__( 'Full Size Bed', 'foam-form-commerce-kit' ) . '</strong><p>' . esc_html__( 'Open it up for guests, hosting, or all-day lounge living.', 'foam-form-commerce-kit' ) . '</p></div>';
		echo '</div>';
		echo '</section>';
	}

	/**
	 * Render specifications section.
	 *
	 * @return void
	 */
	public function render_specifications_section() {
		if ( ! is_product() ) {
			return;
		}

		global $product;
		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$dimensions = get_post_meta( $product->get_id(), '_foam_dimensions', true );
		$weight     = get_post_meta( $product->get_id(), '_foam_weight', true );
		$colors     = get_post_meta( $product->get_id(), '_foam_colors', true );

		echo '<section class="foam-section-card foam-specifications-shell">';
		echo '<div class="foam-section-heading"><p class="foam-kicker">' . esc_html__( 'Section 4', 'foam-form-commerce-kit' ) . '</p><h2>' . esc_html__( 'Dimensions, fit, and room planning', 'foam-form-commerce-kit' ) . '</h2></div>';
		echo '<div class="foam-spec-grid">';
		echo '<div><strong>' . esc_html__( 'Human Scale Comparison', 'foam-form-commerce-kit' ) . '</strong><span>' . esc_html__( 'Balanced seat depth and approachable height for modern apartment layouts.', 'foam-form-commerce-kit' ) . '</span></div>';
		echo '<div><strong>' . esc_html__( 'Apartment Fit Guide', 'foam-form-commerce-kit' ) . '</strong><span>' . esc_html( $dimensions ? $dimensions : __( 'Compact enough for smaller living rooms and guest rooms.', 'foam-form-commerce-kit' ) ) . '</span></div>';
		echo '<div><strong>' . esc_html__( 'Room Placement Examples', 'foam-form-commerce-kit' ) . '</strong><span>' . esc_html__( 'Works in studios, office lounges, guest rooms, and living room corners.', 'foam-form-commerce-kit' ) . '</span></div>';
		echo '<div><strong>' . esc_html__( 'Weight', 'foam-form-commerce-kit' ) . '</strong><span>' . esc_html( $weight ? $weight : __( 'Designed to be delivery-friendly for its size.', 'foam-form-commerce-kit' ) ) . '</span></div>';
		echo '<div><strong>' . esc_html__( 'Color', 'foam-form-commerce-kit' ) . '</strong><span>' . esc_html( $colors ? $colors : __( 'Neutral designer-friendly tones.', 'foam-form-commerce-kit' ) ) . '</span></div>';
		echo '<div><strong>' . esc_html__( 'Shipping', 'foam-form-commerce-kit' ) . '</strong><span>' . esc_html__( 'Fast dispatch with free shipping over $50 and clear return support.', 'foam-form-commerce-kit' ) . '</span></div>';
		echo '</div>';
		echo '</section>';
	}

	/**
	 * Render material layers section.
	 *
	 * @return void
	 */
	public function render_material_layers_section() {
		if ( ! is_product() ) {
			return;
		}

		echo '<section class="foam-section-card foam-layers-shell">';
		echo '<div class="foam-section-heading"><p class="foam-kicker">' . esc_html__( 'Section 5', 'foam-form-commerce-kit' ) . '</p><h2>' . esc_html__( 'Materials and comfort layers', 'foam-form-commerce-kit' ) . '</h2></div>';
		echo '<div class="foam-layer-stack">';
		echo '<div class="foam-layer foam-layer--top"><strong>' . esc_html__( 'Breathable Fabric Layer', 'foam-form-commerce-kit' ) . '</strong><span>' . esc_html__( 'Soft-touch upholstery with a premium visual texture.', 'foam-form-commerce-kit' ) . '</span></div>';
		echo '<div class="foam-layer foam-layer--middle"><strong>' . esc_html__( 'Memory Foam Layer', 'foam-form-commerce-kit' ) . '</strong><span>' . esc_html__( 'Pressure-friendly comfort that supports lounging and guest sleep.', 'foam-form-commerce-kit' ) . '</span></div>';
		echo '<div class="foam-layer foam-layer--bottom"><strong>' . esc_html__( 'Support Foam Layer', 'foam-form-commerce-kit' ) . '</strong><span>' . esc_html__( 'A denser base structure for shape retention and daily durability.', 'foam-form-commerce-kit' ) . '</span></div>';
		echo '</div>';
		echo '</section>';
	}

	/**
	 * Render real-life scenes section.
	 *
	 * @return void
	 */
	public function render_real_life_scenes_section() {
		if ( ! is_product() ) {
			return;
		}

		echo '<section class="foam-section-card foam-product-scenes-shell">';
		echo '<div class="foam-section-heading"><p class="foam-kicker">' . esc_html__( 'Section 6', 'foam-form-commerce-kit' ) . '</p><h2>' . esc_html__( 'Real-life scene inspiration', 'foam-form-commerce-kit' ) . '</h2></div>';
		echo '<div class="foam-product-scenes-grid">';
		echo '<article class="foam-product-scene foam-product-scene--living"><div><span>' . esc_html__( 'Living Room', 'foam-form-commerce-kit' ) . '</span><strong>' . esc_html__( 'Editorial calm for everyday lounging', 'foam-form-commerce-kit' ) . '</strong></div></article>';
		echo '<article class="foam-product-scene foam-product-scene--guest"><div><span>' . esc_html__( 'Guest Room', 'foam-form-commerce-kit' ) . '</span><strong>' . esc_html__( 'Convertible comfort when guests stay over', 'foam-form-commerce-kit' ) . '</strong></div></article>';
		echo '<article class="foam-product-scene foam-product-scene--studio"><div><span>' . esc_html__( 'Studio Apartment', 'foam-form-commerce-kit' ) . '</span><strong>' . esc_html__( 'Small-space friendly without looking temporary', 'foam-form-commerce-kit' ) . '</strong></div></article>';
		echo '<article class="foam-product-scene foam-product-scene--office"><div><span>' . esc_html__( 'Office', 'foam-form-commerce-kit' ) . '</span><strong>' . esc_html__( 'Softens workspaces and creative rooms', 'foam-form-commerce-kit' ) . '</strong></div></article>';
		echo '</div>';
		echo '</section>';
	}

	/**
	 * Render FAQ section.
	 *
	 * @return void
	 */
	public function render_faq_section() {
		if ( ! is_product() ) {
			return;
		}

		global $product;
		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$faqs = array(
			array(
				'q' => __( 'How firm is the foam?', 'foam-form-commerce-kit' ),
				'a' => __( 'The comfort feel is designed to balance plush first-impression softness with supportive daily use.', 'foam-form-commerce-kit' ),
			),
			array(
				'q' => __( 'Can pets scratch it?', 'foam-form-commerce-kit' ),
				'a' => __( 'Pet-friendly fabrics are easier to maintain, though we still recommend normal upholstery care habits.', 'foam-form-commerce-kit' ),
			),
			array(
				'q' => __( 'Does it require assembly?', 'foam-form-commerce-kit' ),
				'a' => __( 'No. Most products only need unboxing, expansion, and light shaping.', 'foam-form-commerce-kit' ),
			),
			array(
				'q' => __( 'How long does shipping take?', 'foam-form-commerce-kit' ),
				'a' => __( 'Most in-stock orders dispatch in 1 to 2 business days with US-focused fulfillment timing.', 'foam-form-commerce-kit' ),
			),
			array(
				'q' => __( 'Can I return it?', 'foam-form-commerce-kit' ),
				'a' => __( 'Yes. Returns are supported within the stated policy window for products in original condition.', 'foam-form-commerce-kit' ),
			),
		);

		update_post_meta( $product->get_id(), '_foam_faq_schema', $faqs );

		echo '<section class="foam-faq-shell foam-section-card">';
		echo '<div class="foam-section-heading"><p class="foam-kicker">' . esc_html__( 'Section 7', 'foam-form-commerce-kit' ) . '</p><h2>' . esc_html__( 'Frequently asked questions', 'foam-form-commerce-kit' ) . '</h2></div>';
		foreach ( $faqs as $faq ) {
			echo '<details><summary><strong>' . esc_html( $faq['q'] ) . '</strong></summary><p>' . esc_html( $faq['a'] ) . '</p></details>';
		}
		echo '</section>';
	}

	/**
	 * Render frequently bought together section.
	 *
	 * @return void
	 */
	public function render_frequently_bought_together() {
		global $product;

		if ( ! is_product() || ! $product instanceof WC_Product ) {
			return;
		}

		$related_ids = wc_get_related_products( $product->get_id(), 3 );
		if ( empty( $related_ids ) ) {
			return;
		}

		echo '<section class="foam-fbt-shell foam-section-card">';
		echo '<div class="foam-section-heading"><p class="foam-kicker">' . esc_html__( 'Bundle upsell', 'foam-form-commerce-kit' ) . '</p><h2>' . esc_html__( 'Frequently Bought Together', 'foam-form-commerce-kit' ) . '</h2><p>' . esc_html__( 'Recommended add-ons can expand into pillows, throws, and companion seating for higher AOV.', 'foam-form-commerce-kit' ) . '</p></div>';
		echo do_shortcode( '[products ids="' . esc_attr( implode( ',', $related_ids ) ) . '" columns="3" orderby="post__in"]' );
		echo '</section>';
	}

	/**
	 * Render final CTA section.
	 *
	 * @return void
	 */
	public function render_final_cta_section() {
		if ( ! is_product() ) {
			return;
		}

		echo '<section class="foam-section-card foam-final-cta">';
		echo '<div><p class="foam-kicker">' . esc_html__( 'Final CTA', 'foam-form-commerce-kit' ) . '</p><h2>' . esc_html__( 'Bring home premium comfort with the feel of a trusted furniture brand.', 'foam-form-commerce-kit' ) . '</h2></div>';
		echo '<div class="foam-final-cta__actions"><a class="foam-button foam-scroll-cart" href="#cart">' . esc_html__( 'Add To Cart', 'foam-form-commerce-kit' ) . '</a><a class="foam-button foam-button--secondary" href="/shipping-policy">' . esc_html__( 'Shipping & Returns', 'foam-form-commerce-kit' ) . '</a></div>';
		echo '</section>';
	}

	/**
	 * Render mobile sticky add to cart bar.
	 *
	 * @return void
	 */
	public function render_mobile_sticky_cart() {
		if ( ! is_product() ) {
			return;
		}

		global $product;
		if ( ! $product instanceof WC_Product || ! $product->is_purchasable() ) {
			return;
		}
		?>
		<div class="foam-sticky-cart">
			<div class="foam-sticky-cart__inner">
				<div>
					<strong><?php echo esc_html( $product->get_name() ); ?></strong><br>
					<span><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
				</div>
				<button type="button" class="foam-button foam-sticky-cart__button"><?php esc_html_e( 'Add to Cart', 'foam-form-commerce-kit' ); ?></button>
			</div>
		</div>
		<?php
	}

	/**
	 * Append offer hint on price.
	 *
	 * @param string     $price_html Price markup.
	 * @param WC_Product $product Product object.
	 * @return string
	 */
	public function append_offer_hint( $price_html, $product ) {
		if ( ! $product instanceof WC_Product || is_admin() ) {
			return $price_html;
		}

		if ( $product->get_price() && (float) $product->get_price() >= 50 ) {
			$price_html .= '<small class="foam-price-note">' . esc_html__( 'Fast US shipping included', 'foam-form-commerce-kit' ) . '</small>';
		}

		return $price_html;
	}

	/**
	 * Enhance structured data output.
	 *
	 * @param array      $markup Structured data.
	 * @param WC_Product $product Product object.
	 * @return array
	 */
	public function enhance_structured_data( $markup, $product ) {
		$markup['brand'] = array(
			'@type' => 'Brand',
			'name'  => 'sonovafurn',
		);
		$markup['audience'] = array(
			'@type'           => 'PeopleAudience',
			'suggestedGender' => 'unisex',
			'geographicArea'  => array(
				'@type' => 'Country',
				'name'  => 'United States',
			),
		);

		return $markup;
	}

	/**
	 * Render FAQ schema.
	 *
	 * @return void
	 */
	public function render_faq_schema() {
		if ( ! is_product() ) {
			return;
		}

		$product_id = get_the_ID();
		$faqs       = get_post_meta( $product_id, '_foam_faq_schema', true );
		if ( ! is_array( $faqs ) || empty( $faqs ) ) {
			return;
		}

		$schema = array(
			'@context'   => 'https://schema.org',
			'@type'      => 'FAQPage',
			'mainEntity' => array(),
		);

		foreach ( $faqs as $faq ) {
			if ( empty( $faq['q'] ) || empty( $faq['a'] ) ) {
				continue;
			}

			$schema['mainEntity'][] = array(
				'@type'          => 'Question',
				'name'           => $faq['q'],
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => $faq['a'],
				),
			);
		}

		if ( ! empty( $schema['mainEntity'] ) ) {
			echo '<script type="application/ld+json">' . wp_json_encode( $schema ) . '</script>' . "\n";
		}
	}

	/**
	 * Render archive badge before product image.
	 *
	 * @return void
	 */
	public function render_archive_badges() {
		global $product;
		if ( $product instanceof WC_Product && has_term( 'best-seller', 'product_badge', $product->get_id() ) ) {
			echo '<span class="foam-best-seller-flag">' . esc_html__( 'Best Seller', 'foam-form-commerce-kit' ) . '</span>';
		}
	}

}

