<?php
/*
 * Plugin Name:          WooCommerce Wholesale Prices
 * Plugin URI:           https://wholesalesuiteplugin.com
 * Description:          WooCommerce Extension to Provide Wholesale Prices Functionality
 * Author:               Rymera Web Co
 * Version:              1.14.1
 * Author URI:           http://rymera.com.au/
 * Text Domain:          woocommerce-wholesale-prices
 * WC requires at least: 3.0.9
 * WC tested up to:      5.3
 */

// This file is the main plugin boot loader

/**
 * Register Global Deactivation Hook.
 * Codebase that must be run on plugin deactivation whether or not dependencies are present.
 * Necessary to prevent activation code from being executed more than once.
 *
 * @since 1.2.9
 * @since 1.3.0 Add multi-site support.
 *
 * @param boolean $network_wide Flag that determines if the plugin is activated in a multi-site environment.
 */
function wwp_global_plugin_deactivate($network_wide) {

    global $wpdb;

    // check if it is a multisite network
    if (is_multisite()) {

        // check if the plugin has been activated on the network or on a single site
        if ($network_wide) {

            // get ids of all sites
            $blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");

            foreach ($blog_ids as $blog_id) {

                switch_to_blog($blog_id);
                delete_option('wwp_option_activation_code_triggered');
                delete_option('wwp_option_installed_version');
                delete_option('wwp_running');
            }

            restore_current_blog();
        } else {

            // activated on a single site, in a multi-site
            delete_option('wwp_option_activation_code_triggered');
            delete_option('wwp_option_installed_version');
            delete_option('wwp_running');
        }
    } else {

        // activated on a single site
        delete_option('wwp_option_activation_code_triggered');
        delete_option('wwp_option_installed_version');
        delete_option('wwp_running');
    }
}

register_deactivation_hook(__FILE__, 'wwp_global_plugin_deactivate');

require_once 'woocommerce-wholesale-prices.options.php';
require_once 'includes/class-wwp-helper-functions.php';

/**
 * Check if WooCommerce is active
 */
if (WWP_Helper_Functions::is_plugin_active('woocommerce/woocommerce.php')) {

    $execute_wwp = true;

    if (WWP_Helper_Functions::is_plugin_active('woocommerce-wholesale-prices-premium/woocommerce-wholesale-prices-premium.bootstrap.php')) {

        $wwpp_plugin_data = WWP_Helper_Functions::get_plugin_data('woocommerce-wholesale-prices-premium/woocommerce-wholesale-prices-premium.bootstrap.php');

        // WWP 1.4.0 requires WWPP 1.14.0
        // WWP 1.5.0 requires WWPP 1.15.0
        // WWP 1.6.0 requires WWPP 1.16.0
        if (version_compare($wwpp_plugin_data['Version'], '1.16.0', '<')) {

            /**
             * Add important notice, WWPP minimum version requirement not meet.
             *
             * @since 1.4.1
             */
            function wwp_missing_plugin_dependency_notice() {

                ?>

                <div class="error">
                    <h3><?php _e('Important Notice:', 'woocommerce-wholesale-prices');?></h3>
                    <p><?php _e('We have detected an outdated version of WooCommerce Wholesale Prices Premium. You require at least version 1.16.0 for this version of WooCommerce Wholesale Prices ( 1.6.0 ). Please update now.', 'woocommerce-wholesale-prices');?></p>
                </div>

        <?php

            }

            add_action('admin_notices', 'wwp_missing_plugin_dependency_notice');
            $execute_wwp = false;
        }
    }

    if ($execute_wwp) {

        // Initialize main plugin class
        require_once 'woocommerce-wholesale-prices.plugin.php';
        $wc_wholesale_prices = WooCommerceWholeSalePrices::instance();
        $GLOBALS['wc_wholesale_prices'] = $wc_wholesale_prices;

        // Execute WWP
        $wc_wholesale_prices->run();

        update_option('wwp_running', 'yes');
    } else {
        update_option('wwp_running', 'no');
    }

} else {

    /**
     * Provide admin notice when plugin dependency is missing.
     *
     * @since 1.2.9
     */
    function wwp_missing_plugin_dependency_notice() {

        $plugin_base_path = 'woocommerce/woocommerce.php';
        $plugin_install_text = '<a href="' . wp_nonce_url('update.php?action=install-plugin&plugin=woocommerce', 'install-plugin_woocommerce') . '">' . __('Click here to install from WordPress.org repo &rarr;', 'woocommerce-wholesale-prices') . '</a>';

        if (file_exists(trailingslashit(WP_PLUGIN_DIR) . plugin_basename($plugin_base_path))) {
            $plugin_install_text = '<a href="' . wp_nonce_url('plugins.php?action=activate&amp;plugin=' . $plugin_base_path . '&amp;plugin_status=all&amp;s', 'activate-plugin_' . $plugin_base_path) . '" title="' . __('Activate this plugin', 'woocommerce-wholesale-prices') . '" class="edit">' . __('Click here to activate &rarr;', 'woocommerce-wholesale-prices') . '</a>';
        }
        ?>

        <div class="error">
            <p>
                <?php _e('<b>WooCommerce Wholesale Prices</b> plugin missing dependency.<br/><br/>Please ensure you have the <a href="http://wordpress.org/plugins/woocommerce/" target="_blank">WooCommerce</a> plugin installed and activated.<br/>', 'woocommerce-wholesale-prices');?>
                <?php echo $plugin_install_text; ?>
            </p>
        </div>

<?php

    }

    add_action('admin_notices', 'wwp_missing_plugin_dependency_notice');

    update_option('wwp_running', 'no');
}
