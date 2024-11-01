<table class="form-table">
    <tr>
        <th><label for="status" ><?= __( 'Status', 'codup-gift-card' )?></label></th>
        <td>
            <input type="text" id="status" name="status" class="" value="<?= esc_attr__( $status )?>" readonly="readonly" >         
        </td>
    </tr>
    <tr>
        <th><label for="initial"><?= __( 'Original Balance ( ' .get_woocommerce_currency_symbol() .' )', 'codup-gift-card' )?></label></th>
        <td>
            <input type="number" id="initial" name="initial" class="" value="<?= esc_attr__( $Initial_amount )?>"  readonly="readonly" >
            <p class="description"><?= __( 'Initial Amount of Gift Card', 'codup-gift-card' )?> </p>
        </td>
    </tr>
    <tr>
        <th><label for="balance"><?= __( 'Remaining Balance ( ' .get_woocommerce_currency_symbol() .' )', 'codup-gift-card' )?></label></th>
        <td>
            <input type="number" id="balance" name="balance" class="" value="<?= esc_attr__( $balance )?>"  readonly="readonly" >
            <p class="description"><?= __( 'Remaining balance in a gift card.', 'codup-gift-card' )?> </p>
        </td>
    </tr>
    <tr>
        <th><label for="expiration_date"><?= __( 'Expiration Date', 'codup-gift-card' )?></label></th>
        <td>
            <input type="date" id="expiration_date" name="expiration_date"  value="<?=$expiration_date?>" >
            <p class="description"><?= __( 'Expiration Date of the gift card.', 'codup-gift-card' )?> </p>
        </td>
    </tr>
    <tr>
        <th><?= __( 'Sender Name ', 'codup-gift-card' )?></th>
        <td>
            <?=__( $sender_name )?>
        </td>
    </tr>
    <tr>
        <th><label for="recipient_name" ><?= __( 'Recipient name', 'codup-gift-card' )?></label></th>
        <td>
            <input type="text" id="recipient_name" name="recipient_name" class="" value="<?= esc_attr__( $gc_details['name'] )?>" readonly="readonly">
            
        </td>
    </tr>
    <tr>
        <th><label for="recipient_email" ><?= __( 'Recipient email', 'codup-gift-card' )?></label></th>
        <td>
            <input type="text" id="recipient_email" name="recipient_email" class="" value="<?= esc_attr__( $gc_details['email'] )?>" readonly="readonly" >         
        </td>
    </tr>
    <tr>
        <th><label for="recipient_message" ><?= __( 'Recipient message', 'codup-gift-card' )?></label></th>
        <td>
          <textarea name="recipient_message"  id="recipient_message" rows="2"  cols="50" maxlength="500" readonly="readonly"  ><?= esc_attr__( $gc_details['message'] )?>
          </textarea>
        </td>
    </tr>
</table>