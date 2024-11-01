<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class  WC_Product_Codup_Gift_Card extends WC_Product {
    
        public $min_price = null;
		
        /**
         * @var float Maximum amount from the gift card amount list
         */
        public $max_price = null;

        /**
         * @var array gift card amount list
         */
        private $amounts;

        /**
         * @var int gift card amounts count
         */
        private $amounts_count;
        
        private $amount_type;


        public function __construct( $product ) {
            
            parent::__construct( $product );
            $this->product_type = 'codup_gift_card';
            $this->virtual      = true;
            $this->amounts      = $this->get_gift_card_amounts();
            $this->amounts_count= (is_array($this->amounts)) ?count ( $this->amounts ) :0;
            $this->set_prop( 'sold_individually', wc_string_to_bool( 'yes' ) );
            $this->amount_type = get_post_meta($this->id, '_codup_gc_amount_type',true);
        
            if ( $this->amounts_count ) {
                
                $this->min_price = $this->amounts[0];
                $this->max_price = $this->amounts[ $this->amounts_count - 1 ];
            }
            

        }
        
        public function get_gift_card_amounts(){
            $amounts = array();
            $amount_type  = get_post_meta($this->id, '_codup_gc_amount_type',true);
            if($amount_type == 'userdefined'){
                $amounts[]  = get_post_meta($this->id, '_codup_gc_minimum',true);   
                $amounts[]  = get_post_meta($this->id, '_codup_gc_maximum',true);
                                
            }else{

                $amounts= get_post_meta(  $this->id , '_codup_gc_amount',true);
            }
            return $amounts;
        }
        
        public function get_price_html( $deprecated = '' ){
            if( $this->amount_type && $this->min_price && $this->max_price ){
                $price = $this->min_price !== $this->max_price ? sprintf ( _x ( '%1$s&ndash;%2$s', 'Price range: from-to'), wc_price ( $this->min_price ), wc_price ( $this->max_price ) ) : wc_price ( $this->min_price );
                return $price ; 
            
            }
            return false;
            
        } 
        /**
		 * Check if the product is purchasable
		 *
		 * @return bool
		 */
		public function is_purchasable() {
			
			if ( ! $this->amounts_count  || ! $this->amount_type  ) {
				
				$purchasable = false;
			} else {
				$purchasable = true;
				
			}
			return apply_filters ( 'woocommerce_is_purchasable', $purchasable, $this );
		}
                
                
                public function is_virtual() {
			return true;
		}

}
