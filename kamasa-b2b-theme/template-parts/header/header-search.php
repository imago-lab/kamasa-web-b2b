<?php
/**
 * Header search form optimized for SearchWP.
 *
 * This template can be included from header.php to output the primary product search form.
 * It keeps the default WordPress search field (name="s") for compatibility while
 * adding a SearchWP engine selector so the "Productos" engine is used.
 *
 * @package Kamasa_B2B_Theme
 */

$search_placeholder = __( 'Buscar por código, marca o producto…', 'kamasa-b2b-theme' );
$search_label       = __( 'Buscar productos:', 'kamasa-b2b-theme' );
$search_engine      = apply_filters( 'kamasa_b2b_searchwp_engine_slug', 'productos' );
?>

<div class="header-search">
    <form role="search" method="get" class="header-search__form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
        <label class="screen-reader-text" for="searchwp-product-search-field"><?php echo esc_html( $search_label ); ?></label>
        <input
            type="search"
            id="searchwp-product-search-field"
            class="header-search__field"
            placeholder="<?php echo esc_attr( $search_placeholder ); ?>"
            value="<?php echo esc_attr( get_search_query() ); ?>"
            name="s"
            autocomplete="off"
        />
        <input type="hidden" name="engine" value="<?php echo esc_attr( $search_engine ); ?>" />
        <input type="hidden" name="post_type" value="product" />
        <button type="submit" class="header-search__submit"><?php esc_html_e( 'Buscar', 'kamasa-b2b-theme' ); ?></button>
    </form>
</div>
