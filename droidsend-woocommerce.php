<?php

/**
 * Plugin Name:       DroidSend SMS for WooCommerce
 * Description:       Send WooCommerce order updates to customers with DroidSend SMS Gateway. 
 * Version:           1.0.0
 * Author:            DroidSend
 * Author URI:        https://www.DroidSend.com/
 * License:           GPLv2 or later
 * Text Domain:       DroidSend
 */

 // don't call the file directly
if ( !defined( 'ABSPATH' ) ) exit;

include 'includes/core-import.php';
new DroidSendSMS(__FILE__);
