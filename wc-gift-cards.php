<?php
/*
 * Plugin Name: Codup WooCommerce Gift Cards
 * Description: This plugin gives you the ability to create and sell gift cards on your woocommerce store. Your users can buy these gift cards and send them to their loved ones via email who may then redeem these gift cards from your store.
 * Version: 1.1.1.18
 * Author: Codup.io
 * Author URI: http://codup.io
 * Requires at least: 4.4
 * Tested up to: 5.4.1
 * 
 * Text Domain: codup-gift-card
 * Domain Path: /i18n/languages/
 * 
 * @package WooCommerce
 * @category Core
 * @author Codup.io
 * 
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
define('CWRF_PLUGIN_NAME', 'wc-gift-cards-by-codup-io');
define('CWRF_PLUGIN_VER', '1.1.1.15');
define('CWRF_PAGE_SLUG', 'codup_gift_card');

defined ( 'CODUP_GC_URL' ) || define ( 'CODUP_GC_URL', plugins_url ( '/', __FILE__ ) );
defined ( 'CODUP_GC_DIR' ) || define ( 'CODUP_GC_DIR', plugin_dir_path ( __FILE__ ) );
defined ( 'CODUP_GC_ASSETS_URL' ) || define ( 'CODUP_GC_ASSETS_URL', CODUP_GC_URL . 'assets' );
defined ( 'CODUP_GC_TEMPLATES_DIR' ) || define ( 'CODUP_GC_TEMPLATES_DIR', CODUP_GC_DIR . 'templates' );
define('CODUP_GC_NAME', 'wc-gift-cards-by-codup-io');
define('CODUP_GC_VERSION', '1.1.1.14');
define('CODUP_GC_SLUG', 'codup-gift-card');

function codup_gc_register_gift_card_product_type() {
        if(class_exists('WooCommerce')) {
          require plugin_dir_path( __FILE__ ) . 'includes/functions.php';
          require plugin_dir_path( __FILE__ ) . 'includes/class-codup-gift-card-product.php';
          require plugin_dir_path( __FILE__ ) . 'includes/class-codup-gift-card.php';        
          require plugin_dir_path( __FILE__ ) . 'includes/class-codup-gc-email.php';
          
        }

}

add_action( 'plugins_loaded', 'codup_gc_register_gift_card_product_type' );

/**
 * Add to product type drop down.
 */
function codup_add_gift_card_product( $types ){

	// Key should be exactly the same as in the class
	$types[ 'codup_gift_card' ] = __( 'Gift Card' );

	return $types;

}

add_filter( 'product_type_selector', 'codup_add_gift_card_product' );

/**
 * Check if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    
    require plugin_dir_path( __FILE__ ) . 'includes/class-codup-woocommerce-gift-card.php';
    Codup_WC_Gift_Card ::init();
    require plugin_dir_path(__FILE__). 'lib/codupads/codupads.php';
    new CodupAds();
}

if ( ! function_exists ( 'codup_gc_instance' ) ) {
    
    function codup_gc_instance () {
        return Codup_WC_Gift_Card::instance ();
    }
}

