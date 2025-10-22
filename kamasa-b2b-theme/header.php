<?php
/**
 * Plantilla de cabecera para Kamasa B2B Theme.
 *
 * @package Kamasa_B2B_Theme
 */

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#main-content"><?php esc_html_e( 'Saltar al contenido', 'kamasa-b2b-theme' ); ?></a>

<header id="masthead" class="site-header">
    <div class="container">
        <div class="site-logo">
            <?php
            if ( function_exists( 'the_custom_logo' ) && has_custom_logo() ) {
                the_custom_logo();
            } else {
                ?>
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-title-link">
                    <?php bloginfo( 'name' ); ?>
                </a>
                <?php
            }
            ?>
        </div>

        <div class="header-content-wrapper">
            <div class="header-top">
                <div class="header-search">
                    <?php get_search_form(); ?>
                </div>
                <div class="header-account">
                    <?php if ( is_user_logged_in() ) : ?>
                        <?php
                        $account_url = '';

                        if ( function_exists( 'wc_get_page_permalink' ) ) {
                            $account_url = wc_get_page_permalink( 'myaccount' );
                        }

                        if ( ! $account_url ) {
                            $account_url = admin_url( 'profile.php' );
                        }
                        ?>
                        <a href="<?php echo esc_url( $account_url ); ?>">
                            <?php esc_html_e( 'Mi cuenta', 'kamasa-b2b-theme' ); ?>
                        </a>
                    <?php else : ?>
                        <a href="<?php echo esc_url( wp_login_url() ); ?>">
                            <?php esc_html_e( 'Acceder', 'kamasa-b2b-theme' ); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="header-bottom">
                <nav id="site-navigation" class="main-navigation" aria-label="<?php esc_attr_e( 'Menú Principal', 'kamasa-b2b-theme' ); ?>">
                    <?php
                    wp_nav_menu(
                        [
                            'theme_location' => 'primary',
                            'menu_id'        => 'primary-menu',
                            'container'      => false,
                        ]
                    );
                    ?>
                </nav>
            </div>
        </div>
    </div>
</header>

<main id="main-content" class="site-main">
