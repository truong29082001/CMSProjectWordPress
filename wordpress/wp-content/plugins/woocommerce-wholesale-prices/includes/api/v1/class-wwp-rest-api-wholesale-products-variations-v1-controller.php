<?php if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

if (!class_exists('WWP_REST_Wholesale_Product_Variations_V1_Controller')) {

    /**
     * Model that houses the logic of WWP integration with WC API WPP Wholesale Products Variations.
     *
     * @since 1.12
     */
    class WWP_REST_Wholesale_Product_Variations_V1_Controller extends WC_REST_Product_Variations_Controller {

        /*
        |--------------------------------------------------------------------------
        | Class Properties
        |--------------------------------------------------------------------------
         */

        /**
         * Property that holds the single main instance of WWP_REST_Wholesale_Product_Variations_V1_Controller.
         *
         * @var WWP_REST_Wholesale_Product_Variations_V1_Controller
         */
        private static $_instance;

        /**
         * Endpoint namespace.
         *
         * @var string
         */
        protected $namespace = 'wholesale/v1';

        /**
         * Route base.
         *
         * @var string
         */
        protected $rest_base = 'products/(?P<product_id>[\d]+)/variations';

        /**
         * Post type.
         *
         * @var string
         */
        protected $post_type = 'product_variation';

        /**
         * Wholesale role.
         *
         * @var string
         */
        protected $wholesale_role = '';

        /**
         * WWP_REST_Wholesale_Products_v1_Controller.
         *
         * @var object
         */
        protected $wwp_rest_wholesale_products_v1_controller;

        /**
         * WWP Wholesale Roles.
         *
         * @var array
         */
        protected $registered_wholesale_roles;

        /*
        |--------------------------------------------------------------------------
        | Class Methods
        |--------------------------------------------------------------------------
         */

        /**
         * WWP_REST_Wholesale_Product_Variations_V1_Controller constructor.
         *
         * @since 1.12
         * @access public
         */
        public function __construct() {

            global $wc_wholesale_prices;
            $this->wwp_rest_wholesale_products_v1_controller = $wc_wholesale_prices->wwp_rest_api->wwp_rest_api_wholesale_products_controller;

            if (empty($this->registered_wholesale_roles)) {

                $this->registered_wholesale_roles = $wc_wholesale_prices->wwp_wholesale_roles->getAllRegisteredWholesaleRoles();
            }

            // Fires when preparing to serve an API request.
            add_action("rest_api_init", array($this, "register_routes"));

            // Include wholesale data into the response
            add_filter("woocommerce_rest_prepare_{$this->post_type}_object", array($this->wwp_rest_wholesale_products_v1_controller, "add_wholesale_data_on_response"), 10, 3);

            // Filter the query arguments of the request.
            add_filter("woocommerce_rest_{$this->post_type}_object_query", array($this, "query_args"), 10, 2);

            // Fires after a single object is created or updated via the REST API.
            add_action("woocommerce_rest_insert_{$this->post_type}_object", array($this->wwp_rest_wholesale_products_v1_controller, "create_update_wholesale_product"), 10, 3);

            // Insert '_have_wholesale_price' and '_variations_with_wholesale_price' meta after inserting variation
            add_action('wwp_after_variation_create_item', array($this, 'set_wholesale_price_meta_variable'), 10, 2);

            // After Deleting Variation delete parent meta _variations_with_wholesale_price
            add_action('wwp_after_variation_delete_item', array($this, 'update_variable_wholesale_price_meta_flag'), 10, 2);

        }

        /**
         * Query args.
         *
         * @param array           $args    Request args.
         * @param WP_REST_Request $request Request data.
         *
         * @since 1.12
         * @access public
         * @return array
         */
        public function query_args($args, $request) {

            $args_copy = (array) $args;

            // Check if not wholesale endpoint
            if (!$this->wwp_rest_wholesale_products_v1_controller->is_wholesale_endpoint($request)) {
                return $args;
            }

            // Get request role type
            $this->wholesale_role = !empty($request['wholesale_role']) ? sanitize_text_field($request['wholesale_role']) : sanitize_text_field($this->wholesale_role);

            // Only show wholesale products request
            $only_return_wholesale_products = !empty($request['return_wholesale_products']) ? filter_var($request['return_wholesale_products'], FILTER_VALIDATE_BOOLEAN) : false;

            // Fetch wholesale products and include in post__in
            if ($only_return_wholesale_products || apply_filters('wwp_only_show_wholesale_products_to_wholesale_users', false)) {

                // Has general discount filter. If this role has general discount then don't check per products. All products now considered wholesale
                if (apply_filters('wwp_has_general_discount', false, $args, $request) === false) {

                    // Has category level discount
                    if (apply_filters('wwp_has_category_discount', false, $args, $request) === false) {
                        $wholesale_products = $this->get_wholesale_variations($this->wholesale_role, $request);
                        $args_copy['post__in'] = array_values(array_unique(array_merge($args['post__in'], $wholesale_products)));

                        if (empty($args_copy['post__in'])) {
                            $args_copy['post__in'] = array(0);
                        }
                    }
                }

            }

            return apply_filters('wwp_rest_wholesale_variations_query_args', $args_copy, $args, $request);

        }

        /**
         * Get Variations with Wholesale Prices.
         *
         * @param string     $wholesale_role
         * @param WP_REST_Request $request Request data.
         *
         * @since 1.12
         * @access public
         * @return array
         */
        public function get_wholesale_variations($wholesale_role = '', $request) {

            global $wpdb;

            $have_wholesale_price_meta_list = array();
            $wholesale_role = sanitize_text_field($wholesale_role);

            $wholesale_roles_list = !empty($wholesale_role) ? array($wholesale_role => 1) : $this->registered_wholesale_roles;

            foreach ($wholesale_roles_list as $role => $data) {
                array_push($have_wholesale_price_meta_list, "'" . $role . "_wholesale_price'");
            }

            $have_wholesale_price_meta_list = implode(', ', $have_wholesale_price_meta_list);

            $wholesale_products = array();
            $product_id = intval($request['product_id']); // cast as Integer value, returns 0 if not int, returns whole number if value is float.

            $results = $wpdb->get_results("SELECT DISTINCT p.ID FROM $wpdb->posts p
												INNER JOIN $wpdb->postmeta pm1 ON ( p.ID = pm1.post_id )
												INNER JOIN $wpdb->postmeta pm2 ON ( p.ID = pm2.post_id )
												WHERE p.post_status = 'publish'
													AND p.post_type = '" . $this->post_type . "'
                                                    AND p.post_parent = " . $product_id . "
													AND (
															pm1.meta_key IN ( " . $have_wholesale_price_meta_list . " ) AND CAST( pm1.meta_value AS SIGNED ) > 0
														)
                                            ", ARRAY_A);

            if ($results) {

                foreach ($results as $product) {
                    $wholesale_products[] = $product['ID'];
                }

            }

            return $wholesale_products;

        }

        /**
         * Add checking on the response when fetching variations.
         *
         * @param WP_REST_Request $request Request data.
         *
         * @since 1.12
         * @access public
         * @return WP_REST_Response|WP_Error
         */
        public function get_items($request) {

            do_action('wwp_before_variation_get_items', $request);

            $wholesale_role = isset($request['wholesale_role']) ? sanitize_text_field($request['wholesale_role']) : sanitize_text_field($this->wholesale_role);
            $product_id = (int) $request['product_id'];

            if (!empty($wholesale_role) && !isset($this->registered_wholesale_roles[$wholesale_role])) {
                return new WP_Error('wholesale_rest_cannot_view', __('Invalid wholesale role.', 'woocommerce-wholesale-prices'), array('status' => rest_authorization_required_code()));
            }

            $response = parent::get_items($request);

            do_action('wwp_after_variation_get_items', $request, $response);

            return $response;

        }

        /**
         * Override WC Delete variation. Check first if variation is has wholesale price for it to be deleted.
         *
         * @param WP_REST_Request $request Request data.
         *
         * @since 1.12
         * @access public
         * @return array
         */
        public function delete_item($request) {

            do_action('wwp_before_variation_delete_item', $request);

            global $wc_wholesale_prices;

            $_REQUEST['request'] = $request;

            // Force Delete Variation
            $request->set_param('force', true);

            $response = parent::delete_item($request);

            do_action('wwp_after_variation_delete_item', $request, $response);

            return $response;

        }

        /* Validate if fetched variation is wholesale product
         *
         * @param WP_REST_Request         $request
         *
         * @since 1.12
         * @access public
         * @return WP_REST_Response|WP_Error
         */
        public function get_item($request) {

            do_action('wwp_before_variation_get_item', $request);

            $only_return_wholesale_products = !empty($request['return_wholesale_products']) ? filter_var($request['return_wholesale_products'], FILTER_VALIDATE_BOOLEAN) : false;
            $wholesale_role = isset($request['wholesale_role']) ? sanitize_text_field($request['wholesale_role']) : sanitize_text_field($this->wholesale_role);
            $variation_id = (int) $request['id'];

            // WWPP is not active
            if ($only_return_wholesale_products && !WWP_Helper_Functions::is_wwpp_active()) {

                // If just a regular product ( without wholesale price ) then show an error
                if (empty($wholesale_role) || get_post_meta($variation_id, $wholesale_role . '_wholesale_price', true) <= 0) {
                    return new WP_Error('wholesale_rest_cannot_view', __('Not a wholesale product.', 'woocommerce-wholesale-prices'), array('status' => rest_authorization_required_code()));
                }

            } else if (WWP_Helper_Functions::is_wwpp_active() && apply_filters('wwp_only_show_wholesale_products_to_wholesale_users', false)) {

                // WWPP active
                $wholesale_products = $this->get_wholesale_variations($this->wholesale_role, $request);
                if (empty($wholesale_role) || !in_array($request['id'], $wholesale_products)) {
                    return new WP_Error('wholesale_rest_cannot_view', __('Not a wholesale product.', 'woocommerce-wholesale-prices'), array('status' => rest_authorization_required_code()));
                }
            }

            $response = parent::get_item($request);

            do_action('wwp_after_variation_get_item', $request, $response);

            return $response;

        }

        /**
         * Extra validation on variation creation.
         *
         * @param WP_REST_Request         $request
         *
         * @since 1.12
         * @access public
         * @return WP_REST_Response|WP_Error
         */
        public function create_item($request) {

            do_action('wwp_before_variation_create_item', $request);

            if (!isset($request['wholesale_price'])) {
                return new WP_Error('wholesale_rest_cannot_create', __('Unable to create. Please provide "wholesale_price" in the request paremeter.', 'woocommerce-wholesale-prices'), array('status' => rest_authorization_required_code()));
            }

            // Check if wholesale price is set. Make wholesale price as the basis to create wholesale product.
            if (isset($request['wholesale_price'])) {

                if (!is_array($request['wholesale_price']) || empty($request['wholesale_price'])) {
                    return new WP_Error('wholesale_rest_cannot_create', __('Unable to create. Invalid wholesale price.', 'woocommerce-wholesale-prices'), array('status' => rest_authorization_required_code()));
                }

                if (is_array($request['wholesale_price'])) {

                    $total_valid_wholesale_price = 0;

                    foreach ($request['wholesale_price'] as $role => $price) {

                        // Validate if wholesale role exist
                        if (is_numeric($price) && array_key_exists($role, $this->registered_wholesale_roles)) {
                            $total_valid_wholesale_price += 1;
                        }

                    }

                    if (empty($total_valid_wholesale_price)) {
                        return new WP_Error('wholesale_rest_cannot_create', __('Unable to create. Invalid wholesale price.', 'woocommerce-wholesale-prices'), array('status' => rest_authorization_required_code()));
                    }

                }

            }

            $response = parent::create_item($request);

            do_action('wwp_after_variation_create_item', $request, $response);

            return $response;

        }

        /**
         * Set _have_wholesale_price and _variations_with_wholesale_price meta in variable level if the created variation has wholesale price set.
         *
         * @param WP_REST_Request         $request
         * @param WP_REST_Response        $response
         *
         * @since 1.12
         * @since 1.13.3
         * @access public
         * @return WP_REST_Response|WP_Error
         */
        public function set_wholesale_price_meta_variable($request, $response) {

            if (isset($request['product_id'])) {

                $variable_id = intval($request['product_id']);
                $variation_id = $response->data['id'];

                $wholesale_role_dicounts = $response->data['wholesale_data']['wholesale_price'];

                if ($wholesale_role_dicounts) {

                    foreach ($wholesale_role_dicounts as $role => $discount) {

                        update_post_meta($variable_id, $role . '_have_wholesale_price', 'yes');
                        add_post_meta($variable_id, $role . '_variations_with_wholesale_price', $variation_id);

                    }

                }

            }

        }

        /**
         * Update _have_wholesale_price and _variations_with_wholesale_price meta in variable level if the variation is deleted.
         *
         * @param WP_REST_Request         $request
         * @param WP_REST_Response        $response
         *
         * @since 1.12
         * @access public
         * @return WP_REST_Response|WP_Error
         */
        public function update_variable_wholesale_price_meta_flag($request, $response) {

            global $wc_wholesale_prices;

            if (isset($request['product_id'])) {

                $variable_id = intval($request['product_id']);
                $variation_id = $response->data['id'];

                $wholesale_roles = $this->registered_wholesale_roles;
                $product = wc_get_product($variable_id);
                $variations = $product->get_available_variations();

                if ($wholesale_roles) {

                    foreach ($wholesale_roles as $role => $data) {

                        delete_post_meta($variable_id, $role . '_variations_with_wholesale_price', $variation_id);

                        $price_arr = $wc_wholesale_prices->wwp_wholesale_prices->get_product_wholesale_price_on_shop_v3($variable_id, array($role));

                        if (!empty($price_arr['wholesale_price'])) {
                            update_post_meta($variable_id, $role . '_have_wholesale_price', 'yes');
                        } else {
                            delete_post_meta($variable_id, $role . '_have_wholesale_price');
                        }

                    }

                }

                // If all variations are removed then set stock status to outofstock
                if (empty($variations)) {
                    update_post_meta($variable_id, '_stock_status', 'outofstock');
                }
            }

        }

    }

}
