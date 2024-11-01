<div class="options_group show_if_codup_gift_card">

    <p class="form-field ">
        <label for="_codup_gc_amount_type"><?php echo __('Amount', 'woocommerce'); ?></label>

        <input type="radio" name="_codup_gc_amount_type"  value="preset" <?php checked($amount_type, 'preset'); ?>  ><span class="description" >Preset</span>
        <input type="radio" name="_codup_gc_amount_type" class ="gc_amount_type" value="userdefined" <?php checked($amount_type, 'userdefined'); ?> ><span class="description" >User defined</span>

        <span class="description"></span>
    </p>

    <p class="form-field gift-card-amount"  >
        <label><?php _e("Gift card amount", 'woocommerce'); ?></label>
        <span class="wrap add-new-amount-section">
            <input type="text" id="gift_card-amount" name="gift_card-amount" class="short" style=""
                   placeholder="">
            <a href="#" class="add-new-amount button button-primary"><?php _e("Add", 'woocommerce'); ?></a>
        </span>
        <span id="amount_validation" ></span>
    </p>

    <?php
    $this->codup_gc_show_gift_card_amount_list($thepostid);
    $userdefined_codup_gc_minimum = get_post_meta($thepostid, '_codup_gc_minimum', true);
    $userdefined_codup_gc_maximum = get_post_meta($thepostid, '_codup_gc_maximum', true);
    ?>
    <p class="form-field amount-range" id="range-div" >
        <label for="_wcgc_amount_range"><?php echo __('Select min and max amount', 'woocommerce'); ?></label>     
        <input placeholder="min" class="gift_card_range" type="number" name="_codup_gc_minimum" value="<?php echo $userdefined_codup_gc_minimum; ?>" min="1" />
        <input placeholder="max"  class="gift_card_range"   type="number" name="_codup_gc_maximum" value="<?php echo $userdefined_codup_gc_maximum; ?>" min="<?php echo $userdefined_codup_gc_minimum; ?>"  />
        <span class="description"></span>
        <button class="button button-primary" id="save-range" type="button" >Save</button></br>
        <span id="range_validation" ></span>
        <span id="success-message" style="color:blue;"></span>
    </p>
    
    <p class="form-field" id="cgc-exipry" >
        <label for="codup-gc-expires"><?php _e("Expires ", 'codup-gift-card'); ?></label>
        <span class="wrap">
            <input type="checkbox" name="codup-gc-expires" <?php checked($has_expiry,'yes'); ?> >
        </span>
       <span class="description" > if the gift card expires?</span>
       
    </p>    
    <p class="form-field cgc-exp " id="cgc-exipry-days">
        <label class="codup-gc-exp-days" ><?php _e("Expires after ", 'codup-gift-card'); ?></label>
        <span class="wrap">
            <input type="number" id="codup-gc-exp-days" name="codup-gc-exp-days" value="<?=$expiry?>">
            <span class="description" ><strong>days</strong></span>
        </span>
        
    </p>
</div>      