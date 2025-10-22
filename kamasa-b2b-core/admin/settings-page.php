<?php
/**
 * Settings page for Kamasa B2B Core plugin.
 *
 * @package Kamasa_B2B_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Register the Kamasa B2B settings submenu page.
 *
 * @return void
 */
function kamasa_b2b_register_settings_page() {
    add_submenu_page(
        'options-general.php',
        __( 'Ajustes Kamasa B2B', 'kamasa-b2b-core' ),
        __( 'Kamasa B2B', 'kamasa-b2b-core' ),
        'manage_options',
        'kamasa-b2b-settings',
        'kamasa_b2b_render_settings_page'
    );
}
add_action( 'admin_menu', 'kamasa_b2b_register_settings_page' );

/**
 * Register settings, sections and fields for Kamasa B2B settings page.
 *
 * @return void
 */
function kamasa_b2b_register_settings() {
    register_setting(
        'kamasa_b2b_options_group',
        'kamasa_b2b_settings',
        'kamasa_b2b_sanitize_settings'
    );

    add_settings_section(
        'kamasa_b2b_section_integrations',
        __( 'Integraciones Externas (n8n)', 'kamasa-b2b-core' ),
        'kamasa_b2b_render_integrations_section',
        'kamasa-b2b-settings'
    );

    add_settings_field(
        'kamasa_n8n_quote_webhook_url',
        __( 'URL Webhook Cotizaciones', 'kamasa-b2b-core' ),
        'kamasa_b2b_render_quote_webhook_field',
        'kamasa-b2b-settings',
        'kamasa_b2b_section_integrations'
    );

    add_settings_field(
        'kamasa_n8n_rag_webhook_url',
        __( 'URL Webhook Agente IA', 'kamasa-b2b-core' ),
        'kamasa_b2b_render_rag_webhook_field',
        'kamasa-b2b-settings',
        'kamasa_b2b_section_integrations'
    );
}
add_action( 'admin_init', 'kamasa_b2b_register_settings' );

/**
 * Render integrations section description.
 *
 * @return void
 */
function kamasa_b2b_render_integrations_section() {
    echo '<p>' . esc_html__( 'Configura las URLs de los webhooks de n8n que integran funcionalidades externas.', 'kamasa-b2b-core' ) . '</p>';
}

/**
 * Render the quote webhook URL field.
 *
 * @return void
 */
function kamasa_b2b_render_quote_webhook_field() {
    $options = get_option( 'kamasa_b2b_settings', array() );
    $url     = isset( $options['kamasa_n8n_quote_webhook_url'] ) ? esc_url( $options['kamasa_n8n_quote_webhook_url'] ) : '';

    printf(
        "<input type='url' class='regular-text' name='kamasa_b2b_settings[kamasa_n8n_quote_webhook_url]' value='%s' placeholder='%s' />",
        esc_attr( $url ),
        esc_attr__( 'https://tu-n8n.example/webhook/cotizaciones', 'kamasa-b2b-core' )
    );
}

/**
 * Render the RAG webhook URL field.
 *
 * @return void
 */
function kamasa_b2b_render_rag_webhook_field() {
    $options = get_option( 'kamasa_b2b_settings', array() );
    $url     = isset( $options['kamasa_n8n_rag_webhook_url'] ) ? esc_url( $options['kamasa_n8n_rag_webhook_url'] ) : '';

    printf(
        "<input type='url' class='regular-text' name='kamasa_b2b_settings[kamasa_n8n_rag_webhook_url]' value='%s' placeholder='%s' />",
        esc_attr( $url ),
        esc_attr__( 'https://tu-n8n.example/webhook/agente-ia', 'kamasa-b2b-core' )
    );
}

/**
 * Render the settings page.
 *
 * @return void
 */
function kamasa_b2b_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Ajustes Kamasa B2B', 'kamasa-b2b-core' ); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'kamasa_b2b_options_group' );
            do_settings_sections( 'kamasa-b2b-settings' );
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

/**
 * Sanitize settings input.
 *
 * @param array $input Array of input values.
 *
 * @return array
 */
function kamasa_b2b_sanitize_settings( $input ) {
    $new_input = array();

    if ( isset( $input['kamasa_n8n_quote_webhook_url'] ) ) {
        $new_input['kamasa_n8n_quote_webhook_url'] = esc_url_raw( $input['kamasa_n8n_quote_webhook_url'] );
    }

    if ( isset( $input['kamasa_n8n_rag_webhook_url'] ) ) {
        $new_input['kamasa_n8n_rag_webhook_url'] = esc_url_raw( $input['kamasa_n8n_rag_webhook_url'] );
    }

    return $new_input;
}
