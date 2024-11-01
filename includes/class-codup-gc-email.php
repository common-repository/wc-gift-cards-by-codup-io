<?php

//

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!class_exists("WC_Email")) {
    require_once( WC()->plugin_path() . '/includes/emails/class-wc-email.php' );
}

/**
 * A custom GiftCard WooCommerce Email class
 *
 * @since 0.1
 * @extends \WC_Email
 */
    class Codup_GC_Email extends WC_Email {

    /**
     * Set email defaults
     *
     * @since 0.1
     */
    public function __construct() {


        $this->id          = 'codup_gc';
        $this->title       = 'Codup Gift Card';
        $this->description = 'Gift Card emails are sent when a customer pays for an order having a gift card.';

        $this->template_html    = 'emails/gift-card.php';
        //$this->template_plain = 'emails/plain/admin-new-order.php';

        $this->heading = __('Gift Card', 'codup-gift-card');
        $this->subject = __('Gift Card from {sender_name}', 'codup-gift-card');
       
        add_action('codup_gift_card_order_paid', array($this, 'trigger') ,10 ,2 );

        parent::__construct();
        $this->email_type = "html";
        
        
    }

    /**
     * Determine if the email should actually be sent and setup email merge variables
     *
     * @since 0.1
     * @param int $order_id
     */
    public function trigger($order_id, $order) {
       

        if (!$order_id)
            return;


        $this->object = new WC_Order($order_id);
        $item_id = get_post_meta($order_id,'gift_card_item',true);
        $this->recipient = wc_get_order_item_meta($item_id, '_codup_gc_recipient_email', true);
        
        $sender_name = $this->object->billing_first_name . ' ' . $this->object->billing_last_name;
                        
        if (!$this->is_enabled() || !$this->get_recipient())
            return;

        $this->subject  = str_replace("{sender_name}", $sender_name, $this->subject);

        $this->send($this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());
    }

    /**
     * get_content_html function.
     *
     * @since 0.1
     * @return string
     */
    public function get_content_html() {

        ob_start();
       wc_get_template($this->template_html, array(
            'email_heading' => $this->get_heading(),
            'email_type'    => $this->email_type,
            'order'         => $this->object,
            'sender_name'   => $this->object->billing_first_name . ' ' . $this->object->billing_last_name,
            'recipient'     => get_recipient_details($this->object),
            'gift_card'     => get_card_details($this->object),
            'sent_to_admin' => false,
            'plain_text'    => false,
            'email'         => $this,
            ), '', trailingslashit(CODUP_GC_TEMPLATES_DIR));
        return ob_get_clean();
    }

    /**
     * get_content_plain function.
     *
     * @since 0.1
     * @return string
     */
    public function get_content_plain() {
        ob_start();
        woocommerce_get_template($this->template_plain, array(
            'order' => $this->object,
            'email_heading' => $this->get_heading()
        ));
        return ob_get_clean();
    }

    /**
     * Initialize Settings Form Fields
     *
     * @since 0.1
     */
    public function init_form_fields() {

        $this->form_fields = array(
            'enabled' => array(
                'title' => 'Enable/Disable',
                'type' => 'checkbox',
                'label' => 'Enable this email notification',
                'default' => 'yes'
            ),
            'subject' => array(
                'title' => 'Subject',
                'type' => 'text',
                'description' => sprintf('This controls the email subject line. Leave blank to use the default subject: <code>%s</code>.', $this->subject),
                'placeholder' => $this->subject,
                'default' => ''
            ),
            'heading' => array(
                'title' => 'Email Heading',
                'type' => 'text',
                'description' => sprintf(__('This controls the main heading contained within the email notification. Leave blank to use the default heading: <code>%s</code>.'), $this->heading),
                'placeholder' => $this->heading,
                'default' => ''
            ),
            'email_type' => array(
                'title' => 'Email type',
                'type' => 'select',
                'description' => 'Choose which format of email to send.',
                'default' => 'html',
                'class' => 'email_type',
                'options' => array(
                    'plain' => 'Plain text',
                    'html' => 'HTML', 'woocommerce',
                    'multipart' => 'Multipart', 'woocommerce',
                )
            )
        );
    }

}
