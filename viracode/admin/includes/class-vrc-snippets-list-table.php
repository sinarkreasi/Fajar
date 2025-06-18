<?php
/**
 * Creates the WP_List_Table for displaying snippets.
 *
 * @package    Viracode
 * @subpackage Viracode/admin/includes
 * @author     Your Name <email@example.com>
 */

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class VRC_Snippets_List_Table extends WP_List_Table {

    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct( array(
            'singular' => esc_html__( 'Snippet', 'viracode' ), // singular name of the listed records
            'plural'   => esc_html__( 'Snippets', 'viracode' ), // plural name of the listed records
            'ajax'     => false // does this table support ajax?
        ) );
    }

    /**
     * Retrieve snippets data from the database.
     *
     * @param int $per_page
     * @param int $page_number
     * @return array
     */
    public static function get_snippets( $per_page = 20, $page_number = 1 ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vrc_snippets';
        $sql = "SELECT * FROM {$table_name}";

        // Ordering
        $orderby = isset( $_REQUEST['orderby'] ) ? sanitize_sql_orderby( $_REQUEST['orderby'] ) : 'name';
        $order = isset( $_REQUEST['order'] ) ? strtoupper( sanitize_key( $_REQUEST['order'] ) ) : 'ASC';
        if ( $orderby && $order ) {
             // Basic validation for orderby columns - extend as needed
            $allowed_orderby = ['id', 'name', 'type', 'scope', 'status', 'updated_at'];
            if (in_array($orderby, $allowed_orderby, true)) {
                $sql .= ' ORDER BY ' . $orderby . ' ' . $order;
            } else {
                $sql .= ' ORDER BY name ASC'; // Default fallback
            }
        } else {
             $sql .= ' ORDER BY name ASC'; // Default
        }


        $sql .= " LIMIT $per_page";
        $sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;

        $result = $wpdb->get_results( $sql, 'ARRAY_A' );
        return $result;
    }

    /**
     * Delete a snippet record.
     *
     * @param int $id snippet ID
     */
    public static function delete_snippet( $id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vrc_snippets';
        $wpdb->delete( $table_name, array( 'id' => $id ), array( '%d' ) );
    }

    /**
     * Returns the count of records in the database.
     *
     * @return null|string
     */
    public static function record_count() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vrc_snippets';
        $sql = "SELECT COUNT(*) FROM {$table_name}";
        return $wpdb->get_var( $sql );
    }

    /**
     * Text displayed when no snippet data is available.
     */
    public function no_items() {
        esc_html_e( 'No snippets found.', 'viracode' );
    }

    /**
     * Render a column when no custom render function is found.
     *
     * @param array $item
     * @param string $column_name
     * @return mixed
     */
    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'description':
                return wp_trim_words( $item[ $column_name ], 10, '...' );
            case 'type':
            case 'scope':
            case 'status':
            case 'priority':
            case 'created_at':
            case 'updated_at':
                return esc_html( $item[ $column_name ] );
            default:
                return print_r( $item, true ); //Show the whole array for troubleshooting purposes
        }
    }

    /**
     * Render the checkbox column.
     */
    function column_cb( $item ) {
        return sprintf(
            '<input type="checkbox" name="bulk-delete[]" value="%s" />', $item['id']
        );
    }

    /**
     * Render the name column with actions.
     */
    function column_name( $item ) {
        $delete_nonce = wp_create_nonce( 'vrc_delete_snippet' );
        $edit_nonce = wp_create_nonce( 'vrc_edit_snippet' ); // Though edit will be a link

        $title = '<strong>' . esc_html( $item['name'] ) . '</strong>';

        $actions = array(
            'edit'   => sprintf( '<a href="?page=%s&action=%s&snippet_id=%s&_wpnonce=%s">%s</a>',
                                sanitize_text_field($_REQUEST['page']) . '-add-new', // Navigate to add-new page for editing
                                'edit',
                                absint( $item['id'] ),
                                $edit_nonce,
                                    esc_html__( 'Edit', 'viracode' ) ),
                'delete' => sprintf( '<a href="?page=%s&action=%s&snippet_id=%s&_wpnonce=%s" onclick="return confirm(\'%s\')">%s</a>',
                                esc_attr( $_REQUEST['page'] ),
                                'vrc_delete', // Custom action for deletion
                                absint( $item['id'] ),
                                $delete_nonce,
                                    esc_js( esc_html__( 'Are you sure you want to delete this snippet? This action cannot be undone.', 'viracode' ) ),
                                    esc_html__( 'Delete', 'viracode' ) ),
        );
         // Add toggle status action
        $toggle_action = $item['status'] === 'active' ? 'vrc_deactivate' : 'vrc_activate';
            $toggle_label = $item['status'] === 'active' ? esc_html__( 'Deactivate', 'viracode' ) : esc_html__( 'Activate', 'viracode' );
        $toggle_nonce = wp_create_nonce( $toggle_action . '_' . $item['id'] ); // Unique nonce per snippet and action

        $actions['toggle_status'] = sprintf(
            '<a href="?page=%s&action=%s&snippet_id=%s&_wpnonce=%s">%s</a>',
            esc_attr( $_REQUEST['page'] ),
            esc_attr( $toggle_action ),
            absint( $item['id'] ),
            $toggle_nonce,
            $toggle_label
        );


        return $title . $this->row_actions( $actions );
    }

    /**
     * Render the shortcode column.
     */
    function column_shortcode( $item ) {
        return sprintf( '[viracode id="%d"]', $item['id'] );
    }


    /**
     *  Associative array of columns
     *
     * @return array
     */
    function get_columns() {
        $columns = array(
            'cb'          => '<input type="checkbox" />', // Not translatable
            'name'        => esc_html__( 'Name', 'viracode' ),
            'shortcode'   => esc_html__( 'Shortcode', 'viracode' ),
            'type'        => esc_html__( 'Type', 'viracode' ),
            'scope'       => esc_html__( 'Scope', 'viracode' ),
            'status'      => esc_html__( 'Status', 'viracode' ),
            'priority'    => esc_html__( 'Priority', 'viracode' ),
            'updated_at'  => esc_html__( 'Last Modified', 'viracode' )
        );
        return $columns;
    }

    /**
     * Columns to make sortable.
     *
     * @return array
     */
    public function get_sortable_columns() {
        $sortable_columns = array(
            'name' => array( 'name', true ),
            'type' => array( 'type', false ),
            'scope' => array( 'scope', false ),
            'status' => array( 'status', false ),
            'priority' => array( 'priority', false ),
            'updated_at' => array( 'updated_at', false )
        );
        return $sortable_columns;
    }

    /**
     * Returns an associative array containing the bulk action
     *
     * @return array
     */
    public function get_bulk_actions() {
        $actions = array(
                'bulk-delete' => esc_html__( 'Delete', 'viracode' ),
                'bulk-activate' => esc_html__( 'Activate', 'viracode' ),
                'bulk-deactivate' => esc_html__( 'Deactivate', 'viracode' ),
        );
        return $actions;
    }

    /**
     * Handles data query and filter, sorting, and pagination.
     */
    public function prepare_items() {
        $this->_column_headers = $this->get_column_info();

        /** Process bulk action */
        $this->process_bulk_action();

        $per_page     = $this->get_items_per_page( 'snippets_per_page', 20 );
        $current_page = $this->get_pagenum();
        $total_items  = self::record_count();

        $this->set_pagination_args( array(
            'total_items' => $total_items, //WE have to calculate the total number of items
            'per_page'    => $per_page //WE have to determine how many items to show on a page
        ) );

        $this->items = self::get_snippets( $per_page, $current_page );
    }

    /**
     * Process bulk actions.
     */
    public function process_bulk_action() {
        // Detect when a bulk action is being triggered
        if ( 'vrc_delete' === $this->current_action() ) { // Single delete, ensuring it's specific
            $nonce = isset($_REQUEST['_wpnonce']) ? esc_attr( $_REQUEST['_wpnonce'] ) : '';
            $snippet_id = isset($_GET['snippet_id']) ? absint( $_GET['snippet_id'] ) : 0;

            if ( $snippet_id && wp_verify_nonce( $nonce, 'vrc_delete_snippet' ) ) {
                self::delete_snippet( $snippet_id );
                wp_redirect( remove_query_arg( array( 'action', 'snippet_id', '_wpnonce' ) ) );
                exit;
            } else {
                // Consider adding an admin notice for nonce failure instead of die()
                wp_die( esc_html__( 'Nonce verification failed or snippet ID missing for delete action!', 'viracode' ) );
            }
        }


        // If the delete bulk action is triggered
        if ( ( isset( $_POST['action'] ) && $_POST['action'] === 'bulk-delete' )
             || ( isset( $_POST['action2'] ) && $_POST['action2'] === 'bulk-delete' )
        ) {
            // Verify the nonce for bulk actions
            $nonce = isset($_POST['_wpnonce_vrc_bulk_action']) ? sanitize_text_field($_POST['_wpnonce_vrc_bulk_action']) : '';
            if ( ! wp_verify_nonce( $nonce, 'vrc_bulk_action_nonce' ) ) {
                 wp_die( esc_html__( 'Bulk action nonce verification failed!', 'viracode' ) );
            }

            $delete_ids = isset($_POST['bulk-delete']) ? array_map('absint', $_POST['bulk-delete']) : array();

            if ( !empty($delete_ids) ) {
                foreach ( $delete_ids as $id ) {
                    self::delete_snippet( $id );
                }
                wp_redirect( remove_query_arg( array( 'action', 'action2', '_wpnonce_vrc_bulk_action', 'bulk-delete' ) ) );
                exit;
            }
        }

        // Process activate/deactivate single item actions
        $action = $this->current_action();
        if ( in_array( $action, array( 'vrc_activate', 'vrc_deactivate' ) ) ) {
            $snippet_id = isset( $_GET['snippet_id'] ) ? absint( $_GET['snippet_id'] ) : 0;
            $nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( $_GET['_wpnonce'] ) : '';

            if ( $snippet_id && wp_verify_nonce( $nonce, $action . '_' . $snippet_id ) ) {
                $new_status = ( $action === 'vrc_activate' ) ? 'active' : 'inactive';
                self::update_snippet_status( $snippet_id, $new_status );
                wp_redirect( remove_query_arg( array( 'action', 'snippet_id', '_wpnonce' ) ) );
                exit;
            } else if ($snippet_id) { // Nonce failed or not provided for a valid snippet_id
                 wp_die( esc_html__( 'Nonce verification failed for status toggle action!', 'viracode' ) );
            }
        }

        // Process bulk activate/deactivate
        $bulk_action_triggered = '';
        if (isset($_POST['action']) && $_POST['action'] !== '-1') {
            $bulk_action_triggered = sanitize_text_field($_POST['action']);
        } elseif (isset($_POST['action2']) && $_POST['action2'] !== '-1') {
            $bulk_action_triggered = sanitize_text_field($_POST['action2']);
        }

        if ( in_array( $bulk_action_triggered, array( 'bulk-activate', 'bulk-deactivate' ) ) ) {
            // Verify the nonce for bulk actions
            $nonce = isset($_POST['_wpnonce_vrc_bulk_action']) ? sanitize_text_field($_POST['_wpnonce_vrc_bulk_action']) : '';
            if ( ! wp_verify_nonce( $nonce, 'vrc_bulk_action_nonce' ) ) {
                 wp_die( esc_html__( 'Bulk action nonce verification failed!', 'viracode' ) );
            }

            $snippet_ids = isset( $_POST['bulk-delete'] ) ? array_map( 'absint', $_POST['bulk-delete'] ) : array();

            if ( ! empty( $snippet_ids ) ) {
                $new_status = ( $bulk_action_triggered === 'bulk-activate' ) ? 'active' : 'inactive';
                foreach ( $snippet_ids as $snippet_id ) {
                    self::update_snippet_status( $snippet_id, $new_status );
                }
                wp_redirect( remove_query_arg( array( 'action', 'action2', '_wpnonce_vrc_bulk_action', 'bulk-delete' ) ) );
                exit;
            }
        }
    }

    /**
     * Update snippet status.
     */
    public static function update_snippet_status( $id, $status ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vrc_snippets';
        // Ensure status is either 'active' or 'inactive'
        $status = in_array( $status, array( 'active', 'inactive' ), true ) ? $status : 'inactive';
        $wpdb->update(
            $table_name,
            array( 'status' => $status ),
            array( 'id' => $id ),
            array( '%s' ),
            array( '%d' )
        );
    }
}
?>
