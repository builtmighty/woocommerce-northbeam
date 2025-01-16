<?php
/*
Plugin Name: WooCommerce Northbeam
Plugin URI: https://builtmighty.com
Description: Northbeam tells you what channels, campaigns and creatives are actually driving revenue. Developed by data scientists and marketers with decades of experience scaling brands.
Version: 1.0.0
Author: Built Mighty
Author URI: https://builtmighty.com
Requires Plugins: woocommerce
Copyright: Built Mighty
Text Domain: woo-northbeam
Copyright © 2025 Built Mighty. All Rights Reserved.
*/

/**
 * Namespace.
 *
 * @since   1.0.0
 */
namespace Northbeam;

/**
 * Disallow direct access.
 */
if( ! defined( 'WPINC' ) ) { die; }

/**
 * Constants.
 *
 * @since   1.0.0
 */
define( 'NORTHBEAM_VERSION', '1.0.0' );
define( 'NORTHBEAM_NAME', 'woo-northbeam' );
define( 'NORTHBEAM_PATH', trailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'NORTHBEAM_URI', trailingslashit( plugin_dir_url( __FILE__ ) ) );

/**
 * On activation.
 *
 * @since   1.0.0
 */
register_activation_hook( __FILE__, '\Northbeam\activation' );
function activation() {

    // Flush rewrite rules.
    flush_rewrite_rules();

}

/**
 * On deactivation.
 *
 * @since   1.0.0
 */
register_deactivation_hook( __FILE__, '\Northbeam\deactivation' );
function deactivation() {

    // Flush rewrite rules.
    flush_rewrite_rules();

}

/**
 * Load classes.
 *
 * @since   1.0.0
 */
add_action( 'plugins_loaded', '\Northbeam\load_classes' );
function load_classes() {

    /**
     * Load.
     *
     * @since   1.0.0
     */
    require_once NORTHBEAM_PATH . 'classes/init.php';
    require_once NORTHBEAM_PATH . 'classes/private/class-settings.php';
    require_once NORTHBEAM_PATH . 'classes/public/class-order.php';
    require_once NORTHBEAM_PATH . 'classes/public/class-api.php';
    require_once NORTHBEAM_PATH . 'classes/public/class-hooks.php';

    /**
     * Initiate.
     *
     * @since   1.0.0
     */
    \Northbeam\Plugin::get_instance();

}