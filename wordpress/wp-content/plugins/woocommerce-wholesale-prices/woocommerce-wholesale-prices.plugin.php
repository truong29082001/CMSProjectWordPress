<?php
if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

require_once 'includes/class-wwp-aelia-currency-switcher-integration-helper.php';
require_once 'includes/class-wwp-wholesale-roles.php';

require_once 'includes/class-wwp-bootstrap.php';
require_once 'includes/class-wwp-script-loader.php';
require_once 'includes/admin-custom-fields/products/class-wwp-admin-custom-fields-simple-product.php';
require_once 'includes/admin-custom-fields/products/class-wwp-admin-custom-fields-variable-product.php';
require_once 'includes/class-wwp-products-cpt.php';
require_once 'includes/class-wwp-wholesale-prices.php';
require_once 'includes/class-wwp-order.php';
require_once 'includes/class-wwp-duplicate-product.php';
require_once 'includes/class-wwp-marketing.php';
require_once 'includes/class-wwp-wholesale-roles-admin-page.php';
require_once 'includes/class-wwp-import-export.php';
require_once 'includes/class-wwp-product-visibility.php';
require_once 'includes/class-wwp-lead-capture.php';
require_once 'includes/class-wwp-usage.php';

// WC Admin Notes
require_once 'includes/wc-admin-notes/class-wwp-install-acfw.php';
require_once 'includes/wc-admin-notes/class-wwp-wws-bundle.php';
require_once 'includes/wc-admin-notes/class-wwp-store-owner-tips-fb.php';
require_once 'includes/wc-admin-notes/class-wwp-wws-youtube.php';
require_once 'includes/wc-admin-notes/class-wwp-wws-review.php';

// REST API
require_once 'includes/api/class-wwp-rest-api.php';

// Compatibility
require_once 'includes/compatibility/class-wwp-wpml-compatibility.php';

/**
 * This is the main plugin class. It's purpose generally is for "ALL PLUGIN RELATED STUFF ONLY".
 * This file or class may also serve as a controller to some degree but most if not all business logic is distributed
 * across include files.
 *
 * Class WooCommerceWholeSalePrices
 */
class WooCommerceWholeSalePrices {

    /*
    |------------------------------------------------------------------------------------------------------------------
    | Class Members
    |------------------------------------------------------------------------------------------------------------------
     */

    private static $_instance;

    public $wwp_wholesale_roles;
    private $_wwp_wholesale_prices;

    public $wwp_bootstrap;
    public $wwp_script_loader;
    public $wwp_admin_custom_fields_simple_product;
    public $wwp_admin_custom_fields_variable_product;
    public $wwp_products_cpt;
    public $wwp_wholesale_prices;
    public $wwp_order;
    public $wwp_duplicate_product;
    public $wwp_marketing;
    public $wwp_wholesale_roles_admin_page;
    public $wwp_import_export;
    public $wwp_rest_api;
    public $wwp_product_visibility;
    public $wwp_lead_capture;
    public $wwp_usage;

    const VERSION = '1.14.1';

    /*
    |--------------------------------------------------------------------------
    | Class Methods
    |--------------------------------------------------------------------------
     */

    /**
     * WooCommerceWholeSalePrices constructor.
     *
     * @since 1.0.0
     * @since 1.14.0
     * @access public
     */
    public function __construct() {

        $this->wwp_wholesale_roles = WWP_Wholesale_Roles::getInstance();

        $this->wwp_wholesale_prices = WWP_Wholesale_Prices::instance(array('WWP_Wholesale_Roles' => $this->wwp_wholesale_roles));
        $this->wwp_bootstrap = WWP_Bootstrap::instance(array('WWP_Wholesale_Roles' => $this->wwp_wholesale_roles, 'WWP_CURRENT_VERSION' => self::VERSION));
        $this->wwp_script_loader = WWP_Script_Loader::instance(array('WWP_Wholesale_Roles' => $this->wwp_wholesale_roles, 'WWP_Wholesale_Prices' => $this->wwp_wholesale_prices, 'WWP_CURRENT_VERSION' => self::VERSION));
        $this->wwp_admin_custom_fields_simple_product = WWP_Admin_Custom_Fields_Simple_Product::instance(array('WWP_Wholesale_Roles' => $this->wwp_wholesale_roles));
        $this->wwp_admin_custom_fields_variable_product = WWP_Admin_Custom_Fields_Variable_Product::instance(array('WWP_Wholesale_Roles' => $this->wwp_wholesale_roles));
        $this->wwp_products_cpt = WWP_Products_CPT::instance(array(
            'WWP_Wholesale_Roles' => $this->wwp_wholesale_roles,
            'WWP_Wholesale_Prices' => $this->wwp_wholesale_prices,
        ));
        $this->wwp_order = WWP_Order::instance(array('WWP_Wholesale_Roles' => $this->wwp_wholesale_roles));
        $this->wwp_duplicate_product = WWP_Duplicate_Product::instance(array('WWP_Wholesale_Roles' => $this->wwp_wholesale_roles));
        $this->wwp_marketing = WWP_Marketing::instance(array('WWP_Wholesale_Roles' => $this->wwp_wholesale_roles));
        $this->wwp_wholesale_roles_admin_page = WWP_Wholesale_Roles_Admin_Page::instance(array('WWP_Wholesale_Roles' => $this->wwp_wholesale_roles));
        $this->wwp_import_export = WWP_Import_export::instance(array('WWP_Wholesale_Roles' => $this->wwp_wholesale_roles));
        $this->wwp_wpml_compatibility = WWP_WPML_Compatibility::instance();
        $this->wwp_product_visibility = WWP_Product_Visibility::instance(array('WWP_Wholesale_Roles' => $this->wwp_wholesale_roles));
        $this->wwp_lead_capture = WWP_Lead_Capture::instance();
        $this->wwp_usage = WWP_Usage::instance();

        // REST API
        $this->wwp_rest_api = WWP_REST_API::instance(array());
    }

    /**
     * Singleton Pattern.
     * Ensure that only one instance of WooCommerceWholeSalePrices is loaded or can be loaded (Singleton Pattern).
     *
     * @since 1.0.0
     * @since 1.14.0
     * @access public
     *
     * @return WooCommerceWholeSalePrices
     */
    public static function instance() {

        if (!self::$_instance instanceof self) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }

    // @deprecated Will be remove on future versions
    public static function getInstance() {
        return self::instance();
    }

    /*
    |-------------------------------------------------------------------------------------------------------------------
    | Plugin Settings
    |-------------------------------------------------------------------------------------------------------------------
     */

    /**
     * Activate plugin settings.
     *
     * @since 1.0.0
     * @since 1.14.0 Refactor codebase.
     * @access public
     */
    public function activate_plugin_settings() {

        add_filter("woocommerce_get_settings_pages", array($this, 'initialize_plugin_settings'));
    }

    /**
     * Initialize plugin settings.
     *
     * @since 1.0.0
     * @since 1.14.0 Refactor codebase.
     * @access public
     *
     * @param array $settings Array of WC settings.
     * @return array Filtered array of wc settings.
     */
    public function initialize_plugin_settings($settings) {

        $settings[] = include WWP_INCLUDES_PATH . "class-wwp-settings.php";

        return $settings;
    }

    /*
    |-------------------------------------------------------------------------------------------------------------------
    | Execution WWPP
    |-------------------------------------------------------------------------------------------------------------------
     */

    /**
     * Execute WWP. Triggers the execution codes of the plugin models.
     *
     * @since 1.3.0
     * @access public
     */
    public function run() {

        $this->wwp_marketing->run();
        $this->wwp_wholesale_roles->run();
        $this->wwp_bootstrap->run();
        $this->wwp_script_loader->run();
        $this->wwp_admin_custom_fields_simple_product->run();
        $this->wwp_admin_custom_fields_variable_product->run();
        $this->wwp_wholesale_prices->run();
        $this->wwp_order->run();
        $this->wwp_duplicate_product->run();
        $this->wwp_products_cpt->run();
        $this->wwp_wholesale_roles_admin_page->run();
        $this->wwp_import_export->run();
        $this->wwp_wpml_compatibility->run();
        $this->wwp_product_visibility->run();
        $this->wwp_lead_capture->run();
        $this->wwp_usage->run();

        // REST API
        $this->wwp_rest_api->run();

        // Load default prices settings content if premium add on isn't present
        if (!WWP_Helper_Functions::is_wwpp_active()) {

            // Initialize Plugin Settings only if WWPP is not active
            $this->activate_plugin_settings();
        }
    }
}
