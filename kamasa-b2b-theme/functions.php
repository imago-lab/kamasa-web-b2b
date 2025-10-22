<?php
/**
 * Funciones principales del tema hijo Kamasa B2B Theme.
 */

if ( ! function_exists( 'kamasa_b2b_enqueue_styles' ) ) {
    /**
     * Encola las hojas de estilo del tema padre e hijo.
     */
    function kamasa_b2b_enqueue_styles() {
        $parent_style = 'parent-style';

        wp_enqueue_style(
            $parent_style,
            get_template_directory_uri() . '/style.css',
            [],
            wp_get_theme( get_template() )->get( 'Version' )
        );

        wp_enqueue_style(
            'child-style',
            get_stylesheet_uri(),
            [ $parent_style ],
            wp_get_theme()->get( 'Version' )
        );
    }
}
add_action( 'wp_enqueue_scripts', 'kamasa_b2b_enqueue_styles' );

if ( ! function_exists( 'kamasa_b2b_register_menus' ) ) {
    /**
     * Registra las ubicaciones de menús del tema hijo.
     */
    function kamasa_b2b_register_menus() {
        register_nav_menus(
            [
                'primary' => __( 'Menú Principal', 'kamasa-b2b-theme' ),
            ]
        );
    }
}
add_action( 'after_setup_theme', 'kamasa_b2b_register_menus' );

