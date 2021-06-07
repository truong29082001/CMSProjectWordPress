<?php if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

if (!class_exists('WWP_REST_Wholesale_Roles_V1_Controller')) {

    /**
     * Model that houses the logic of WWP integration with WC API WPP Wholesale Products.
     *
     * @since 1.12
     */
    class WWP_REST_Wholesale_Roles_V1_Controller extends WC_REST_Controller {

        /*
        |--------------------------------------------------------------------------
        | Class Properties
        |--------------------------------------------------------------------------
         */

        /**
         * Property that holds the single main instance of WWP_REST_Wholesale_Roles_V1_Controller.
         *
         * @var WWP_REST_Wholesale_Roles_V1_Controller
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
        protected $rest_base = 'roles';

        /**
         * WWP object.
         *
         * @var object
         */
        protected $wc_wholesale_prices;

        /*
        |--------------------------------------------------------------------------
        | Class Methods
        |--------------------------------------------------------------------------
         */

        /**
         * WWP_REST_Wholesale_Roles_V1_Controller constructor.
         *
         * @since 1.12
         * @access public
         */
        public function __construct() {

            global $wc_wholesale_prices;

            $this->wc_wholesale_prices = $wc_wholesale_prices;

            // Fires when preparing to serve an API request.
            add_action("rest_api_init", array($this, "register_routes"));

        }

        /**
         * Register routes for wholesale roles API.
         *
         * @since 1.12
         * @access public
         */
        public function register_routes() {

            register_rest_route($this->namespace, '/' . $this->rest_base, array(
                array(
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => array($this, 'get_items'),
                    'permission_callback' => array($this, 'get_items_permissions_check'),
                ),
            ));

            register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<role_key>[a-z0-9_]*)', array(
                array(
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => array($this, 'get_item'),
                    'permission_callback' => array($this, 'get_item_permissions_check'),
                ),
                array(
                    'methods' => WP_REST_Server::EDITABLE,
                    'callback' => array($this, 'update_item'),
                    'permission_callback' => array($this, 'update_item_permissions_check'),
                ),
            ));

        }

        /**
         * Check if a given request has access to read items.
         *
         * @param  WP_REST_Request $request Full details about the request.
         * @return WP_Error|boolean
         */
        public function get_items_permissions_check($request) {

            if (!wc_rest_check_post_permissions('product', 'read')) {
                return new WP_Error('wholesale_rest_cannot_view', __('Sorry, you cannot list resources.', 'woocommerce'), array('status' => rest_authorization_required_code()));
            }

            return true;
        }

        /**
         * Check if a given request has access to read an item.
         *
         * @param  WP_REST_Request $request Full details about the request.
         * @return WP_Error|boolean
         */
        public function get_item_permissions_check($request) {
            $post = get_post((int) $request['id']);

            if ($post && !wc_rest_check_post_permissions('product', 'read', $post->ID)) {
                return new WP_Error('wholesale_rest_cannot_view', __('Sorry, you cannot view this resource.', 'woocommerce'), array('status' => rest_authorization_required_code()));
            }

            return true;
        }

        /**
         * Check if a given request has access to update an item.
         *
         * @param  WP_REST_Request $request Full details about the request.
         * @return WP_Error|boolean
         */
        public function update_item_permissions_check($request) {
            $post = get_post((int) $request['id']);

            if ($post && !wc_rest_check_post_permissions('product', 'edit', $post->ID)) {
                return new WP_Error('wholesale_rest_cannot_edit', __('Sorry, you are not allowed to edit this resource.', 'woocommerce'), array('status' => rest_authorization_required_code()));
            }

            return true;
        }

        /**
         * Get all items from the collection.
         *
         * @param WP_REST_Request         $request
         *
         * @since 1.12
         * @access public
         * @return WP_REST_Request
         */
        public function get_items($request) {

            $wholesale_roles = apply_filters('wwp_api_fetch_wholesale_role_filter', $this->wc_wholesale_prices->wwp_wholesale_roles->getAllRegisteredWholesaleRoles(), $request);

            $wholesale_roles = apply_filters('wwp_get_items_api', $this->hide_only_allow_wholesale_purchases($wholesale_roles), $request);

            return new WP_REST_Response($wholesale_roles, 200);

        }

        /**
         * Get one item from the collection.
         *
         * @param WP_REST_Request         $request
         *
         * @since 1.12
         * @access public
         * @return WP_Error|WP_REST_Request
         */
        public function get_item($request) {

            $wholesale_roles = $this->wc_wholesale_prices->wwp_wholesale_roles->getAllRegisteredWholesaleRoles();
            $role_key = isset($request['role_key']) ? $request['role_key'] : '';

            if (!empty($role_key) && isset($wholesale_roles[$role_key])) {

                $wholesale_role = apply_filters('wwp_api_fetch_wholesale_role_filter', $this->hide_only_allow_wholesale_purchases($wholesale_roles[$role_key]), $request);

                return new WP_REST_Response($wholesale_role, 200);

            }

            return new WP_Error('wholesale_rest_cannot_view', __('Item not found.', 'woocommerce-wholesale-prices'), array('status' => rest_authorization_required_code()));

        }

        /**
         * Update one item from the collection.
         *
         * @param WP_REST_Request         $request
         *
         * @since 1.12
         * @access public
         * @return WP_Error|WP_REST_Request
         */
        public function update_item($request) {

            $wholesale_roles = $this->getAllRegisteredWholesaleRoles();
            $role_key = isset($request['role_key']) ? $request['role_key'] : '';

            if (!empty($role_key) && isset($this->wc_wholesale_prices->wwp_wholesale_roles->getAllRegisteredWholesaleRoles()[$role_key])) {

                if (isset($request['role_name'])) {
                    $wholesale_roles[$role_key]['roleName'] = $request['role_name'];
                }

                if (isset($request['description'])) {
                    $wholesale_roles[$role_key]['desc'] = $request['description'];
                }

                $wholesale_roles = apply_filters('wwp_api_update_wholesale_roles_filter', $wholesale_roles, $request);

                update_option(WWP_OPTIONS_REGISTERED_CUSTOM_ROLES, serialize($wholesale_roles));

                $result = array(
                    'message' => 'Wholesale Role "' . $role_key . '" has been updated.',
                    'data' => array($role_key => $this->hide_only_allow_wholesale_purchases($wholesale_roles[$role_key])),
                );

                return new WP_REST_Response($result, 200);

            }

            return new WP_Error('wholesale_rest_cannot_update', __('Item not found.', 'woocommerce-wholesale-prices'), array('status' => rest_authorization_required_code()));

        }

        /**
         * Delete one item from the collection.
         *
         * @param WP_REST_Request         $request
         *
         * @since 1.12
         * @access public
         * @return WP_Error|WP_REST_Request
         */
        public function delete_item($request) {

            return new WP_Error('wholesale_rest_cannot_delete', __('Item not found.', 'woocommerce-wholesale-prices'), array('status' => rest_authorization_required_code()));

        }

        /**
         * Don't return "onlyAllowWholesalePurchases" if WWPP is disabled. This is WWPP feature.
         *
         * @param array         $wholesale_roles
         *
         * @since 1.12
         * @access public
         * @return array
         */
        public function hide_only_allow_wholesale_purchases($wholesale_roles) {

            if (!WWP_Helper_Functions::is_wwpp_active()) {
                unset($wholesale_roles['onlyAllowWholesalePurchases']);
                unset($wholesale_roles['wholesale_customer']['onlyAllowWholesalePurchases']);
            }

            return $wholesale_roles;

        }

        /**
         * Return all registered custom wholesale roles.
         * @since 1.12
         * @access public
         *
         * @return array
         */
        public function getAllRegisteredWholesaleRoles() {

            $all_registered_wholesale_roles = unserialize(get_option(WWP_OPTIONS_REGISTERED_CUSTOM_ROLES));

            if (!is_array($all_registered_wholesale_roles)) {
                $all_registered_wholesale_roles = array();
            }

            return $all_registered_wholesale_roles;

        }

    }

}
