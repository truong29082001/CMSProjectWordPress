<?php if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

if (!class_exists('WWP_Script_Loader')) {

    /**
     * Model that houses the logic of loading in scripts to various pages of the plugin.
     *
     * @since 1.4.0
     */
    class WWP_Script_Loader
    {

        /*
        |--------------------------------------------------------------------------
        | Class Properties
        |--------------------------------------------------------------------------
         */

        /**
         * Property that holds the single main instance of WWP_Script_Loader.
         *
         * @since 1.4.0
         * @access private
         * @var WWP_Script_Loader
         */
        private static $_instance;

        /**
         * Model that houses the logic of retrieving information relating to wholesale role/s of a user.
         *
         * @since 1.4.0
         * @access private
         * @var WWP_Wholesale_Roles
         */
        private $_wwp_wholesale_roles;

        /**
         * Model that houses logic of wholesale prices.
         *
         * @since 1.4.0
         * @access private
         * @var WWP_Wholesale_Prices
         */
        private $_wwp_wholesale_prices;

        /**
         * Current WWP version.
         *
         * @since 1.3.1
         * @access private
         * @var int
         */
        private $_wwp_current_version;

        /*
        |--------------------------------------------------------------------------
        | Class Methods
        |--------------------------------------------------------------------------
         */

        /**
         * WWP_Script_Loader constructor.
         *
         * @since 1.4.0
         * @access public
         *
         * @param array $dependencies Array of instance objects of all dependencies of WWP_Script_Loader model.
         */
        public function __construct($dependencies)
        {

            $this->_wwp_wholesale_roles = $dependencies['WWP_Wholesale_Roles'];
            $this->_wwp_wholesale_prices = $dependencies['WWP_Wholesale_Prices'];
            $this->_wwp_current_version = $dependencies['WWP_CURRENT_VERSION'];
        }

        /**
         * Ensure that only one instance of WWP_Script_Loader is loaded or can be loaded (Singleton Pattern).
         *
         * @since 1.4.0
         * @access public
         *
         * @param array $dependencies Array of instance objects of all dependencies of WWP_Script_Loader model.
         * @return WWP_Script_Loader
         */
        public static function instance($dependencies)
        {

            if (!self::$_instance instanceof self) {
                self::$_instance = new self($dependencies);
            }

            return self::$_instance;
        }

        /**
         * Load admin or backend related styles and scripts.
         * Only load em on the right time and on the right place.
         *
         * @since 1.0.0
         * @since 1.4.0 Refactor codebase and move to its own model.
         * @access public
         *
         * @param string $handle Hook suffix for the current admin page.
         */
        public function load_back_end_styles_and_scripts($handle)
        {

            // Prepare everything needed
            $screen = get_current_screen();

            $post_type = get_post_type();
            if (!$post_type && isset($_GET['post_type'])) {
                $post_type = $_GET['post_type'];
            }

            $review_response = get_option(WWP_REVIEW_REQUEST_RESPONSE);


            /***********************************************************************************************************
             * General Backend Overrides (Careful, this is loaded on all backend pages!)
             **********************************************************************************************************/
            wp_enqueue_style('wwp_wcoverrides_css', WWP_CSS_URL . 'wwp-back-end-wcoverrides.css', array(), $this->_wwp_current_version, 'all');

            /***********************************************************************************************************
             * Show Review Request Popup
             **********************************************************************************************************/
            if (is_admin() && current_user_can('manage_options') && get_option(WWP_SHOW_REQUEST_REVIEW) === 'yes' && ($review_response === 'review-later' || empty($review_response))) {

                if (WWP_Helper_Functions::is_wwpp_active()) {

                    $msg = sprintf(__('<p>We see you have been using Wholesale Suite for a couple of weeks now – thank you once again for your purchase and we hope you are enjoying it so far!</p>
                                         <p>We’d really appreciate it if you could take a few minutes to write a 5-star review of our Wholesale Prices plugin on WordPress.org!</p>
                                         <p>Your comment will go a long way to helping us grow and giving new users the confidence to give us a try.</p>
                                         <p>Thanks in advance, we are looking forward to reading it!</p>
                                         <p>PS. If you ever need support, please just <a href="%1$s" target="_blank">get in touch here.</a></p>', "woocommerce-wholesale-prices"), 'https://goo.gl/pDDRTp');
                } else {

                    $msg = __("<p>Thanks for using our free WooCommerce Wholesale Prices plugin – we hope you are enjoying it so far.</p>
                                <p>We’d really appreciate it if you could take a few minutes to write a 5-star review of our Wholesale Prices plugin on WordPress.org!</p>
                                <p>Your comment will go a long way to helping us grow and giving new users the confidence to give us a try.</p>
                                <p>Thanks in advance, we are looking forward to reading it!</p>", "woocommerce-wholesale-prices");
                }

                wp_enqueue_style('wwp-wwp-review', WWP_CSS_URL . '/wwp-review.css', array(), $this->_wwp_current_version, 'all');
                wp_enqueue_style('wwp-vex-css', WWP_JS_URL . 'lib/vexjs/vex.css', array(), $this->_wwp_current_version, 'all');
                wp_enqueue_style('wwp-vex-theme-plain-css', WWP_JS_URL . 'lib/vexjs/vex-theme-plain.css', array(), $this->_wwp_current_version, 'all');
                wp_enqueue_script('wwp-vex-js', WWP_JS_URL . 'lib/vexjs/vex.combined.min.js', array(), $this->_wwp_current_version, true);
                wp_enqueue_script('wwp-review-request', WWP_JS_URL . 'backend/wwp-review-request.js', array(), $this->_wwp_current_version, true);
                wp_localize_script('wwp-review-request', 'review_request_args', array(
                    'js_url' => WWP_IMAGES_URL,
                    'msg' => $msg,
                    'review_link' => 'https://goo.gl/FVRQcH',
                ));
            }

            /***********************************************************************************************************
             * Product Listing & Edit Product Pages
             **********************************************************************************************************/
            if (in_array($screen->id, array('edit-product'))) {
                // Product listing

                wp_enqueue_style('wwp_cpt_product_listing_admin_main_css', WWP_CSS_URL . 'backend/cpt/product/wwp-cpt-product-listing-admin-main.css', array(), $this->_wwp_current_version, 'all');
                wp_enqueue_script('wwp_cpt_product_listing_admin_main_js', WWP_JS_URL . 'backend/cpt/product/wwp-cpt-product-listing-admin-main.js', array('jquery', 'jquery-ui-core', 'jquery-ui-accordion'), $this->_wwp_current_version); // Must not be loaded on footer, else it won't work

            } else if (($handle == 'post-new.php' || $handle == 'post.php') && $post_type == 'product') {
                // Single product admin page ( new and edit product )

                // VEX
                wp_enqueue_style('wwp-vex-css', WWP_JS_URL . 'lib/vexjs/vex.css', array(), $this->_wwp_current_version, 'all');
                wp_enqueue_style('wwp-vex-theme-plain-css', WWP_JS_URL . 'lib/vexjs/vex-theme-plain.css', array(), $this->_wwp_current_version, 'all');
                wp_enqueue_style('wwp-vex-styling-css', WWP_CSS_URL . 'wwp-vex-styling.css', array(), $this->_wwp_current_version, 'all');
                wp_enqueue_script('wwp-vex-js', WWP_JS_URL . 'lib/vexjs/vex.combined.min.js', array(), $this->_wwp_current_version, true);

                // Chosen
                wp_enqueue_style('wwp_chosen_css', WWP_JS_URL . 'lib/chosen/chosen.min.css', array(), $this->_wwp_current_version, 'all');
                wp_enqueue_script('wwp_chosen_js', WWP_JS_URL . 'lib/chosen/chosen.jquery.min.js', array('jquery'), $this->_wwp_current_version, true);

                // Single Product specific CSS/JS
                wp_enqueue_style('wwp_cpt_product_single_admin_main_css', WWP_CSS_URL . 'backend/cpt/product/wwp-cpt-product-single-admin-main.css', array(), $this->_wwp_current_version, 'all');
                wp_enqueue_script('wwp_cpt_product_single_admin_main_js', WWP_JS_URL . 'backend/cpt/product/wwp-cpt-product-single-admin-main.js', array('jquery', 'jquery-ui-core', 'jquery-ui-accordion'), $this->_wwp_current_version, true);
                wp_enqueue_script('wwp_single_variable_product_admin_custom_bulk_actions_js', WWP_JS_URL . 'backend/cpt/product/wwp-single-variable-product-admin-custom-bulk-actions.js', array('jquery'), $this->_wwp_current_version, true);

                wp_localize_script('wwp_single_variable_product_admin_custom_bulk_actions_js', 'wwp_custom_bulk_actions_params', array(
                    'wholesale_roles' => $this->_wwp_wholesale_roles->getAllRegisteredWholesaleRoles(),
                    'i18n_prompt_message' => __('Enter a value (leave blank to remove pricing)', 'woocommerce-wholesale-prices'),
                ));

                // Upsell specific
                if (!WWP_Helper_Functions::is_wwpp_active()) {
                    wp_enqueue_style('wwp-backend-product-page-upsell', WWP_CSS_URL . 'wwp-backend-product-page-upsell.css', array(), $this->_wwp_current_version, 'all');
                    wp_enqueue_script('wwp-backend-product-page-upsell', WWP_JS_URL . 'backend/wwp-backend-product-page-upsell.js', array(), $this->_wwp_current_version, true);

                    wp_localize_script('wwp-backend-product-page-upsell', 'backend_product_page_upsell_args', array(
                        'images_url' => WWP_IMAGES_URL,
                        'wholesale_prices' => array(
                            'title' => __("<h4>Upgrade For Additional Wholesale Price Levels</h4>", "woocommerce-wholesale-prices"),
                            'msg' => __("<p>WooCommerce Wholesale Prices Premium lets you add additional levels of pricing 
                        by adding more wholesale user roles. Click below for all the details.</p>", "woocommerce-wholesale-prices"),
                            'link' => 'https://wholesalesuiteplugin.com/woocommerce-wholesale-prices-premium/?utm_source=freeplugin&utm_medium=upsell&utm_campaign=wwpproducteditadditionalprices',
                        ),
                        'product_visibility' => array(
                            'title' => __("<h4>Upgrade For Product Visibility Features</h4>", "woocommerce-wholesale-prices"),
                            'msg' => __("<p>WooCommerce Wholesale Prices Premium lets you change the visibility of
                        your products by selecting which wholesale roles should be able to see it. Click below for all 
                        the details.</p>", "woocommerce-wholesale-prices"),
                            'link' => 'https://wholesalesuiteplugin.com/woocommerce-wholesale-prices-premium/?utm_source=freeplugin&utm_medium=upsell&utm_campaign=wwpproducteditproductvisibility',
                        ),
                        'button_text' => __("See Features & Pricing", "woocommerce-wholesale-prices"),
                        'bonus_text' => __("<p><span style=\"font-weight: bold;\">Bonus:</span> Wholesale Prices lite users get 
                    <span class=\"green-text\">50% off regular price</span>, automatically applied at checkout.")
                    ));
                }
            }


            /***********************************************************************************************************
             * Backend Common CSS
             **********************************************************************************************************/
            if (get_option('wwp_admin_notice_getting_started_show') === 'yes' || get_option(WWP_SHOW_INSTALL_ACFWF_NOTICE) === 'yes' || (isset($_GET['tab']) && $_GET['tab'] == 'wwp_settings')) {
                wp_enqueue_style('wwp_backend_main_css', WWP_CSS_URL . 'wwp-back-end-main.css', array(), $this->_wwp_current_version, 'all');
            }

            /***********************************************************************************************************
             * Notices
             **********************************************************************************************************/
            // Getting Started notice. Notice shows up on every page in the backend unless the message is dismissed
            if (get_option('wwp_admin_notice_getting_started_show') === 'yes') {
                wp_enqueue_script('wwp_getting_started_js', WWP_JS_URL . 'backend/wwp-getting-started.js', array('jquery'), $this->_wwp_current_version, true);
            }

            // Install ACFWF notice. Notice shows up on every page in the backend unless the message is dismissed
            if (get_option(WWP_SHOW_INSTALL_ACFWF_NOTICE) === 'yes') {
                wp_enqueue_script('wwp_acfwf_install_notice_js', WWP_JS_URL . 'backend/wwp-acfwf-install-notice.js', array('jquery'), $this->_wwp_current_version, true);
            }


            /***********************************************************************************************************
             * Wholesale Roles Page
             **********************************************************************************************************/
            // Load script if premium add on isn't present
            if (
                $screen->id === 'woocommerce_page_wwpp-wholesale-roles-page' &&
                !WWP_Helper_Functions::is_wwpp_active()
            ) {

                // Vex
                wp_enqueue_style('wwp-vex-css', WWP_JS_URL . 'lib/vexjs/vex.css', array(), $this->_wwp_current_version, 'all');
                wp_enqueue_style('wwp-vex-theme-plain-css', WWP_JS_URL . 'lib/vexjs/vex-theme-plain.css', array(), $this->_wwp_current_version, 'all');
                wp_enqueue_style('wwp-vex-styling-css', WWP_CSS_URL . 'wwp-vex-styling.css', array(), $this->_wwp_current_version, 'all');
                wp_enqueue_script('wwp-vex-js', WWP_JS_URL . 'lib/vexjs/vex.combined.min.js', array(), $this->_wwp_current_version, true);

                // Toastr
                wp_enqueue_style('wwp_toastr_css', WWP_JS_URL . 'lib/toastr/toastr.min.css', array(), $this->_wwp_current_version, 'all');
                wp_enqueue_script('wwp_toastr_js', WWP_JS_URL . 'lib/toastr/toastr.min.js', array('jquery'), $this->_wwp_current_version, true);

                // Roles page specific css styling
                wp_enqueue_style('wwp_roles_page_css', WWP_CSS_URL . 'wwp-backend-wholesale-roles.css', array(), $this->_wwp_current_version, 'all');

                wp_enqueue_script('wwp_backEndAjaxServices_js', WWP_JS_URL . 'app/modules/BackEndAjaxServices.js', array('jquery'), $this->_wwp_current_version, true);
                wp_enqueue_script('wwp_wholesaleRolesFormActions_js', WWP_JS_URL . 'app/modules/WholesaleRolesFormActions.js', array('jquery'), $this->_wwp_current_version, true);

                wp_enqueue_script('wwp_wholesaleRolesListingActions_js', WWP_JS_URL . 'app/modules/WholesaleRolesListingActions.js', array('jquery'), $this->_wwp_current_version, true);
                wp_localize_script('wwp_wholesaleRolesListingActions_js', 'wwp_wholesaleRolesListingActions_params', array(
                    'i18n_yes' => __('Yes', 'woocommerce-wholesale-prices'),
                    'i18n_no' => __('No', 'woocommerce-wholesale-prices'),
                ));

                wp_enqueue_script('wwp_wholesale_roles_main_js', WWP_JS_URL . 'app/wholesale-roles-main.js', array('jquery', 'jquery-tiptip'), $this->_wwp_current_version, true);
                wp_localize_script('wwp_wholesale_roles_main_js', 'wwp_wholesale_roles_main_params', array(
                    'i18n_enter_role_name' => __('Please Enter Role Name', 'woocommerce-wholesale-prices'),
                    'i18n_error_wholesale_form' => __('Error in Wholesale Form', 'woocommerce-wholesale-prices'),
                    'i18n_enter_role_key' => __('Please Enter Role Key', 'woocommerce-wholesale-prices'),
                    'i18n_role_successfully_edited' => __('Wholesale Role Successfully Edited', 'woocommerce-wholesale-prices'),
                    'i18n_successfully_edited_role' => __('Successfully Edited Role', 'woocommerce-wholesale-prices'),
                    'i18n_failed_edit_role' => __('Failed to Edit Wholesale Role', 'woocommerce-wholesale-prices'),
                    'i18n_upsell_message' => $this->role_page_upsell_message(),
                ));
            }

            /***********************************************************************************************************
             * Wholesale Roles Page
             **********************************************************************************************************/
            // Load script if premium add on isn't present
            if (
                $screen->id === 'woocommerce_page_wwp-lead-capture-page' &&
                !WWP_Helper_Functions::is_wwlc_active()
            ) {
                // Lead Capture page specific css styling
                wp_enqueue_style('wwp_lead_capture_page_css', WWP_CSS_URL . 'wwp-lead-capture.css', array(), $this->_wwp_current_version, 'all');
            }

            /***********************************************************************************************************
             * WWP Settings
             **********************************************************************************************************/
            if (
                !WWP_Helper_Functions::is_wwpp_active() &&
                isset($_GET['tab']) && $_GET['tab'] == 'wwp_settings'
            ) {

                // Queue up stuff that is used on all tabs
                wp_enqueue_style('wwp-vex-css', WWP_JS_URL . 'lib/vexjs/vex.css', array(), $this->_wwp_current_version, 'all');
                wp_enqueue_style('wwp-vex-theme-plain-css', WWP_JS_URL . 'lib/vexjs/vex-theme-plain.css', array(), $this->_wwp_current_version, 'all');
                wp_enqueue_style('wwp-vex-styling-css', WWP_CSS_URL . 'wwp-vex-styling.css', array(), $this->_wwp_current_version, 'all');
                wp_enqueue_script('wwp-vex-js', WWP_JS_URL . 'lib/vexjs/vex.combined.min.js', array(), $this->_wwp_current_version, true);


                // Handle each section of the settings (General, Price, Tax, Upgrade)
                if (isset($_GET['section'])) {
                    switch ($_GET['section']) {
                        case 'wwpp_setting_price_section':
                            wp_enqueue_style('wwp-price-css', WWP_CSS_URL . 'wwp-price-settings.css', array(), $this->_wwp_current_version, 'all');
                            wp_enqueue_script('wwp-price-settings', WWP_JS_URL . 'backend/wwp-price-setting.js', array(), $this->_wwp_current_version, true);
                            wp_localize_script('wwp-price-settings', 'price_settings_args', array(
                                'images_url' => WWP_IMAGES_URL,
                                'use_regular_price' => array(
                                    'title' => __("<h4>Define Prices By Percentage Globally Or On Categories</h4>", "woocommerce-wholesale-prices"),
                                    'msg' => __("<p>In WooCommerce Wholesale Prices Premium you can set your wholesale prices by a percentage on a category or site-wide general level.
                                            This can save heaps of time instead of setting wholesale pricing on individual products. Read more about it below.</p>", "woocommerce-wholesale-prices"),
                                    'link' => 'https://wholesalesuiteplugin.com/woocommerce-wholesale-prices-premium/?utm_source=freeplugin&utm_medium=upsell&utm_campaign=wwppricesettingsalwaysuseregularwwpplink',
                                ),
                                'variable_product_price_display' => array(
                                    'title' => __("<h4>Change How Variable Product Prices Are Displayed</h4>", "woocommerce-wholesale-prices"),
                                    'msg' => __("<p>Changing how your variable product prices are displayed can reduce the amount of computational work WooCommerce does on load, making your site faster.
                            Access this optimization option and more in the WooCommerce Wholesale Prices Premium plugin.</p>", "woocommerce-wholesale-prices"),
                                    'link' => 'https://wholesalesuiteplugin.com/woocommerce-wholesale-prices-premium/?utm_source=freeplugin&utm_medium=upsell&utm_campaign=wwppricesettingsvariabledisplaywwpplink',
                                ),
                                'button_text' => __("See Features & Pricing", "woocommerce-wholesale-prices"),
                            ));
                            break;

                        case 'wwpp_setting_tax_section':
                            wp_enqueue_style('wwp-tax-css', WWP_CSS_URL . 'wwp-tax-settings.css', array(), $this->_wwp_current_version, 'all');
                            wp_enqueue_script('wwp-tax-settings', WWP_JS_URL . 'backend/wwp-tax-setting.js', array(), $this->_wwp_current_version, true);
                            wp_localize_script('wwp-tax-settings', 'tax_settings_args', array(
                                'images_url' => WWP_IMAGES_URL,
                                'tax_exemption' => array(
                                    'title' => __("<h4>Upgrade To Wholesale Suite For Tax Exemption</h4>", "woocommerce-wholesale-prices"),
                                    'msg' => __("<p>Wholesale Suite is the #1 best rated wholesale solution for WooCommerce.</p>
                                                    <p>Prices Premium (one of the three plugins) features in-depth tax exemption controls including being able to turn on/off tax exemption just for specific wholesale roles.</p>", "woocommerce-wholesale-prices"),
                                    'link' => 'https://wholesalesuiteplugin.com/bundle/?utm_source=freeplugin&utm_medium=upsell&utm_campaign=wwptaxexemptionpopupbutton',
                                ),
                                'tax_display' => array(
                                    'title' => __("<h4>Upgrade To Wholesale Suite For Advanced Tax Display</h4>", "woocommerce-wholesale-prices"),
                                    'msg' => __("<p>Wholesale Suite is the #1 best rated wholesale solution for WooCommerce. Prices Premium (one of the three plugins) features in-depth tax display controls.</p>", "woocommerce-wholesale-prices"),
                                    'link' => 'https://wholesalesuiteplugin.com/bundle/?utm_source=freeplugin&utm_medium=upsell&utm_campaign=wwptaxdisplaypopupbutton',
                                ),
                                'suffix_overrides' => array(
                                    'title' => __("<h4>Upgrade To Wholesale Suite For Suffix Overrides</h4>", "woocommerce-wholesale-prices"),
                                    'msg' => __("<p>Wholesale Suite is the #1 best rated wholesale solution for WooCommerce. Prices Premium (one of three plugins) features advanced price suffix controls.</p>
                                                    <p>This can help in complex tax situations where prices suffixes should be different for wholesale customers.</p>", "woocommerce-wholesale-prices"),
                                    'link' => 'https://wholesalesuiteplugin.com/bundle/?utm_source=freeplugin&utm_medium=upsell&utm_campaign=wwppricesuffixpopupbutton',
                                ),
                                'button_text' => __("See Features & Pricing", "woocommerce-wholesale-prices"),
                            ));
                            break;

                        case 'wwp_upgrade_section':
                            wp_enqueue_style('wwp_wwp_upgrade_css', WWP_CSS_URL . 'wwp-upgrade.css', array(), $this->_wwp_current_version, 'all');
                            break;

                        default:
                            break;
                    }
                } else {
                    // General page
                    wp_enqueue_style('wwp-general-css', WWP_CSS_URL . 'wwp-general-settings.css', array(), $this->_wwp_current_version, 'all');
                }
            }

            // enqueue script to replace icons in wc-admin marketing.
            if ($screen->id === 'woocommerce_page_wc-admin' || $screen->id === 'edit-shop_coupon') {
                wp_enqueue_script('wwp-wc-admin-icons', WWP_JS_URL . 'backend/wwp-wc-admin-icons.js', array('jquery'), $this->_wwp_current_version, true);
                wp_localize_script('wwp-wc-admin-icons', 'wwpAdminIcons', array(
                    'imgUrl' => WWP_IMAGES_URL,
                ));
            }
        }

        /**
         * Load frontend related styles and scripts.
         * Only load em on the right time and on the right place.
         *
         * @since 1.0.0
         * @since 1.4.0 Refactor codebase and move to its own model.
         * @access public
         */
        public function load_front_end_styles_and_scripts()
        {

            global $post;

            if (is_product()) {

                /*
                 * This is about the issue where if variable product has variation with all having the same price.
                 * Wholesale price for a selected variation won't show on the single variable product page.
                 *
                 * This issue is already fixed in wwpp. Now if wwpp is installed and active, let wwpp fix this issue.
                 * Only fix this issue here in wwp if wwpp is not present.
                 *
                 * Note the fix on WWPP is different from the fix here coz WWPP has additional features to consider compared to WWP.
                 */
                if (!WWP_Helper_Functions::is_wwpp_active()) {

                    wp_enqueue_style('wwp_single_product_page_css', WWP_CSS_URL . 'frontend/product/wwp-single-product-page.css', array(), $this->_wwp_current_version, 'all');

                    if ($post->post_type == 'product') {

                        $product = wc_get_product($post->ID);

                        if (WWP_Helper_Functions::wwp_get_product_type($product) === 'variable') {

                            $userWholesaleRole = $this->_wwp_wholesale_roles->getUserWholesaleRole();
                            $variationsArr = array();

                            if (!empty($userWholesaleRole)) {

                                $variations = WWP_Helper_Functions::wwp_get_variable_product_variations($product);

                                foreach ($variations as $variation) {

                                    $variationProduct = wc_get_product($variation['variation_id']);

                                    $currVarPrice = $variation['display_price'];
                                    $price_arr = $this->_wwp_wholesale_prices->get_product_wholesale_price_on_shop_v3($variation['variation_id'], $userWholesaleRole);
                                    $wholesalePrice = $price_arr['wholesale_price'];
                                    $variationsArr[] = array(
                                        'variation_id' => $variation['variation_id'],
                                        'raw_regular_price' => (float) $currVarPrice,
                                        'raw_wholesale_price' => (float) $wholesalePrice,
                                        'has_wholesale_price' => is_numeric($wholesalePrice),
                                    );
                                }

                                // #WWP-51
                                // Check if variable product has same regular price and same wholesale price
                                // If true then don't load the script below
                                $same_reg_price = true;
                                $temp_reg_price = null;
                                $same_wholesale_price = true;
                                $temp_wholesale_price = null;

                                foreach ($variationsArr as $varData) {

                                    if (is_null($temp_reg_price)) {
                                        $temp_reg_price = $varData['raw_regular_price'];
                                    } elseif ($same_reg_price) {
                                        $same_reg_price = $temp_reg_price == $varData['raw_regular_price'];
                                    }

                                    if (is_null($temp_wholesale_price)) {
                                        $temp_wholesale_price = $varData['raw_wholesale_price'];
                                    } elseif ($same_wholesale_price) {
                                        $same_wholesale_price = $temp_wholesale_price == $varData['raw_wholesale_price'];
                                    }
                                }

                                $same_prices = $same_reg_price && $same_wholesale_price;

                                if (!$same_prices) // If prices are not the same, make sure to load the price html markup
                                {
                                    add_filter('woocommerce_show_variation_price', function () {
                                        return true;
                                    });
                                }
                            } // if ( !empty( $userWholesaleRole ) )

                        } // if ( WWP_Helper_Functions::wwp_get_product_type( $product ) === 'variable' )

                    } // if ( $post->post_type == 'product' )

                } // if wwpp is not active

            }
        }

        /**
         * Upsell message shown as popup in wholesale roles page.
         *
         * @since 1.11
         * @access public
         */
        private function role_page_upsell_message()
        {

            ob_start(); ?>

            <div class="upsell-area">
                <h2><?php _e('Additional Wholesale Roles (Premium)', 'woocommerce-wholesale-prices'); ?></h2>
                <p><?php _e('You\'re currently using the free version of WooCommerce Wholesale Prices which lets you have one level of wholesale customers.', 'woocommerce-wholesale-prices'); ?></p>
                <p><?php echo sprintf(__('In the <a href="%1$s" target="_blank">Premium add-on</a> you can add multiple wholesale roles. This will let you create separate "levels" of wholesale customers,
                    each of which can have separate wholesale pricing, shipping and payment mapping, order minimums and more.', 'woocommerce-wholesale-prices'), 'https://wholesalesuiteplugin.com/woocommerce-wholesale-prices-premium/?utm_source=freeplugin&utm_medium=upsell&utm_campaign=wwprolespagelinkpopup'); ?>
                </p>
                <p>
                    <a class="button" href="https://wholesalesuiteplugin.com/woocommerce-wholesale-prices-premium/?utm_source=freeplugin&utm_medium=upsell&utm_campaign=wwprolespagebuttonpopup" target="_blank">
                        <?php _e('See the full feature list', 'woocommerce-wholesale-prices'); ?>
                        <span class="dashicons dashicons-arrow-right-alt" style="margin-top: 7px"></span>
                    </a>
                    <img class="fivestar" src="<?php echo WWP_IMAGES_URL; ?>/5star.png" />
                </p>
            </div><?php

                    return ob_get_clean();
                }

                /**
                 * Execute model.
                 *
                 * @since 1.4.0
                 * @access public
                 */
                public function run()
                {

                    add_action('admin_enqueue_scripts', array($this, 'load_back_end_styles_and_scripts'), 10, 1);
                    add_action("wp_enqueue_scripts", array($this, 'load_front_end_styles_and_scripts'), 10);
                }
            }
        }
