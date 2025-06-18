<?php
/**
 * Handles the shortcode functionality for Viracode.
 *
 * @package    Viracode
 * @subpackage Viracode/includes
 * @author     Your Name <email@example.com>
 */
class VRC_Shortcodes {

    /**
     * Handles the [viracode] shortcode.
     *
     * Attributes:
     *   'id' (int) - The ID of the snippet.
     *   'name' (string) - The unique name of the snippet.
     *
     * @since 1.0.0
     * @param array $atts Shortcode attributes.
     * @return string Output of the shortcode.
     */
    public static function viracode_shortcode_handler( $atts ) {
        $atts = shortcode_atts( array(
            'id'   => 0,
            'name' => '',
        ), $atts, 'viracode' );

        $snippet_id = absint( $atts['id'] );
        $snippet_name = sanitize_text_field( $atts['name'] );

        if ( ! $snippet_id && empty( $snippet_name ) ) {
            return '<!-- ' . esc_html__( 'Viracode: Snippet ID or Name not provided.', 'viracode' ) . ' -->';
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'vrc_snippets';
        $snippet = null;

        if ( $snippet_id > 0 ) {
            $snippet = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d AND status = 'active'", $snippet_id ) );
        } elseif ( ! empty( $snippet_name ) ) {
            $snippet = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE name = %s AND status = 'active'", $snippet_name ) );
        }

        if ( ! $snippet ) {
            if ($snippet_id) return '<!-- ' . sprintf(esc_html__( 'Viracode: Active snippet with ID %d not found.', 'viracode' ), $snippet_id) . ' -->';
            if ($snippet_name) return '<!-- ' . sprintf(esc_html__( 'Viracode: Active snippet with Name "%s" not found.', 'viracode' ), esc_html($snippet_name)) . ' -->';
            return '<!-- ' . esc_html__( 'Viracode: Snippet not found or is inactive.', 'viracode' ) . ' -->';
        }

        // Ensure snippet code is not empty before processing
        if ( empty(trim($snippet->code)) ) {
            return '<!-- ' . sprintf(esc_html__( 'Viracode: Snippet ID %d ("%s") has empty code.', 'viracode' ), $snippet->id, esc_html($snippet->name)) . ' -->';
        }

        switch ( $snippet->type ) {
            case 'php':
                ob_start();
                try {
                    eval( "?>" . $snippet->code . "<?php " );
                } catch (Throwable $e) {
                    error_log( sprintf(__( 'Viracode Shortcode Error (ID: %1$d, Name: %2$s): %3$s', 'viracode' ), $snippet->id, $snippet->name, $e->getMessage()) );
                    if (current_user_can('manage_options')) {
                        return '<!-- ' . sprintf(esc_html__( 'Viracode PHP Error (Snippet ID: %1$d, Name: %2$s): %3$s. Check site error logs.', 'viracode' ), $snippet->id, esc_html($snippet->name), esc_html($e->getMessage())) . ' -->';
                    }
                    return '<!-- ' . sprintf(esc_html__( 'Viracode: Error executing PHP snippet ID %d.', 'viracode' ), $snippet->id) . ' -->';
                }
                return ob_get_clean();

            case 'js':
                $js_output = "
<script type=\"text/javascript\" id=\"viracode-js-snippet-sc-" . esc_attr($snippet->id) . "\">
";
                $js_output .= "// " . sprintf(esc_html__( 'Viracode Shortcode Snippet ID: %1$d - %2$s', 'viracode' ), esc_attr($snippet->id), esc_html($snippet->name)) . "
";
                $js_output .= $snippet->code . "
";
                $js_output .= "</script>
";
                return $js_output;

            case 'css':
                $css_output = "
<style type=\"text/css\" id=\"viracode-css-snippet-sc-" . esc_attr($snippet->id) . "\">
";
                $css_output .= "/* " . sprintf(esc_html__( 'Viracode Shortcode Snippet ID: %1$d - %2$s', 'viracode' ), esc_attr($snippet->id), esc_html($snippet->name)) . " */
";
                $css_output .= $snippet->code . "
";
                $css_output .= "</style>
";
                return $css_output;

            case 'html':
                return "<!-- " . sprintf(esc_html__( 'Viracode HTML Snippet ID: %1$d - %2$s', 'viracode' ), esc_attr($snippet->id), esc_html($snippet->name)) . " -->\n" . $snippet->code;

            default:
                return '<!-- ' . sprintf(esc_html__( 'Viracode: Unknown snippet type "%1$s" for snippet ID %2$d.', 'viracode' ), esc_html($snippet->type), $snippet->id) . ' -->';
        }
    }
}
