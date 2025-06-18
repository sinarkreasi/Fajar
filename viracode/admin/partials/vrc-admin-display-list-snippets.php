<?php
/**
 * Provides the admin area view for the plugin.
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://example.com/viracode
 * @since      1.0.0
 *
 * @package    Viracode
 * @subpackage Viracode/admin/partials
 */
?>
<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?>
        <a href="<?php echo admin_url( 'admin.php?page=viracode-add-new' ); ?>" class="page-title-action">
            <?php esc_html_e( 'Add New Snippet', 'viracode' ); ?>
        </a>
    </h1>

    <?php
    // Create an instance of our package class...
    // Note: $this->plugin_name is not available here directly.
    // If VRC_Admin instance context is needed, it should be passed to this partial.
    // For now, constructing the link directly or using a passed variable.
    $list_table = new VRC_Snippets_List_Table();

    // Fetch, prepare, sort, and filter our data...
    $list_table->prepare_items();
    ?>
    <form method="post">
        <?php
        // For plugins, this should be the secret field:
        wp_nonce_field( 'vrc_bulk_action_nonce', '_wpnonce_vrc_bulk_action' );

        // ... LATER $list_table->search_box( 'search', 'search_id' );
        $list_table->display();
        ?>
    </form>
</div>
