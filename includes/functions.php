<?php

function gift_card_in_cart() {
    global $woocommerce;
    $contents = $woocommerce->cart->get_cart_contents();
    $is_gift_card = false;
    foreach ($contents as $key => $content) {
        if ($content['data']->get_type() == 'codup_gift_card') {
            $is_gift_card = true;
        }
    }

    return $is_gift_card;
}

/**
 * Get the recipient detail of the gift card.
 * @param object $order
 * @return array
 */
function get_recipient_details($order) {

    $details = array();
    $item_id = get_post_meta($order->ID,'gift_card_item',true);
    $details['name'] = ucfirst(wc_get_order_item_meta($item_id, '_codup_gc_recipient_name', true));
    $details['email'] = wc_get_order_item_meta($item_id, '_codup_gc_recipient_email', true);
    $message = wc_get_order_item_meta($item_id, '_codup_gc_recipient_message', true);

    if ($message) {
        $details['message'] = $message;
    }
    return $details;
}

function get_card_details($order) {
    
    $gift_card = array();
    $items = $order->get_items();

    foreach ($items as $item_id => $item_data) {
        $product_id = $item_data["product_id"];
        $product = wc_get_product($product_id);

        if (( $product instanceof WC_Product_Codup_Gift_Card)) {
            $gift_card_post_id = wc_get_order_item_meta($item_id, '_codup_gift_card_post_id', true);
            $expiry   = get_post_meta($gift_card_post_id , '_codup_gift_card_expiration_date' , true);
            $gift_card['amount'] = wc_price((float)  wc_get_order_item_meta($item_id, '_codup_gc_amount',true));
            $gift_card['card_number'] = wc_get_order_item_meta($item_id, '_codup_gift_card_number', true);
            $gift_card['featured_image'] = wp_get_attachment_url(get_post_thumbnail_id($product_id));
            $gift_card ['expiry']   = $expiry ;
        }
    }
    
    return $gift_card;
}

/**Get the oroginal amount of the gift card.
 * @param int|string $codup_gc_id
 * @param bool $price
 * @return string
 */
function get_initial_amount( $codup_gc_id , $price = false ){
     
    $order_id = get_post_meta( $codup_gc_id , '_codup_gift_card_order_id', true );
    $item_id  = get_post_meta($order_id,'gift_card_item',true);
    
    if($price){
        return wc_price((float) wc_get_order_item_meta($item_id, '_codup_gc_amount',true));
    }
    
    return wc_get_order_item_meta($item_id, '_codup_gc_amount',true);
    
 }
 
/**
 * Get the expiry of gift card.
 * @param string|int $product_id
 * @return string
 */ 
function get_expiry_date($product_id){
    
    $has_expiry  = get_post_meta( $product_id, '_codup_gc_has_expiry', true );
    $expiry      = get_post_meta( $product_id, '_codup_gc_expiry', true );
        
    if($has_expiry == 'yes'){
        $today = date("Y-m-d");
        $newdate = strtotime ( '+'.$expiry .'day' , strtotime ( $today ) ) ;
        $newdate = date ( 'Y-m-j' , $newdate );
        return $newdate;
    }
    return ;
}