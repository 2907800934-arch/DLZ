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
		add_action( 'woocommerce_single_product_summary', array( $this, 'render_feature_bullets' ), 21 );
		add_action( 'woocommerce_single_product_summary', array( $this, 'render_color_selector' ), 23 );
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
		echo '<span>' . esc_html__( 'Gallery', 'foam-form-commerce-kit' ) . '</span>';
		echo '<span>' . esc_html__( 'Material notes', 'foam-form-commerce-kit' ) . '</span>';
		echo '<span>' . esc_html__( 'Room context', 'foam-form-commerce-kit' ) . '</span>';
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

		echo '<div class="foam-social-proof">';
		echo '<span>' . esc_html__( 'Designed for compact living rooms, guest rooms, and more flexible domestic layouts', 'foam-form-commerce-kit' ) . '</span>';
		echo '<span>' . esc_html__( 'Compressed delivery helps reduce friction around stairs, entries, and building access', 'foam-form-commerce-kit' ) . '</span>';
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

		echo '<div class="foam-offer-banner">' . esc_html__( 'Pricing is shown together with fit, delivery, and material information below.', 'foam-form-commerce-kit' ) . '</div>';
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
		echo '<p class="foam-financing-option">' . esc_html( sprintf( __( 'Estimated at $%s across four payments when installment services are available', 'foam-form-commerce-kit' ), $installment ) ) . '</p>';
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
			__( 'Convertible format for compact rooms and guest-ready use', 'foam-form-commerce-kit' ),
			__( 'No assembly required, with a straightforward unbox-and-settle setup', 'foam-form-commerce-kit' ),
			__( 'Practical upholstery selected for easier routine care', 'foam-form-commerce-kit' ),
		);

		echo '<div class="foam-feature-bullets"><h3>' . esc_html__( 'What it is designed to do', 'foam-form-commerce-kit' ) . '</h3><ul>';
		foreach ( $bullets as $bullet ) {
			echo '<li>' . esc_html( $bullet ) . '</li>';
		}
		echo '</ul></div>';
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

		echo '<section class="foam-section-card foam-materials-card">';
		echo '<div class="foam-materials-card__intro"><span>' . esc_html__( 'Material notes', 'foam-form-commerce-kit' ) . '</span><p>' . esc_html__( 'A shorter view of what shapes the feel, finish, and everyday durability of the piece.', 'foam-form-commerce-kit' ) . '</p></div>';
		echo '<div class="foam-materials-grid">';
		echo '<article><strong>' . esc_html__( 'Foam structure', 'foam-form-commerce-kit' ) . '</strong><p>' . esc_html( $density ? $density : __( 'Supportive foam intended for repeated daily sitting and sleeping use.', 'foam-form-commerce-kit' ) ) . '</p></article>';
		echo '<article><strong>' . esc_html__( 'Surface material', 'foam-form-commerce-kit' ) . '</strong><p>' . esc_html( $fabric ? $fabric : __( 'Textured upholstery selected for a calmer visual finish and easier routine care.', 'foam-form-commerce-kit' ) ) . '</p></article>';
		echo '<article><strong>' . esc_html__( 'Durability context', 'foam-form-commerce-kit' ) . '</strong><p>' . esc_html( $durability ? $durability : __( 'Developed for flexible domestic use, including guest setups and smaller-space living.', 'foam-form-commerce-kit' ) ) . '</p></article>';
		echo '</div>';
		echo '</section>';
	}

	/**
	 * Render trust badges.
	 *
	 * @return void
	 */
	public function render_trust_row() {
		echo '<section class="foam-trust-row foam-section-card">';
		echo '<article><strong>' . esc_html__( 'Shipping notes', 'foam-form-commerce-kit' ) . '</strong><p>' . esc_html__( 'Compressed delivery helps with tighter entries, stairs, and apartment access.', 'foam-form-commerce-kit' ) . '</p></article>';
		echo '<article><strong>' . esc_html__( 'Return window', 'foam-form-commerce-kit' ) . '</strong><p>' . esc_html__( 'Policy timing stays visible so purchase decisions can remain measured.', 'foam-form-commerce-kit' ) . '</p></article>';
		echo '<article><strong>' . esc_html__( 'Material details', 'foam-form-commerce-kit' ) . '</strong><p>' . esc_html__( 'Foam, fabric, and maintenance notes stay close to the buying area.', 'foam-form-commerce-kit' ) . '</p></article>';
		echo '<article><strong>' . esc_html__( 'Simple setup', 'foam-form-commerce-kit' ) . '</strong><p>' . esc_html__( 'Most pieces require unboxing, expansion time, and only light shaping.', 'foam-form-commerce-kit' ) . '</p></article>';
		echo '</section>';
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

		echo '<div class="foam-buy-now-row"><a class="foam-text-link foam-scroll-cart" href="#cart">' . esc_html__( 'Go to purchase options', 'foam-form-commerce-kit' ) . '</a></div>';
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
		echo '<div class="foam-section-heading"><p class="foam-kicker">' . esc_html__( 'Technology', 'foam-form-commerce-kit' ) . '</p><h2>' . esc_html__( 'Practical reasons this format settles well into everyday rooms', 'foam-form-commerce-kit' ) . '</h2></div>';
		echo '<div class="foam-benefits-grid">';
		echo '<div class="foam-icon-card"><h3>' . esc_html__( 'Layered support foam', 'foam-form-commerce-kit' ) . '</h3><p>' . esc_html__( 'Foam structure selected to balance softness, support, and recovery through regular use.', 'foam-form-commerce-kit' ) . '</p></div>';
		echo '<div class="foam-icon-card"><h3>' . esc_html__( 'Convertible use', 'foam-form-commerce-kit' ) . '</h3><p>' . esc_html__( 'Useful when one room needs to shift between everyday seating and guest accommodation.', 'foam-form-commerce-kit' ) . '</p></div>';
		echo '<div class="foam-icon-card"><h3>' . esc_html__( 'Routine maintenance', 'foam-form-commerce-kit' ) . '</h3><p>' . esc_html__( 'Surface materials are chosen with regular cleaning and lived-in use in mind.', 'foam-form-commerce-kit' ) . '</p></div>';
		echo '<div class="foam-icon-card"><h3>' . esc_html__( 'Entry-friendly delivery', 'foam-form-commerce-kit' ) . '</h3><p>' . esc_html__( 'Compressed transport is especially helpful where access, stairs, or hallways are more limited.', 'foam-form-commerce-kit' ) . '</p></div>';
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
		echo '<div class="foam-section-heading"><p class="foam-kicker">' . esc_html__( 'Use case', 'foam-form-commerce-kit' ) . '</p><h2>' . esc_html__( 'From everyday seating to guest-ready sleeping space', 'foam-form-commerce-kit' ) . '</h2></div>';
		echo '<div class="foam-before-after-grid">';
		echo '<div class="foam-before-after-card"><span>' . esc_html__( 'Before', 'foam-form-commerce-kit' ) . '</span><strong>' . esc_html__( 'Small Sofa', 'foam-form-commerce-kit' ) . '</strong><p>' . esc_html__( 'Compact enough for apartment entries and smaller rooms.', 'foam-form-commerce-kit' ) . '</p></div>';
		echo '<div class="foam-before-after-arrow" aria-hidden="true">&rarr;</div>';
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
		echo '<div class="foam-section-heading"><p class="foam-kicker">' . esc_html__( 'Fit guide', 'foam-form-commerce-kit' ) . '</p><h2>' . esc_html__( 'Dimensions, room fit, and placement context', 'foam-form-commerce-kit' ) . '</h2></div>';
		echo '<div class="foam-spec-grid">';
		echo '<div><strong>' . esc_html__( 'Scale reference', 'foam-form-commerce-kit' ) . '</strong><span>' . esc_html__( 'Seat depth and height are balanced for domestic use rather than oversized showroom proportion.', 'foam-form-commerce-kit' ) . '</span></div>';
		echo '<div><strong>' . esc_html__( 'Room fit', 'foam-form-commerce-kit' ) . '</strong><span>' . esc_html( $dimensions ? $dimensions : __( 'Sized to work in smaller living rooms, guest rooms, and apartment layouts.', 'foam-form-commerce-kit' ) ) . '</span></div>';
		echo '<div><strong>' . esc_html__( 'Placement examples', 'foam-form-commerce-kit' ) . '</strong><span>' . esc_html__( 'Appropriate for studios, office lounges, guest rooms, and secondary sitting areas.', 'foam-form-commerce-kit' ) . '</span></div>';
		echo '<div><strong>' . esc_html__( 'Weight', 'foam-form-commerce-kit' ) . '</strong><span>' . esc_html( $weight ? $weight : __( 'Designed to be delivery-friendly for its size.', 'foam-form-commerce-kit' ) ) . '</span></div>';
		echo '<div><strong>' . esc_html__( 'Color', 'foam-form-commerce-kit' ) . '</strong><span>' . esc_html( $colors ? $colors : __( 'Neutral designer-friendly tones.', 'foam-form-commerce-kit' ) ) . '</span></div>';
		echo '<div><strong>' . esc_html__( 'Shipping', 'foam-form-commerce-kit' ) . '</strong><span>' . esc_html__( 'Delivery information is paired with returns and access notes to make room planning easier.', 'foam-form-commerce-kit' ) . '</span></div>';
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
		echo '<div class="foam-section-heading"><p class="foam-kicker">' . esc_html__( 'Materials', 'foam-form-commerce-kit' ) . '</p><h2>' . esc_html__( 'A simpler view of how the layers work together', 'foam-form-commerce-kit' ) . '</h2></div>';
		echo '<div class="foam-layer-stack">';
		echo '<div class="foam-layer foam-layer--top"><strong>' . esc_html__( 'Outer upholstery', 'foam-form-commerce-kit' ) . '</strong><span>' . esc_html__( 'A textured surface selected for a calmer visual finish and regular household use.', 'foam-form-commerce-kit' ) . '</span></div>';
		echo '<div class="foam-layer foam-layer--middle"><strong>' . esc_html__( 'Comfort layer', 'foam-form-commerce-kit' ) . '</strong><span>' . esc_html__( 'The softer layer that helps with pressure relief in sitting and guest-sleep scenarios.', 'foam-form-commerce-kit' ) . '</span></div>';
		echo '<div class="foam-layer foam-layer--bottom"><strong>' . esc_html__( 'Support base', 'foam-form-commerce-kit' ) . '</strong><span>' . esc_html__( 'A denser lower structure intended to help with shape retention and repeated use.', 'foam-form-commerce-kit' ) . '</span></div>';
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
		echo '<div class="foam-section-heading"><p class="foam-kicker">' . esc_html__( 'Lifestyle', 'foam-form-commerce-kit' ) . '</p><h2>' . esc_html__( 'Room examples based on actual domestic use', 'foam-form-commerce-kit' ) . '</h2></div>';
		echo '<div class="foam-product-scenes-grid">';
		echo '<article class="foam-product-scene foam-product-scene--living"><div><span>' . esc_html__( 'Living Room', 'foam-form-commerce-kit' ) . '</span><strong>' . esc_html__( 'For daily sitting, reading, and relaxed evening use', 'foam-form-commerce-kit' ) . '</strong></div></article>';
		echo '<article class="foam-product-scene foam-product-scene--guest"><div><span>' . esc_html__( 'Guest Room', 'foam-form-commerce-kit' ) . '</span><strong>' . esc_html__( 'For occasional hosting without dedicating a full bed year-round', 'foam-form-commerce-kit' ) . '</strong></div></article>';
		echo '<article class="foam-product-scene foam-product-scene--studio"><div><span>' . esc_html__( 'Studio Apartment', 'foam-form-commerce-kit' ) . '</span><strong>' . esc_html__( 'For rooms where circulation, storage, and sleeping function overlap', 'foam-form-commerce-kit' ) . '</strong></div></article>';
		echo '<article class="foam-product-scene foam-product-scene--office"><div><span>' . esc_html__( 'Office', 'foam-form-commerce-kit' ) . '</span><strong>' . esc_html__( 'For secondary rooms that need softer seating without visual weight', 'foam-form-commerce-kit' ) . '</strong></div></article>';
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
				'a' => __( 'The feel is intended to balance a softer first impression with enough structure for repeated sitting and occasional sleeping use.', 'foam-form-commerce-kit' ),
			),
			array(
				'q' => __( 'Can pets scratch it?', 'foam-form-commerce-kit' ),
				'a' => __( 'The fabrics are selected to be more practical for homes with pets, though normal upholstery care is still recommended.', 'foam-form-commerce-kit' ),
			),
			array(
				'q' => __( 'Does it require assembly?', 'foam-form-commerce-kit' ),
				'a' => __( 'No. Most products only require unboxing, expansion time, and light shaping after unpacking.', 'foam-form-commerce-kit' ),
			),
			array(
				'q' => __( 'How long does shipping take?', 'foam-form-commerce-kit' ),
				'a' => __( 'Most in-stock orders dispatch in one to two business days, with final timing depending on destination and carrier scheduling.', 'foam-form-commerce-kit' ),
			),
			array(
				'q' => __( 'Can I return it?', 'foam-form-commerce-kit' ),
				'a' => __( 'Yes. Returns are supported within the stated policy window for products kept in original condition.', 'foam-form-commerce-kit' ),
			),
		);

		update_post_meta( $product->get_id(), '_foam_faq_schema', $faqs );

		echo '<section class="foam-faq-shell foam-section-card">';
		echo '<div class="foam-section-heading"><p class="foam-kicker">' . esc_html__( 'Questions', 'foam-form-commerce-kit' ) . '</p><h2>' . esc_html__( 'The main purchase questions, kept close to the product context', 'foam-form-commerce-kit' ) . '</h2></div>';
		foreach ( $faqs as $faq ) {
			echo '<details class="foam-product-faq-item"><summary><strong>' . esc_html( $faq['q'] ) . '</strong></summary><p>' . esc_html( $faq['a'] ) . '</p></details>';
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
		echo '<div class="foam-section-heading"><p class="foam-kicker">' . esc_html__( 'Related pieces', 'foam-form-commerce-kit' ) . '</p><h2>' . esc_html__( 'Companion items that fit the same room story', 'foam-form-commerce-kit' ) . '</h2><p>' . esc_html__( 'Useful as supporting pieces when the room needs a more complete setup, rather than as aggressive add-ons.', 'foam-form-commerce-kit' ) . '</p></div>';
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
		echo '<div><p class="foam-kicker">' . esc_html__( 'Next step', 'foam-form-commerce-kit' ) . '</p><h2>' . esc_html__( 'Continue only after the fit, material logic, and delivery approach feel appropriate for the room.', 'foam-form-commerce-kit' ) . '</h2></div>';
		echo '<div class="foam-final-cta__actions"><a class="foam-text-link foam-scroll-cart" href="#cart">' . esc_html__( 'Review purchase options', 'foam-form-commerce-kit' ) . '</a><a class="foam-text-link" href="/shipping-policy">' . esc_html__( 'Read shipping, delivery, and return details', 'foam-form-commerce-kit' ) . '</a></div>';
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
				<button type="button" class="foam-button foam-sticky-cart__button"><?php esc_html_e( 'Review options', 'foam-form-commerce-kit' ); ?></button>
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

		if ( is_shop() || is_product_taxonomy() || is_product_category() ) {
			return $price_html;
		}

		if ( $product->get_price() && (float) $product->get_price() >= 50 ) {
			$price_html .= '<small class="foam-price-note">' . esc_html__( 'Delivery and room-fit notes available below', 'foam-form-commerce-kit' ) . '</small>';
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
