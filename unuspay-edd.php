<?php

/**
 * Plugin Name: Unuspay Crypto payment for Easy Digital Downloads
 * Plugin URI: https://unuspay.com
 * Description: Pay with Crypto For Easy Digital Downloads, Let your customer pay with ETH, USDC, USDT, DAI, lowest fees, non-custodail & no fraud/chargeback, 50+ cryptos. Invoice, payment link, payment button.
 * Version: 0.0.1
 * Author: Unuspay
 * Author URI: https://unuspay.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: Expand customer base with crypto payment, non-custodail & no fraud/chargeback, low fees, 50+ cryptos. Invoice, payment link, payment button.
 * Tags: Crypto, cryptocurrency, crypto payment, erc20, cryptocurrency, e-commerce, bitcoin, bitcoin lighting network, ethereum, crypto pay, smooth withdrawals, cryptocurrency payments, low commission, pay with meta mask, payment button, invoice, crypto woocommerce，bitcoin woocommerce，ethereum，pay crypto，virtual currency，bitcoin wordpress plugin，free crypto plugin
 * Requires at least: 5.8
 * Requires PHP: 7.2
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define gateway name
define("UNUSPAY_GATEWAY_NAME", "edd_unuspay_gateway");

// Registering Unuspay Gateway as a Payment Gateway in EDD
function unuspay_edd_register_gateway($gateways)
{
    $gateways[UNUSPAY_GATEWAY_NAME] = array(
        'admin_label' => 'Unuspay Gateway',
        'checkout_label' => __('Unuspay Crypto Payment Gateway', 'easy-digital-downloads'),
    );
    return $gateways;
}

register_activation_hook(__FILE__, 'setup_plugin');
function setup_plugin()
{
    global $wpdb;
    $latestDbVersion = 5;
    $currentDbVersion = get_option('unuspay_edd_db_version');

    if (!empty($currentDbVersion) && $currentDbVersion >= $latestDbVersion) {
        return;
    }
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta("
		CREATE TABLE  IF NOT EXISTS {$wpdb->prefix}edd_unuspay_checkouts (
			id VARCHAR(36) NOT NULL,
			order_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			accept LONGTEXT NOT NULL,
			created_at datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
			PRIMARY KEY  (id)
		);"
        );
    dbDelta("
        CREATE TABLE  IF NOT EXISTS {$wpdb->prefix}edd_unuspay_transactions (
        			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        			order_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
        			checkout_id VARCHAR(36) NOT NULL,
        			tracking_uuid VARCHAR(64) NOT NULL,
        			blockchain TINYTEXT NOT NULL,
        			transaction_id TINYTEXT NOT NULL,
        			sender_id TINYTEXT NOT NULL,
        			receiver_id TINYTEXT NOT NULL,
        			token_id TINYTEXT NOT NULL,
        			amount TINYTEXT NOT NULL,
        			status TINYTEXT NOT NULL,
        			failed_reason TINYTEXT NOT NULL,
        			confirmed_by TINYTEXT NOT NULL,
        			confirmed_at datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
        			created_at datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
        			PRIMARY KEY  (id),
        			KEY tracking_uuid_index (tracking_uuid)
        		);
	");
    update_option('unuspay_edd_db_version', $latestDbVersion);
}

add_filter('edd_payment_gateways', 'unuspay_edd_register_gateway');

// Register a subsection for Unuspay Gateway in gateway options tab
function unuspay_edd_register_gateway_section($gateway_sections)
{
    $gateway_sections[UNUSPAY_GATEWAY_NAME] = __('Unuspay Gateway', 'easy-digital-downloads');
    return $gateway_sections;
}

add_filter('edd_settings_sections_gateways', 'unuspay_edd_register_gateway_section');


$unuspay_edd_title = "";
$unuspay_edd_payment_key = "";

// Register the Unuspay Gateway settings for Unuspay Gateway subsection
function unuspay_edd_add_gateway_settings($gateway_settings)
{
    global $unuspay_edd_title, $unuspay_edd_payment_key;

    $unuspay_intro = '<p style="color:blue"><b>Remember to select Unuspay as one of your active payment gateway.</b></p>';
    $unuspay_intro .= '<p style="margin-top: 10px"><b>UNUSPAY official <a href="https://unuspay.com/" target="_blank">website.</a></b></p>';
    $unuspay_intro .= '<p style="margin-top: 10px;">Unuspay has no setup fees, no subscription fees, no hidden costs, no chargebacks. Pure non-custodial, no third party charge, all transactions are peer-to-peer. Merchants send crypto payment link directly to customers with no middleman, no code required.</p>';
    $unuspay_intro .= '<p style="margin-top: 20px;"><b>PARTNER INCENTIVE REWARD PROGRAM!</b></p>';
    $unuspay_intro .= '<p style="margin-top: 10px;">Join hundreds of popular WordPress, WooCommerce sellers benefiting from using Unuspay as their global growth partner. Start accepting Crypto in 1 minute and see the immediate impact of our managed platform.</p>';
    $unuspay_intro .= '<p style="margin-top: 20px;"><b>Learn more about <a href="https://unuspay.com/partner/" target="_blank">Partner</a> Program!</b></p>';
    $unuspay_intro .= '<p style="margin-top: 10px;">Register a partner account and get a percentage of their transaction-based profit. dashboard.</p>';
    $unuspay_intro .= '<p>Easy sign-up referral link to get merchants. Lifetime reward. Manage your merchants in the partner dashboard. The more merchants you bring, the more reward you get!</p>';
    $unuspay_intro .= '<p style="margin-top: 20px;"><a href="https://dashboard.unuspay.com/" target="_blank">Get Started</a></p>';

    $unuspay_settings = array(
        UNUSPAY_GATEWAY_NAME => array(
            'id' => UNUSPAY_GATEWAY_NAME,
            'name' => '<a id="UNUSPAY"></a><strong>' . __('Unuspay', 'easy-digital-downloads') . '</strong>',
            'desc' => __('UNUSPAY official website.', 'easy-digital-downloads'),
            'type' => 'header',
        ),
        UNUSPAY_GATEWAY_NAME . '_intro' => array(
            'id' => UNUSPAY_GATEWAY_NAME . '_intro',
            'name' => "<a target='_blank' href='https://unuspay.com/'><img border='0' style='width: 190px;height: 60px;'src='" . plugins_url('/images/unuspay.png', __FILE__) . "'></a>",
            'desc' => $unuspay_intro,
            'type' => 'descriptive_text',
        ),
        UNUSPAY_GATEWAY_NAME . '_title' => array(
            'id' => UNUSPAY_GATEWAY_NAME . '_title',
            'name' => __('Title', 'easy-digital-downloads'),
            'desc' => __('Payment method title that the customer will see on your checkout page', 'easy-digital-downloads'),
            'type' => 'text',
            'size' => 'regular',
            'std' => $unuspay_edd_title
        ),
        UNUSPAY_GATEWAY_NAME . '_payment_key' => array(
            'id' => UNUSPAY_GATEWAY_NAME . '_payment_key',
            'name' => __('PaymentKey', 'easy-digital-downloads'),
            'desc' => __('Unuspay Payment Key', 'easy-digital-downloads'),
            'type' => 'text',
            'size' => 'regular',
            'std' => $unuspay_edd_payment_key
        ),

    );

    $unuspay_settings = apply_filters('edd_' . UNUSPAY_GATEWAY_NAME . '_settings', $unuspay_settings);
    $gateway_settings[UNUSPAY_GATEWAY_NAME] = $unuspay_settings;
    return $gateway_settings;
}

add_filter('edd_settings_gateways', 'unuspay_edd_add_gateway_settings');

function unuspay_edd_init_settings()
{
    global $edd_options;

    $unuspay_edd_title = edd_get_option(UNUSPAY_GATEWAY_NAME . '_title', '');
    $unuspay_edd_payment_key = edd_get_option(UNUSPAY_GATEWAY_NAME . '_payment_key', '');

    $arr = array(UNUSPAY_GATEWAY_NAME . '_title', UNUSPAY_GATEWAY_NAME . '_payment_key');


    if (!$unuspay_edd_title) {
        $unuspay_edd_title = __('Unuspay Crypto Payment Gateway', 'easy-digital-downloads');
        edd_update_option(UNUSPAY_GATEWAY_NAME . '_title', $unuspay_edd_title);
    }

    $unuspay_edd_payment_key = trim($unuspay_edd_payment_key);
    edd_update_option(UNUSPAY_GATEWAY_NAME . '_payment_key', $unuspay_edd_payment_key);

    /*if (isset($_GET["page"]) && isset($_GET["tab"]) && $_GET["page"] == "edd-settings" && $_GET["tab"] == "gateways") {
        try {
            unuspay_edd_verify_unuspay_key($unuspay_edd_payment_key);
        } catch (Exception $e) {
            unuspay_edd_log_error("[unuspay_edd_init_settings] request to unuspay key verification failed, error:" . json_encode($e));
        }
    }*/
}

/*function unuspay_edd_verify_unuspay_key($payment_key)
{
    $key_result = wp_remote_get('https://dashboard.unuspay.com/api/plugin/key/verify?id=' . $merchant_id . '&key=' . $merchant_key . '&name=EASYDIGITALDOWNLOADS&url=' . parse_url(site_url(), PHP_URL_HOST));
    $response_data = json_decode($key_result['body'], true);

    if (!($response_data['data'])) {
        add_action('admin_notices', 'unuspay_edd_admin_notice_for_key');
        add_action('admin_notices', 'unuspay_edd_admin_notice_for_unuspay_active');
    }
}*/

function unuspay_edd_admin_notice_for_key()
{
    ?>
    <div class="notice notice-error is-dismissible">
        <p><?php _e('[Unuspay EDD] The Unuspay MerchantID and PublicKey you entered is incorrect. Please check the video link for more information.', 'easy-digital-downloads'); ?>
            (<a href="https://youtu.be/zLLLjBnuc3g" target="blank">https://youtu.be/zLLLjBnuc3g</a>)</p>
    </div>
    <?php
}


function unuspay_edd_admin_notice_for_unuspay_active()
{
    ?>
    <div class="notice notice-info is-dismissible"
         style="color: #fff; background-image: linear-gradient(to right , #529df8, #541ccc);">
        <p><?php _e('[Unuspay EDD] Remember to select Unuspay as one of your active payment gateway.', 'easy-digital-downloads'); ?></p>
    </div>
    <?php
}

function unuspay_edd_log_error($message)
{
    error_log(date('Y-m-d H:i:s') . ' ERROR: ' . $message . "\n", 3, ABSPATH . "/wp-content/plugins/web3-easy-digital-downloads-unuspay-payments/logs/error.log");
    //error_log(date('Y-m-d H:i:s') . ' ERROR: ' . $message . "\n");
}

function unuspay_edd_process_payment($purchase_data)
{
    try{
    global $wpdb;
    if (!wp_verify_nonce($purchase_data['gateway_nonce'], 'edd-gateway')) {
        unuspay_edd_log_error("[unuspay_edd_process_payment] gateway_nonce is invalid: " . $purchase_data['gateway_nonce']);
        wp_die(__('Nonce verification has failed', 'unuspay-edd'), __('Error', 'unuspay-edd'), array('response' => 403));
    }

    $payment_data = array(
        "price" => $purchase_data['price'],
        "date" => $purchase_data['date'],
        "user_email" => $purchase_data['user_email'],
        "purchase_key" => $purchase_data['purchase_key'],
        "currency" => edd_get_currency(),
        "downloads" => $purchase_data['downloads'],
        "user_info" => $purchase_data['user_info'],
        "cart_details" => $purchase_data['cart_details'],
        "status" => "pending"
    );

    $payment_id = edd_insert_payment($payment_data);
    if ( $payment_id) {

        $checkout_id = wp_generate_uuid4();
        $payment = edd_get_payment($payment_id);
        $accept = getUnusPayOrder( $payment ,$checkout_id);
        /*$accept= array(
            'name' => 'John',
            'age' => 30,
            'city' => 'New York'
        );*/
        $result = $wpdb->insert( "{$wpdb->prefix}edd_unuspay_checkouts", array(
            'id' => $checkout_id,
            'order_id' =>$payment_id,
            'accept' => json_encode( $accept ),
            'created_at' => current_time( 'mysql' )
        ));
        if ( false === $result ) {
            $error_message = $wpdb->last_error;

            throw new Exception( 'Storing checkout failed: ' . $error_message );
        }
       /* $redirect_url= "Location: ". 'unuspay-checkout-' . $checkout_id . '@' . time();
        header($redirect_url);
        die();
        return rest_ensure_response( '{}' );*/
       edd_send_back_to_checkout('?unuspay-checkout=' . $checkout_id . '@' . time());
       /* return( [
            'result'         => 'success',
            'redirect'       => 'unuspay-checkout-' . $checkout_id . '@' . time()
            // 'redirect'       => get_option('woocommerce_enable_signup_and_login_from_checkout') === 'yes' ? $order->get_checkout_payment_url() . '#wc-depay-checkout-' . $checkout_id . '@' . time() : '#wc-depay-checkout-' . $checkout_id . '@' . time()
        ] );*/
    }
    }catch (Exception $e){
        unuspay_edd_log_error( 'Storing checkout failed: '. $e->getMessage() );
        wp_die(__('Storing checkout failed', 'unuspay-edd'), __('Error', 'unuspay-edd'), array('response' => 403));

       // edd_send_back_to_checkout();
        //edd_send_back_to_checkout('?payment-mode=' . $purchase_data['post_data']['edd-gateway']);

    }
    /*if ($payment) {
        $userID = edd_get_payment_user_id($payment);

        if ($userID == "-1") {
            $userID = 0;
        }

        $user = (!$userID) ? __('Guest', 'unuspay-edd') : "<a href='" . admin_url("user-edit.php?user_id=" . $userID) . "'>user" . $userID . "</a>";
        edd_insert_payment_note($payment, sprintf(__('Order Created by %s. <br/> Awaiting cryptocurrency payment ...', 'unuspay-edd'), $user) . '<br/>');

        edd_empty_cart();
        edd_send_to_success_page();
    } else {
        unuspay_edd_log_error("[unuspay_edd_process_payment] Payment creation failed while processing unuspay crypto payment. Payment data: " . json_encode($payment_data));
        edd_record_gateway_error(__('Payment Error', 'unuspay-edd'), sprintf(__('Payment creation failed while processing crypto purchase. Payment data: %s', 'unuspay-edd'), json_encode($payment_data)), $payment);
        edd_send_back_to_checkout('?payment-mode=' . $purchase_data['post_data']['edd-gateway']);
    }*/
}
 function getUnusPayOrder( $order ,$checkout_id ) {
    $lang=$_SERVER['HTTP_ACCEPT_LANGUAGE'];
    $headers = array(
        'accept-language' => $lang,
        'Content-Type' => 'application/json; charset=utf-8',
    );
    $website=get_option("siteurl");

    $total = $order->total;
    $currency = $order->currency;

    $payment_key = edd_get_option(UNUSPAY_GATEWAY_NAME . '_payment_key', '');
    if ( empty( $payment_key ) ) {
        unuspay_edd_log_error( 'No payment key found!' );
        throw new Exception( 'No payment key found!' );
    }

    $post_response = wp_remote_post( "http://110.41.71.103:9080/payment/ecommerce/order",
        array(
            'headers' => $headers,
            'body' => json_encode([
                'checkout_id' => $checkout_id,
                'website' => $website,
                'lang' => $lang,
                'orderNo' => $order->id,
                'email' => $order->email,
                'payLinkId' => $payment_key,
                'currency' => $currency,
                'amount' => $total
            ]),
            'method' => 'POST',
            'data_format' => 'body'
        )
    );
    $post_response_code = $post_response['response']['code'];
    $post_response_successful = ! is_wp_error( $post_response_code ) && $post_response_code >= 200 && $post_response_code < 300;
    if(!$post_response_successful){
        unuspay_edd_log_error( 'ecommerce order failed!' . $post_response->get_error_message() );
        throw new Exception( 'request failed!' );
    }
    $post_response_json = json_decode( $post_response['body']);
    if($post_response_json->code!=200){
        unuspay_edd_log_error( 'ecommerce order failed!' . $post_response->get_error_message() );
        throw new Exception( 'request failed!' );
    }

    return $post_response_json;
}
add_action('edd_gateway_' . UNUSPAY_GATEWAY_NAME, 'unuspay_edd_process_payment');

function unuspay_edd_cryptocoin_payment($payment)
{
    try {


    if (edd_get_payment_gateway($payment->ID) == UNUSPAY_GATEWAY_NAME && is_object($payment)) {
        $status = $payment->status;
        $amount = edd_get_payment_amount($payment->ID);
        $currency = edd_get_payment_currency_code($payment->ID);
        $orderID = $payment->ID;
        $userID = edd_get_payment_user_id($payment->ID);

        if (!$userID) {
            $userID = "guest";
        } elseif ($userID == "-1") {
            $userID = 0;
        }

        if ($status == "complete") {
            return true;
        }

        $unuspay_edd_payment_key = edd_get_option(UNUSPAY_GATEWAY_NAME . '_payment_key', '');

        if (!$payment || !$payment->ID) {
            unuspay_edd_log_error("[cryptocoin_payment] Unable to get payment object. Payment data: " . json_encode($payment));
            echo '<h3>' . esc_html(__('ERROR', 'unuspay-edd')) . '</h3>' . esc_html(PHP_EOL);
            echo "<p class='edd-alert edd-alert-error'>" . esc_html(__('Unable to get payment object. You can contact the email(contact@unuspay.com) to get more help.', 'unuspay-edd')) . '</p>';
            return false;
        } else {
            if ($amount < 0) {
                unuspay_edd_log_error("[cryptocoin_payment] Order amount < 0, amount: " . $amount);
                echo '<h3>' . esc_html(__('ERROR', 'unuspay-edd')) . '</h3>' . esc_html(PHP_EOL);
                echo "<p class='edd-alert edd-alert-error'>" . esc_html(__("The order amount must be greater than or equal to 0. Please contact us(contact@unuspay.com) if you need assistance.", 'unuspay-edd') . esc_html(" ") . esc_html($currency)) . "</p>";
                return false;
            } elseif (!$unuspay_edd_payment_key || $unuspay_edd_payment_key == "" ) {
                unuspay_edd_log_error("[cryptocoin_payment]  payment_key is invalid, payment_key: " . $unuspay_edd_payment_key);
                echo '<h3>' . esc_html(__('ERROR', 'unuspay-edd')) . '</h3>' . esc_html(PHP_EOL);
                echo "<p class='edd-alert edd-alert-error'>" . esc_html(__("The merchant did not set the plugin configuration. Please contact merchant or us(contact@unuspay.com) if you need assistance.", 'unuspay-edd')) . "</p>";
                return false;
            } else {
                unuspay_edd_generate_checkout_token($orderID, $amount, $currency);
                return true;
            }
        }
    }
    }catch (Exception $e){
        unuspay_edd_log_error( 'Storing checkout failed: '. $e->getMessage() );
    }

    return false;
}

function unuspay_edd_generate_checkout_token($orderID, $amount, $currency_code)
{
    global $wp;
    global $wpdb;
    $checkout_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT checkout_id FROM {$wpdb->prefix}edd_unuspay_checkouts WHERE id = %s LIMIT 1",
            $orderID
        )
    );

    /* return( [
            'result'         => 'success',
            'redirect'       => 'unuspay-checkout-' . $checkout_id . '@' . time()
            // 'redirect'       => get_option('woocommerce_enable_signup_and_login_from_checkout') === 'yes' ? $order->get_checkout_payment_url() . '#wc-depay-checkout-' . $checkout_id . '@' . time() : '#wc-depay-checkout-' . $checkout_id . '@' . time()
        ] );*/
   /* $redirect_url= "Location: ". 'unuspay-checkout-' . $checkout_id . '@' . time();
    header($redirect_url);
    die();*/
    return rest_ensure_response( '{}' );
    /*$unuspay_edd_merchant_id = edd_get_option(UNUSPAY_GATEWAY_NAME . '_merchant_id', '');
    $unuspay_edd_merchant_key = edd_get_option(UNUSPAY_GATEWAY_NAME . '_merchant_key', '');

    $unuspay_generate_checkout_token = "https://dashboard.unuspay.com/api/order/pay/token";
    $unuspay_checkout_url = "https://dashboard.unuspay.com/#/cashier/choose?token=";

    $platform = "EASYDIGITALDOWNLOADS";
    $callback_url = trim(get_site_url(), "/ ") . "/unuspay.edd.callback.php?status=completed&type=AURPAYEDD&platform=UNUSPAY&order_id=" . $orderID;

    $current_url = home_url(add_query_arg(array(), $wp->request));
    $succeed_url = $current_url;

    $origin = array(
        'id' => $orderID,
        'price' => $amount,
        'currency' => $currency_code,
        'callback_url' => $callback_url,
        'succeed_url' => $succeed_url,
        'url' => trim(get_site_url(), "/ "),
    );

    $data = array(
        'platform' => $platform,
        'origin' => $origin,
        'user_id' => $unuspay_edd_merchant_id,
        'key' => $unuspay_edd_merchant_key
    );

    $token_result = unuspay_edd_http_post($unuspay_generate_checkout_token, json_encode($data), $unuspay_edd_merchant_key);
    $response_data = json_decode($token_result['body'], true);
    if (isset($response_data['data']) && $response_data['code'] == 0 && isset($response_data['data']['token']) && $response_data['data']['token'] != "") {
        $token = $response_data['data']['token'];
        $redirect_url = "Location: " . $unuspay_checkout_url . $token;
        header($redirect_url);
        die();
    } else {
        unuspay_edd_log_error("[unuspay_edd_generate_checkout_token] request to unuspay failed, response_data:" . json_encode($response_data));
    }

    return $response_data;*/
}

function unuspay_edd_http_post($url, $data, $API_KEY)
{
    $body = $data;
    $headers = array(
        'Content-Type' => 'application/json; charset=utf-8',
        'Content-Length' => strlen($data),
        'API-KEY' => $API_KEY,
    );
    $args = array(
        'body' => $body,
        'timeout' => '5',
        'redirection' => '5',
        'httpversion' => '1.0',
        'blocking' => true,
        'headers' => $headers,
    );

    $response = wp_remote_post($url, $args);

    if ($response) {
        return $response;
    }
    return [];
}

add_action('edd_order_receipt_before_table', 'unuspay_edd_cryptocoin_payment');

function unuspay_edd_callback_parse_request()
{
    ob_start();

    include_once(plugin_dir_path(__FILE__) . "includes/unuspay.edd.callback.php");

    if (ob_get_level() > 0) {
        ob_flush();
    }

    return true;
}

add_action('parse_request', 'unuspay_edd_callback_parse_request');

function unuspay_edd_disable_checkout_userInfo_details()
{
    remove_action('edd_after_cc_fields', 'edd_default_cc_address_fields');
    remove_action('edd_cc_form', 'edd_get_cc_form');

    unuspay_edd_init_settings();
}

add_action('init', 'unuspay_edd_disable_checkout_userInfo_details');

function unuspay_edd_payment_icon($icons = array())
{
    $icons[plugins_url('assets/images/img_logo_1.png', __FILE__)] = 'Unuspay';

    return $icons;
}

add_filter('edd_accepted_payment_icons', 'unuspay_edd_payment_icon');

if (!function_exists('unuspay_edd_render_usage_notice')) {
    function unuspay_edd_render_usage_notice()
    {
        global $pagenow;
        $admin_pages = ['index.php', 'plugins.php'];
        if (in_array($pagenow, $admin_pages)) {
            ?>
            <div class="ap-connection-banner unuspay-usage-notice">

                <div class="ap-connection-banner__container-top-text">
                    <span class="notice-dismiss unuspay-usage-notice__dismiss" title="Dismiss this notice"></span>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <rect x="0" fill="none" width="24" height="24"/>
                        <g>
                            <path d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm1 15h-2v-2h2v2zm0-4h-2l-.5-6h3l-.5 6z"/>
                        </g>
                    </svg>
                    <span>You're almost done. Setup Unuspay to enable Crypto Payment for you Easy Digital Downloads site.</span>
                </div>
                <div class="ap-connection-banner__inner">
                    <div class="ap-connection-banner__content">
                        <div class="ap-connection-banner__logo">
                            <img src="<?php echo esc_url(plugins_url('assets/images/logo_aurpay.svg', __FILE__)); ?>"
                                 alt="logo">
                        </div>
                        <h2 class="ap-connection-banner__title">Empower Your Business with Unuspay Crypto Payment</h2>
                        <div class="ap-connection-banner__columns">
                            <div class="ap-connection-banner__text">⭐ Get listed on our online directory to attract
                                <span style="color: #007AFF">300 millions</span> of crypto owners.
                            </div>
                            <div class="ap-connection-banner__text">⭐ Earn up to <span style="color: #007AFF">150,000 satoshi</span>
                                rewards for merchants who finished all settings and more.
                            </div>
                        </div>
                        <div class="ap-connection-banner__rows">
                            <div class="ap-connection-banner__text ap-connection-banner__step">By setting up Unuspay,
                                get a merchant account and save your "<span style="color: #007AFF">Merchant ID</span>" &
                                "<span style="color: #007AFF">Public Key</span>" in Easy Digital Downloads Payment
                                settings.
                            </div>
                            <a id="ap-connect-button--alt" rel="external" target="_blank"
                               href="https://dashboard.unuspay.com/#/login?cur_url=/integration&platform=EASYDIGITALDOWNLOADS"
                               class="ap-banner-cta-button ap_step_edd_1">Setup Unuspay</a>
                        </div>
                        <div class="ap-connection-banner__rows" style="display: none;">
                            <div class="ap-connection-banner__text ap-connection-banner__step">Save your PublicKey in
                                EasyDigitalDownloads Payment settings.
                            </div>
                            <a id="ap-connect-button--alt" target="_self"
                               href="<?php echo admin_url('edit.php?post_type=download&page=edd-settings&tab=gateways&section=edd_unuspay_gateway') ?>"
                               class="ap-banner-cta-button ap_step_edd_2">Settings</a>
                        </div>
                    </div>
                    <div class="ap-connection-banner__image-container">
                        <picture>
                            <source type="image/webp"
                                    srcset="<?php echo esc_url(plugins_url('assets/images/img_aurpay.webp', __FILE__)); ?> 1x, <?php echo esc_url(plugins_url('assets/images/img_aurpay-2x.webp', __FILE__)); ?> 2x">
                            <img class="ap-connection-banner__image"
                                 srcset="<?php echo esc_url(plugins_url('assets/images/img_aurpay.png', __FILE__)); ?> 1x, <?php echo esc_url(plugins_url('assets/images/img_aurpay-2x.png', __FILE__)); ?> 2x"
                                 src="<?php echo esc_url(plugins_url('assets/images/img_aurpay.png', __FILE__)); ?>"
                                 alt="">
                        </picture>
                        <img class="ap-connection-banner__image-background"
                             src="<?php echo esc_url(plugins_url('assets/images/background.svg', __FILE__)); ?>"/>
                    </div>
                </div>
            </div>

            <?php

            wp_enqueue_script(
                'unuspay-notice-banner-js',
                plugin_dir_url(__FILE__) . 'assets/js/unuspay-usage-notice.js',
                array('jquery')
            );
        }
    }
}

function unuspay_edd_plugins_loaded()
{
    if (!function_exists('EDD')) {
        return false;
    }

    $unuspay_edd_payment_key = edd_get_option(UNUSPAY_GATEWAY_NAME . '_payment_key', '');

    if (isset($unuspay_edd_payment_key) && $unuspay_edd_payment_key != "" ) {
        return false;
    } else {
        wp_enqueue_style('unuspay-edd-notice-banner-style', plugin_dir_url(__FILE__) . 'assets/css/unuspay-usage-notice.css');
        add_action('admin_notices', 'unuspay_edd_render_usage_notice');
    }
}

add_action('plugins_loaded', 'unuspay_edd_plugins_loaded');

function unuspay_edd_action_links($links, $file)
{
    static $this_plugin;

    if (!class_exists('Easy_Digital_Downloads')) return $links;

    if (false === isset($this_plugin) || true === empty($this_plugin)) {
        $this_plugin = plugin_basename(__FILE__);
    }

    if ($file == $this_plugin) {
        $unuspay_link = '<a href="https://dashboard.unuspay.com/#/login?cur_url=/integration&platform=EASYDIGITALDOWNLOADS" target="_blank" style="color: #39b54a; font-weight: bold;">' . __('Get Unuspay', 'unuspay') . '</a>';
        $settings_link = '<a href="' . admin_url('edit.php?post_type=download&page=edd-settings&tab=gateways#unuspay') . '">' . __('Settings', 'unuspay-edd') . '</a>';
        array_unshift($links, $unuspay_link, $settings_link);
    }

    return $links;
}

add_filter('plugin_action_links', 'unuspay_edd_action_links', 10, 2);

function unuspay_edd_plugin_row_meta($plugin_meta, $plugin_file)
{
    static $this_plugin;

    if (isset($this_plugin) === false || empty($this_plugin) === true) {
        $this_plugin = plugin_basename(__FILE__);
    }

    if ($this_plugin === $plugin_file) {
        $row_meta = [
            'dome' => '<a style="color: #39b54a;" href="https://example-wp.unuspay.com/downloads/" aria-label="' . esc_attr(__('View Unuspay Demo', 'unuspay-wc')) . '" target="_blank">' . __('Demo', 'unuspay-wc') . '</a>',
            'video' => '<a style="color: #39b54a;" href="https://youtu.be/zLLLjBnuc3g" aria-label="' . esc_attr(__('View Unuspay Video Tutorials', 'unuspay-wc')) . '" target="_blank">' . __('Video Tutorials', 'unuspay-wc') . '</a>',
        ];

        $plugin_meta = array_merge($plugin_meta, $row_meta);
    }

    return $plugin_meta;
}

add_filter('plugin_row_meta', 'unuspay_edd_plugin_row_meta', 10, 2);

add_action(
    'rest_api_init',
    function () {
        register_rest_route(
            'unuspay/edd',
            '/checkouts/(?P<id>[\w-]+)',
            [
                'methods' => 'POST',
                'callback' => 'get_checkout_accept',
                'permission_callback' => '__return_true'
            ]
        );
        register_rest_route(
            'unuspay/edd',
            '/checkouts/(?P<id>[\w-]+)/track',
            [
                'methods' => 'POST',
                'callback' => 'track_payment',
                'permission_callback' => '__return_true'
            ]
        );
        register_rest_route(
            'unuspay/edd',
            '/validate',
            array(
                'methods' => 'POST,GET',
                'callback' => 'process_notify',
                'permission_callback' => '__return_true'
            )
        );
        register_rest_route(
            'unuspay/edd',
            '/release',
            [
                'methods' => 'POST',
                'callback' => 'check_release',
                'permission_callback' => '__return_true'
            ]
        );

    }
);

function get_checkout_accept($request)
{

    global $wpdb;
    $id = $request->get_param('id');
    $accept = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT accept FROM {$wpdb->prefix}edd_unuspay_checkouts WHERE id = %s LIMIT 1",
            $id
        )
    );
    $order_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT order_id FROM {$wpdb->prefix}edd_unuspay_checkouts WHERE id = %s LIMIT 1",
            $id
        )
    );
    $checkout_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}edd_unuspay_checkouts WHERE id = %s LIMIT 1",
            $id
        )
    );
    $order  = edd_get_payment($order_id);

    if ($order->status==='complete' || $order->status==='pending') {
        $response = rest_ensure_response(
            json_encode([
                'redirect' => edd_get_success_page_uri()
            ])
        );
    } else {
        $response = rest_ensure_response($accept);
    }

    $response->header('X-Checkout', json_encode([
        'request_id' => $id,
        'checkout_id' => $checkout_id,
        'order_id' => $order_id,
        'total' => $order->get_total(),
        'currency' => $order->get_currency()
    ]));
    return $response;
}

function track_payment($request)
{

    global $wpdb;
    $jsonBody = $request->get_json_params();
    $id = $jsonBody->id;
    $accept = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT accept FROM {$wpdb->prefix}edd_unuspay_checkouts WHERE id = %s LIMIT 1",
            $id
        )
    );
    $order_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT order_id FROM {$wpdb->prefix}edd_unuspay_checkouts WHERE id = %s LIMIT 1",
            $id
        )
    );
    $payment = edd_get_payment($order_id);

    $tracking_uuid = wp_generate_uuid4();

    $total = $payment->total;

    $transaction_id = $jsonBody->transaction;

    if (empty($transaction_id)) { // PAYMENT TRACE

        if ($payment->status('complete') || $payment->status('pending')) {
            unuspay_edd_log_error('Order has been completed already!');
            throw new Exception('Order has been completed already!');
        }


    } else { // PAYMENT TRACKING

        $result = $wpdb->insert("{$wpdb->prefix}edd_unuspay_transactions", array(
            'order_id' => $order_id,
            'checkout_id' => $id,
            'tracking_uuid' => $tracking_uuid,
            'blockchain' => $jsonBody->blockchain,
            'transaction_id' => $transaction_id,
            'sender_id' => $jsonBody->sender,
            'receiver_id' => '',
            'token_id' => '',
            'amount' => 0.00,
            'status' => 'VALIDATING',

            'created_at' => current_time('mysql')
        ));
        if (false === $result) {
            unuspay_edd_log_error('Storing tracking failed!');
            throw new Exception('Storing tracking failed!!');
        }

    }

    $endpoint = 'http://110.41.71.103:8080/payment/pay';

    $jsonBody->callback = get_site_url(null, 'index.php?rest_route=/unuspay/edd/validate');
    $jsonBody->trackingId = $tracking_uuid;
    $post = wp_remote_post($endpoint,
        array(
            'body' => json_encode($jsonBody),
            'method' => 'POST',
            'data_format' => 'body'
        )
    );

    $response = rest_ensure_response('{}');

    if (!is_wp_error($post) && (wp_remote_retrieve_response_code($post) == 200 || wp_remote_retrieve_response_code($post) == 201) && wp_remote_retrieve_body($post)->code == 200) {
        $response->set_status(200);
    } else {
        if (is_wp_error($post)) {
            UnusPay_WC_Payments::log($post->get_error_message());
        } else {
            error_log(wp_remote_retrieve_body($post));
        }
        $response->set_status(500);
    }

    return $response;
}

function check_release($request)
{

    global $wpdb;
    $jsonBody = $request->get_json_params();

    $checkout_id = $jsonBody->id;
    $existing_transaction_status = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT status FROM {$wpdb->prefix}edd_unuspay_transactions WHERE checkout_id = %s ORDER BY created_at DESC LIMIT 1",
            $checkout_id
        )
    );

    if ('VALIDATING' === $existing_transaction_status) {
        $tracking_uuid = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT tracking_uuid FROM {$wpdb->prefix}edd_unuspay_transactions WHERE checkout_id = %s ORDER BY created_at DESC LIMIT 1",
                $checkout_id
            )
        );

        $endpoint = 'http://110.41.71.103:8080/payment/release';

        $response = wp_remote_post($endpoint,
            array(
                'body' => json_encode($jsonBody),
                'method' => 'POST',
                'data_format' => 'body'
            )
        );
        $rspBody = wp_remote_retrieve_body($response);
        if (!is_wp_error($response) && (wp_remote_retrieve_response_code($response) == 200 || wp_remote_retrieve_response_code($response) == 201) && $rspBody->code == 200) {


            $order_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT order_id FROM {$wpdb->prefix}edd_unuspay_transactions WHERE tracking_uuid = %s ORDER BY id DESC LIMIT 1",
                    $tracking_uuid
                )
            );

            $expected_blockchain = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT blockchain FROM {$wpdb->prefix}edd_unuspay_transactions WHERE tracking_uuid = %s ORDER BY id DESC LIMIT 1",
                    $tracking_uuid
                )
            );
            $expected_transaction = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT transaction_id FROM {$wpdb->prefix}edd_unuspay_transactions WHERE tracking_uuid = %s ORDER BY id DESC LIMIT 1",
                    $tracking_uuid
                )
            );
            $order = wc_get_order($order_id);
            //$responseBody = json_decode( $response['body'] );
            $status = $rspBody->data->status;
            $transaction = $rspBody->data->transaction;

            if ($expected_transaction != $transaction) {
                $wpdb->query(
                    $wpdb->prepare(
                        "UPDATE {$wpdb->prefix}edd_unuspay_transactions SET transaction_id = %s WHERE tracking_uuid = %s",
                        $transaction,
                        $tracking_uuid
                    )
                );
            }

            if (
                'success' === $status &&
                $rspBody->data->blockchain === $expected_blockchain
					) {
                $wpdb->query(
                    $wpdb->prepare(
                        "UPDATE {$wpdb->prefix}edd_unuspay_transactions SET status = %s, confirmed_at = %s, confirmed_by = %s, failed_reason = NULL WHERE tracking_uuid = %s",
                        'SUCCESS',
                        current_time('mysql'),
                        'API',
                        $tracking_uuid
                    )
                );
                edd_update_order_status( $order_id, 'complete' );
            } else if ('failed' === $status) {
                $failed_reason = 'fail';
                if (empty($failed_reason)) {
                    $failed_reason = 'MISMATCH';
                }
                UnusPay_WC_Payments::log('Validation failed: ' . $failed_reason);
                $wpdb->query(
                    $wpdb->prepare(
                        "UPDATE {$wpdb->prefix}edd_unuspay_transactions SET failed_reason = %s, status = %s, confirmed_by = %s WHERE tracking_uuid = %s",
                        $failed_reason,
                        'FAILED',
                        'API',
                        $tracking_uuid
                    )
                );
                edd_update_order_status( $order_id, 'faild' );
            }
			}
    }

    $existing_transaction_status = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT status FROM {$wpdb->prefix}edd_unuspay_transactions WHERE checkout_id = %s ORDER BY created_at DESC LIMIT 1",
            $checkout_id
        )
    );

    if (empty($existing_transaction_status) || 'VALIDATING' === $existing_transaction_status) {
        $response = new WP_REST_Response();
        $response->set_status(200);
        return $response;
    }

    $order_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT order_id FROM {$wpdb->prefix}edd_unuspay_transactions WHERE checkout_id = %s ORDER BY id DESC LIMIT 1",
            $checkout_id
        )
    );
    $order = wc_get_order($order_id);


    if ('SUCCESS' === $existing_transaction_status) {
        $response = rest_ensure_response([
            'code' => 200,
            'data' => [
                'status' => 'success',
                'forward_to' => edd_get_success_page_uri()
            ]
        ]);
        $response->set_status(200);
        return $response;
    } else {
        $failed_reason = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT failed_reason FROM {$wpdb->prefix}edd_unuspay_transactions WHERE checkout_id = %s ORDER BY id DESC LIMIT 1",
                $checkout_id
            )
        );
        $response = rest_ensure_response([
            'code' => 200,
            'data' => [
                'status' => 'failed'
            ]
        ]);

        $response->set_status(200);
        return $response;
    }
}

function process_notify(WP_REST_Request $request)
{
    global $wpdb;
    $response = new WP_REST_Response();


    $tracking_uuid = $request->get_param('trackingId');
    $existing_transaction_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}edd_unuspay_transactions WHERE tracking_uuid = %s ORDER BY id DESC LIMIT 1",
            $tracking_uuid
        )
    );

    if (empty($existing_transaction_id)) {
        UnusPay_WC_Payments::log('Transaction not found for tracking_uuid');
        $response->set_status(404);
        return $response;
    }

    $order_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT order_id FROM {$wpdb->prefix}edd_unuspay_transactions WHERE tracking_uuid = %s ORDER BY id DESC LIMIT 1",
            $tracking_uuid
        )
    );

    $expected_blockchain = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT blockchain FROM {$wpdb->prefix}edd_unuspay_transactions WHERE tracking_uuid = %s ORDER BY id DESC LIMIT 1",
            $tracking_uuid
        )
    );
    $expected_transaction = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT transaction_id FROM {$wpdb->prefix}edd_unuspay_transactions WHERE tracking_uuid = %s ORDER BY id DESC LIMIT 1",
            $tracking_uuid
        )
    );

    $status = $request->get_param('status');
    $transaction = $request->get_param('transaction');

    if ($expected_transaction != $transaction) {
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$wpdb->prefix}edd_unuspay_transactions SET transaction_id = %s WHERE tracking_uuid = %s",
                $transaction,
                $tracking_uuid
            )
        );
    }

    if (
        'success' === $status &&
        $request->get_param('blockchain') === $expected_blockchain
    ) {
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$wpdb->prefix}edd_unuspay_transactions SET status = %s, confirmed_at = %s, confirmed_by = %s, failed_reason = NULL WHERE tracking_uuid = %s",
                'SUCCESS',
                current_time('mysql'),
                'API',
                $tracking_uuid
            )
        );
        edd_update_order_status($order_id, 'complete');
    } else {
        $failed_reason = $request->get_param('failed_reason');
        if (empty($failed_reason)) {
            $failed_reason = 'MISMATCH';
        }
        UnusPay_WC_Payments::log('Validation failed: ' . $failed_reason);
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$wpdb->prefix}edd_unuspay_transactions SET failed_reason = %s, status = %s, confirmed_by = %s WHERE tracking_uuid = %s",
                $failed_reason,
                'FAILED',
                'API',
                $tracking_uuid
            )
        );
        edd_update_order_status($order_id, 'failed');
    }

    $response->set_status(200);
    return $response;
}

function get_edd_options()
{
    global $edd_options;

    return $edd_options;
}


function process_completed(
    string $invoice_status,
    string $invoice_id,
    int    $order_id,
    string $event_name
): void
{
    if (!in_array($invoice_status, array('confirmed', 'completed'), true)) {
        return;
    }

    $note = 'BitPay Invoice ID: <a target = "_blank" href = "' . BitPayEndpoint::get_url(
            unuspay_get_edd_options()['test_mode'],
            $invoice_id
        ) . '">' . $invoice_id . '</a> processing has been completed.';

    edd_insert_payment_note($order_id, $note);
    unuspay_bitpay_checkout_transactions->update_status($event_name, $order_id, $invoice_id);
    edd_update_order_status($order_id, 'complete');
}


function process_processing(
    string $invoice_status,
    string $invoice_id,
    int    $order_id,
    string $event_name
): void
{
    if ('paid' !== $invoice_status) {
        return;
    }

    $note = 'BitPay Invoice ID: <a target = "_blank" href = "' . BitPayEndpoint::get_url(
            unuspay_get_edd_options()['test_mode'],
            $invoice_id
        ) . '">' . $invoice_id . '</a> is processing.';

    edd_insert_payment_note($order_id, $note);
    unuspay_bitpay_checkout_transactions->update_status($event_name, $order_id, $invoice_id);
    edd_update_order_status($order_id, 'processing');
}


function process_failed(
    string $invoice_status,
    string $invoice_id,
    int    $order_id,
    string $event_name
): void
{
    if (!in_array($invoice_status, array('invalid', 'declined'), true)) {
        return;
    }

    $note = 'BitPay Invoice ID: <a target = "_blank" href = "' . BitPayEndpoint::get_url(
            unuspay_get_edd_options()['test_mode'],
            $invoice_id
        ) . '">' . $invoice_id . '</a> has become invalid because of network congestion.  Order will automatically update when the status changes.';

    edd_insert_payment_note($order_id, $note);
    unuspay_bitpay_checkout_transactions->update_status($event_name, $order_id, $invoice_id);
    edd_update_order_status($order_id, 'failed');
}


function process_abandoned(
    string $invoice_status,
    string $invoice_id,
    int    $order_id,
    string $event_name
): void
{
    if ('expired' !== $invoice_status) {
        return;
    }

    unuspay_bitpay_checkout_transactions->update_status($event_name, $order_id, $invoice_id);
    edd_update_order_status($order_id, 'abandoned');
}


function process_refunded(
    string $invoice_id,
    int    $order_id,
    string $event_name
): void
{
    $note = 'BitPay Invoice ID: <a target = "_blank" href = "' . BitPayEndpoint::get_url(
            unuspay_get_edd_options()['test_mode'],
            $invoice_id
        ) . '">' . $invoice_id . '</a> has been refunded.';

    edd_insert_payment_note($order_id, $note);
    unuspay_bitpay_checkout_transactions->update_status($event_name, $order_id, $invoice_id);
    edd_update_order_status($order_id, 'refunded');
}