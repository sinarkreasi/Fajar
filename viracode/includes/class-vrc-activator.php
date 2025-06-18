<?php
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Viracode
 * @subpackage Viracode/includes
 * @author     Your Name <email@example.com>
 */
class VRC_Activator {

    /**
     * Creates the custom database table for storing snippets.
     *
     * This method is called when the plugin is activated. It checks if the table
     * already exists and, if not, creates it.
     *
     * @since    1.0.0
     */
    public static function activate() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vrc_snippets';
        $charset_collate = $wpdb->get_charset_collate();

        // Check if table exists before trying to create it
        if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) != $table_name ) {
            $sql = "CREATE TABLE $table_name (
                id BIGINT(20) NOT NULL AUTO_INCREMENT,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                code LONGTEXT NOT NULL,
                type VARCHAR(10) NOT NULL DEFAULT 'php',
                scope VARCHAR(20) NOT NULL DEFAULT 'everywhere',
                priority INT(11) NOT NULL DEFAULT 10,
                status VARCHAR(10) NOT NULL DEFAULT 'active',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                KEY idx_status_scope_priority (status, scope, priority),
                KEY idx_type (type)
            ) $charset_collate;";

            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta( $sql );
        }

        // Store plugin activation time if not already set (optional, for tracking)
        if ( ! get_option( 'vrc_installed_time' ) ) {
            update_option( 'vrc_installed_time', time() );
        }

        // Set a version option for future upgrade routines
        update_option( 'vrc_plugin_version', VRC_VERSION );
    }
}
?>
