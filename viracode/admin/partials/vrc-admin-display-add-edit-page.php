<?php
/**
 * Provides the admin area view for adding/editing snippets.
 *
 * @link       https://example.com/viracode
 * @since      1.0.0
 *
 * @package    Viracode
 * @subpackage Viracode/admin/partials
 */

// Determine if it's an edit or add new page
$is_edit_mode = isset( $_GET['action'] ) && $_GET['action'] === 'edit' && isset( $_GET['snippet_id'] );
$snippet_id = $is_edit_mode ? absint( $_GET['snippet_id'] ) : 0;
$snippet = null;

// This is not ideal. In a real plugin, VRC_Admin instance would be passed or available via a service locator.
// For the purpose of this task, we'll assume VRC_Admin class is loaded and we can instantiate it.
// This will be handled properly when VRC_Admin->display_add_new_snippet_page calls this partial.
// For now, to make get_snippet_by_id available, we do this:
if ( class_exists('VRC_Admin') ) {
    // $vrc_admin_instance is typically the $plugin_admin object from VRC_Core
    // We don't have direct access to it here without passing it.
    // A temporary VRC_Admin object for get_snippet_by_id
    // This part needs to be refactored later so $vrc_admin is properly injected or VRC_Admin::get_snippet_by_id is static
    $temp_vrc_admin_for_form = new VRC_Admin( 'viracode', '1.0.0');
}


if ( $is_edit_mode ) {
    if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ), 'vrc_edit_snippet' ) ) {
        wp_die( esc_html__( 'Nonce verification failed. Are you sure you want to do this?', 'viracode' ) );
    }
    if (isset($temp_vrc_admin_for_form)) {
        $snippet = $temp_vrc_admin_for_form->get_snippet_by_id( $snippet_id );
    }
    if ( ! $snippet ) {
        wp_die( esc_html__( 'Snippet not found.', 'viracode' ) );
    }
}

// Default values for a new snippet
$default_snippet = array(
    'id'          => 0, // Ensure id is present for new snippets too
    'name'        => '',
    'description' => '',
    'code'        => '',
    'type'        => 'php',
    'scope'       => 'everywhere',
    'priority'    => 10,
    'status'      => 'active'
);

$current_snippet = $is_edit_mode && $snippet ? (array) $snippet : $default_snippet;

// Retrieve and clear any stored admin notices
$admin_notices = get_transient('settings_errors');
delete_transient('settings_errors');

?>
<div class="wrap">
    <h1>
        <?php if ( $is_edit_mode ) : ?>
            <?php esc_html_e( 'Edit Snippet', 'viracode' ); ?>
            <a href="<?php echo admin_url( 'admin.php?page=viracode-add-new' ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'viracode' ); ?></a>
        <?php else : ?>
            <?php esc_html_e( 'Add New Snippet', 'viracode' ); ?>
        <?php endif; ?>
    </h1>

    <?php
    // Display admin notices if any
    if ( !empty($admin_notices) ) {
        settings_errors( 'vrc_admin_notices', false, true ); // true for sanitize, true for $is_transient
    }
    ?>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <input type="hidden" name="action" value="vrc_save_snippet">
        <?php wp_nonce_field( 'vrc_save_snippet_action', 'vrc_save_snippet_nonce' ); ?>
        <?php if ( $is_edit_mode ) : ?>
            <input type="hidden" name="snippet_id" value="<?php echo esc_attr( $current_snippet['id'] ); ?>">
        <?php endif; ?>

        <table class="form-table">
            <tbody>
                <!-- Snippet Name -->
                <tr>
                    <th scope="row">
                        <label for="vrc-name"><?php esc_html_e( 'Snippet Name', 'viracode' ); ?></label>
                    </th>
                    <td>
                        <input type="text" id="vrc-name" name="vrc_name" class="regular-text" value="<?php echo esc_attr( $current_snippet['name'] ); ?>" required>
                        <p class="description"><?php esc_html_e( 'A descriptive name for your snippet.', 'viracode' ); ?></p>
                    </td>
                </tr>

                <!-- Description -->
                <tr>
                    <th scope="row">
                        <label for="vrc-description"><?php esc_html_e( 'Description', 'viracode' ); ?></label>
                    </th>
                    <td>
                        <textarea id="vrc-description" name="vrc_description" rows="3" class="large-text"><?php echo esc_textarea( $current_snippet['description'] ); ?></textarea>
                        <p class="description"><?php esc_html_e( 'Optional. A brief description of what the snippet does.', 'viracode' ); ?></p>
                    </td>
                </tr>

                <!-- Code -->
                <tr>
                    <th scope="row">
                        <label for="vrc-code"><?php esc_html_e( 'Code', 'viracode' ); ?></label>
                    </th>
                    <td>
                        <textarea id="vrc-code" name="vrc_code" rows="15" class="large-text code" style="font-family: monospace; white-space: pre;"><?php echo esc_textarea( $current_snippet['code'] ); ?></textarea>
                        <p class="description"><?php esc_html_e( 'Enter your code snippet here.', 'viracode' ); ?></p>
                    </td>
                </tr>

                <!-- Type -->
                <tr>
                    <th scope="row">
                        <label for="vrc-type"><?php esc_html_e( 'Type', 'viracode' ); ?></label>
                    </th>
                    <td>
                        <select id="vrc-type" name="vrc_type">
                            <option value="php" <?php selected( $current_snippet['type'], 'php' ); ?>><?php esc_html_e( 'PHP', 'viracode' ); ?></option>
                            <option value="js" <?php selected( $current_snippet['type'], 'js' ); ?>><?php esc_html_e( 'JavaScript (JS)', 'viracode' ); ?></option>
                            <option value="css" <?php selected( $current_snippet['type'], 'css' ); ?>><?php esc_html_e( 'CSS', 'viracode' ); ?></option>
                            <option value="html" <?php selected( $current_snippet['type'], 'html' ); ?>><?php esc_html_e( 'HTML', 'viracode' ); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e( 'The type of code snippet.', 'viracode' ); ?></p>
                    </td>
                </tr>

                <!-- Scope -->
                <tr>
                    <th scope="row">
                        <label for="vrc-scope"><?php esc_html_e( 'Scope', 'viracode' ); ?></label>
                    </th>
                    <td>
                        <select id="vrc-scope" name="vrc_scope">
                            <option value="everywhere" <?php selected( $current_snippet['scope'], 'everywhere' ); ?>><?php esc_html_e( 'Run Everywhere', 'viracode' ); ?></option>
                            <option value="frontend" <?php selected( $current_snippet['scope'], 'frontend' ); ?>><?php esc_html_e( 'Frontend Only', 'viracode' ); ?></option>
                            <option value="backend" <?php selected( $current_snippet['scope'], 'backend' ); ?>><?php esc_html_e( 'Backend (Admin) Only', 'viracode' ); ?></option>
                            <option value="header" <?php selected( $current_snippet['scope'], 'header' ); ?>><?php esc_html_e( 'Insert into <head>', 'viracode' ); ?></option>
                            <option value="footer" <?php selected( $current_snippet['scope'], 'footer' ); ?>><?php esc_html_e( 'Insert before </body> (Footer)', 'viracode' ); ?></option>
                            <option value="shortcode" <?php selected( $current_snippet['scope'], 'shortcode' ); ?>><?php esc_html_e( 'Manual via Shortcode', 'viracode' ); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e( 'Where the snippet should be executed or inserted.', 'viracode' ); ?></p>
                    </td>
                </tr>

                <!-- Priority -->
                <tr>
                    <th scope="row">
                        <label for="vrc-priority"><?php esc_html_e( 'Priority', 'viracode' ); ?></label>
                    </th>
                    <td>
                        <input type="number" id="vrc-priority" name="vrc_priority" class="small-text" value="<?php echo esc_attr( $current_snippet['priority'] ); ?>" min="1" step="1">
                        <p class="description"><?php esc_html_e( 'Execution order. Lower numbers run earlier. Default: 10.', 'viracode' ); ?></p>
                    </td>
                </tr>

                <!-- Status -->
                <tr>
                    <th scope="row">
                        <label for="vrc-status"><?php esc_html_e( 'Status', 'viracode' ); ?></label>
                    </th>
                    <td>
                        <select id="vrc-status" name="vrc_status">
                            <option value="active" <?php selected( $current_snippet['status'], 'active' ); ?>><?php esc_html_e( 'Active', 'viracode' ); ?></option>
                            <option value="inactive" <?php selected( $current_snippet['status'], 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'viracode' ); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e( 'Activate or deactivate the snippet.', 'viracode' ); ?></p>
                    </td>
                </tr>
            </tbody>
        </table>

        <?php submit_button( $is_edit_mode ? __( 'Update Snippet', 'viracode' ) : __( 'Save Snippet', 'viracode' ) ); ?>
    </form>
</div>
