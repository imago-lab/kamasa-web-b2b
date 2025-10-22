<?php
/**
 * Search results template for Kamasa B2B Theme.
 *
 * @package Kamasa_B2B_Theme
 */

global $wp_query;

get_header();
?>

<main id="primary" class="site-main site-main--search">
    <header class="page-header page-header--search">
        <h1 class="page-title">
            <?php
            printf(
                /* translators: %s: search query. */
                esc_html__( 'Resultados de búsqueda para: %s', 'kamasa-b2b-theme' ),
                '<span>' . esc_html( get_search_query() ) . '</span>'
            );
            ?>
        </h1>
    </header>

    <?php if ( have_posts() ) : ?>
        <div class="search-results">
            <?php
            while ( have_posts() ) :
                the_post();

                if ( 'product' === get_post_type() ) {
                    wc_get_template_part( 'content', 'product' );
                } else {
                    ?>
                    <article id="post-<?php the_ID(); ?>" <?php post_class( 'search-result search-result--generic' ); ?>>
                        <header class="entry-header">
                            <h2 class="entry-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                        </header>
                        <div class="entry-summary">
                            <?php the_excerpt(); ?>
                        </div>
                    </article>
                    <?php
                }
            endwhile;
            ?>
        </div>

        <?php the_posts_pagination(); ?>
    <?php else : ?>
        <div class="no-results">
            <p><?php esc_html_e( 'No se encontraron resultados para tu búsqueda.', 'kamasa-b2b-theme' ); ?></p>
        </div>
    <?php endif; ?>
</main>

<?php
get_footer();
