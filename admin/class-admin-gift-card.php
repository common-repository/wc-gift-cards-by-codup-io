<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Codup_GC_Admin {
    
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

            /**
             * Add product type gift card.
             */
            
            add_action ( 'admin_enqueue_scripts', array( $this, 'enqueue_backend_files' ) );
            
            /**
             * Show gift card balance .
             */
            add_filter(  'manage_codup_gift_card_posts_columns', array( $this, 'gc_table_head' ) );
            add_action(  'manage_codup_gift_card_posts_custom_column', array( $this,'gc_table_content' ), 10, 2 );
            add_action( 'load-post.php',     array( $this, 'init_metabox' ) );
            add_action( 'load-post-new.php', array( $this, 'init_metabox' ) );
            
            /**
             * Remove quick edit.
             */
            add_action('admin_menu', array( $this, 'disable_new_posts') );
            add_filter( 'post_row_actions', array( $this, 'remove_row_quick_edit'), 10, 2 );
            add_filter( 'page_row_actions', array( $this, 'remove_row_quick_edit'), 10, 2 );
         
            /**
             * Add gift card setting.
             */
            add_action ( 'woocommerce_product_options_general_product_data', array( $this, 'codup_gc_show_gift_card_product_content' ) );
            
            /**
             * Save gift card setting.
             */
            add_action ( 'woocommerce_process_product_meta', array( $this, 'codup_gc_save_gift_card' ) );
            
            /**
             * Ajax callback to add new gift card amount.
             */
            add_action ( 'wp_ajax_codup_add_gift_card_amount', array( $this, 'codup_gc_add_amount_callback' ) );
            add_action( 'wp_ajax_codup_remove_gift_card_amount', array( $this, 'remove_gift_card_amount_callback' ) );
            add_action ( 'wp_ajax_codup_add_gift_card_range', array( $this, 'add_gift_card_range' ) );

            add_filter( 'woocommerce_product_data_tabs', array( $this, 'hide_shipping_tab') );
            add_filter( 'rwmb_meta_boxes', array( $this, 'prefix_register_meta_boxes') );
    
    }
    
    /**
     * Enqueue assets in wp-admin.
     * @param type $hook
     */
    
    public function enqueue_backend_files( $hook ) {

            wp_enqueue_style ( 'wcgc-admin-css', CODUP_GC_ASSETS_URL . '/css/wcgc-admin.css' );

            wp_register_script ( "wcgc-admin",CODUP_GC_URL . 'assets/js/wcgc-admin.js' ,
                                    array(
                                            'jquery',
                                            'jquery-blockui',
                                    ));

            wp_localize_script ( 'wcgc-admin', 'wcgc_vars', array(
                                    'loader'   => apply_filters ( 'yith_questions_and_answers_loader', CODUP_GC_ASSETS_URL  . '/images/loading.gif' ),
                                    'ajax_url' => admin_url ( 'admin-ajax.php' ),
            ) );
            
            wp_enqueue_script ( "wcgc-admin" );

    }
    /**
     * Add metabox to show giftcard data.
     */
    public function init_metabox() {

            add_action( 'add_meta_boxes', array( $this, 'add_metabox'  )        );
            add_action( 'save_post', array( $this, 'save_metabox' ), 10, 2 );

    }
    public function add_metabox() {

            add_meta_box(
                    'codup_gc_info',
                    __( 'Gift Card Details', 'codup-gift-card' ),
                    array( $this, 'render_metabox' ),
                    'codup_gift_card',
                    'advanced',
                    'default'
            );

	}
    public function render_metabox( $post ){
            wp_nonce_field( 'codup_gift_card_nonce_action', 'cgc_nonce' );
            $gc  = new Codup_Gift_Card($post->ID);
            $balance     = get_post_meta( $post->ID, '_codup_gift_card_amount', true );
            $order_id    = get_post_meta( $post->ID, '_codup_gift_card_order_id', true);
            $status      = ($gc->is_enabled() && !$gc->is_expired()) ?'Active': 'Expired';
            $order       = new WC_Order( $order_id );
            $sender_name = $order->billing_first_name.' '.$order->billing_last_name;
           
            if ( $order->get_user_id( 'edit' ) ) {
                $sender_name =  sprintf( '<a href="%s">%s</a>',esc_url( add_query_arg( 'user_id', $order->get_user_id( 'edit' ), admin_url( 'user-edit.php' ) ) ),$sender_name);
            
            }
            $gc_details  = get_recipient_details($order);
            $expiration_date = get_post_meta( $post->ID, '_codup_gift_card_expiration_date', true );
            $Initial_amount  = get_initial_amount($post->ID);

            //   Set default values.
            if( empty( $balance ) ){ $balance = '';}
            if( empty( $sender_name ) ){ $sender_name = '';}
            if( empty( $expiration_date ) ){ $expiration_date = '';}
            if( empty($gc_details) ){
                $gc_details =array(
                    'name' => '',
                    'email'=> '',
                    'message' => ''

                );
            }


            include CODUP_GC_TEMPLATES_DIR.'/gift-card-meta.php';
    }
    
    /**
     * Save gift card data
     * @param string|int $post_id
     * @param object $post
     */
    public function save_metabox( $post_id, $post ) {

            $expiration_date = isset( $_POST[ 'expiration_date' ] ) ? sanitize_text_field( $_POST[ 'expiration_date' ] ) : '';
            update_post_meta( $post_id, '_codup_gift_card_expiration_date', $expiration_date );
//            $amount = isset( $_POST[ 'balance' ] ) ? $_POST[ 'balance' ] : 0;
//            update_post_meta( $post_id, '_codup_gift_card_amount', $amount );

    }
    
    /**
     * Display gift card setting on Add/edit product.
     * @global int $thepostid
     */
    
    public function codup_gc_show_gift_card_product_content() {
            
            global $thepostid;
            $amount_type = get_post_meta($thepostid, '_codup_gc_amount_type', true);
            $has_expiry  = get_post_meta( $thepostid, '_codup_gc_has_expiry', true );
            $expiry      = get_post_meta( $thepostid, '_codup_gc_expiry', true );
            
            if(!$amount_type){
                $amount_type = 'preset';
            }
            require CODUP_GC_DIR.'/templates/wcgc-gift-card-settings.php' ;
        
    }
    
    
    
    /**
     * Save gift card setting.
     * @param object $post_id
     */
    
    public function codup_gc_save_gift_card($post_id) {
        
            $product = wc_get_product ( $post_id);
            

            if(isset($_POST['_codup_gc_amount_type'])){
              $response =   update_post_meta( $post_id, '_codup_gc_amount_type', $_POST['_codup_gc_amount_type'] );
            }
            if(isset($_POST['_codup_gc_minimum'])){

                update_post_meta( $post_id, '_codup_gc_minimum', $_POST['_codup_gc_minimum'] );
            }
            if(isset($_POST['_codup_gc_maximum']) && $_POST['_codup_gc_maximum'] > $_POST['_codup_gc_minimum']){
                update_post_meta( $post_id, '_codup_gc_maximum', $_POST['_codup_gc_maximum'] );
            }

            if(isset($_POST['codup-gc-expires']) ){
                $has_expiry =($_POST['codup-gc-expires'] == 'on' )? 'yes' : 'no';         
                update_post_meta( $post_id, '_codup_gc_has_expiry', $has_expiry  );
                update_post_meta( $post_id, '_codup_gc_expiry', $_POST['codup-gc-exp-days'] );
                
            }else{           
                update_post_meta( $post_id, '_codup_gc_has_expiry', 'no' );
            }
        
	
    }
    
    /**
     * Add new gift card value and display list of amount
     */
    
    public function codup_gc_add_amount_callback() {
            
            $amount = $_POST['amount'];

            $product_id =  $_POST['product_id'] ;
            $card_amounts = get_post_meta( $product_id, '_codup_gc_amount', true);
            if(is_numeric($_POST['amount'])){
                $message =  'Duplicate amount.';
                $response = false;
                if( !is_array($card_amounts) ) {
                    $card_amounts =array ();
                }
                if(!in_array($amount, $card_amounts)){
                        $message = null;
                        $response = true;
                        $card_amounts[] = $amount;
                        sort ( $card_amounts, SORT_NUMERIC );
                        update_post_meta( $product_id, '_codup_gc_amount' , $card_amounts);
                        
                }
                
            }else{
                $response = false;
                $message  = 'Not a valid number.';
            }
            ob_start ();
            $this->codup_gc_show_gift_card_amount_list ( $product_id );
            $html = ob_get_contents ();
	    ob_end_clean ();
            update_post_meta( $product_id, '_codup_gc_amount_type', 'preset' );
            wp_send_json ( array( "card_amounts" => $html , 'success' => $response , 'message' => $message ) );
    }
    
    public function remove_gift_card_amount_callback(){
        $amount     = $_POST['amount'];
        $product_id = $_POST['product_id'];
       
        $card_amounts = get_post_meta( $product_id, '_codup_gc_amount', true);
        $key = array_search ($amount, $card_amounts);
        
        unset($card_amounts[$key]);
        $res = update_post_meta( $product_id, '_codup_gc_amount' , $card_amounts);
         update_post_meta( $product_id, '_codup_gc_amount_type', 'preset' );
        wp_send_json( array( "code" => $res ) );
        
    }

        /**
     * Get all preset amounts for a gift card.
     * @param int $product_id
     */
    
    private function codup_gc_show_gift_card_amount_list( $product_id ) {

            $amounts = get_post_meta( $product_id, '_codup_gc_amount',true);
            ?>
            <p class="form-field _gift_card_amount_field">

                    <?php if ( $amounts ): ?>
                            <?php foreach ( $amounts as $amount ) : ?>
                                    <span class="variation-amount"><?php echo wc_price ( filter_var($amount, FILTER_SANITIZE_NUMBER_INT) ); ?>
                                            <input type="hidden" name="gift-card-amounts[]" value="<?php _e ( $amount ); ?>">
                                            <a href="#" class="gc-remove-amount"></a>
                                    </span>
                            <?php endforeach; ?>
                  
                    <?php endif; ?>
            </p>        
            <?php
    }
    
    /**
     * Hide shipping tab when product gift card is selected .
     * @param array $product_data_tabs
     * @return string
     */
    public function hide_shipping_tab( $product_data_tabs ) {
        
            $product_data_tabs['shipping']['class'][]= 'hide_if_codup_gift_card';  
            $product_data_tabs['attribute']['class'][]= 'hide_if_codup_gift_card';  
            return $product_data_tabs;
    }
    
    /**
     * Add Column for remaining balance of gift card.
     * @param array $columns
     * @return string
     */
    public function gc_table_head( $columns ) {

        $columns['status']  = 'Gift card Status';
        $columns['original']  = 'Original Balance';
        $columns['balance']   = 'Remaining Balance';

        return $columns;

    }

    /**
     * Show balance of gift card.
     * @param array $column_name
     * @param int|string $post_id
     */
    public function gc_table_content( $column_name, $post_id ) {

        if( $column_name == 'balance' ) {
            $amount = get_post_meta( $post_id, '_codup_gift_card_amount', true );
               echo wc_price((float) $amount);
        }
        
        if( $column_name == 'original' ){
            echo get_initial_amount($post_id, true);
        }
        
        if( $column_name == 'status' ){
            $gc  = new Codup_Gift_Card($post_id);
            echo  ($gc->is_enabled() && !$gc->is_expired()) ?'Active': 'Expired';
        }
    }
    
    public function add_gift_card_range(){
        
        $product_id = $_POST['product_id'];
       
        $res = update_post_meta( $product_id, '_codup_gc_amount_type', 'userdefined' );

        $res = update_post_meta( $product_id, '_codup_gc_minimum', $_POST['min'] );

        $res = update_post_meta( $product_id, '_codup_gc_maximum', $_POST['max'] );
        
        wp_send_json(array( "code" => $res ));
        
    }
    
    /**
     * Remove Quick edit.
     * @param array $unset_actions
     * @param object $post
     * @return array
     */
    function remove_row_quick_edit($unset_actions, $post ){
        
        if ( ! is_post_type_archive( 'codup_gift_card' ) ) {
        return $actions;

        if ( isset( $actions['inline hide-if-no-js'] ) ) {
            unset( $actions['inline hide-if-no-js'] );
        }
        
        return $actions;
    }
   

        
    }
     function disable_new_posts() {
        global $submenu;
        unset($submenu['edit.php?post_type=codup_gift_card'][10]);
 
    }
}
