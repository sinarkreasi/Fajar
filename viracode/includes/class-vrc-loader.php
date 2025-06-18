<?php
/**
 * Register all actions and filters for the plugin.
 *
 * Maintain a list of all hooks that are registered throughout
 * the plugin, and register them with the WordPress API. Call the
 * run function to execute the list of actions and filters.
 *
 * @package    Viracode
 * @subpackage Viracode/includes
 * @author     Your Name <email@example.com>
 */
class VRC_Loader {

    protected $actions;
    protected $filters;
    protected $shortcodes; // Add this

    public function __construct() {
        $this->actions = array();
        $this->filters = array();
        $this->shortcodes = array(); // Initialize this
    }

    public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
        $this->actions = $this->add_hook_data( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
    }

    public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
        $this->filters = $this->add_hook_data( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
    }

    // Renamed to avoid conflict with WordPress add_shortcode if used directly
    public function add_plugin_shortcode( $tag, $component_class_or_object, $callback_method ) {
        $this->shortcodes[] = array(
            'tag'             => $tag,
            'component'       => $component_class_or_object, // Can be class name for static calls or object
            'callback'        => $callback_method
        );
    }

    private function add_hook_data( $hooks_array, $hook, $component, $callback, $priority, $accepted_args ) {
        $hooks_array[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args
        );
        return $hooks_array;
    }

    public function run() {
        foreach ( $this->filters as $hook_data ) {
            add_filter( $hook_data['hook'], array( $hook_data['component'], $hook_data['callback'] ), $hook_data['priority'], $hook_data['accepted_args'] );
        }
        foreach ( $this->actions as $hook_data ) {
            add_action( $hook_data['hook'], array( $hook_data['component'], $hook_data['callback'] ), $hook_data['priority'], $hook_data['accepted_args'] );
        }
        foreach ( $this->shortcodes as $shortcode_data ) {
            // If component is a string (class name), assume static call. If object, use object.
            $callback = is_string($shortcode_data['component']) ?
                        array( $shortcode_data['component'], $shortcode_data['callback'] ) :
                        array( $shortcode_data['component'], $shortcode_data['callback'] );
            add_shortcode( $shortcode_data['tag'], $callback );
        }
    }
}
?>
