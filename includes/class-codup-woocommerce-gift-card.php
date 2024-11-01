<?php

if (!defined('ABSPATH')) {
    exit;
}

class Codup_WC_Gift_Card {

    private static $instance;

    public static function init() {
        if (self::$instance == null) {
            self::$instance = new Codup_WC_Gift_Card();
        }
    }

    public static function instance() {

        if (self::$instance == null) {
            self::init();
        }

        return self::$instance;
    }

    public function __construct() {

        add_action('admin_notices',array($this, 'codup_gc_ads'));

        $this->codup_gc_load_dependencies();
        $this->codup_gc_define_admin_hooks();

        add_action('init', array($this, 'on_plugin_init'));
        add_action('woocommerce_codup_gift_card_add_to_cart', array($this, 'codup_gc_add_to_cart'), 30);
        /*
         * 
         */
        add_action('woocommerce_add_to_cart_handler_codup_gift_card', array($this, 'add_to_cart_handler'));
        /**
         * Recipient field for gift card
         */
        add_action('woocommerce_before_add_to_cart_button',  array($this, 'add_gift_card_fields'), 9);
        add_filter('woocommerce_get_item_data', array($this, 'show_gift_card_meta'), 99, 2);
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'add_gift_catd_fields_to_order_items'), 10, 4);
         /**
         * Validate gift card fields
         */
        add_filter('codup_gift_card_validation', array($this, 'validate_gift_card'));

        /**
         * price
         */
        add_action('woocommerce_before_calculate_totals', array($this, 'codup_gc_custom_price'));
        add_filter('woocommerce_add_cart_item_data', array($this, 'codup_gc_cart_item_price'), 10, 3);

        add_filter('woocommerce_hidden_order_itemmeta', array($this, 'codup_gc_hide_item_meta'));
        add_action('woocommerce_order_status_changed', array($this, 'codup_gc_generate_gift_card_number'), 10, 3);
        
        /**
         * Show gift card code in order edit.
         */
        add_action('woocommerce_order_item_meta_start', array($this, 'codup_gc_show_gift_card_code',), 10, 3);
        add_filter('woocommerce_get_shop_coupon_data', array($this, 'verify_coupon_code'), 10, 2);
        add_action('woocommerce_new_order_item', array($this, 'deduct_amount_from_gift_card_wc_3_plus'), 10, 3);
                
        /**
         * For gift card email template default path 
         */
        add_filter('woocommerce_email_classes', array($this, 'add_codup_gc_woocommerce_email'));
        add_filter('woocommerce_locate_core_template', array($this, 'locate_core_template'), 10, 3);

        /**
         * Sold individually
         * 
         */
        add_action('woocommerce_add_to_cart_handler_codup_gift_card', array($this, 'gift_card_add_to_cart_handler'), 10, 1);
        add_filter('woocommerce_add_to_cart_validation', array($this, 'gift_card_in_cart'), 99, 2);
        add_filter('woocommerce_available_payment_gateways', array($this, 'conditional_payment_gateways'), 10, 1);
        
    }

    public function codup_gc_ads(){

        $post_type=isset($_GET['post_type']) ? $_GET['post_type'] : '';
        if( $post_type == 'codup_gift_card' ) {
            do_shortcode("[codup_ads_top]");
        }
    }

    public function codup_gc_load_dependencies() {
        require_once CODUP_GC_DIR . '/admin/class-admin-gift-card.php';
        
    }

    private function codup_gc_define_admin_hooks() {
        $gc_admin = new Codup_GC_Admin();
    }

    public function on_plugin_init() {
        $this->init_post_type();
    }

    public function init_post_type() {
        
        $labels = array( 
            'name' => _x( 'Codup Gift Cards', 'gallery' ),
            'singular_name' => _x( 'Codup Gift Card', 'gallery' ),
            'add_new' => _x( 'Add New', 'gallery' ),
            'add_new_item' => _x( 'Add New Codup Gift Cards', 'gallery' ),
            'edit_item' => _x( 'Edit Codup Gift Card', 'gallery' ),
            'new_item' => _x( 'New Codup Gift Card', 'gallery' ),
            'view_item' => _x( 'View Codup Gift Card', 'gallery' ),
            'search_items' => _x( 'Search Codup Gift Cards', 'gallery' ),
            'not_found' => _x( 'No Codup Gift Card found', 'gallery' ),
            'not_found_in_trash' => _x( 'No Codup Gift Cards found in Trash', 'gallery' ),
            'parent_item_colon' => _x( 'Parent Codup Gift Card:', 'gallery' ),
            'menu_name' => _x( 'Codup Gift Cards', 'gallery' ),
        );
        $args = array(

            'labels' => $labels,
            'description' => 'Codup Gift Cards',
            'edit_item' => __('Edit Codup Gift Card'),
            'supports' => array(
                'title',
            ),
            'hierarchical' => false,
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => false,
            'show_in_admin_bar' => false,
            'menu_position' => 9,
            'can_export' => false,
            'has_archive' => false,
            'exclude_from_search' => true,
            'menu_icon' => 'dashicons-clipboard',
            'query_var' => false,
        );

        // Registering your Gift card post type
        register_post_type('codup_gift_card', $args);
    }

    /**
     * Codup gift card's add-to-cart handler.
     */
    public function add_to_cart_handler() {

        $product_id = absint($_REQUEST['add-to-cart']);
        $new_gift_card = new Codup_Gift_Card();
        $new_gift_card->product_id = $product_id;
        $new_gift_card->set_amount(10);
        WC()->cart->add_to_cart($product_id, 1, 0, array(), (array) $new_gift_card);

        //wc_add_to_cart_message(array($product_id => 1), true);
        return true;
    }

    /**
     * Update product's price to user entered price.
     * @param object $cart_object
     */
    public function codup_gc_custom_price($cart_object) {

        foreach ($cart_object->cart_contents as $key => $value) {

            if ($value['codup-gc-price']) {
                $value['data']->set_price($value['codup-gc-price']);
            }
        }
    }

    public function codup_gc_cart_item_price($cart_item_meta, $product_id, $variation_id) {

        global $woocommerce;

        if (isset($_POST['codup-gc-price'])) {
            $cart_item_meta['codup-gc-price'] = $_POST['codup-gc-price'];
        } else if (isset($_POST['gift_card_amount'])) {

            $cart_item_meta['codup-gc-price'] = $_POST['gift_card_amount'];
        }
        if (isset($_POST['recipient_name'])) {
            $cart_item_meta['recipient_name'] = $_POST['recipient_name'];
        }
        if (isset($_POST['recipient_email'])) {
            $cart_item_meta['recipient_email'] = $_POST['recipient_email'];
        }
        if (isset($_POST['recipient_message'])) {
            $cart_item_meta['recipient_message'] = trim($_POST['recipient_message']);
        }
        $cart_item_meta['codup-gc-amount'] = $cart_item_meta['codup-gc-price'];
        
        return $cart_item_meta;
    }

  

    public function codup_gc_add_to_cart() {
        global $product;
        wc_get_template('single-product/add-to-cart/codup_gift_card.php', '', '', trailingslashit(CODUP_GC_TEMPLATES_DIR));
    }

    /*
     * Display input on single product page
     * @return html
     */

    function add_gift_card_fields() {
        global  $product;
        if($product->get_type() == 'codup_gift_card'){
            include(CODUP_GC_TEMPLATES_DIR . '/gift-card-fields.php');
        }
    }

    
    public function codup_gc_show_gift_card_code($order_item_id, $item, $order) {

        $code = wc_get_order_item_meta($order_item_id, '_codup_gift_card_number');

        if (!empty($code)) {
            printf('<br>' . __('Gift card code: %s'), $code);
        }
    }

    public function codup_gc_hide_item_meta($args){
        $args[] = 'gift_card_post_id';
        $args[] = 'gift_card_amount';
        return $args;
    }

    public function codup_gc_generate_gift_card_number($order, $old_status, $new_status) {

        if (is_numeric($order)) {
            $order = wc_get_order($order);
        }
        $allowed_status = array('processing');

        if (!in_array($new_status, $allowed_status)) {
            return;
        }

        foreach ($order->get_items('line_item') as $order_item_id => $order_item_data) {

            $product_id = $order_item_data["product_id"];
            //$amount = $order_item_data["line_total"] + $order_item_data["line_tax"];

            $prev_code = wc_get_order_item_meta($order_item_id, '_codup_gift_card_number');
            $amount= wc_get_order_item_meta($order_item_id, '_codup_gc_amount',true);
            if (!empty($prev_code)) {
                continue;
            }


            $product = wc_get_product($product_id);


            if (!( $product instanceof WC_Product_Codup_Gift_Card )) {
                continue;
            }
            $is_gift_card = true;
            /*
             * Order item_Id of gift card
             */
            update_post_meta($order->ID, 'gift_card_item', $order_item_id);
            $gift_card = codup_gc_instance()->codup_gc_create_gift_card($product_id, $order->ID, $amount);

            wc_update_order_item_meta($order_item_id, '_codup_gift_card_number', $gift_card->gift_card_number);
            wc_update_order_item_meta($order_item_id, '_codup_gift_card_post_id', $gift_card->ID);
        }

        if ($is_gift_card) {
            do_action('codup_gift_card_order_paid', $order->id, $order);
        }

        //$this->codup_gc_order_completed($order->id);
    }

    public function codup_gc_create_gift_card($product_id, $order_id, $amount) {

        $gift_card = new Codup_Gift_Card ();
        $gift_card->generate_gift_card_code();
        $gift_card->set_amount($amount);
        $gift_card->product_id = $product_id;
        $gift_card->order_id = $order_id;
        $gift_card->expiration_date = get_expiry_date($product_id);

        $gift_card->save();

        return $gift_card;
    }

    public function deduct_amount_from_gift_card($id, $item_id, $code, $discount_amount, $discount_amount_tax) {
        $gift = codup_gc_instance()->get_gift_card_by_code($code);
        if ($gift != null) {
            $gift->deduct_amount($discount_amount + $discount_amount_tax);
        }
    }

    public function deduct_amount_from_gift_card_wc_3_plus($item_id, $item, $order_id) {
        if ($item instanceof WC_Order_Item_Coupon) {
            $this->deduct_amount_from_gift_card($item->get_id(), $item_id, $item->get_code(), $item->get_discount(), $item->get_discount_tax());
        }
    }

    public function verify_coupon_code($return_val, $code) {

        $gift_card = codup_gc_instance()->get_gift_card_by_code($code);

        if ( null == $gift_card ) {
            return $return_val;
        }

        if ($gift_card->ID) {
            if (!$gift_card->is_enabled()) {
                add_filter('woocommerce_coupon_error', array($this, 'gift_card_not_exist'), 10, 3);
                return $return_val;
            }
            if ($gift_card->is_expired()) {
                add_filter('woocommerce_coupon_error', array($this, 'gift_card_limit_reached'), 10, 3);
                return false;
            }

            $temp_coupon_array = array(
                'discount_type' => 'fixed_cart',
                'coupon_amount' => $gift_card->get_amount(),
                'amount' => $gift_card->get_amount(),
                'id' => true,
            );

            return $temp_coupon_array;
        }

        return $return_val;
    }

    public function get_gift_card_by_code($code) {
        $object = get_page_by_title($code, OBJECT, 'codup_gift_card');
        if (null == $object) {
            return null;
        }
        $args = array('gift_card_number' => $code);
        return new Codup_Gift_Card($object);
    }

    public function gift_card_not_exist($err, $err_code, $instance) {
        switch ($err_code) {
            case $instance::E_WC_COUPON_NOT_EXIST:
                $err = sprintf(__('Coupon "%s" does not exist!', 'codup-gift-card'), $instance->get_code());
                break;
        }
        return $err;
    }

    public function gift_card_limit_reached($err, $err_code, $instance) {
        switch ($err_code) {
            case $instance::E_WC_COUPON_NOT_EXIST:
                $err = __('This coupon has expired.', 'codup-gift-card');
                break;
        }
        return $err;
    }

    public function add_codup_gc_woocommerce_email($email_classes) {

        $email_classes['Codup_GC_Email'] = new Codup_GC_Email();
        return $email_classes;
    }

    /**
     * Locate the plugin email templates
     *
     * @param $core_file
     * @param $template
     * @param $template_base
     *
     * @return string
     */
    public function locate_core_template($core_file, $template, $template_base) {

        $custom_template = array(
            'emails/gift-card.php',
         //   'emails/plain/send-gift-card.php',
        );

        if (in_array($template, $custom_template)) {
            $core_file = trailingslashit(CODUP_GC_TEMPLATES_DIR) . $template;
        }

        return $core_file;
    }

    /*
     * Remove any other product from cart if adding Gift Card.
     */

    public function gift_card_add_to_cart_handler() {
        $product_id = apply_filters('woocommerce_add_to_cart_product_id', absint($_REQUEST['add-to-cart']));
        $_product = wc_get_product($product_id);
        $passed_validation = apply_filters('woocommerce_add_to_cart_validation', true, $product_id, 1);
        $passed_validation = apply_filters('codup_gift_card_validation', true, $_product);

        if ($_product->get_type() == 'codup_gift_card') {
            WC()->cart->empty_cart();
        }

        if ($passed_validation && false !== WC()->cart->add_to_cart($product_id, 1)) {
            wc_add_to_cart_message(array($product_id => 1), true);
            return $passed_validation;
        }
        return $passed_validation;
    }

    public function gift_card_in_cart($passed, $added_product_id) {

        if (gift_card_in_cart()) {
            WC()->cart->empty_cart();
        }
        return $passed;
    }

    public function validate_gift_card($passed) {


        if (isset($_POST['recipient_name']) && empty($_POST['recipient_name'])) {
            wc_add_notice(__('<b>Recipient name </b>is a required field.'), 'error');
            $passed = false;
        }

        if (isset($_POST['recipient_email']) && empty($_POST['recipient_email'])) {
            wc_add_notice(__('<b>Recipient Email </b>is a required field.'), 'error');
            $passed = false;
        } else if (!is_email($_POST['recipient_email']) && !empty($_POST['recipient_email'])) {

            wc_add_notice(__('<b>Recipient Email </b>is not a valid email address.'), 'error');
            $passed = false;
        }
        return $passed;
    }

    function show_gift_card_meta($item_data, $cart_item) {
        $gc_items = array();

        if (!empty($item_data)) {
            $gc_items = $item_data;
        }
        if ($custom_field_value = $cart_item['recipient_name']) {
            $gc_items[] = array(
                'key' => 'recipient_name',
                'name' => __('Recipient Name', 'codup-gift-card'),
                'recipient_name' => $custom_field_value,
                'display' => $custom_field_value,
            );
        }
        if ($custom_field_value = $cart_item['recipient_email']) {
            $gc_items[] = array(
                'key' => 'recipient_email',
                'name' => __('Recipient Email', 'codup-gift-card'),
                'recipient_email' => $custom_field_value,
                'display' => $custom_field_value,
            );
        }
        if ($custom_field_value = $cart_item['recipient_message']) {
            $gc_items[] = array(
                'key' => 'recipient_message',
                'name' => __('Message', 'codup-gift-card'),
                'message' => $custom_field_value,
                'display' => $custom_field_value,
            );
        }


        return $gc_items;
    }

    function add_gift_catd_fields_to_order_items($item, $cart_item_key, $values, $order) {
        if ($values['recipient_name']) {
            $item->add_meta_data('_codup_gc_recipient_name', sanitize_text_field($values['recipient_name']));
        }
        if ($values['recipient_email']) {
            $item->add_meta_data('_codup_gc_recipient_email', sanitize_text_field($values['recipient_email']));
        }
        if ($values['recipient_message']) {
            $item->add_meta_data('_codup_gc_recipient_message', $values['recipient_message']);
        }
        if ($values['codup-gc-amount']) {
            $item->add_meta_data('_codup_gc_amount', $values['codup-gc-amount']);
        }
    }
    /**
     * Remove COD if gift card is in cart.
     * @param array $available_gateways
     * @return array
     */
    function conditional_payment_gateways($available_gateways){
        if( ! is_admin() ) { 
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item ) {     
            $product = wc_get_product($cart_item['product_id']);
        
            if($product->is_type('codup_gift_card')) {
                $gift_card = true;
            }
        }
        if($gift_card){
            unset($available_gateways['cod']);
        }
        return $available_gateways;
    }
}

}
