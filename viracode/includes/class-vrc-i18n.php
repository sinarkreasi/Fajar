<?php
/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Viracode
 * @subpackage Viracode/includes
 * @author     Your Name <email@example.com>
 */
class VRC_i18n {

    /**
     * The domain specified for this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $domain    The domain identifier for this plugin.
     */
    private $domain;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $domain    The domain identifier for this plugin.
     */
    public function __construct( $domain ) {
        $this->domain = $domain;
    }

    /**
     * Load the plugin text domain for translation.
     *
     * @since    1.0.0
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            $this->domain,
            false,
            dirname( plugin_basename( VRC_PLUGIN_FILE ) ) . '/languages/'
        );
    }
}
?>
