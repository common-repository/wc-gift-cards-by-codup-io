<?php
if (!defined('ABSPATH')) {
    exit;
}

global $product;
$product_id = get_the_ID();
$price_type = get_post_meta($product_id, '_codup_gc_amount_type', true);

do_action('woocommerce_before_add_to_cart_form');
?>

<form class="cart" method="post" enctype='multipart/form-data' data-product_id="<?php echo esc_attr($product->get_id()); ?>">
        <?php if (!$product->is_purchasable()) : ?>
        <p class="gift-card-not-valid">
        <?php _e("This product cannot be purchased", 'yith-woocommerce-gift-cards'); ?>
        </p>
    <?php
    else :
        if ($price_type == 'userdefined') {
            $userdefined_minimum = get_post_meta($product_id, '_codup_gc_minimum', true);
            $userdefined_maximum = get_post_meta($product_id, '_codup_gc_maximum', true);
            echo '<table class="variations codup-gc-amount" cellspacing="0">
                    <tbody>
                        <tr>
                        <td class="label"><label for="color">Add gift card amount: (' . get_woocommerce_currency_symbol() . ') </label></td>
                        <td class="value">
                            <input type="number" class="codup-gc-text" name="codup-gc-price" value="" min="' . $userdefined_minimum . '" max="' . $userdefined_maximum . '" required />
                        </td>
                    </tr>                             
                    </tbody>
                </table>';
        } else {
            echo '<table class="variations codup-gc-amount" cellspacing="0">
                    <tbody>
                        <tr>
                        <td class="label"><label for="color">Select amount:(' . get_woocommerce_currency_symbol() . ')</label></td>
                        <td class="value">';
            $amounts = get_post_meta($product_id, '_codup_gc_amount', true);
            if ($amounts) {
                ?>  
                <select class="codup-gc-text" name="gift_card_amount" ><br> <?php
            foreach ($amounts as $amount) {
                    ?>
                        <option value="<?php echo $amount ?>"><?php echo $amount ?></option>
                        <?php
                    }

                    echo '</select>
                            
                        </td>
                    </tr>                             
                    </tbody>
                </table>';
                }
            }
            /**
             * @since 2.1.0.
             */
            do_action('woocommerce_before_add_to_cart_button');


            /**
             * @since 3.0.0.
             */
            do_action('woocommerce_before_add_to_cart_quantity');


            /**
             * @since 3.0.0.
             */
            do_action('woocommerce_after_add_to_cart_quantity');
            ?>
            <button type="submit" name="add-to-cart" value="<?php echo esc_attr($product->get_id()); ?>" class="single_add_to_cart_button button alt"><?php echo esc_html($product->single_add_to_cart_text()); ?></button>

            <?php
            /**
             * @since 2.1.0.
             */
            do_action('woocommerce_after_add_to_cart_button');
            ?>
<?php endif; ?>
</form>

<?php do_action('woocommerce_after_add_to_cart_form'); ?>
