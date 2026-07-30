<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://racmanuel.dev/
 * @since      1.0.0
 *
 * @package    Localpilot
 * @subpackage Localpilot/includes
 */

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
 * @package    Localpilot
 * @subpackage Localpilot/includes
 * @author     racmanuel <developer@racmanuel.dev>
 */
class Localpilot
{

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Localpilot_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The unique prefix of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_prefix    The string used to uniquely prefix technical functions of this plugin.
     */
    protected $plugin_prefix;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct()
    {

        if (defined('LOCALPILOT_VERSION')) {

            $this->version = LOCALPILOT_VERSION;

        } else {

            $this->version = '1.1.6-dev';

        }

        $this->plugin_name   = 'localpilot';
        $this->plugin_prefix = 'lclplt_';

        $this->load_dependencies();
        
        $this->set_locale();
        
        $this->define_shared_hooks();

        
        $this->define_admin_hooks();
        
        
        $this->define_public_hooks();
        

    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Localpilot_Loader. Orchestrates the hooks of the plugin.
     * - Localpilot_I18n. Defines internationalization functionality.
     * - Localpilot_Admin. Defines all hooks for the admin area.
     * - Localpilot_Public. Defines all hooks for the public side of the site.
     * - Localpilot_Delivery_Status. Status constants and transition rules.
     * - Localpilot_Capabilities. Capability helpers.
     * - Localpilot_Order_Delivery_Meta. WC_Order meta adapter.
     * - Localpilot_DB_Schema. Database schema manager.
     * - Localpilot_Assignment_Repository. Assignment data access.
     * - Localpilot_Event_Repository. Event data access.
     * - Localpilot_Driver_Role. Role and capability registration.
     * - Localpilot_Driver_Repository. Driver user queries.
     * - Localpilot_Assignment_Service. Assign/reassign/unassign.
     * - Localpilot_Delivery_Query. Filtered delivery queries.
     * - Localpilot_Delivery_Transition_Service. State machine.
     *
     * Admin modules (loaded on is_admin()):
     * - Localpilot_User_Profile. Admin driver profile fields.
     * - Localpilot_Orders_List. Columns and filters in orders list.
     * - Localpilot_Order_Editor. Meta box and admin actions.
     * - Localpilot_Settings. WooCommerce settings tab.
     *
     * Public modules (loaded on front-end requests):
     * - Localpilot_My_Account. Mis-entregas endpoint and rendering.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies()
    {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-localpilot-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-localpilot-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-localpilot-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-localpilot-public.php';

        /*
         * ------------------------------------------------------------------
         * LocalPilot domain modules.
         *
         * These are loaded unconditionally — none of them extend WooCommerce
         * classes or trigger WC-side effects at file-load time. They are safe
         * to require before WooCommerce itself has initialised.
         *
         * Context-specific integrations are loaded by their respective hook
         * definitions below, so API and frontend requests avoid admin code.
         * ------------------------------------------------------------------
         */

        // Delivery domain.
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/deliveries/class-localpilot-delivery-status.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/deliveries/class-localpilot-order-delivery-meta.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/deliveries/class-localpilot-assignment-service.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/deliveries/class-localpilot-delivery-query.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/deliveries/class-localpilot-delivery-transition-service.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/deliveries/class-localpilot-driver-role.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/deliveries/class-localpilot-driver-repository.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/deliveries/class-localpilot-proof-service.php';

        // Database / repositories.
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/database/class-localpilot-db-schema.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/database/class-localpilot-assignment-repository.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/database/class-localpilot-event-repository.php';

        // Helpers.
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/helpers/class-localpilot-capabilities.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/helpers/class-localpilot-event-presenter.php';

        // Maps / geocoding.
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/maps/class-localpilot-mapbox-client.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/maps/class-localpilot-geocoding-service.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/location/class-localpilot-location-validation-service.php';

        // Cross-context integrations.
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/integrations/class-localpilot-email-registry.php';

        $this->loader = new Localpilot_Loader();

    }

    
    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Localpilot_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale()
    {

        $plugin_i18n = new Localpilot_I18n();

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');

    }
    

    
    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks()
    {

        if ( ! is_admin() ) {
            return;
        }

        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-localpilot-user-profile.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-localpilot-orders-list.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-localpilot-order-editor.php';

        $plugin_admin = new Localpilot_Admin($this->get_plugin_name(), $this->get_plugin_prefix(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-localpilot-schema-handler.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-localpilot-role-handler.php';

        // Schema upgrade on admin requests (catches plugin updates).
        $this->loader->add_action('admin_init', 'Localpilot_Schema_Handler', 'maybe_upgrade');

        // Register/re-register driver role and capabilities on admin_init.
        $this->loader->add_action('admin_init', 'Localpilot_Role_Handler', 'register');

        $user_profile = new Localpilot_User_Profile();
        $orders_list  = new Localpilot_Orders_List();
        $order_editor = new Localpilot_Order_Editor();

        // User profile fields (driver-specific).
        $this->loader->add_action('show_user_profile', $user_profile, 'render_fields');
        $this->loader->add_action('edit_user_profile', $user_profile, 'render_fields');
        $this->loader->add_action('personal_options_update', $user_profile, 'save_fields');
        $this->loader->add_action('edit_user_profile_update', $user_profile, 'save_fields');
        $this->loader->add_filter('manage_users_columns', $user_profile, 'add_columns');
        $this->loader->add_filter('manage_users_custom_column', $user_profile, 'render_column', 10, 3);

        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-localpilot-settings-handler.php';

        // Register WooCommerce Settings tab (WC_Settings_Page parent needs WC loaded).
        $this->loader->add_filter('woocommerce_get_settings_pages', 'Localpilot_Settings_Handler', 'register_settings_page');

        // Orders list columns and filters for HPOS and legacy order screens.
        $this->loader->add_filter('woocommerce_shop_order_list_table_columns', $orders_list, 'add_columns', 20);
        $this->loader->add_action('woocommerce_shop_order_list_table_custom_column', $orders_list, 'render_columns_hpos', 20, 2);
        $this->loader->add_filter('manage_edit-shop_order_columns', $orders_list, 'add_columns', 20);
        $this->loader->add_action('manage_shop_order_posts_custom_column', $orders_list, 'render_columns_legacy', 20, 2);
        $this->loader->add_action('woocommerce_order_list_table_restrict_manage_orders', $orders_list, 'render_filters_hpos', 20, 2);
        $this->loader->add_filter('woocommerce_shop_order_list_table_prepare_items_query_args', $orders_list, 'apply_filters_hpos');
        $this->loader->add_action('restrict_manage_posts', $orders_list, 'render_filters_legacy', 20);
        $this->loader->add_filter('request', $orders_list, 'apply_filters_legacy');

        // Order editor meta box and delivery action handler.
        $this->loader->add_action('add_meta_boxes', $order_editor, 'add_meta_box');
        $this->loader->add_action('woocommerce_process_shop_order_meta', $order_editor, 'handle_actions', 50, 2);
        $this->loader->add_filter($order_editor->get_meta_box_order_filter_hook(), $order_editor, 'force_normal_context', 100);

        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-localpilot-notice-handler.php';

        // Show admin notice after successful delivery action redirect.
        $this->loader->add_action('admin_notices', 'Localpilot_Notice_Handler', 'show_delivery_notice');

        // Register the transient-based admin notice cleaner.
        $this->loader->add_action('admin_init', 'Localpilot_Notice_Handler', 'clean_delivery_notice');
        
    }

    /**
     * Register callbacks that must run in every WordPress execution context.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_shared_hooks()
    {
        // Email registry and delivery event notifications.
        $this->loader->add_filter('woocommerce_email_classes', 'Localpilot_Email_Registry', 'register_emails');
        $this->loader->add_action('lclplt_delivery_assigned', 'Localpilot_Email_Registry', 'delivery_assigned', 20, 4);
        $this->loader->add_action('lclplt_delivery_unassigned', 'Localpilot_Email_Registry', 'delivery_unassigned', 20, 3);
        $this->loader->add_action('lclplt_delivery_reassigned', 'Localpilot_Email_Registry', 'delivery_reassigned', 20, 5);
        $this->loader->add_action('lclplt_delivery_started', 'Localpilot_Email_Registry', 'delivery_started', 20, 4);
        $this->loader->add_action('lclplt_delivery_completed', 'Localpilot_Email_Registry', 'delivery_completed', 20, 4);
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/maps/class-localpilot-geocoding-handler.php';

        // Geocoding on delivery assignment.
        $this->loader->add_action('lclplt_delivery_assigned', 'Localpilot_Geocoding_Handler', 'maybe_geocode_order', 10, 4);
    }


    

    
    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks()
    {

        if ( is_admin() ) {
            return;
        }

        $plugin_public = new Localpilot_Public($this->get_plugin_name(), $this->get_plugin_prefix(), $this->get_version());

        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-localpilot-my-account.php';

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_mapbox_scripts');

        // My Account endpoint, menu, and rendering.
        $this->loader->add_action('init', 'Localpilot_My_Account', 'register_endpoint');
        $this->loader->add_filter('woocommerce_account_menu_items', 'Localpilot_My_Account', 'add_menu_item', 40);
        $this->loader->add_action('woocommerce_account_' . Localpilot_My_Account::ENDPOINT . '_endpoint', 'Localpilot_My_Account', 'render');

        
        // Shortcode name must be the same as in shortcode_atts() third parameter.
        $this->loader->add_shortcode($this->get_plugin_prefix() . 'shortcode', $plugin_public, 'lclplt_shortcode_func');
        

    }
    

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run()
    {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name()
    {
        return $this->plugin_name;
    }

    /**
     * The unique prefix of the plugin used to uniquely prefix technical functions.
     *
     * @since     1.0.0
     * @return    string    The prefix of the plugin.
     */
    public function get_plugin_prefix()
    {
        return $this->plugin_prefix;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    Localpilot_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader()
    {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version()
    {
        return $this->version;
    }

}
