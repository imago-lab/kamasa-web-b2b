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
    // Acciones al activar (por ejemplo, flush rewrite rules si registras CPTs/Taxonomies después).
}
register_activation_hook( __FILE__, 'kamasa_b2b_activate' );

/**
 * Acciones al desactivar el plugin.
 *
 * @return void
 */
function kamasa_b2b_deactivate() {
    // Acciones al desactivar (por ejemplo, limpiar cron jobs personalizados).
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

    if ( is_admin() ) {
        require_once KAMASA_B2B_PLUGIN_DIR . 'admin/meta-box-precios-volumen.php';
    }
}
add_action( 'plugins_loaded', 'kamasa_b2b_init' );
