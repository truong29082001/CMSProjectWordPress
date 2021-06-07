<?php if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

if (!class_exists('WWP_REST_Wholesale_Products_V1_Controller')) {

    /**
     * Model that houses the logic of WWPP integration with WC API WPP Wholesale Products.
     *
     * @since 1.12
     */
    class WWP_REST_Wholesale_Products_V1_Controller extends WC_REST_Products_Controller {

        /*
        |--------------------------------------------------------------------------
        | Class Properties
        |--------------------------------------------------------------------------
         */

        /**
         * Property that holds the single main instance of WWP_REST_Wholesale_Products_V1_Controller.
         *
         * @var WWP_REST_Wholesale_Products_V1_Controller
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
        protected $rest_base = 'products';

        /**
         * Post type.
         *
         * @var string
         */
        protected $post_type = 'product';

        /**
         * Wholesale role.
         *
         * @var string
         */
        protected $wholesale_role = '';

        /**
         * WWPP Wholesale Roles.
         *
         * @var array
         */
        protected $registered_wholesale_roles = array();

        /*
        |--------------------------------------------------------------------------
        | Class Methods
        |--------------------------------------------------------------------------
         */

        /**
         * WWP_REST_Wholesale_Products_V1_Controller constructor.
         *
         * @since 1.12
         * @access public
         */
        public function __construct() {

            if (empty($this->registered_wholesale_roles)) {

                global $wc_wholesale_prices;
                $this->registered_wholesale_roles = $wc_wholesale_prices->wwp_wholesale_roles->getAllRegisteredWholesaleRoles();
            }

            // Fires when preparing to serve an API request.
            add_action("rest_api_init", array($this, "register_routes"));

            // Filter the query arguments of the request.
            add_filter("woocommerce_rest_{$this->post_type}_object_query", array($this, "query_args"), 10, 2);

            // Include wholesale data into the response
            add_filter("woocommerce_rest_prepare_{$this->post_type}_object", array($this, "add_wholesale_data_on_response"), 10, 3);

            // Fires after a single object is created or updated via the REST API.
            add_action("woocommerce_rest_insert_{$this->post_type}_object", array($this, "create_update_wholesale_product"), 10, 3);

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
            if (!$this->is_wholesale_endpoint($request)) {
                return $args;
            }

            // Get request role type
            $this->wholesale_role = !empty($request['wholesale_role']) ? sanitize_text_field($request['wholesale_role']) : sanitize_text_field($this->wholesale_role);

            // Only show wholesale products request
            $only_return_wholesale_products = !empty($request['return_wholesale_products']) ? filter_var($request['return_wholesale_products'], FILTER_VALIDATE_BOOLEAN) : false;

            // Fetch wholesale products and include in post__in
            if ($only_return_wholesale_products || apply_filters('wwp_only_show_wholesale_products_to_wholesale_users', false)) {
                $wholesale_products = $this->get_wholesale_products($this->wholesale_role);
                $args_copy['post__in'] = array_values(array_unique(array_merge($args['post__in'], $wholesale_products)));

                if (empty($args_copy['post__in'])) {
                    $args_copy['post__in'] = array(0);
                }
            }

            return apply_filters('wwp_rest_wholesale_products_query_args', $args_copy, $args, $request);

        }

        /**
         * Get simple and variable Wholesale Products.
         *
         * @param string     $wholesale_role
         *
         * @since 1.12
         * @access public
         * @return array
         */
        public function get_wholesale_products($wholesale_role) {

            global $wpdb;

            $have_wholesale_price_meta_list = array();
            $variations_with_wholesale_price_meta_list = array();
            $wholesale_role = sanitize_text_field($wholesale_role);

            // Used to check if variable has wholesale variations.
            // wholesale discount set via the category will be check in WWPP(when its active).
            // Also this prevents fetching non-existing wholesale roles in case it was removed but the meta still exist in the product.
            $wholesale_roles_list = !empty($wholesale_role) ? array($wholesale_role => 1) : $this->registered_wholesale_roles;

            foreach ($wholesale_roles_list as $role => $data) {
                array_push($have_wholesale_price_meta_list, "'" . $role . "_have_wholesale_price'");
                array_push($variations_with_wholesale_price_meta_list, "'" . $role . "_variations_with_wholesale_price'");
            }

            $have_wholesale_price_meta_list = implode(', ', $have_wholesale_price_meta_list);
            $variations_with_wholesale_price_meta_list = implode(', ', $variations_with_wholesale_price_meta_list);

            $wholesale_products = array();

            // Allow deletion of wholesale products with status of draft only if request method is DELETE
            if (isset($_REQUEST['request']) && $_REQUEST['request']->get_method() === 'DELETE') {
                $post_status = "IN ( 'publish' , 'draft' , 'trash' )";
            } else {
                $post_status = "= 'publish'";
            }

            // WWPP Wholesale Discount by category
            $wholesale_price_set_by_product_cat = apply_filters('wwp_wholesale_price_set_by_product_cat', '', $wholesale_role);

            $results = $wpdb->get_results("SELECT DISTINCT p.ID FROM $wpdb->posts p

												INNER JOIN $wpdb->postmeta pm1 ON ( p.ID = pm1.post_id )
												INNER JOIN $wpdb->postmeta pm2 ON ( p.ID = pm2.post_id )
												WHERE p.post_status " . $post_status . "
													AND p.post_type = 'product'
													AND (
															( pm1.meta_key IN ( " . $have_wholesale_price_meta_list . " ) AND pm1.meta_value = 'yes' )
															AND
															(
																( pm2.meta_key LIKE '%" . $wholesale_role . "_wholesale_price%' AND CAST( pm2.meta_value AS SIGNED ) > 0 )
																OR
																( pm2.meta_key IN ( " . $variations_with_wholesale_price_meta_list . " ) AND pm2.meta_value = 'yes' )
																OR
                                                                ( pm2.meta_key IN ( " . $variations_with_wholesale_price_meta_list . " ) AND CAST( pm2.meta_value AS SIGNED ) > 0 )
                                                                " . $wholesale_price_set_by_product_cat . "
															)
														)
											", ARRAY_A);

            if ($results) {

                foreach ($results as $product) {
                    $wholesale_products[] = $product['ID'];
                }

            }

            return apply_filters('wwp_rest_wholesale_products', $wholesale_products, $wholesale_role);

        }

        /**
         * Modify the response to include WWP wholesale data.
         *
         * @param WP_REST_Response         $response
         * @param WC_Product              $object
         * @param WP_REST_Request         $request
         *
         * @since 1.12
         *
         * @access public
         * @return array
         */
        public function add_wholesale_data_on_response($response, $object, $request) {

            // Check if not wholesale endpoint
            if (!$this->is_wholesale_endpoint($request)) {
                return $response;
            }

            // Add wholesale data. Add also WWPP meta data.
            $response->data['wholesale_data'] = $this->get_wwp_meta_data($object, $request);

            // Remove WWPP meta in meta data
            // Only show meta_data if 'show_meta_data=true' is provided in the request parameter else hide it by default.
            if (isset($request['show_meta_data']) && filter_var($request['show_meta_data'], FILTER_VALIDATE_BOOLEAN) === true) {
                $response->data['meta_data'] = $this->remove_wwpp_meta($response->data['meta_data']);
            } else {
                unset($response->data['meta_data']);
            }

            // Only show categories if 'show_categories=true' is provided in the api request else hide by default.
            if (!isset($request['show_categories']) || (isset($request['show_categories']) && filter_var($request['show_categories'], FILTER_VALIDATE_BOOLEAN) === false)) {
                unset($response->data['categories']);
            }

            // Remove links in response
            $links = $response->get_links();
            if (!empty($links)) {
                foreach ($links as $key => $link) {
                    $response->remove_link($key);
                }
            }

            return apply_filters("wwp_rest_response_{$this->post_type}_object", $response, $object, $request);

        }

        /**
         * Check if the request coming from wholesale endpoint
         *
         * @param WC_Product         $product
         * @param WP_REST_Request         $request
         *
         * @since 1.12
         * @access public
         * @return array
         */
        public function get_wwp_meta_data($product, $request) {

            $product_id = $product->get_id();

            $meta_data = array(
                'wholesale_price' => array(),
            );

            // Get formatted Wholesale Price
            if (isset($request['wholesale_role'])) {

                global $wc_wholesale_prices;

                $wwp_wholesale_prices_instance = new WWP_Wholesale_Prices(array());
                $wholesale_role = sanitize_text_field($request['wholesale_role']);

                $meta_data['price_html'] = $wwp_wholesale_prices_instance->wholesale_price_html_filter($product->get_price_html(), $product, array($wholesale_role));

            }

            foreach ($this->registered_wholesale_roles as $role => $data) {

                $wholesale_price = get_post_meta($product_id, $role . '_wholesale_price', true);

                if (!empty($wholesale_price)) {
                    $meta_data['wholesale_price'] = array_merge($meta_data['wholesale_price'], array($role => $wholesale_price));
                }

            }

            return apply_filters('wwp_meta_data', array_filter($meta_data), $product, $request);

        }

        /**
         * Unset WWP and WWPP meta in meta_data property. WWPP meta will be transfered to its own property called wholesale_data.
         *
         * @param array         $meta_data
         *
         * @since 1.12
         * @access public
         * @return array
         */
        public function remove_wwpp_meta($meta_data) {

            $meta_to_remove = apply_filters('wwp_meta_to_hide_from_response', array(
                'wwpp_ignore_cat_level_wholesale_discount',
                'wwpp_ignore_role_level_wholesale_discount',
                'wwpp_post_meta_quantity_discount_rule_mapping',
                'wwpp_product_wholesale_visibility_filter',
                'wwpp_post_meta_enable_quantity_discount_rule',
            ), $meta_data);

            $new_meta_data = $meta_data;

            if (!empty($new_meta_data)) {

                foreach ($new_meta_data as $key => $data) {

                    if (in_array($data->key, $meta_to_remove)) {
                        unset($new_meta_data[$key]);
                    } else if (strpos($data->key, '_wholesale_price') !== false ||
                        strpos($data->key, '_have_wholesale_price') !== false ||
                        strpos($data->key, '_wholesale_minimum_order_quantity') !== false ||
                        strpos($data->key, '_wholesale_order_quantity_step') !== false) {
                        unset($new_meta_data[$key]);
                    }

                }

            }

            return apply_filters('remove_wwpp_meta', array_values($new_meta_data), $meta_data);

        }

        /**
         * Check if the request coming from wholesale endpoint
         *
         * @param WP_REST_Request         $request
         *
         * @since 1.12
         * @access public
         * @return bool
         */
        public static function is_wholesale_endpoint($request) {

            return apply_filters('wwp_is_wholesale_endpoint', is_a($request, 'WP_REST_Request') && strpos($request->get_route(), 'wholesale/v1') !== false ? true : false, $request);

        }

        /**
         * Fires after a single object is created or updated via the REST API.
         * Note: This function seems to be firing 3x. Need to optimize in the future.
         *
         * @param WC_Product              $product
         * @param WP_REST_Request         $request
         * @param Boolean                 $create_product     True is creating, False is updating
         *
         * @since 1.12
         * @access public
         */
        public function create_update_wholesale_product($product, $request, $create_product) {

            do_action('wwp_before_create_update_wholesale_product', $product, $request, $create_product);

            // Check if not wholesale endpoint then dont proceed
            if (!$this->is_wholesale_endpoint($request)) {
                return;
            }

            // Import variables into the current symbol table from an array
            extract($request->get_params());

            // Get product type
            $product_type = WWP_Helper_Functions::wwp_get_product_type($product);

            // The product id
            $product_id = $product->get_id();

            // Check if wholesale price is set
            if (isset($wholesale_price) && in_array($product_type, array('simple', 'variation'))) {

                // Multiple wholesale price is set
                if (is_array($wholesale_price)) {

                    foreach ($wholesale_price as $role => $price) {

                        // Validate if wholesale role exist
                        if (is_numeric($price) && array_key_exists($role, $this->registered_wholesale_roles)) {

                            update_post_meta($product_id, $role . '_wholesale_price', $price);
                            update_post_meta($product_id, $role . '_have_wholesale_price', 'yes');

                        }

                        // If user updates the wholesale and if its empty still do update the meta
                        if (!$create_product && empty($price)) {
                            update_post_meta($product_id, $role . '_wholesale_price', $price);
                        }

                    }

                }

            }

            do_action('wwp_after_create_update_wholesale_product', $product, $request, $create_product);

        }

        /**
         * Check if the product is a valid wholesale product. Only wholesale product can be deleted using wholesale/v1 namespace.
         *
         * @param  WP_REST_Request $request Full details about the request.
         *
         * @since 1.12.0
         * @access public
         * @return bool|WP_Error
         */
        public function delete_item($request) {

            do_action('wwp_before_product_delete_item', $request);

            $response = parent::delete_item($request);

            do_action('wwp_after_product_delete_item', $request, $response);

            return $response;

        }

        /* Creating wholesale product
         *
         * @param WP_REST_Request         $request
         *
         * @since 1.12
         * @access public
         * @return WP_REST_Response|WP_Error
         */
        public function create_item($request) {

            do_action('wwp_before_product_create_item', $request);

            if (!isset($request['wholesale_price']) && (isset($request['type'])) && $request['type'] != 'variable') {
                return new WP_Error('wholesale_rest_cannot_create', __('Unable to create. Please provide "wholesale_price" in the request paremeter.', 'woocommerce-wholesale-prices'), array('status' => rest_authorization_required_code()));
            }

            // Check if wholesale price is set. Make wholesale price as the basis to create wholesale product.
            if ((!isset($request['wholesale_price']) ||
                !isset($request['wholesale_price']['wholesale_customer']) ||
                $request['wholesale_price']['wholesale_customer'] <= 0) &&
                $request['type'] != 'variable'
            ) {

                return new WP_Error('wholesale_rest_cannot_create', __('Unable to create. Invalid wholesale price.', 'woocommerce-wholesale-prices'), array('status' => rest_authorization_required_code()));

            }

            $response = parent::create_item($request);

            do_action('wwp_after_product_create_item', $request, $response);

            return $response;

        }

        /* Validate if fetched item is wholesale product
         *
         * @param WP_REST_Request         $request
         *
         * @since 1.12
         * @access public
         * @return WP_REST_Response|WP_Error
         */
        public function get_item($request) {

            do_action('wwp_before_product_get_item', $request);

            $wholesale_role = isset($request['wholesale_role']) ? sanitize_text_field($request['wholesale_role']) : $this->wholesale_role;

            if (!empty($wholesale_role) && !isset($this->registered_wholesale_roles[$wholesale_role])) {
                return new WP_Error('wholesale_rest_cannot_view', __('Invalid wholesale role.', 'woocommerce-wholesale-prices'), array('status' => rest_authorization_required_code()));
            }

            $only_return_wholesale_products = !empty($request['return_wholesale_products']) ? filter_var($request['return_wholesale_products'], FILTER_VALIDATE_BOOLEAN) : false;

            // If just a regular product ( without wholesale price ) then show an error only
            // If wholesale_customer is set and if only_return_wholesale_products = true OR WWPP Only show wholesale products to wholesale users is enabled
            // Skip checking if wholesale product when general discount is set for this current wholesale role
            if (apply_filters('wwp_general_discount_is_set', false, $request) === false && ($only_return_wholesale_products || apply_filters('wwp_only_show_wholesale_products_to_wholesale_users', false))) {

                if (empty($wholesale_role)) {
                    return new WP_Error('wholesale_rest_cannot_view', __('Not a wholesale product.', 'woocommerce-wholesale-prices'), array('status' => rest_authorization_required_code()));
                }

                $wholesale_products = $this->get_wholesale_products($wholesale_role);

                if (!in_array($request['id'], $wholesale_products)) {
                    return new WP_Error('wholesale_rest_cannot_view', __('Not a wholesale product.', 'woocommerce-wholesale-prices'), array('status' => rest_authorization_required_code()));
                }

            }

            $response = parent::get_item($request);

            do_action('wwp_after_product_get_item', $request, $response);

            return $response;

        }

        /* Validate if the request wholesale role is valid
         *
         * @param WP_REST_Request         $request
         *
         * @since 1.12
         * @access public
         * @return WP_REST_Response|WP_Error
         */
        public function get_items($request) {

            do_action('wwp_before_product_get_items', $request);
            $wholesale_role = isset($request['wholesale_role']) ? sanitize_text_field($request['wholesale_role']) : '';

            if (!empty($wholesale_role) && !isset($this->registered_wholesale_roles[$wholesale_role])) {
                return new WP_Error('wholesale_cannot_view', __('Invalid wholesale role.', 'woocommerce-wholesale-prices'), array('status' => rest_authorization_required_code()));
            }

            $response = parent::get_items($request);

            do_action('wwp_after_product_get_items', $request, $response);

            return $response;

        }

    }

}
