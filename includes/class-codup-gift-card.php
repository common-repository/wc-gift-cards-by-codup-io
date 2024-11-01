<?php
if ( ! defined ( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists ( 'Codup_Gift_Card' ) ) {

  
    class Codup_Gift_Card {
       
        public $ID;

        public $product_id;

        public $order_id;

        public $gift_card_number;

        public $amount;
        
        public $expiration_date;
        
        public $status;

    
        public function __construct ( $args = null ) {
            $this->temporary_key = md5 ( microtime ( true ) . rand () );

            if ( is_numeric ( $args ) || ( $args instanceof WP_Post ) ) {
                $args = $this->get_array ( $args );
            }

            if ( $args ) {
                foreach ( $args as $key => $value ) {
                    $this->{$key} = $value;
                }
            }
        }

        /**
         * Get an array of attribute and values for this object     
         */
        private function get_array ( $post ) {
            if ( is_numeric ( $post ) ) {
                $post = get_post ( $post );
            } else if ( ! ( $post instanceof WP_Post ) || ( 'codup_gift_card' != $post->post_type ) ) {
                return null;
            }

            if ( ! isset( $post ) ) {
                return null;
            }

            return array (
                "ID"               => $post->ID,
                "amount"           => get_post_meta ( $post->ID,'_codup_gift_card_amount', true ),
                "gift_card_number" => $post->post_title,
                "product_id"       => $post->post_parent,
                "order_id"         => get_post_meta ( $post->ID,'_codup_gift_card_order_id', true ),
                "expiration_date"  => get_post_meta( $post->ID, '_codup_gift_card_expiration_date', true ),
            );
        }

        /**
         * Set the current gift card amount
         */
        public function set_amount ( $amount ) {

            $this->amount = $amount;
            if ( $this->ID ) {
                update_post_meta ( $this->ID, '_codup_gift_card_amount', $amount );
            }
        }

        /**
         * Retrieve the gift card balance
         */
        public function get_amount () {
            return $this->amount;
        }

        /**
         * Generate a new unique code
         */
        public function generate_gift_card_code ( $overwrite = false ) {
            if ( ! $overwrite && ! empty( $this->gift_card_number ) ) {
                return false;   // gift card code not updated
            }

            
            $code = strtoupper ( substr ( base_convert ( sha1 ( uniqid ( mt_rand () ) ), 16, 36 ), 0, 16 ) );

            $code = sprintf ( "%s-%s-%s-%s",
                substr ( $code, 0, 4 ),
                substr ( $code, 4, 4 ),
                substr ( $code, 8, 4 ),
                substr ( $code, 12, 4 )
            );

            $this->gift_card_number = $code;

            return true;
           
        }

        /**
         * Deduct an amount from the gift card
         */
        public function deduct_amount ( $amount ) {
            $new_amount = $this->get_amount () - $amount;
            if ( $new_amount < 0 ) {
                $new_amount = 0;
            }
            $this->set_amount ( $new_amount );
        }

        /**
         * Check if the gift card has enough balance to cover the amount requested
         */
        public function has_credit ( $amount ) {
            return $this->get_amount () >= $amount;
        }

        /**
         * Save the current question
         */
        public function save () {
            // Create post object
            $args = array (
                'post_title'  => $this->gift_card_number,
                'post_status' => 'publish',
                'post_type'   => 'codup_gift_card',
                'post_parent' => $this->product_id,
            );

            if ( ! isset( $this->ID ) ) {
                // Insert the post into the database
                $this->ID = wp_insert_post ( $args );
            } else {
                $args[ "ID" ] = $this->ID;
                $this->ID     = wp_update_post ( $args );
            }

            //  Save Gift Card post_meta
            update_post_meta ( $this->ID, '_codup_gift_card_amount', $this->amount );
            update_post_meta ( $this->ID, '_codup_gift_card_order_id', $this->order_id );
            
            if($this->expiration_date){
                update_post_meta ($this->ID,'_codup_gift_card_expiration_date',$this->expiration_date);     
            }


            return $this->ID;
        }
        /**
        * The gift card exists
        */
        public function exists() {
            return $this->ID > 0;
        }

        public function is_enabled(){
            $status = get_post_status( $this->ID );
           
            if($status  == 'publish'){
                return true;
            }
            return false;
        }
        
        public function is_expired(){
            
            $today = date("Y-m-d");          
            $expiry = isset( $this->expiration_date );
            
            if( ($this->amount > 0)  ){
                if($expiry && $this->expiration_date >= $today){
                    return false;
                }else{
                    return false;
                }                
            }
            return true;
        }
    }
}