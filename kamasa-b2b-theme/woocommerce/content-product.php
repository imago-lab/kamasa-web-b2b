<?php
/**
 * Template part for displaying products within the catalog loop.
 *
 * @package kamasa-b2b-theme
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

global $product;

if ( empty( $product ) || ! $product->is_visible() ) {
    return;
}

// Keep track of WooCommerce actions that we temporarily remove so that we can restore them afterwards.
$actions_to_restore = array();

if ( has_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close' ) ) {
    remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );
    $actions_to_restore[] = array(
        'hook'     => 'woocommerce_after_shop_loop_item',
        'callback' => 'woocommerce_template_loop_product_link_close',
        'priority' => 5,
    );
}

$link_opened_by_action = has_action( 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open' );
$manual_link_open      = false;
?>

<li <?php wc_product_class( 'kamasa-product-card__item', $product ); ?>>
    <div class="kamasa-product-card">
        <div class="kamasa-product-card__link">
            <?php
            // Provide a manual fallback for the product link if WooCommerce is not handling it through the action.
            if ( ! $link_opened_by_action ) {
                printf(
                    '<a href="%1$s" class="woocommerce-LoopProduct-link woocommerce-loop-product__link kamasa-product-card__link-anchor">',
                    esc_url( get_permalink() )
                );
                $manual_link_open = true;
            }

            /**
             * Hook: woocommerce_before_shop_loop_item.
             *
             * This hook typically outputs the opening <a> tag around the product.
             */
            do_action( 'woocommerce_before_shop_loop_item' );
            ?>

            <div class="kamasa-product-card__thumbnail">
                <?php
                /**
                 * Hook: woocommerce_before_shop_loop_item_title.
                 *
                 * Displays badges (e.g. sale flash) and the product image thumbnail.
                 */
                do_action( 'woocommerce_before_shop_loop_item_title' );

                if ( ! has_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail' ) && function_exists( 'woocommerce_template_loop_product_thumbnail' ) ) {
                    woocommerce_template_loop_product_thumbnail();
                }
                ?>
            </div>

            <div class="kamasa-product-card__title">
                <?php
                /**
                 * Hook: woocommerce_shop_loop_item_title.
                 *
                 * Outputs the product title within the loop.
                 */
                do_action( 'woocommerce_shop_loop_item_title' );

                if ( ! has_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title' ) && function_exists( 'woocommerce_template_loop_product_title' ) ) {
                    woocommerce_template_loop_product_title();
                }
                ?>
            </div>

            <?php
            if ( $link_opened_by_action || $manual_link_open ) {
                echo '</a>';
            }
            ?>
        </div>

        <div class="kamasa-product-card__meta">
            <div class="kamasa-product-card__price">
                <?php
                /**
                 * Hook: woocommerce_after_shop_loop_item_title.
                 *
                 * Responsible for rendering elements such as ratings and prices.
                 * Our custom price logic (kamasa_calcular_precio_display) hooks into this output.
                 */
                do_action( 'woocommerce_after_shop_loop_item_title' );

                if ( ! has_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price' ) && function_exists( 'woocommerce_template_loop_price' ) ) {
                    woocommerce_template_loop_price();
                }
                ?>
            </div>

            <div class="kamasa-product-card__actions">
                <?php
                // Capture the standard add-to-cart (or quote) button plus any additional actions hooked in.
                ob_start();
                do_action( 'woocommerce_after_shop_loop_item' );
                $after_shop_loop_item_content = ob_get_clean();

                if ( ! empty( $after_shop_loop_item_content ) ) {
                    echo $after_shop_loop_item_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                } elseif ( function_exists( 'woocommerce_template_loop_add_to_cart' ) ) {
                    woocommerce_template_loop_add_to_cart();
                }

                // Display the custom compare button below the primary action.
                if ( function_exists( 'kamasa_display_compare_button' ) ) :
                    ?>
                    <div class="kamasa-product-card__compare">
                        <?php kamasa_display_compare_button( get_the_ID() ); ?>
                    </div>
                    <?php
                endif;
                ?>
            </div>
        </div>
    </div>
</li>

<?php
// Restore WooCommerce actions that were temporarily removed for this template override.
foreach ( $actions_to_restore as $action_to_restore ) {
    add_action( $action_to_restore['hook'], $action_to_restore['callback'], $action_to_restore['priority'] );
}
