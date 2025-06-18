<?php
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Viracode
 * @subpackage Viracode/includes
 * @author     Your Name <email@example.com>
 */
class VRC_Core {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      VRC_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The snippet loader instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      VRC_Snippet_Loader    $snippet_loader    Handles snippet fetching and execution.
     */
    protected $snippet_loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * The text domain of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_text_domain    The text domain of the plugin.
     */
    protected $plugin_text_domain;


    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct() {
        if ( defined( 'VRC_VERSION' ) ) {
            $this->version = VRC_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'viracode';
        $this->plugin_text_domain = 'viracode'; // Define text domain

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_execution_hooks();
        $this->define_shortcode_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - VRC_Loader. Orchestrates the hooks of the plugin.
     * - VRC_i18n. Defines internationalization functionality.
     * - VRC_Admin. Defines all hooks for the admin area.
     * - VRC_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        require_once VRC_PLUGIN_DIR . 'includes/class-vrc-loader.php';
        require_once VRC_PLUGIN_DIR . 'includes/class-vrc-i18n.php'; // Require i18n class
        require_once VRC_PLUGIN_DIR . 'admin/class-vrc-admin.php';
        require_once VRC_PLUGIN_DIR . 'admin/includes/class-vrc-snippets-list-table.php';
        require_once VRC_PLUGIN_DIR . 'includes/class-vrc-snippet-loader.php';
        require_once VRC_PLUGIN_DIR . 'includes/class-vrc-shortcodes.php';

        $this->loader = new VRC_Loader();
        $this->snippet_loader = VRC_Snippet_Loader::instance();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the VRC_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {
        $plugin_i18n = new VRC_i18n( $this->get_plugin_text_domain() );
        $this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $plugin_admin = new VRC_Admin( $this->get_plugin_name(), $this->get_version() );
        $this->loader->add_action( 'admin_menu', $plugin_admin, 'add_admin_menu' );

        $this->loader->add_action( 'admin_post_vrc_save_snippet', $plugin_admin, 'handle_save_snippet' );

        // $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
        // $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
    }

    /**
     * Register hooks related to snippet execution.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_execution_hooks() {
        $this->loader->add_action( 'plugins_loaded', $this->snippet_loader, 'maybe_execute_php_snippets', 20 );
        $this->loader->add_action( 'wp_head', $this->snippet_loader, 'maybe_insert_header_assets', 99 );
        $this->loader->add_action( 'wp_head', $this->snippet_loader, 'maybe_insert_header_html', 99 );
        $this->loader->add_action( 'wp_footer', $this->snippet_loader, 'maybe_insert_footer_assets', 99 );
    }

    /**
     * Register shortcodes.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_shortcode_hooks() {
        $this->loader->add_plugin_shortcode( 'viracode', 'VRC_Shortcodes', 'viracode_shortcode_handler' );
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
         $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }

    /**
     * Retrieve the text domain of the plugin.
     *
     * @since     1.0.0
     * @return    string    The text domain of the plugin.
     */
    public function get_plugin_text_domain() {
        return $this->plugin_text_domain;
    }
}
?>
