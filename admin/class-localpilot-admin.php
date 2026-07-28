<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://racmanuel.dev/
 * @since      1.0.0
 *
 * @package    Localpilot
 * @subpackage Localpilot/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two hooks to
 * enqueue the admin-facing stylesheet and JavaScript.
 * As you add hooks and methods, update this description.
 *
 * @package    Localpilot
 * @subpackage Localpilot/admin
 * @author     racmanuel <developer@racmanuel.dev>
 */
class Localpilot_Admin
{

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The unique prefix of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_prefix    The string used to uniquely prefix technical functions of this plugin.
     */
    private $plugin_prefix;

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
     * @param      string $plugin_name       The name of this plugin.
     * @param      string $plugin_prefix    The unique prefix of this plugin.
     * @param      string $version    The version of this plugin.
     */
    public function __construct($plugin_name, $plugin_prefix, $version)
    {

        $this->plugin_name   = $plugin_name;
        $this->plugin_prefix = $plugin_prefix;
        $this->version       = $version;

    }

    /**
     * Check if assets should be loaded on the current admin page.
     *
     * @param string $hook_suffix The current admin page hook.
     * @return bool
     */
    private function should_load_assets($hook_suffix)
    {
        // Order list screens.
        if (function_exists('wc_get_page_screen_id')) {
            $order_screen = wc_get_page_screen_id('shop-order');
            if ($hook_suffix === $order_screen) {
                return true;
            }
        }
        // Legacy order list.
        if ('edit.php' === $hook_suffix && isset($_GET['post_type']) && 'shop_order' === $_GET['post_type']) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return true;
        }
        // WooCommerce settings.
        if ('woocommerce_page_wc-settings' === $hook_suffix && isset($_GET['tab']) && 'localpilot' === $_GET['tab']) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return true;
        }
        // User profile.
        if (in_array($hook_suffix, array('profile.php', 'user-edit.php'), true)) {
            return true;
        }
        return false;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     * @param string $hook_suffix The current admin page.
     */
    public function enqueue_styles($hook_suffix)
    {
        if (!$this->should_load_assets($hook_suffix)) {
            return;
        }

        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/localpilot-admin.css', [], $this->version, 'all');

    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     * @param string $hook_suffix The current admin page.
     */
    public function enqueue_scripts($hook_suffix)
    {
        if (!$this->should_load_assets($hook_suffix)) {
            return;
        }

        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/localpilot-admin.js', ['jquery'], $this->version, true);

        // Mapbox — only on order edit screen (HPOS and legacy).
        $is_order_edit = false;
        if (function_exists('wc_get_page_screen_id')) {
            $order_screen = wc_get_page_screen_id('shop-order');
            if ($hook_suffix === $order_screen && isset($_GET['action']) && 'edit' === $_GET['action']) {
                $is_order_edit = true;
            }
        }
        if ('post.php' === $hook_suffix && 'shop_order' === get_post_type()) {
            $is_order_edit = true;
        }
        if ($is_order_edit) {
            $this->enqueue_mapbox_admin();
        }
    }

    /**
     * Enqueue Mapbox GL JS and admin map script.
     *
     * Only loads when Mapbox is enabled and we're on the order edit page.
     *
     * @since    1.0.0
     */
    private function enqueue_mapbox_admin() {
        if ( 'yes' !== get_option( 'lclplt_enable_mapbox', 'no' ) ) {
            return;
        }

        $token = get_option( 'lclplt_mapbox_token', '' );
        if ( empty( $token ) ) {
            return;
        }

        wp_enqueue_style( 'mapbox-gl', 'https://api.mapbox.com/mapbox-gl-js/v3.9.4/mapbox-gl.css', array(), '3.9.4' );
        wp_enqueue_script( 'mapbox-gl', 'https://api.mapbox.com/mapbox-gl-js/v3.9.4/mapbox-gl.js', array(), '3.9.4', true );

        wp_enqueue_script(
            $this->plugin_name . '-mapbox',
            plugin_dir_url( __FILE__ ) . 'js/localpilot-mapbox.js',
            array( 'jquery', 'mapbox-gl' ),
            $this->version,
            true
        );
    }

    
}
