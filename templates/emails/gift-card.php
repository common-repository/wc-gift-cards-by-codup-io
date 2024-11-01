<?php
/**
 * Codup gift card email template
 * 
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/gift-card.php .
 * 
 * @param  array  $gift_card    Featured image,amount and gift card code.
 * @param  array  $recipient    Name,email  and custom message.
 * @param  string $sender_name  Sender of the gift card.   
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action('woocommerce_email_header', $email_heading, $email);
?>
<?php
if ($gift_card['featured_image']) {
    printf('<img src="%s" style="width:100%%" />', $gift_card['featured_image']);
}
?>
<div style=" margin: 0 0 16px;">
    
        <?php printf(__('Dear <strong>%s</strong>,', 'codup-gift-card'), $recipient['name']);
        ?>
  
</div>
<div style=" margin: 0 0 16px;">
    
        <?php printf(__('Hope you\'re doing well, This email is to inform you that <strong>%s</strong> have sent you a gift via %s.', 'codup-gift-card'), $sender_name, make_clickable(site_url()));
        ?>
</div>
<div>
    <?php
    if ($recipient['message']) {
        printf(__('%s also added following message for you:<p><blockquote>&ldquo; %s &rdquo;</blockquote></p>', 'codup-gift-card'), $sender_name, $recipient['message']);
    }
    ?>
</div>
<div style="margin: 0 0 16px;text-align: center;">
    <?php
     printf(__('Go ahead and claim your gift', 'codup-gift-card'));
    ?>  
    
</div>
<div style="background-color: #dcdada;text-align: center; border: dashed 2px grey;">
    <?php
    printf(__('The Gift Card code is <strong>%s</strong><br />Gift Card amount: <strong>%s</strong><br />', 'codup-gift-card'), $gift_card['card_number'],$gift_card['amount']);
    
    if($gift_card['expiry']){
        printf(__('Expires on: <strong>%s</strong>', 'codup-gift-card'), date('F j, Y',strtotime($gift_card['expiry']) ));
    } else{
         printf(__('Expires: <strong>Never</strong>', 'codup-gift-card'));
    }
     
    ?> 
</div>

<?php
/**
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action('woocommerce_email_footer', $email);
