<?php
/**
 * The Template for displaying product archives, including the main shop page.
 *
 * @package kamasa-b2b-theme
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

get_header( 'shop' );

/**
 * Hook: woocommerce_before_main_content.
 *
 * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
 * @hooked woocommerce_breadcrumb - 20
 */
do_action( 'woocommerce_before_main_content' );
?>

<div class="kamasa-product-archive-wrapper"><!-- Parent container for the two-column layout -->
    <aside class="kamasa-product-filters">
        <h2><?php esc_html_e( 'Filtrar Productos', 'kamasa-b2b-theme' ); ?></h2>

        <h3><?php esc_html_e( 'Categorías', 'kamasa-b2b-theme' ); ?></h3>
        <?php
        // FacetWP facet for product categories.
        echo facetwp_display( 'facet', 'categorias_producto' );
        ?>

        <h3><?php esc_html_e( 'Marca', 'kamasa-b2b-theme' ); ?></h3>
        <?php
        // FacetWP facet for product brand attribute.
        echo facetwp_display( 'facet', 'marca' );
        ?>

        <h3><?php esc_html_e( 'Voltaje', 'kamasa-b2b-theme' ); ?></h3>
        <?php
        // FacetWP facet for product voltage attribute.
        echo facetwp_display( 'facet', 'voltaje' );
        ?>

        <?php
        // Uncomment the line below to display a reset button for FacetWP filters.
        // echo facetwp_display( 'reset' );
        ?>
    </aside>

    <div class="kamasa-product-archive facetwp-template"><!-- facetwp-template class allows FacetWP to refresh this container via AJAX -->
        <?php
        // (Optional) Display currently selected FacetWP filters.
        echo facetwp_display( 'selections' );

        if ( woocommerce_product_loop() ) :
            /**
             * Hook: woocommerce_before_shop_loop.
             *
             * @hooked woocommerce_output_all_notices - 10
             * @hooked woocommerce_result_count - 20
             * @hooked woocommerce_catalog_ordering - 30
             */
            do_action( 'woocommerce_before_shop_loop' );

            woocommerce_product_loop_start();

            if ( wc_get_loop_prop( 'total' ) ) {
                while ( have_posts() ) {
                    the_post();

                    /**
                     * Hook: woocommerce_shop_loop.
                     */
                    do_action( 'woocommerce_shop_loop' );

                    wc_get_template_part( 'content', 'product' );
                }
            }

            woocommerce_product_loop_end();

            /**
             * Hook: woocommerce_after_shop_loop.
             *
             * @hooked woocommerce_pagination - 10
             */
            do_action( 'woocommerce_after_shop_loop' );
        else :
            /**
             * Hook: woocommerce_no_products_found.
             *
             * @hooked wc_no_products_found - 10
             */
            do_action( 'woocommerce_no_products_found' );
        endif;
        ?>
    </div>
</div>

<?php
/**
 * Hook: woocommerce_after_main_content.
 *
 * @hooked woocommerce_output_content_wrapper_end - 10 (outputs closing divs for the content)
 */
do_action( 'woocommerce_after_main_content' );

/**
 * Hook: woocommerce_sidebar.
 *
 * Note: We omit the default WooCommerce sidebar because the layout above already
 * includes a dedicated FacetWP filters sidebar.
 */
// do_action( 'woocommerce_sidebar' );

get_footer( 'shop' );

/*
Suggested CSS for two-column FacetWP layout:
.kamasa-product-archive-wrapper {
    display: flex;
    gap: 2rem;
}

.kamasa-product-filters {
    flex: 0 0 280px;
}

.kamasa-product-archive {
    flex: 1 1 auto;
}

@media (max-width: 992px) {
    .kamasa-product-archive-wrapper {
        flex-direction: column;
    }
}
*/
