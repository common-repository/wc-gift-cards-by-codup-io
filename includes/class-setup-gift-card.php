<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Codup_WC_Gift_Card {
    
    private static $instance;
   
    public static function init() {
        
        if ( self::$instance == null ) {
            self::$instance = new Codup_WC_Gift_Card();
        }
    }

    public static function instance() {
       
        if ( self::$instance == null ) {
            self::init();
        }
        return self::$instance;
    }

    public function __construct() { 
            
        $this->codup_gc_load_dependencies();
        $this->codup_gc_define_admin_hooks();
        
        add_action( 'init', array( $this, 'on_plugin_init' ) );
        add_action ( 'woocommerce_codup_gift_card_add_to_cart', array( $this, 'codup_gc_add_to_cart' ), 30 );
        add_action ( 'woocommerce_add_to_cart_handler_codup_gift_card', array( $this, 'add_to_cart_handler' ) );
        
       
        
        add_action( 'woocommerce_before_calculate_totals', array($this, 'codup_gc_custom_price' ));
        add_filter( 'woocommerce_add_cart_item_data', array($this, 'codup_gc_cart_item_price' ),10,3 );
        add_action( 'woocommerce_after_order_notes', array($this, 'codup_gc_checkout_fields') );
        add_action( 'woocommerce_checkout_process', array($this, 'codup_gc_verify_recepient_details'));
        
        add_filter( 'woocommerce_hidden_order_itemmeta', array( $this, 'codup_gc_hide_item_meta' ) );
        add_action( 'woocommerce_order_status_changed', array( $this, 'codup_gc_generate_gift_card_number' ), 10, 3 );
        add_action( 'woocommerce_order_item_meta_start', array($this, 'codup_gc_show_gift_card_code',), 10, 3 );
        
        add_action( 'woocommerce_checkout_update_order_meta', array($this, 'codup_gc_checkout_field_update_order_meta') );
            
        /**
        * Display field value on the order edit page
        */
        add_action( 'woocommerce_admin_order_data_after_billing_address', array($this, 'codup_gc_checkout_field_display_admin_order_meta')  );
        add_action( 'woocommerce_order_status_processing', array($this, 'codup_gc_order_completed') );
        add_filter( 'woocommerce_get_shop_coupon_data', array( $this, 'verify_coupon_code' ), 10, 2 );
	    add_action( 'woocommerce_new_order_item', array( $this, 'deduct_amount_from_gift_card_wc_3_plus' ), 10, 3 );
            
    }
    
    public function codup_gc_load_dependencies(){
        print_r('Die');die();

        require_once CODUP_GC_DIR. '/admin/class-admin-gift-card.php';
        require_once CODUP_GC_DIR . '/lib/codupads/codupads.php';   
    }
    
    private function codup_gc_define_admin_hooks() {
       $gc_admin = new Codup_GC_Admin();
    }
        
    public function on_plugin_init() {
		$this->init_post_type();
    }
                
    public function init_post_type() {
        $args = array(
            'label'               =>'Codup Gift Cards',
            'description'         => 'Codup Gift Cards',
            'supports'            => array(
                    'title',
            ),
            'hierarchical'        => false,
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_nav_menus'   => false,
            'show_in_admin_bar'   => false,
            'menu_position'       => 9,
            'can_export'          => false,
            'has_archive'         => false,
            'exclude_from_search' => true,
            'menu_icon'           => 'dashicons-clipboard',
            'query_var'           => false,
        );
        // Registering your Gift card post type
        register_post_type( 'codup_gift_card', $args );
    }

    /**
     * Codup gift card's add-to-cart handler.
     */
    public function add_to_cart_handler() {
        
        $product_id = absint ( $_REQUEST['add-to-cart'] );
        $new_gift_card             = new Codup_Gift_Card();
        $new_gift_card->product_id = $product_id;
        $new_gift_card->set_amount( 10 );
        WC ()->cart->add_to_cart ( $product_id , 1 , 0, array(), (array) $new_gift_card  );
        
        wc_add_to_cart_message ( array($product_id => 1 ), true );
        return true;
    }
    
    /**
     * Update product's price to user entered price.
     * @param object $cart_object
     */
    public function codup_gc_custom_price( $cart_object ) {
        foreach ( $cart_object->cart_contents as $key => $value ) {
            if( $value['codup-gc-price']){
                $value['data']->set_price( $value['codup-gc-price'] );
            }
        }
    }
    
    public function codup_gc_cart_item_price( $cart_item_meta, $product_id, $variation_id ){
        global $woocommerce;

        if(isset($_POST['codup-gc-price'])){
            $cart_item_meta['codup-gc-price'] = $_POST['codup-gc-price'];
        }
        else if(isset($_POST['gift_card_amount'])){    
            $cart_item_meta['codup-gc-price'] = $_POST['gift_card_amount'];
        }

        return $cart_item_meta; 
    }	         
    
    /**
     *  Add recipient fields on checkout. 
     * @param array $checkout
     */
    public function codup_gc_checkout_fields( $checkout ) {

        $has_gift_card =false;
        foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
            $product = $cart_item['data'];

            if ( ( $product instanceof WC_Product_Codup_Gift_Card ) ) {
                $has_gift_card = true;               
            }
        }
        
       if( $has_gift_card ) {
            echo '<div id="codup_gc_checkout_fields"><h2>' . __('Recipient Details') . '</h2>';

            woocommerce_form_field( 'recipient_name', array(
                'type'          => 'text',
                'class'         => array('my-field-class form-row-wide'),
                'label'         => 'Name',
                'required'      => true,
                ), $checkout->get_value( 'recipient_name' ));

            woocommerce_form_field( 'recipient_email', array(
                'type'          => 'email',
                'required'      => true,
                'class'         => array('my-field-class form-row-wide'),
                'label'         => 'Email',
            ), $checkout->get_value( 'recipient_email' ));

            woocommerce_form_field( 'recipient_message', array(
                'type'          => 'textarea',
                'label'         => 'Message',
                'placeholder'   => 'Be precise, you only have 500 characters.',
                'custom_attributes'=> array( 'maxlength' => 500 ),
            ), $checkout->get_value( 'recipient_message' ));

            echo '</div>';
       }
    }

    public function codup_gc_verify_recepient_details() {
       $validate = 0;
       
       if(isset($_POST['recipient_name']) && empty($_POST['recipient_name'])){
            wc_add_notice(__( '<b>Recipient name </b>is a required field.' ) ,error );
       }
       
       if(isset($_POST['recipient_email']) && empty($_POST['recipient_email'])){
            wc_add_notice(__( '<b>Recipient Email </b>is a required field.' ) ,error );
            
       }
       else if(!is_email($_POST['recipient_email']) && !empty($_POST['recipient_email'])){
            wc_add_notice(__( '<b>Recipient Email </b>is not a valid email address.' ) ,error );
       }
           
    }

    public function codup_gc_add_to_cart() {
        global $product; 
        wc_get_template ( 'single-product/add-to-cart/codup_gift_card.php', '', '', trailingslashit ( CODUP_GC_TEMPLATES_DIR ) );
    }  
    
    public function codup_gc_checkout_field_update_order_meta( $order_id ) {
   
        update_post_meta( $order_id, '_codup_gc_recipient_name', sanitize_text_field( $_POST['recipient_name'] ) );
        update_post_meta( $order_id, '_codup_gc_recipient_email', sanitize_text_field( $_POST['recipient_email'] ) );
        
        if ( ! empty( $_POST['recipient_message'] ) ) {
            update_post_meta( $order_id, '_codup_gc_recipient_message', sanitize_text_field( $_POST['recipient_message'] ) );
        }
    }

    public function codup_gc_checkout_field_display_admin_order_meta($order) {
    
        $html .= '<p><strong>Reciepient name: </strong><br>'. get_post_meta( $order->id, '_codup_gc_recipient_name', true ) ;
        $html .= '</p><p><strong>Recipient Email: </strong><br>'.get_post_meta( $order->id, '_codup_gc_recipient_email', true );
        $html .= '</p><p><strong>Message:</strong><br>'.get_post_meta( $order->id, '_codup_gc_recipient_message', true );
        $html .= '</p>';
        echo $html;
    }

    public function codup_gc_order_completed( $order_id ) {
        
        $site     =  '<a href="'.site_url().'">'.site_url().'</a>';
        $order    =  new WC_Order( $order_id );
        $customer =  new WC_Customer( $order->get_user_id());
        $fname    =  $order->billing_first_name;
        $lname    =  $order->billing_last_name;       
        $name     =  get_post_meta( $order_id, '_codup_gc_recipient_name', true ) ;
        $to_email =  get_post_meta( $order_id, '_codup_gc_recipient_email', true );
        $r_message=  get_post_meta( $order_id, '_codup_gc_recipient_message', true );
        
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[]  = "From: ".get_bloginfo('name')." <".get_option('admin_email').">"."\r\n";
        $items = $order->get_items();

        foreach ( $items as $item_id => $item_data) {

            $product_id = $item_data["product_id"];
            $product     = wc_get_product($product_id );

            if (  ( $product instanceof WC_Product_Codup_Gift_Card ) ) {

                $amount = wc_price ( (float) $item_data['line_total'] );
                $card_number = wc_get_order_item_meta($item_id, '_codup_gift_card_number', true) ;
                $email_heading = 'Gift Card'; 
                
                ob_start() ; 
                $message = WC_Emails::email_header( $email_heading );   
                
                $featured_image= wp_get_attachment_url( get_post_thumbnail_id($product_id));
                
                if($featured_image){
                    $message .= '<img src="'.$featured_image.'" style="width:100%" />';
                }
                
                $message .="<div><p> Dear <strong>$name</strong>,</p><p> Hope you're doing well, This email is to inform that  <strong> $fname $lname  </strong> have sent you the gift worth of  $amount  via  $site  </p>";
                if( $r_message ){
                    $message .="$fname $lname also added following message for you:<p><blockquote>&ldquo; $r_message &rdquo;</blockquote></p>";
                }
                $message .="<p> Go ahead and claim your gift by entering the code : $card_number  </p></div>";
                echo $message;

                $footer = WC_Emails::email_footer(); 
                echo '<style type="text/css">';
                echo     wc_get_template( 'emails/email-styles.php');
                echo    '</style>';
                $message = ob_get_contents();
                
                ob_end_clean();

                wp_mail($to_email, "Gift card from $fname $lname ", $message,  $headers);

            }
        }
    }

    public function codup_gc_show_gift_card_code( $order_item_id, $item, $order ) {
        
        $code = wc_get_order_item_meta( $order_item_id, '_codup_gift_card_number' );
        if ( ! empty( $code ) ) {
            printf( '<br>' . __( 'Gift card code: %s' ), $code );
        }
    }

    public function codup_gc_hide_item_meta( $args ) {
        
        $args[] = 'gift_card_post_id';
        return $args;
    }

    public function codup_gc_generate_gift_card_number( $order, $old_status, $new_status ) {
			
        if ( is_numeric( $order ) ) {
            $order = wc_get_order( $order );
        }

        $allowed_status = array( 'processing' ) ;

        if ( ! in_array( $new_status, $allowed_status ) ) {
            return;
        }

        foreach ( $order->get_items( 'line_item' ) as $order_item_id => $order_item_data ) {

            $product_id = $order_item_data["product_id"];
            $amount     = $order_item_data["line_total"] + $order_item_data["line_tax"];

            $prev_code = wc_get_order_item_meta( $order_item_id, '_codup_gift_card_number' );
            if ( ! empty( $prev_code ) ) {
                continue;
            }

            $product = wc_get_product( $product_id );

            if ( ! ( $product instanceof WC_Product_Codup_Gift_Card ) ) {
                continue;
            }

            $gift_card = codup_gc_instance()->codup_gc_create_gift_card( $product_id,$order->ID, $amount );
            
            wc_update_order_item_meta( $order_item_id, '_codup_gift_card_number', $gift_card->gift_card_number );
            wc_update_order_item_meta( $order_item_id, '_codup_gift_card_post_id', $gift_card->ID );

        }
        $this->codup_gc_order_completed($order->id );
    }

    public function codup_gc_create_gift_card( $product_id,$order_id, $amount ) {

        $gift_card = new Codup_Gift_Card ();
        $gift_card->generate_gift_card_code();
        $gift_card->set_amount( $amount );
        $gift_card->product_id = $product_id;
        $gift_card->order_id   = $order_id;

        $gift_card->save();

        return $gift_card;
    }

    public function deduct_amount_from_gift_card( $id, $item_id, $code, $discount_amount, $discount_amount_tax ) {
        
        $gift = codup_gc_instance()->get_gift_card_by_code( $code );
        if ( $gift != null ) {
            $gift->deduct_amount( $discount_amount + $discount_amount_tax );
        }
    }
		
    public function deduct_amount_from_gift_card_wc_3_plus( $item_id, $item, $order_id ) {
        
        if ( $item instanceof WC_Order_Item_Coupon ) {
            $this->deduct_amount_from_gift_card( $item->get_id(), $item_id, $item->get_code(), $item->get_discount(), $item->get_discount_tax() );
        }
    }

    public function verify_coupon_code( $return_val, $code ) {
                        
    	$gift_card = codup_gc_instance()->get_gift_card_by_code( $code );
			
    	if ( null == $gift_card ) {
    		return $return_val;
    	}
			
    	if ( $gift_card->ID ) {
    		$temp_coupon_array = array(
    			'discount_type' => 'fixed_cart',
    			'coupon_amount' => $gift_card->get_amount(),
    			'amount'        => $gift_card->get_amount(),
    			'id'            => true,
    		);
    		
    		return $temp_coupon_array;
    	}
			
		return $return_val;
	}

    public function get_gift_card_by_code( $code ) {
            
        $object = get_page_by_title( $code, OBJECT, 'codup_gift_card' );
        if ( null == $object ) {
            return null;
        }
        $args = array( 'gift_card_number' => $code );

        return new Codup_Gift_Card( $object );
    }
}
