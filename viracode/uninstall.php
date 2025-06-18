<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @link       https://example.com/viracode
 * @since      1.0.0
 *
 * @package    Viracode
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// TODO:
// - Delete custom database table (e.g., wp_vrc_snippets)
// - Delete options from wp_options table (e.g., delete_option('vrc_settings'))
// - Potentially remove user roles or capabilities if any were added.
?>
