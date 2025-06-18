<?php
/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Viracode
 * @subpackage Viracode/includes
 * @author     Your Name <email@example.com>
 */
class VRC_Deactivator {

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // Deactivation code will go here.
        // Example: flush_rewrite_rules(); if CPTs or taxonomies were registered.
        // For now, perhaps remove the option added on activation.
        // delete_option( 'vrc_installed_time' ); // Or perhaps not, depends on requirements.
    }
}
?>
