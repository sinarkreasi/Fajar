<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for enqueueing
 * the admin-specific stylesheet and JavaScript.
 *
 * @package    Viracode
 * @subpackage Viracode/admin
 * @author     Your Name <email@example.com>
 */
class VRC_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
            // Moved require_once to the methods that use them to ensure WP_List_Table is loaded
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        // wp_enqueue_style( $this->plugin_name, VRC_PLUGIN_URL . 'assets/css/vrc-admin-style.css', array(), $this->version, 'all' );
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        // wp_enqueue_script( $this->plugin_name, VRC_PLUGIN_URL . 'assets/js/vrc-admin-script.js', array( 'jquery' ), $this->version, false );
    }

    /**
     * Add the top-level admin menu page for Viracode.
     *
     * @since    1.0.0
     */
    public function add_admin_menu() {
        add_menu_page(
            __( 'Viracode Code Snippets', 'viracode' ), // Page title
            __( 'Viracode', 'viracode' ),              // Menu title
            'manage_options',                           // Capability required
            $this->plugin_name,                         // Menu slug
            array( $this, 'display_plugin_admin_page' ), // Function to display the page
            'dashicons-editor-code',                    // Icon URL
            75                                          // Position
        );

        // Submenu for "All Snippets" (which is the main page for now)
        add_submenu_page(
            $this->plugin_name,                         // Parent slug
            __( 'All Snippets', 'viracode' ),          // Page title
            __( 'All Snippets', 'viracode' ),          // Menu title
            'manage_options',                           // Capability
            $this->plugin_name,                         // Menu slug (same as parent for the main page)
            array( $this, 'display_plugin_admin_page' )  // Function
        );

        // Submenu for "Add New Snippet"
        add_submenu_page(
            $this->plugin_name,                         // Parent slug
            __( 'Add New Snippet', 'viracode' ),       // Page title
            __( 'Add New', 'viracode' ),               // Menu title
            'manage_options',                           // Capability
            $this->plugin_name . '-add-new',            // Menu slug
            array( $this, 'display_add_new_snippet_page' ) // Function
        );
    }

    /**
     * Callback function to display the main admin page (List Snippets).
     *
     * @since    1.0.0
     */
    public function display_plugin_admin_page() {
            // Moved here from constructor
            require_once VRC_PLUGIN_DIR . 'admin/includes/class-vrc-snippets-list-table.php';
        require_once VRC_PLUGIN_DIR . 'admin/partials/vrc-admin-display-list-snippets.php';
    }

    /**
     * Callback function to display the Add New Snippet page.
     *
     * @since    1.0.0
     */
    public function display_add_new_snippet_page() {
        // This will later include the form for adding/editing snippets
        require_once VRC_PLUGIN_DIR . 'admin/partials/vrc-admin-display-add-edit-page.php';
    }

    /**
     * Retrieve a single snippet by its ID.
     *
     * @since    1.0.0
     * @param    int   $id    The ID of the snippet to retrieve.
     * @return   array|null   The snippet data as an array, or null if not found.
     */
    public function get_snippet_by_id( $id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vrc_snippets';
        $id = absint( $id );

        if ( ! $id ) {
            return null;
        }

        $snippet = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $id ), ARRAY_A );

        return $snippet;
}

    /**
     * Handle the saving (insert/update) of a snippet.
     * Hooked to admin_post_vrc_save_snippet.
     *
     * @since    1.0.0
     */
    public function handle_save_snippet() {
        // Check nonce
        if ( ! isset( $_POST['vrc_save_snippet_nonce'] ) || ! wp_verify_nonce( sanitize_text_field($_POST['vrc_save_snippet_nonce']), 'vrc_save_snippet_action' ) ) {
            wp_die( esc_html__( 'Nonce verification failed. Snippet not saved.', 'viracode' ) );
        }

        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) { // Or a more specific capability later
            wp_die( esc_html__( 'You do not have sufficient permissions to manage snippets.', 'viracode' ) );
        }

        // Sanitize and validate data
        $snippet_id = isset( $_POST['snippet_id'] ) ? absint( $_POST['snippet_id'] ) : 0;

        $data = array(
            'name'        => isset( $_POST['vrc_name'] ) ? sanitize_text_field( stripslashes( $_POST['vrc_name'] ) ) : '',
            'description' => isset( $_POST['vrc_description'] ) ? sanitize_textarea_field( stripslashes( $_POST['vrc_description'] ) ) : '',
            'code'        => isset( $_POST['vrc_code'] ) ? wp_kses_post( stripslashes( $_POST['vrc_code'] ) ) : '', // Using wp_kses_post for general HTML/JS/CSS, PHP needs careful handling.
            'type'        => isset( $_POST['vrc_type'] ) ? sanitize_key( $_POST['vrc_type'] ) : 'php',
            'scope'       => isset( $_POST['vrc_scope'] ) ? sanitize_key( $_POST['vrc_scope'] ) : 'everywhere',
            'priority'    => isset( $_POST['vrc_priority'] ) ? absint( $_POST['vrc_priority'] ) : 10,
            'status'      => isset( $_POST['vrc_status'] ) ? sanitize_key( $_POST['vrc_status'] ) : 'inactive',
        );

        // For PHP code, we might want to skip wp_kses_post or use a different sanitization
        // For now, if it's PHP, we'll just store it as is after stripslashes.
        // A production plugin would need more robust security here for PHP.
        if ($data['type'] === 'php') {
            $data['code'] = isset( $_POST['vrc_code'] ) ? stripslashes( $_POST['vrc_code'] ) : '';
        }


        // Basic validation (name and code are required)
        if ( empty( $data['name'] ) ) {
            add_settings_error('vrc_admin_notices', 'name_required', __( 'Snippet Name is required.', 'viracode' ), 'error');
        }
        if ( empty( $data['code'] ) ) {
             add_settings_error('vrc_admin_notices', 'code_required', __( 'Code cannot be empty.', 'viracode' ), 'error');
        }

        // Check if there are any validation errors
        $errors = get_settings_errors('vrc_admin_notices');

        if ( !empty($errors) ) {
            // Store form data and errors in transient to repopulate form and show errors
            set_transient('vrc_snippet_form_data', $_POST, 60);
            set_transient('settings_errors', $errors, 30);

            $redirect_url = admin_url( 'admin.php?page=' . $this->plugin_name . '-add-new' );
            if ( $snippet_id ) {
                // Add nonce for edit mode if redirecting back to edit
                $edit_nonce = wp_create_nonce('vrc_edit_snippet');
                $redirect_url = admin_url( 'admin.php?page=' . $this->plugin_name . '-add-new&action=edit&snippet_id=' . $snippet_id . '&_wpnonce=' . $edit_nonce );
            }
            wp_safe_redirect( $redirect_url );
            exit;
        }


        global $wpdb;
        $table_name = $wpdb->prefix . 'vrc_snippets';

        if ( $snippet_id > 0 ) { // Update existing snippet
            $data['updated_at'] = current_time( 'mysql', 1 );
            $result = $wpdb->update( $table_name, $data, array( 'id' => $snippet_id ), $this->get_data_formats($data, true), array( '%d' ) );
            if ( false === $result ) {
                add_settings_error('vrc_admin_notices', 'update_failed', __( 'Failed to update snippet. Database error.', 'viracode' ), 'error');
            } else {
                add_settings_error('vrc_admin_notices', 'snippet_updated', __( 'Snippet updated successfully.', 'viracode' ), 'updated');
            }
        } else { // Insert new snippet
            $data['created_at'] = current_time( 'mysql', 1 );
            $data['updated_at'] = current_time( 'mysql', 1 ); // Set updated_at on creation as well
            $result = $wpdb->insert( $table_name, $data, $this->get_data_formats($data, false) );
            if ( false === $result ) {
                add_settings_error('vrc_admin_notices', 'insert_failed', __( 'Failed to save snippet. Database error.', 'viracode' ), 'error');
            } else {
                $snippet_id = $wpdb->insert_id; // Get new ID
                add_settings_error('vrc_admin_notices', 'snippet_saved', __( 'Snippet saved successfully.', 'viracode' ), 'updated');
            }
        }

        // Store notices to be displayed
        set_transient('settings_errors', get_settings_errors(), 30);


        // Redirect after saving
        $redirect_url = admin_url( 'admin.php?page=' . $this->plugin_name ); // Redirect to list table
        // If "Save & Edit" button was hypothetically clicked (not implemented, but good for future)
        // or if there was an error and we want to return to edit view.
        // For now, always redirect to list unless an error occurred during insert/update that was not a validation error.
        if ( isset( $_POST['save_and_edit'] ) && $snippet_id && !empty($wpdb->insert_id)) { // ensure save_and_edit was pressed and successful
             $edit_nonce = wp_create_nonce('vrc_edit_snippet');
            $redirect_url = admin_url( 'admin.php?page=' . $this->plugin_name . '-add-new&action=edit&snippet_id=' . $snippet_id . '&_wpnonce=' . $edit_nonce);
        } else if (get_settings_errors('vrc_admin_notices')) { // If there are general errors (not validation handled above)
            $error_occurred = false;
            foreach(get_settings_errors('vrc_admin_notices') as $error) {
                if ($error['type'] === 'error') {
                    $error_occurred = true;
                    break;
                }
            }
            if ($error_occurred && $snippet_id) { // If error and it was an update or failed insert
                 $edit_nonce = wp_create_nonce('vrc_edit_snippet');
                 $redirect_url = admin_url( 'admin.php?page=' . $this->plugin_name . '-add-new&action=edit&snippet_id=' . $snippet_id . '&_wpnonce=' . $edit_nonce);
            } else if ($error_occurred) { // Error on new snippet insert
                 $redirect_url = admin_url( 'admin.php?page=' . $this->plugin_name . '-add-new' );
            }
        }

        wp_safe_redirect( $redirect_url );
        exit;
    }

    /**
     * Helper to get data formats for wpdb insert/update.
     * @param array $data
     * @param bool $is_update
     * @return array
     */
    private function get_data_formats($data, $is_update = false) {
        $formats = array(
            'name'        => '%s',
            'description' => '%s',
            'code'        => '%s',
            'type'        => '%s',
            'scope'       => '%s',
            'priority'    => '%d',
            'status'      => '%s',
            'created_at'  => '%s',
            'updated_at'  => '%s',
        );
        if ($is_update) {
            unset($formats['created_at']); // Don't update created_at
        }
        return array_intersect_key($formats, $data); // Return only formats for keys present in $data
    }
}
?>
