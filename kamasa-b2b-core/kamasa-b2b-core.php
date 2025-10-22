<?php
/**
 * Plugin Name:       Kamasa B2B Core
 * Plugin URI:        https://www.imagolab.fun/
 * Description:       Funcionalidades B2B personalizadas para la plataforma de Grupo Kamasa.
 * Version:           1.0.0
 * Author:            imago lab
 * Author URI:        https://www.imagolab.fun/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       kamasa-b2b-core
 * Domain Path:       /languages
 *
 * @package Kamasa_B2B_Core
 */

defined( 'ABSPATH' ) || die( '¡Acceso no autorizado!' );

// Definir constantes del plugin.
define( 'KAMASA_B2B_VERSION', '1.0.0' );
define( 'KAMASA_B2B_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'KAMASA_B2B_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Acciones al activar el plugin.
 *
 * @return void
 */
function kamasa_b2b_activate() {
    add_rewrite_endpoint( 'panel-financiero', EP_ROOT | EP_PAGES );
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'kamasa_b2b_activate' );

/**
 * Acciones al desactivar el plugin.
 *
 * @return void
 */
function kamasa_b2b_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'kamasa_b2b_deactivate' );

/**
 * Inicializa la carga del plugin.
 *
 * @return void
 */
function kamasa_b2b_init() {
    // Lugar para cargar archivos esenciales del plugin.

    require_once KAMASA_B2B_PLUGIN_DIR . 'includes/pricing/pricing-logic.php';
    require_once KAMASA_B2B_PLUGIN_DIR . 'includes/frontend/display-logic.php';
    require_once KAMASA_B2B_PLUGIN_DIR . 'includes/api/customer-endpoints.php';
    require_once KAMASA_B2B_PLUGIN_DIR . 'includes/customer-panel/my-account-customization.php';
    require_once KAMASA_B2B_PLUGIN_DIR . 'includes/cpt-conversaciones.php';
    require_once KAMASA_B2B_PLUGIN_DIR . 'includes/api/agente-proxy-endpoint.php';

    add_action( 'init', 'kamasa_register_conversacion_ia_cpt' );
    add_action( 'rest_api_init', 'kamasa_register_agente_proxy_endpoint' );

    if ( is_admin() ) {
        require_once KAMASA_B2B_PLUGIN_DIR . 'admin/meta-box-precios-volumen.php';
        require_once KAMASA_B2B_PLUGIN_DIR . 'admin/settings-page.php';
    }
}
add_action( 'plugins_loaded', 'kamasa_b2b_init' );
