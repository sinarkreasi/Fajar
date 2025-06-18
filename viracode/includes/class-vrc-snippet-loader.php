<?php
/**
 * Handles fetching and executing/inserting code snippets.
 *
 * @package    Viracode
 * @subpackage Viracode/includes
 * @author     Your Name <email@example.com>
 */
class VRC_Snippet_Loader {

    protected static $_instance = null;

    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        // Constructor can be used for general setup if needed in the future
    }

    public function get_active_snippets( $type = null, $scope = null ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vrc_snippets';
        $params = array('active'); // Initial param for status

        // Base SQL query
        $sql = "SELECT * FROM {$table_name} WHERE status = %s";

        if ( $type !== null ) {
            $sql .= " AND type = %s";
            $params[] = $type;
        }

        if ( $scope !== null ) {
            if ( is_array( $scope ) && !empty($scope) ) {
                // Ensure scopes are unique to prevent redundant processing or overly complex queries
                $unique_scopes = array_unique($scope);
                if (empty($unique_scopes)) return array(); // Avoid empty IN clause after unique

                $scope_placeholders = implode( ', ', array_fill( 0, count( $unique_scopes ), '%s' ) );
                $sql .= " AND scope IN ( {$scope_placeholders} )";
                $params = array_merge($params, $unique_scopes);

            } else if (is_string($scope)) {
                $sql .= " AND scope = %s";
                $params[] = $scope;
            }
        }

        $sql .= " ORDER BY priority ASC, id ASC";

        // Prepare and execute the query
        // Check if $wpdb is available and is an object, which it should be in WordPress context
        if (is_object($wpdb) && method_exists($wpdb, 'prepare')) {
            $prepared_sql = $wpdb->prepare($sql, $params);
            if ($prepared_sql === null) { // $wpdb->prepare can return null on error
                // error_log("Viracode: \$wpdb->prepare returned null for SQL: $sql with params: " . print_r($params, true));
                return array();
            }
            $snippets = $wpdb->get_results( $prepared_sql );
        } else {
            // error_log("Viracode: \$wpdb object not available or prepare method missing.");
            return array();
        }

        return $snippets ? $snippets : array();
    }

    protected function get_snippet_name_by_id($snippet_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vrc_snippets';
        $name = null; // Initialize name
        // Check if $wpdb is available and is an object
        if (is_object($wpdb) && method_exists($wpdb, 'prepare')) {
            $name = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM $table_name WHERE id = %d", absint($snippet_id) ) );
        }
        return $name ? $name : esc_html__( 'Unnamed Snippet', 'viracode' ); // Return a default if name is empty or not found
    }


    public function maybe_execute_php_snippets() {
        $execute_scopes = array('everywhere');
         if ( defined( 'WP_ADMIN' ) && WP_ADMIN ) {
            $execute_scopes[] = 'backend';
        } else { // Frontend
            $execute_scopes[] = 'frontend';
        }

        $php_snippets = $this->get_active_snippets( 'php', array_unique($execute_scopes) );

        if ( ! empty( $php_snippets ) ) {
            foreach ( $php_snippets as $snippet ) {
                if ( empty(trim( $snippet->code ) ) ) { // Skip empty code
                    continue;
                }
                $this->execute_php_code( $snippet->code, $snippet->id );
            }
        }
    }

    protected function execute_php_code( $code, $snippet_id ) {
        try {
            eval( "?>" . $code . "<?php " );
        } catch ( Throwable $e ) {
            if ( current_user_can( 'manage_options' ) ) {
                $snippet_name_for_error = $this->get_snippet_name_by_id($snippet_id);
                add_action('admin_notices', function() use ($snippet_id, $snippet_name_for_error, $e) {
                    ?>
                    <div class="notice notice-error is-dismissible">
                        <p>
                            <strong><?php esc_html_e('Viracode Snippet Error:', 'viracode'); ?></strong>
                            <?php
                                printf(
                                    esc_html__('Snippet ID %1$d ("%2$s") generated an error: "%3$s" on/near line %4$d of the snippet code. The snippet has been automatically deactivated.', 'viracode'),
                                    absint($snippet_id),
                                    esc_html($snippet_name_for_error),
                                    esc_html($e->getMessage()),
                                    esc_html( (int)$e->getLine() > 0 ? (int)$e->getLine() -1 : 0)
                                );
                            ?>
                        </p>
                    </div>
                    <?php
                });
            }
            if (class_exists('VRC_Snippets_List_Table')) {
                VRC_Snippets_List_Table::update_snippet_status(absint($snippet_id), 'inactive');
            } else {
                error_log( sprintf(esc_html__("Viracode Critical: Could not deactivate snippet ID %d due to VRC_Snippets_List_Table class not being available at the time of error.", 'viracode'), $snippet_id) );
            }
        }
    }

    public function maybe_insert_header_assets() {
        // CSS for header
        $css_scopes = array('header', 'everywhere');
        if ( !is_admin() ) $css_scopes[] = 'frontend';

        $header_css_snippets = $this->get_active_snippets( 'css', array_unique($css_scopes) );
        if ( ! empty( $header_css_snippets ) ) {
            echo "\n<!-- Viracode CSS - Header -->\n";
            echo "<style type=\"text/css\" id=\"viracode-header-styles\">\n";
            foreach ( $header_css_snippets as $snippet ) {
                if ( empty(trim( $snippet->code ) ) ) continue;
                echo "/* " . sprintf(esc_html__( 'Snippet ID: %1$d - %2$s', 'viracode' ), esc_attr($snippet->id), esc_html($this->get_snippet_name_by_id($snippet->id))) . " */\n";
                echo $snippet->code . "\n";
            }
            echo "</style>\n";
            echo "<!-- /Viracode CSS - Header -->\n";
        }

        // JS for header
        $js_header_scopes = array('header');

        $header_js_snippets = $this->get_active_snippets( 'js', array_unique($js_header_scopes) );
        if ( ! empty( $header_js_snippets ) ) {
            echo "\n<!-- Viracode JS - Header -->\n";
            foreach ( $header_js_snippets as $snippet ) {
                if ( empty(trim( $snippet->code ) ) ) continue;
                echo "<script type=\"text/javascript\" id=\"viracode-js-snippet-head-" . esc_attr($snippet->id) . "\">\n";
                echo "// " . sprintf(esc_html__( 'Snippet ID: %1$d - %2$s', 'viracode' ), esc_attr($snippet->id), esc_html($this->get_snippet_name_by_id($snippet->id))) . "\n";
                echo $snippet->code . "\n";
                echo "</script>\n";
            }
            echo "<!-- /Viracode JS - Header -->\n";
        }
    }

    public function maybe_insert_header_html() {
        $html_header_scopes = array('header');

        $header_html_snippets = $this->get_active_snippets('html', array_unique($html_header_scopes));
        if (!empty($header_html_snippets)) {
            echo "\n<!-- Viracode HTML - Header -->\n";
            foreach ($header_html_snippets as $snippet) {
                if ( empty(trim( $snippet->code ) ) ) continue;
                echo "<!-- " . sprintf(esc_html__( 'Snippet ID: %1$d - %2$s', 'viracode' ), esc_attr($snippet->id), esc_html($this->get_snippet_name_by_id($snippet->id))) . " -->\n";
                echo $snippet->code . "\n";
            }
            echo "<!-- /Viracode HTML - Header -->\n";
        }
    }

    public function maybe_insert_footer_assets() {
        // JS for footer
        $js_footer_scopes = array('footer', 'everywhere');
        if ( !is_admin() ) $js_footer_scopes[] = 'frontend';

        $footer_js_snippets = $this->get_active_snippets( 'js', array_unique($js_footer_scopes) );

        if ( ! empty( $footer_js_snippets ) ) {
            echo "\n<!-- Viracode JS - Footer -->\n";
            foreach ( $footer_js_snippets as $snippet ) {
                if ($snippet->scope === 'header') continue;
                if ( empty(trim( $snippet->code ) ) ) continue;

                echo "<script type=\"text/javascript\" id=\"viracode-js-snippet-foot-" . esc_attr($snippet->id) . "\">\n";
                echo "// " . sprintf(esc_html__( 'Snippet ID: %1$d - %2$s', 'viracode' ), esc_attr($snippet->id), esc_html($this->get_snippet_name_by_id($snippet->id))) . "\n";
                echo $snippet->code . "\n";
                echo "</script>\n";
            }
            echo "<!-- /Viracode JS - Footer -->\n";
        }

        // HTML for footer
        $html_footer_scopes = array('footer', 'everywhere');
        if (!is_admin()) $html_footer_scopes[] = 'frontend';

        $footer_html_snippets = $this->get_active_snippets('html', array_unique($html_footer_scopes));
        if (!empty($footer_html_snippets)) {
            echo "\n<!-- Viracode HTML - Footer -->\n";
            foreach ($footer_html_snippets as $snippet) {
                if ($snippet->scope === 'header') continue;
                if ( empty(trim( $snippet->code ) ) ) continue;
                echo "<!-- " . sprintf(esc_html__( 'Snippet ID: %1$d - %2$s', 'viracode' ), esc_attr($snippet->id), esc_html($this->get_snippet_name_by_id($snippet->id))) . " -->\n";
                echo $snippet->code . "\n";
            }
            echo "<!-- /Viracode HTML - Footer -->\n";
        }
    }
}
