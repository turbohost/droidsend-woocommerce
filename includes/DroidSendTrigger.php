<?php

class DroidSendTrigger
{
    /*
     * Declare setting prefix and other values.
     */

    private $prefix = 'droidsend_sms_woo_';
    private $apiKey, $deviceId, $adminRecipients;
    private $yesAdminMsg;
    private $contentDefault, $contentAdmin;


    /*
     * Initialize values.
     */

    public function __construct()
    {
        /*
         * Get DroidSend configuration settings.
         */
        $this->apiKey = get_option($this->prefix . 'api_key');
        $this->deviceId = get_option($this->prefix . 'device_id');
        $this->adminRecipients = get_option($this->prefix . 'admin_sms_recipients');

        /*
         * Get enabled or disabled.
         */
        $this->yesAdminMsg = get_option($this->prefix . 'enable_admin_sms') == 'yes';

        /*
         * Get messages
         */
        $this->contentDefault = get_option($this->prefix . 'default_sms_template');
        $this->contentAdmin = get_option($this->prefix . 'admin_sms_template');

        add_action('woocommerce_order_status_changed', array($this, 'droidsend_sms_for_events'), 11, 3);
        add_action('woocommerce_new_customer_note', array($this, 'droidSendNotifyOrderNoteSMS'));

                /*
         * Send new order admin SMS
         */
        add_action('woocommerce_order_status_processing', array($this, 'droidsend_admin_sms_for_woo_new_order'), 10, 1);
    }

    public function droidsend_sms_for_events($order_id, $from_status, $to_status)
    {
        if (get_option($this->prefix . 'send_sms_' . $to_status) !== "yes")
            return;
        $this->DroidSend($order_id, $to_status);
    }

    public function droidsend_admin_sms_for_woo_new_order($order_id)
    {
        if ($this->yesAdminMsg)
            $this->DroidSend($order_id, 'admin-order');
    }

    public function droidSendNotifyOrderNoteSMS($data)
    {
        if (get_option($this->prefix . 'enable_notes_sms') !== "yes")
            return;

        $this->DroidSend($data['order_id'], 'new-note', $data['customer_note']);
    }

    public static function shortCode($message, $order_details)
    {
        $replacements_string = array(
            '{{shop_name}}' => get_bloginfo('name'),
            '{{order_id}}' => $order_details->get_order_number(),
            '{{order_amount}}' => $order_details->get_total(),
            '{{order_status}}' => ucfirst($order_details->get_status()),
            '{{first_name}}' => ucfirst($order_details->billing_first_name),
            '{{last_name}}' => ucfirst($order_details->billing_last_name),
            '{{billing_city}}' => ucfirst($order_details->billing_city),
            '{{customer_phone}}' => $order_details->billing_phone,
        );
        return str_replace(array_keys($replacements_string), $replacements_string, $message);
    }

    public static function reformatPhoneNumbers($value)
    {
  
        $number = preg_replace("/[^0-9]/", "", $value);
        if (strlen($number) == 9) {
            $number = "258" . $number;
        } 
        return $number;
    }

    private function DroidSend($order_id, $status, $message_text = '')
    {
        $order_details = new WC_Order($order_id);
        $message = '';

        if ($status == 'admin-order') {
            $message = $this->contentAdmin;
        } elseif ($status == 'new-note') {
            $message_prefix = get_option($this->prefix  . 'note_sms_template');
            $message = $message_prefix .  $message_text;
        } else {
            $message = get_option($this->prefix . $status . '_sms_template');
            if (empty($message))
                $message = $this->contentDefault;
        }

        $message = (empty($message) ? $this->contentDefault : $message);
        $message = self::shortCode($message, $order_details);

        $fName = $order_details->billing_first_name;
        $lName = $order_details->billing_last_name;
        $bEmail = $order_details->billing_email;
        $addr1 = $order_details->billing_address_1;
        $addr2 = $order_details->billing_address_2;
        $bCity = $order_details->billing_city;
        $postC = $order_details->shipping_postcode;
        $address = $addr1 . ', ' . $addr2 . ', ' . $bCity . ', ' . $postC;

        $pn = ('admin-order' === $status ? $this->adminRecipients : $order_details->billing_phone);

        $to_numbers = explode(',', $pn);

        do_action( 'logger', $status );
        foreach ($to_numbers as $numb) {
            if (empty($numb))
                continue;

            $phone = $this->reformatPhoneNumbers($numb);
            

            try {

                $api = new DroidSendAPI($this->apiKey, $this->deviceId);
             
                $response = $api->sendMessage($phone, $message);

                
            } catch (\Throwable $th) {
                throw $th;
            }
        }
    }


}
