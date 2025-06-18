<?php
/**
 * Plugin Name:       Viracode
 * Plugin URI:        https://example.com/viracode
 * Description:       Manages code snippets like WPCode or FluentSnippets.
 * Version:           1.0.0
 * Author:            Your Name
 * Author URI:        https://example.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       viracode
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Define plugin constants
 */
define( 'VRC_VERSION', '1.0.0' );
define( 'VRC_PLUGIN_FILE', __FILE__ );
define( 'VRC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'VRC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'VRC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) ); // Added for convenience

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-vrc-activator.php
 */
function activate_viracode() {
    require_once VRC_PLUGIN_DIR . 'includes/class-vrc-activator.php';
    VRC_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-vrc-deactivator.php
 */
function deactivate_viracode() {
    require_once VRC_PLUGIN_DIR . 'includes/class-vrc-deactivator.php';
    VRC_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_viracode' );
register_deactivation_hook( __FILE__, 'deactivate_viracode' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require VRC_PLUGIN_DIR . 'includes/class-vrc-core.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_viracode() {
    $plugin = new VRC_Core();
    $plugin->run();
}
run_viracode();
?>
