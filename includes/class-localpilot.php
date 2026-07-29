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
     * - Localpilot_User_Profile. Driver profile fields.
     * - Localpilot_Assignment_Service. Assign/reassign/unassign.
     * - Localpilot_Delivery_Query. Filtered delivery queries.
     * - Localpilot_Delivery_Transition_Service. State machine.
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
         * The init_* guard methods (called on `init` / `woocommerce_init`)
         * prevent runtime usage when WooCommerce is absent.
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

        // Integrations.
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/integrations/class-localpilot-user-profile.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/integrations/class-localpilot-orders-list.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/integrations/class-localpilot-order-editor.php';
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

        $plugin_admin = new Localpilot_Admin($this->get_plugin_name(), $this->get_plugin_prefix(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

        // Schema upgrade on admin requests (catches plugin updates).
        $this->loader->add_action('admin_init', $this, 'maybe_upgrade_schema');

        // Register/re-register driver role and capabilities on admin_init.
        $this->loader->add_action('admin_init', $this, 'register_driver_role');

        // User profile fields (driver-specific).
        $this->loader->add_action('init', $this, 'init_user_profile');

        // Register My Account endpoint.
        $this->loader->add_action('init', $this, 'init_my_account');

        // Register WooCommerce Settings tab (WC_Settings_Page parent needs WC loaded).
        $this->loader->add_action('woocommerce_init', $this, 'init_settings_page');

        // Email registry — subscribe to domain hooks.
        $this->loader->add_action('woocommerce_init', $this, 'init_email_registry');

        // Register orders list columns/filters.
        $this->loader->add_action('init', $this, 'init_orders_list');

        // Register order editor meta box.
        $this->loader->add_action('init', $this, 'init_order_editor');

        // Geocoding on delivery assignment.
        $this->loader->add_action('lclplt_delivery_assigned', $this, 'maybe_geocode_order', 10, 4);

        // Show admin notice after successful delivery action redirect.
        $this->loader->add_action('admin_notices', $this, 'show_delivery_notice');

        // Register the transient-based admin notice cleaner.
        $this->loader->add_action('admin_init', $this, 'clean_delivery_notice');
        
    }

    /**
     * Run schema upgrade if the stored version is behind.
     *
     * Fires on admin_init to catch plugin updates that skip activation hook.
     *
     * @since    1.0.0
     * @access   public
     */
    public function maybe_upgrade_schema()
    {
        if ( ! function_exists( 'lclplt_is_woocommerce_active' ) || ! lclplt_is_woocommerce_active() ) {
            return;
        }
        Localpilot_DB_Schema::maybe_upgrade();
    }

    /**
     * Register the driver role and capabilities idempotently.
     *
     * @since    1.0.0
     * @access   public
     */
    public function register_driver_role()
    {
        if ( ! function_exists( 'lclplt_is_woocommerce_active' ) || ! lclplt_is_woocommerce_active() ) {
            return;
        }
        Localpilot_Driver_Role::register();
    }

    /**
     * Initialize the user profile integration for drivers.
     *
     * @since    1.0.0
     * @access   public
     */
    public function init_user_profile()
    {
        if ( ! function_exists( 'lclplt_is_woocommerce_active' ) || ! lclplt_is_woocommerce_active() ) {
            return;
        }
        new Localpilot_User_Profile();
    }

    /**
     * Initialize the My Account endpoint.
     *
     * @since    1.0.0
     * @access   public
     */
    public function init_my_account()
    {
        if ( ! function_exists( 'lclplt_is_woocommerce_active' ) || ! lclplt_is_woocommerce_active() ) {
            return;
        }
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/integrations/class-localpilot-my-account.php';
        Localpilot_My_Account::register();
    }

    /**
     * Initialize the WooCommerce Settings tab.
     *
     * @since    1.0.0
     * @access   public
     */
    public function init_settings_page()
    {
        if ( ! function_exists( 'lclplt_is_woocommerce_active' ) || ! lclplt_is_woocommerce_active() ) {
            return;
        }
        // Loaded lazily via the filter callback to ensure WC_Settings_Page
        // parent class is available.
        add_filter( 'woocommerce_get_settings_pages', array( $this, 'register_settings_page' ) );
    }

    /**
     * Init the email registry.
     *
     * @since    1.0.0
     * @access   public
     */
    public function init_email_registry()
    {
        if ( ! function_exists( 'lclplt_is_woocommerce_active' ) || ! lclplt_is_woocommerce_active() ) {
            return;
        }
        Localpilot_Email_Registry::init();
    }

    /**
     * Register the LocalPilot settings page.
     *
     * @param array $pages Existing settings pages.
     * @return array
     */
    public function register_settings_page( $pages )
    {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/integrations/class-localpilot-settings.php';
        $pages[] = new Localpilot_Settings();
        return $pages;
    }

    /**
     * Initialize the orders list columns and filters.
     *
     * @since    1.0.0
     * @access   public
     */
    public function init_orders_list()
    {
        if ( ! function_exists( 'lclplt_is_woocommerce_active' ) || ! lclplt_is_woocommerce_active() ) {
            return;
        }
        new Localpilot_Orders_List();
    }

    /**
     * Initialize the order editor meta box and handler.
     *
     * @since    1.0.0
     * @access   public
     */
    public function init_order_editor()
    {
        if ( ! function_exists( 'lclplt_is_woocommerce_active' ) || ! lclplt_is_woocommerce_active() ) {
            return;
        }
        new Localpilot_Order_Editor();
    }

    /**
     * Show admin notice after a successful delivery action redirect.
     *
     * @since    1.0.0
     * @access   public
     */
    public function show_delivery_notice()
    {
        if ( ! function_exists( 'lclplt_is_woocommerce_active' ) || ! lclplt_is_woocommerce_active() ) {
            return;
        }
        $user_id  = get_current_user_id();
        $notice   = get_transient( 'lclplt_admin_notice_' . $user_id );
        if ( $notice ) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $notice ) . '</p></div>';
        }
    }

    /**
     * Trigger geocoding when a delivery is assigned.
     *
     * Hooked to lclplt_delivery_assigned. Runs silently — failures are
     * logged but never block the assignment flow.
     *
     * @since    1.0.0
     * @access   public
     * @param    int    $order_id      Order ID.
     * @param    int    $driver_id     Driver user ID.
     * @param    int    $assignment_id Assignment ID.
     * @param    int    $event_id      Event ID.
     */
    public function maybe_geocode_order( $order_id, $driver_id, $assignment_id, $event_id )
    {
        if ( ! function_exists( 'lclplt_is_woocommerce_active' ) || ! lclplt_is_woocommerce_active() ) {
            return;
        }

        $enabled = get_option( 'lclplt_enable_mapbox', 'no' );
        if ( 'yes' !== $enabled ) {
            return;
        }

        $auto = get_option( 'lclplt_auto_geocode', 'yes' );
        if ( 'yes' !== $auto ) {
            return;
        }

        Localpilot_Geocoding_Service::geocode_order( $order_id );
    }

    /**
     * Clean the delivery notice transient after display.
     *
     * @since    1.0.0
     * @access   public
     */
    public function clean_delivery_notice()
    {
        $user_id = get_current_user_id();
        delete_transient( 'lclplt_admin_notice_' . $user_id );
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

        $plugin_public = new Localpilot_Public($this->get_plugin_name(), $this->get_plugin_prefix(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_mapbox_scripts');

        
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
