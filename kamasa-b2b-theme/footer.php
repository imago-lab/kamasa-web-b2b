<?php
/**
 * Plantilla de pie de página para Kamasa B2B Theme.
 *
 * @package Kamasa_B2B_Theme
 */

?>
    </main><!-- #main-content -->

    <footer id="colophon" class="site-footer">
        <div class="container">
            <div class="footer-widgets">
                <?php
                if ( is_active_sidebar( 'footer-1' ) ) {
                    dynamic_sidebar( 'footer-1' );
                }
                ?>
            </div>

            <div class="site-info">
                <p>
                    <?php
                    printf(
                        /* translators: 1: current year, 2: site name. */
                        esc_html__( '© %1$s %2$s. Todos los derechos reservados.', 'kamasa-b2b-theme' ),
                        esc_html( gmdate( 'Y' ) ),
                        esc_html( get_bloginfo( 'name' ) )
                    );
                    ?>
                </p>
                <p>
                    <a href="<?php echo esc_url( home_url( '/aviso-de-privacidad' ) ); ?>">
                        <?php esc_html_e( 'Aviso de privacidad', 'kamasa-b2b-theme' ); ?>
                    </a>
                    <span class="separator" aria-hidden="true">|</span>
                    <a href="<?php echo esc_url( home_url( '/terminos-y-condiciones' ) ); ?>">
                        <?php esc_html_e( 'Términos y condiciones', 'kamasa-b2b-theme' ); ?>
                    </a>
                </p>
            </div>
        </div>
    </footer><!-- #colophon -->

    <?php wp_footer(); ?>
</body>
</html>
