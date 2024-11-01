<?php

if (!defined('ABSPATH')) exit;  

require_once __DIR__ . '/tecnickcom/tcpdf/tcpdf.php';

//Check whether WPML is active
$wpml_active = function_exists('icl_object_id');
$wpml_regstr = function_exists('icl_register_string');
$wpml_trnslt = function_exists('icl_translate');

//Obtain the settings
$suwcwam_settings = get_option('suwcwam_settings');
global $suwcwam_logger;

function suwcwam_field($var)
{
    global $suwcwam_settings;
    return isset($suwcwam_settings[$var]) ? $suwcwam_settings[$var] : '';
}

//Utility function for registering string to WPML
function suwcwam_register_string($str)
{
    global $suwcwam_settings, $wpml_active, $wpml_regstr;
    if ($wpml_active) {
        ($wpml_regstr) ?
            icl_register_string('suwcwam', $str, $suwcwam_settings[$str]) :
            do_action('wpml_register_single_string', 'suwcwam', $str, $suwcwam_settings[$str]);
    }
}

//Utility function to fetch string from WPML
function suwcwam_fetch_string($str)
{
    global $suwcwam_settings, $wpml_active, $wpml_trnslt;
    if ($wpml_active) {
        return ($wpml_trnslt) ?
            icl_translate('suwcwam', $str, $suwcwam_settings[$str]) :
            apply_filters('wpml_translate_single_string', $suwcwam_settings[$str], 'suwcwam', $str);
    }
    return suwcwam_field($str);
}

$api = suwcwam_field('api') ?: 1;
require_once( __DIR__ . "/api/api-{$api}.php" );

//Change label of billing phone field
//Add WhatsApp phone field to Checkout form
add_filter('woocommerce_checkout_fields', 'suwcwam_add_whatsapp_phone_fields');
function suwcwam_add_whatsapp_phone_fields($fields)
{
    $fields['billing']['billing_phone']['label'] = 'Mobile Phone';
    if (!isset($fields['billing']['billing_whatsapp_phone'])) {
        $fields['billing']['billing_whatsapp_phone'] = array(
            'type' => 'tel',
            'label' => __('If you wish to receive updates for your order via WhatsApp then please enter your WhatsApp number here', 'woocommerce'),
            'placeholder' => _x('WhatsApp Enabled Phone Number', 'placeholder', 'woocommerce'),
            'required' => false,
            'class' => array('form-row-wide'),
            'clear' => true,
            'priority' => 1000,
        );
    }
    return $fields;
}

//Add OTP verification after Checkout Form
if (in_array('sms-notifications-for-woocommerce/su-wc-sms-notifications.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('woocommerce_after_checkout_billing_form', 'suwcwam_add_whatsapp_otp_field');
    add_action('woocommerce_checkout_process','suwcwam_validate_whatsapp_otp');
}
function suwcwam_add_whatsapp_otp_field() {
    if (suwcwam_field('require_otp')) { ?>
    <div id='suwcwam-otp-verification-block' style='background:#EEE;padding:10px;border-radius:5px'>
        <b>WhatsApp OTP Verification</b>
        <div class='suwcwam-notifications'>
            <div class="woocommerce-info">
            An OTP has been sent to your WhatsApp number. You need to enter the OTP below before you can place your order.
            </div>
        </div>
        <center>
        <label style='font-weight:bold;color:#000'>OTP</label>
        <input id='suwcwam-otp-field' size='6' style='letter-spacing:5px;font-weight:bold;padding:10px' name='suwcwam_wa_otp'/>
        <input id='suwcwam_resend_otp_btn' type='button' class='button alt' value='Resend OTP'/>
        </center>
        <p>Please make sure you are in a good mobile signal zone. Resend button will get activated in 2 minutes. Please request again if you have not received the OTP in next 2 minutes.</p>
    </div>
    <script>
    jQuery(function($){
        var suwcwam_otp_failure_count = 0,
            suwcwam_otp_resend_count = 0,
            suwcwam_country = '',
            suwcwam_phone = '',
            suwcwam_url = '<?php echo esc_url_raw( admin_url("admin-ajax.php") ); ?>';
        function suwcwam_resend_otp() {
            if (suwcwam_country == '' || suwcwam_phone == '') return;
            var data = {
                'action' : 'suwcwam_send_otp',
                'country' : suwcwam_country,
                'phone' : suwcwam_phone
            };
            console.log('Data', data);
            $.get(suwcwam_url, data, function(res){
                $('#suwcwam-otp-verification-block').show();
                if (res.success) {
                    suwcwam_disableResendOTP();
                    suwcwam_otp_resend_count++;
                } else {
                    suwcwam_otp_failure_count++;
                }
                $('.suwcwam-notifications > .woocommerce-info').text(res.message);
            });
        }
        function suwcwam_enableResendOTP() {
            if (otp_resend_count < 3) {
                $('#suwcwam_resend_otp_btn').prop('disabled', false);
            }
        }
        function suwcwam_disableResendOTP() {
            $('#suwcwam_resend_otp_btn').prop('disabled', true);
            setTimeout(suwcwam_enableResendOTP, 120000);
        }
        $('#suwcwam_resend_otp_btn').click(suwcwam_resend_otp);
        $('input[name="billing_whatsapp_phone"]').change(function(){
            suwcwam_phone = $(this).val().trim();
            if (suwcwam_phone != '') suwcwam_resend_otp();
            else $('#suwcwam-otp-verification-block').hide();
        }).change();
        $('select[name="billing_country"]').change(function(){
            suwcwam_country = $(this).val().trim();
            if (suwcwam_country != '') suwcwam_resend_otp();
            else $('#suwcwam-otp-verification-block').hide();
        }).change();
    });
    </script>
    <?php }
}

function suwcwam_validate_whatsapp_otp() {
    if (suwcwam_field('require_otp')) {
        $country_code = sanitize_text_field($_POST['billing_country']);
        $billing_phone = sanitize_text_field($_REQUEST['billing_whatsapp_phone']);
        if (!empty($country_code) && !empty($billing_phone)) {
            $otp = sanitize_text_field($_POST['suwcwam_wa_otp']) ?? NULL;
            if (!$otp) {
                wc_add_notice( __( 'WhatsApp OTP Verification is required.' ), 'error' );
                return;
            }
            $user_phone = suwcsms_sanitize_phone_number( $country_code, $billing_phone );
            $transient_id = 'OTP_WA_' . $user_phone;
            $otp_number = get_transient($transient_id);
            if ($otp_number && $otp_number == $otp) {
                return;
            } else {
                wc_add_notice( __( 'WhatsApp OTP Verification failed. Please enter the correct OTP.' ), 'error' );
            }
        }
    }
}

//Request OTP send via AJAX
add_action('wp_ajax_suwcwam_send_otp', 'suwcwam_send_otp_callback');
add_action('wp_ajax_nopriv_suwcwam_send_otp', 'suwcwam_send_otp_callback');
function suwcwam_send_otp_callback()
{
    if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'suwcwam_send_otp') {
        $data = ['error' => true, 'message' => 'Failed to generate OTP. Ensure that you have entered the correct number.', 'number' => NULL];
        $country_code = sanitize_text_field($_REQUEST['country']);
        $billing_phone = sanitize_text_field($_REQUEST['phone']);
        if (!empty($country_code) && !empty($billing_phone)) {
            $user_phone = suwcsms_sanitize_phone_number( $country_code, $billing_phone );
            $transient_id = 'OTP_WA_' . $user_phone;
            $otp_number = get_transient( $transient_id ) ?: mt_rand(100000,999999);
            $shop_name = get_bloginfo('name');
            $signature = suwcsms_field('signature');
            suwcsms_send_otp($user_phone, "Dear Customer, Your OTP for WhatsApp number verification on $shop_name is $otp_number. Kindly verify to confirm your order. $signature");
            set_transient( $transient_id, $otp_number, 600 );
            $data = ['success' => true, 'message' => "OTP sent successfully to $user_phone", 'number' => $user_phone];
        }
        wp_send_json($data);
    }
    die();
}

//Display shipping phone field on order edit page
if (!has_action('woocommerce_admin_order_data_after_shipping_address', 'suwcsms_display_shipping_phone_field'))
    add_action('woocommerce_admin_order_data_after_shipping_address', 'suwcwam_display_shipping_phone_field', 10, 1);
function suwcwam_display_shipping_phone_field($order)
{
    echo '<p><strong>' . __('Shipping Phone') . ':</strong> ' . esc_html(get_post_meta($order->get_id()), '_shipping_phone', true) . '</p>';
}

//Initialize the plugin
add_action('init', 'suwcwam_initialize');
function suwcwam_initialize()
{
    suwcwam_register_string('msg_new_order');
    suwcwam_register_string('msg_pending');
    suwcwam_register_string('msg_on_hold');
    suwcwam_register_string('msg_processing');
    suwcwam_register_string('msg_completed');
    suwcwam_register_string('msg_cancelled');
    suwcwam_register_string('msg_refunded');
    suwcwam_register_string('msg_failure');
    suwcwam_register_string('msg_custom');
}

//Add settings page to woocommerce admin menu 
add_action('admin_menu', 'suwcwam_admin_menu', 20);
function suwcwam_admin_menu()
{
    add_submenu_page('woocommerce', __('Social Notification Settings', 'suwcwam'), __('Social Notifications', 'suwcwam'), 'manage_woocommerce', 'suwcwam', 'suwcwam_tab');
    function suwcwam_tab()
    {
        include('settings-page.php');
    }
}

//Add screen id for enqueuing WooCommerce scripts
add_filter('woocommerce_screen_ids', 'suwcwam_screen_id');
function suwcwam_screen_id($screen)
{
    $screen[] = 'woocommerce_page_suwcwam';
    return $screen;
}

//Set the options
add_action('admin_init', 'suwcwam_regiser_settings');
function suwcwam_regiser_settings()
{
    register_setting('suwcwam_settings_group', 'suwcwam_settings');
}

//Schedule notifications for new order
add_action('woocommerce_new_order', 'suwcwam_owner_notification', 20);
function suwcwam_owner_notification($order_id)
{
    if (suwcwam_field('mnumber') == '')
        return;
    $order = new WC_Order($order_id);

    $phones = [ suwcwam_process_phone($order, suwcwam_field('mnumber'), false, true) ];
    $additional_numbers = suwcwam_field('addnumber');
    if (!empty($additional_numbers)) {
        $numbers = explode(",", $additional_numbers);
        foreach ($numbers as $number) {
            $phones[] = suwcwam_process_phone($order, trim($number), false, true);
        }
    }
    suwcwam_send_messages('msg_new_order', $order, $phones);
}

//Schedule notifications for order status change
add_action('woocommerce_order_status_changed', 'suwcwam_process_status', 10, 3);
function suwcwam_process_status($order_id, $old_status, $status)
{
    $order = new WC_Order($order_id);
    $phone = get_post_meta($order->get_id(), '_billing_whatsapp_phone', true);
    if (empty($phone)) return;
    
    //Remove old 'wc-' prefix from the order status
    $status = str_replace('wc-', '', $status);
    
    //Sanitize the phone number
    $phone = suwcwam_process_phone($order, $phone, FALSE);
    
    //Send the messages corresponding to order status
    $setting_key = in_array($status, ['pending', 'on-hold', 'processing', 'completed', 'cancelled', 'refunded', 'failed']) ? ('msg_' . str_replace('-', '_', $status)) : 'msg_custom';
    suwcwam_send_messages($setting_key, $order, $phone);
}

function suwcwam_process_phone($order, $phone, $shipping = false, $owners_phone = false)
{
    //Sanitize phone number
    $phone = str_replace(array('+', '-'), '', filter_var($phone, FILTER_SANITIZE_NUMBER_INT));
    $phone = ltrim($phone, '0');
     
    //Obtain country code prefix
    $country = WC()->countries->get_base_country();
    if (!$owners_phone) {
        $country = $shipping ? $order->get_shipping_country() : $order->get_billing_country();
    }
    $intl_prefix = suwcwam_country_prefix($country);

    //Check for already included prefix
    preg_match("/(\d{1,4})[0-9.\- ]+/", $phone, $prefix);
    
    //If prefix hasn't been added already, add it
    if (strpos($prefix[1], $intl_prefix) !== 0) {
        $phone = $intl_prefix . $phone;
    }

    return $phone;
}


function suwcwam_process_variables($message, $order)
{
    if (empty($message) || FALSE == strpos($message, '%')) return $message;
    $wam_strings = array("id", "status", "prices_include_tax", "tax_display_cart", "display_totals_ex_tax", "display_cart_ex_tax", "order_date", "modified_date", "customer_message", "customer_note", "post_status", "shop_name", "note", "order_product");
    $suwcwam_variables = array("order_key", "billing_first_name", "billing_last_name", "billing_company", "billing_address_1", "billing_address_2", "billing_city", "billing_postcode", "billing_country", "billing_state", "billing_email", "billing_phone", "shipping_first_name", "shipping_last_name", "shipping_company", "shipping_address_1", "shipping_address_2", "shipping_city", "shipping_postcode", "shipping_country", "shipping_state", "shipping_method", "shipping_method_title", "payment_method", "payment_method_title", "order_discount", "cart_discount", "order_tax", "order_shipping", "order_shipping_tax", "order_total");
    $specials = array("order_date", "modified_date", "shop_name", "id", "order_product", 'signature', 'nl');
    $order_variables = get_post_custom($order->get_id()); //WooCommerce 2.1
    $custom_variables = explode("\n", str_replace(array("\r\n", "\r"), "\n", suwcwam_field('variables')));

    preg_match_all("/%(.*?)%/", $message, $search);
    foreach ($search[1] as $variable) {
        $variable = strtolower($variable);

        if (!in_array($variable, $wam_strings) && !in_array($variable, $suwcwam_variables) && !in_array($variable, $specials) && !in_array($variable, $custom_variables)) {
            continue;
        }

        if (!in_array($variable, $specials)) {
            if (in_array($variable, $wam_strings)) {
                $message = str_replace("%" . $variable . "%", $order->$variable, $message); //Standard fields
            } else if (in_array($variable, $suwcwam_variables)) {
                $message = str_replace("%" . $variable . "%", $order_variables["_" . $variable][0], $message); //Meta fields
            } else if (in_array($variable, $custom_variables) && isset($order_variables[$variable])) {
                $message = str_replace("%" . $variable . "%", $order_variables[$variable][0], $message);
            }
        } else if ($variable == "order_date" || $variable == "modified_date") {
            $message = str_replace("%" . $variable . "%", date_i18n(woocommerce_date_format(), strtotime($order->$variable)), $message);
        } else if ($variable == "shop_name") {
            $message = str_replace("%" . $variable . "%", get_bloginfo('name'), $message);
        } else if ($variable == "id") {
            $message = str_replace("%" . $variable . "%", $order->get_order_number(), $message);
        } else if ($variable == "order_product") {
            $products = $order->get_items();
            $quantity = $products[key($products)]['name'];
            if (strlen($quantity) > 10) {
                $quantity = substr($quantity, 0, 10) . "...";
            }
            if (count($products) > 1) {
                $quantity .= " (+" . (count($products) - 1) . ")";
            }
            $message = str_replace("%" . $variable . "%", $quantity, $message);
        } else if ($variable == "signature") {
            $message = str_replace("%" . $variable . "%", suwcwam_field('signature'), $message);
        }
    }
    $message = str_replace("%nl%", PHP_EOL, $message);
    return $message;
}

function suwcwam_country_prefix($country = '')
{
    $countries = array(
        'AC' => '247',
        'AD' => '376',
        'AE' => '971',
        'AF' => '93',
        'AG' => '1268',
        'AI' => '1264',
        'AL' => '355',
        'AM' => '374',
        'AO' => '244',
        'AQ' => '672',
        'AR' => '54',
        'AS' => '1684',
        'AT' => '43',
        'AU' => '61',
        'AW' => '297',
        'AX' => '358',
        'AZ' => '994',
        'BA' => '387',
        'BB' => '1246',
        'BD' => '880',
        'BE' => '32',
        'BF' => '226',
        'BG' => '359',
        'BH' => '973',
        'BI' => '257',
        'BJ' => '229',
        'BL' => '590',
        'BM' => '1441',
        'BN' => '673',
        'BO' => '591',
        'BQ' => '599',
        'BR' => '55',
        'BS' => '1242',
        'BT' => '975',
        'BW' => '267',
        'BY' => '375',
        'BZ' => '501',
        'CA' => '1',
        'CC' => '61',
        'CD' => '243',
        'CF' => '236',
        'CG' => '242',
        'CH' => '41',
        'CI' => '225',
        'CK' => '682',
        'CL' => '56',
        'CM' => '237',
        'CN' => '86',
        'CO' => '57',
        'CR' => '506',
        'CU' => '53',
        'CV' => '238',
        'CW' => '599',
        'CX' => '61',
        'CY' => '357',
        'CZ' => '420',
        'DE' => '49',
        'DJ' => '253',
        'DK' => '45',
        'DM' => '1767',
        'DO' => '1809',
        'DO' => '1829',
        'DO' => '1849',
        'DZ' => '213',
        'EC' => '593',
        'EE' => '372',
        'EG' => '20',
        'EH' => '212',
        'ER' => '291',
        'ES' => '34',
        'ET' => '251',
        'EU' => '388',
        'FI' => '358',
        'FJ' => '679',
        'FK' => '500',
        'FM' => '691',
        'FO' => '298',
        'FR' => '33',
        'GA' => '241',
        'GB' => '44',
        'GD' => '1473',
        'GE' => '995',
        'GF' => '594',
        'GG' => '44',
        'GH' => '233',
        'GI' => '350',
        'GL' => '299',
        'GM' => '220',
        'GN' => '224',
        'GP' => '590',
        'GQ' => '240',
        'GR' => '30',
        'GT' => '502',
        'GU' => '1671',
        'GW' => '245',
        'GY' => '592',
        'HK' => '852',
        'HN' => '504',
        'HR' => '385',
        'HT' => '509',
        'HU' => '36',
        'ID' => '62',
        'IE' => '353',
        'IL' => '972',
        'IM' => '44',
        'IN' => '91',
        'IO' => '246',
        'IQ' => '964',
        'IR' => '98',
        'IS' => '354',
        'IT' => '39',
        'JE' => '44',
        'JM' => '1876',
        'JO' => '962',
        'JP' => '81',
        'KE' => '254',
        'KG' => '996',
        'KH' => '855',
        'KI' => '686',
        'KM' => '269',
        'KN' => '1869',
        'KP' => '850',
        'KR' => '82',
        'KW' => '965',
        'KY' => '1345',
        'KZ' => '7',
        'LA' => '856',
        'LB' => '961',
        'LC' => '1758',
        'LI' => '423',
        'LK' => '94',
        'LR' => '231',
        'LS' => '266',
        'LT' => '370',
        'LU' => '352',
        'LV' => '371',
        'LY' => '218',
        'MA' => '212',
        'MC' => '377',
        'MD' => '373',
        'ME' => '382',
        'MF' => '590',
        'MG' => '261',
        'MH' => '692',
        'MK' => '389',
        'ML' => '223',
        'MM' => '95',
        'MN' => '976',
        'MO' => '853',
        'MP' => '1670',
        'MQ' => '596',
        'MR' => '222',
        'MS' => '1664',
        'MT' => '356',
        'MU' => '230',
        'MV' => '960',
        'MW' => '265',
        'MX' => '52',
        'MY' => '60',
        'MZ' => '258',
        'NA' => '264',
        'NC' => '687',
        'NE' => '227',
        'NF' => '672',
        'NG' => '234',
        'NI' => '505',
        'NL' => '31',
        'NO' => '47',
        'NP' => '977',
        'NR' => '674',
        'NU' => '683',
        'NZ' => '64',
        'OM' => '968',
        'PA' => '507',
        'PE' => '51',
        'PF' => '689',
        'PG' => '675',
        'PH' => '63',
        'PK' => '92',
        'PL' => '48',
        'PM' => '508',
        'PR' => '1787',
        'PR' => '1939',
        'PS' => '970',
        'PT' => '351',
        'PW' => '680',
        'PY' => '595',
        'QA' => '974',
        'QN' => '374',
        'QS' => '252',
        'QY' => '90',
        'RE' => '262',
        'RO' => '40',
        'RS' => '381',
        'RU' => '7',
        'RW' => '250',
        'SA' => '966',
        'SB' => '677',
        'SC' => '248',
        'SD' => '249',
        'SE' => '46',
        'SG' => '65',
        'SH' => '290',
        'SI' => '386',
        'SJ' => '47',
        'SK' => '421',
        'SL' => '232',
        'SM' => '378',
        'SN' => '221',
        'SO' => '252',
        'SR' => '597',
        'SS' => '211',
        'ST' => '239',
        'SV' => '503',
        'SX' => '1721',
        'SY' => '963',
        'SZ' => '268',
        'TA' => '290',
        'TC' => '1649',
        'TD' => '235',
        'TG' => '228',
        'TH' => '66',
        'TJ' => '992',
        'TK' => '690',
        'TL' => '670',
        'TM' => '993',
        'TN' => '216',
        'TO' => '676',
        'TR' => '90',
        'TT' => '1868',
        'TV' => '688',
        'TW' => '886',
        'TZ' => '255',
        'UA' => '380',
        'UG' => '256',
        'UK' => '44',
        'US' => '1',
        'UY' => '598',
        'UZ' => '998',
        'VA' => '379',
        'VA' => '39',
        'VC' => '1784',
        'VE' => '58',
        'VG' => '1284',
        'VI' => '1340',
        'VN' => '84',
        'VU' => '678',
        'WF' => '681',
        'WS' => '685',
        'XC' => '991',
        'XD' => '888',
        'XG' => '881',
        'XL' => '883',
        'XN' => '857',
        'XN' => '858',
        'XN' => '870',
        'XP' => '878',
        'XR' => '979',
        'XS' => '808',
        'XT' => '800',
        'XV' => '882',
        'YE' => '967',
        'YT' => '262',
        'ZA' => '27',
        'ZM' => '260',
        'ZW' => '263'
    );

    return ($country == '') ? $countries : (isset($countries[$country]) ? $countries[$country] : '');
}

function suwcwam_send_wam($phone, $message, $tid='', $url='', $mediaType='')
{
    $token = suwcwam_field('token');
    $sender = suwcwam_field('sender');
    suwcwam_send_wam_text($phone, $message, $tid, $url, $mediaType, $token, $sender);
}

function suwcwam_send_wam_text($phone, $message, $tid, $url, $mediaType, $token, $sender)
{
    global $suwcwam_message_api;

    //Don't send the message if required fields are missing
    if (empty($phone) || (empty($message) && empty($tid) && empty($url)) || empty($token) || empty($sender))
        return;

    try {
        $phones = is_array($phone) ? $phone : [$phone];
        foreach ($phones as $phone) {
            //Encode the message
            $data = SU_WC_WA_Message_API::encode_message( $sender, $phone, $message, $url, $tid, $mediaType );
        
            //Send the message by calling the API
            $response = SU_WC_WA_Message_API::send_message( $token, $data );
            
            //Log the response
            if (1 == suwcwam_field('log_wam')) {
                $log_txt = "Invoked suwcwam_send_wam_text($phone, $message, $tid, $url, $mediaType, $token, $sender)" . PHP_EOL;
                $log_txt .= __('Destination number: ', 'suwcwam') . $phone . PHP_EOL;
                $log_txt .= __('Request data: ', 'suwcwam') . $data . PHP_EOL;
                $log_txt .= __('Gateway response: ', 'suwcwam') . $response . PHP_EOL;
                suwcwam_log_message($log_txt);
            }
        }
    } catch( Exception $e ) {
        suwcwam_log_message($e->getMessage());
    }
}

function suwcwam_log_message($log_txt) {
    global $woocommerce, $suwcwam_logger;
    if ($suwcwam_logger == NULL)
        $suwcwam_logger = class_exists( 'WC_Logger' ) ? new WC_Logger() : $woocommerce->logger();
    $suwcwam_logger->add('suwcwam', esc_html($log_txt));
}

function suwcwam_sanitize_data($data)
{
    $data = (!empty($data)) ? sanitize_text_field($data) : '';
    $data = preg_replace('/[^0-9]/', '', $data);
    return ltrim($data, '0');
}

function suwcwam_country_name($country='') {
    $countries = array(
		"AL" => 'Albania',
		"DZ" => 'Algeria',
		"AS" => 'American Samoa',
		"AD" => 'Andorra',
		"AO" => 'Angola',
		"AI" => 'Anguilla',
		"AQ" => 'Antarctica',
		"AG" => 'Antigua and Barbuda',
		"AR" => 'Argentina',
		"AM" => 'Armenia',
		"AW" => 'Aruba',
		"AU" => 'Australia',
		"AT" => 'Austria',
		"AZ" => 'Azerbaijan',
		"BS" => 'Bahamas',
		"BH" => 'Bahrain',
		"BD" => 'Bangladesh',
		"BB" => 'Barbados',
		"BY" => 'Belarus',
		"BE" => 'Belgium',
		"BZ" => 'Belize',
		"BJ" => 'Benin',
		"BM" => 'Bermuda',
		"BT" => 'Bhutan',
		"BO" => 'Bolivia',
		"BA" => 'Bosnia and Herzegovina',
		"BW" => 'Botswana',
		"BV" => 'Bouvet Island',
		"BR" => 'Brazil',
		"BQ" => 'British Antarctic Territory',
		"IO" => 'British Indian Ocean Territory',
		"VG" => 'British Virgin Islands',
		"BN" => 'Brunei',
		"BG" => 'Bulgaria',
		"BF" => 'Burkina Faso',
		"BI" => 'Burundi',
		"KH" => 'Cambodia',
		"CM" => 'Cameroon',
		"CA" => 'Canada',
		"CT" => 'Canton and Enderbury Islands',
		"CV" => 'Cape Verde',
		"KY" => 'Cayman Islands',
		"CF" => 'Central African Republic',
		"TD" => 'Chad',
		"CL" => 'Chile',
		"CN" => 'China',
		"CX" => 'Christmas Island',
		"CC" => 'Cocos [Keeling] Islands',
		"CO" => 'Colombia',
		"KM" => 'Comoros',
		"CG" => 'Congo - Brazzaville',
		"CD" => 'Congo - Kinshasa',
		"CK" => 'Cook Islands',
		"CR" => 'Costa Rica',
		"HR" => 'Croatia',
		"CU" => 'Cuba',
		"CY" => 'Cyprus',
		"CZ" => 'Czech Republic',
		"CI" => 'Côte d’Ivoire',
		"DK" => 'Denmark',
		"DJ" => 'Djibouti',
		"DM" => 'Dominica',
		"DO" => 'Dominican Republic',
		"NQ" => 'Dronning Maud Land',
		"DD" => 'East Germany',
		"EC" => 'Ecuador',
		"EG" => 'Egypt',
		"SV" => 'El Salvador',
		"GQ" => 'Equatorial Guinea',
		"ER" => 'Eritrea',
		"EE" => 'Estonia',
		"ET" => 'Ethiopia',
		"FK" => 'Falkland Islands',
		"FO" => 'Faroe Islands',
		"FJ" => 'Fiji',
		"FI" => 'Finland',
		"FR" => 'France',
		"GF" => 'French Guiana',
		"PF" => 'French Polynesia',
		"TF" => 'French Southern Territories',
		"FQ" => 'French Southern and Antarctic Territories',
		"GA" => 'Gabon',
		"GM" => 'Gambia',
		"GE" => 'Georgia',
		"DE" => 'Germany',
		"GH" => 'Ghana',
		"GI" => 'Gibraltar',
		"GR" => 'Greece',
		"GL" => 'Greenland',
		"GD" => 'Grenada',
		"GP" => 'Guadeloupe',
		"GU" => 'Guam',
		"GT" => 'Guatemala',
		"GG" => 'Guernsey',
		"GN" => 'Guinea',
		"GW" => 'Guinea-Bissau',
		"GY" => 'Guyana',
		"HT" => 'Haiti',
		"HM" => 'Heard Island and McDonald Islands',
		"HN" => 'Honduras',
		"HK" => 'Hong Kong SAR China',
		"HU" => 'Hungary',
		"IS" => 'Iceland',
		"IN" => 'India',
		"ID" => 'Indonesia',
		"IR" => 'Iran',
		"IQ" => 'Iraq',
		"IE" => 'Ireland',
		"IM" => 'Isle of Man',
		"IL" => 'Israel',
		"IT" => 'Italy',
		"JM" => 'Jamaica',
		"JP" => 'Japan',
		"JE" => 'Jersey',
		"JT" => 'Johnston Island',
		"JO" => 'Jordan',
		"KZ" => 'Kazakhstan',
		"KE" => 'Kenya',
		"KI" => 'Kiribati',
		"KW" => 'Kuwait',
		"KG" => 'Kyrgyzstan',
		"LA" => 'Laos',
		"LV" => 'Latvia',
		"LB" => 'Lebanon',
		"LS" => 'Lesotho',
		"LR" => 'Liberia',
		"LY" => 'Libya',
		"LI" => 'Liechtenstein',
		"LT" => 'Lithuania',
		"LU" => 'Luxembourg',
		"MO" => 'Macau SAR China',
		"MK" => 'Macedonia',
		"MG" => 'Madagascar',
		"MW" => 'Malawi',
		"MY" => 'Malaysia',
		"MV" => 'Maldives',
		"ML" => 'Mali',
		"MT" => 'Malta',
		"MH" => 'Marshall Islands',
		"MQ" => 'Martinique',
		"MR" => 'Mauritania',
		"MU" => 'Mauritius',
		"YT" => 'Mayotte',
		"FX" => 'Metropolitan France',
		"MX" => 'Mexico',
		"FM" => 'Micronesia',
		"MI" => 'Midway Islands',
		"MD" => 'Moldova',
		"MC" => 'Monaco',
		"MN" => 'Mongolia',
		"ME" => 'Montenegro',
		"MS" => 'Montserrat',
		"MA" => 'Morocco',
		"MZ" => 'Mozambique',
		"MM" => 'Myanmar [Burma]',
		"NA" => 'Namibia',
		"NR" => 'Nauru',
		"NP" => 'Nepal',
		"NL" => 'Netherlands',
		"AN" => 'Netherlands Antilles',
		"NT" => 'Neutral Zone',
		"NC" => 'New Caledonia',
		"NZ" => 'New Zealand',
		"NI" => 'Nicaragua',
		"NE" => 'Niger',
		"NG" => 'Nigeria',
		"NU" => 'Niue',
		"NF" => 'Norfolk Island',
		"KP" => 'North Korea',
		"VD" => 'North Vietnam',
		"MP" => 'Northern Mariana Islands',
		"NO" => 'Norway',
		"OM" => 'Oman',
		"PC" => 'Pacific Islands Trust Territory',
		"PK" => 'Pakistan',
		"PW" => 'Palau',
		"PS" => 'Palestinian Territories',
		"PA" => 'Panama',
		"PZ" => 'Panama Canal Zone',
		"PG" => 'Papua New Guinea',
		"PY" => 'Paraguay',
		"YD" => 'People\'s Democratic Republic of Yemen',
		"PE" => 'Peru',
		"PH" => 'Philippines',
		"PN" => 'Pitcairn Islands',
		"PL" => 'Poland',
		"PT" => 'Portugal',
		"PR" => 'Puerto Rico',
		"QA" => 'Qatar',
		"RO" => 'Romania',
		"RU" => 'Russia',
		"RW" => 'Rwanda',
		"RE" => 'Réunion',
		"BL" => 'Saint Barthélemy',
		"SH" => 'Saint Helena',
		"KN" => 'Saint Kitts and Nevis',
		"LC" => 'Saint Lucia',
		"MF" => 'Saint Martin',
		"PM" => 'Saint Pierre and Miquelon',
		"VC" => 'Saint Vincent and the Grenadines',
		"WS" => 'Samoa',
		"SM" => 'San Marino',
		"SA" => 'Saudi Arabia',
		"SN" => 'Senegal',
		"RS" => 'Serbia',
		"CS" => 'Serbia and Montenegro',
		"SC" => 'Seychelles',
		"SL" => 'Sierra Leone',
		"SG" => 'Singapore',
		"SK" => 'Slovakia',
		"SI" => 'Slovenia',
		"SB" => 'Solomon Islands',
		"SO" => 'Somalia',
		"ZA" => 'South Africa',
		"GS" => 'South Georgia and the South Sandwich Islands',
		"KR" => 'South Korea',
		"ES" => 'Spain',
		"LK" => 'Sri Lanka',
		"SD" => 'Sudan',
		"SR" => 'Suriname',
		"SJ" => 'Svalbard and Jan Mayen',
		"SZ" => 'Swaziland',
		"SE" => 'Sweden',
		"CH" => 'Switzerland',
		"SY" => 'Syria',
		"ST" => 'São Tomé and Príncipe',
		"TW" => 'Taiwan',
		"TJ" => 'Tajikistan',
		"TZ" => 'Tanzania',
		"TH" => 'Thailand',
		"TL" => 'Timor-Leste',
		"TG" => 'Togo',
		"TK" => 'Tokelau',
		"TO" => 'Tonga',
		"TT" => 'Trinidad and Tobago',
		"TN" => 'Tunisia',
		"TR" => 'Turkey',
		"TM" => 'Turkmenistan',
		"TC" => 'Turks and Caicos Islands',
		"TV" => 'Tuvalu',
		"UM" => 'U.S. Minor Outlying Islands',
		"PU" => 'U.S. Miscellaneous Pacific Islands',
		"VI" => 'U.S. Virgin Islands',
		"UG" => 'Uganda',
		"UA" => 'Ukraine',
		"SU" => 'Union of Soviet Socialist Republics',
		"AE" => 'United Arab Emirates',
		"GB" => 'United Kingdom',
		"US" => 'United States',
		"ZZ" => 'Unknown or Invalid Region',
		"UY" => 'Uruguay',
		"UZ" => 'Uzbekistan',
		"VU" => 'Vanuatu',
		"VA" => 'Vatican City',
		"VE" => 'Venezuela',
		"VN" => 'Vietnam',
		"WK" => 'Wake Island',
		"WF" => 'Wallis and Futuna',
		"EH" => 'Western Sahara',
		"YE" => 'Yemen',
		"ZM" => 'Zambia',
		"ZW" => 'Zimbabwe',
		"AX" => 'Åland Islands',
	);

    return ($country == '') ? $countries : (isset($countries[$country]) ? $countries[$country] : '');
}

function suwcwam_send_messages($k, $order, $phone) {
    if (empty($k) || empty($order) || empty($phone)) return;
 
    //Text Message
    $send_text = suwcwam_field("send_{$k}_text");
    $text_template = trim(suwcwam_field("{$k}_text_template"));
    if ($send_text && $text_template) {
        $text = suwcwam_process_variables($text_template, $order);
        suwcwam_send_text($phone, $text);
    }

    //Stored Template Message
    $send_stored_template = suwcwam_field("send_{$k}_stored_template");
    $stored_template_id = trim(suwcwam_field("{$k}_template_id"));
    if ($send_stored_template && $stored_template_id) {
        $vars = suwcwam_field("{$k}_template_variables");
        $vars_json = empty($vars) ? '' : suwcwam_process_variables(json_encode($vars), $order);
        $template_id = suwcwam_process_variables($stored_template_id, $order);
        suwcwam_send_stored_template($phone, $template_id, $vars_json);
    }

    //Media
    $send_media = suwcwam_field("send_{$k}_media");
    $media_url = trim(suwcwam_field("{$k}_media_url"));
    $media_type = suwcwam_field("{$k}_media_type");
    $media_caption = suwcwam_process_variables(suwcwam_field("{$k}_media_caption"), $order);
    if ($send_media && $media_url && $media_type) {
        $url = suwcwam_process_variables($media_url, $order);
        suwcwam_send_media($phone, $url, $media_type, $media_caption);
    }

    //Custom PDF
    $send_custom_pdf = suwcwam_field("send_{$k}_custom_pdf");
    $custom_pdf_template = trim(suwcwam_field("{$k}_custom_pdf_template"));
    $pdf_filename = suwcwam_process_variables(suwcwam_field("{$k}_filename"), $order);
    if ($send_custom_pdf && $custom_pdf_template) {
        $html = suwcwam_process_variables($custom_pdf_template, $order);
        suwcwam_send_html_pdf($phone, $html, $order->get_id(), $pdf_filename);
    }
}

function suwcwam_generate_pdf($html, $prefix='') {
    //Create uploads subfolder if missing
    $upload_dir = wp_upload_dir();
    $basedir = $upload_dir['basedir'];
    $baseurl = $upload_dir['baseurl'];
    $pdfdir = $basedir . '/suwcwam';
    if (!file_exists($pdfdir)) {
        wp_mkdir_p($pdfdir);
    }

    //Generate file path and url
    $sub_path = '/' . uniqid($prefix) . '.pdf';
    $filepath = $pdfdir . $sub_path;
    $file_url = $baseurl . '/suwcwam' . $sub_path;

    //Generate and save PDF
    $pdf = new TCPDF();
    $pdf->AddPage();
    $pdf->writeHtml($html);
    $pdf->output($filepath, 'F');

    //Return the PDF URL
    return $file_url;
}

function suwcwam_send_stored_template($phone, $template_id, $vars_json='') {
    return suwcwam_send_wam($phone, $vars_json, $template_id);
}

function suwcwam_send_text($phone, $text) {
    return suwcwam_send_wam($phone, $text);
}

function suwcwam_send_media($phone, $url, $mediaType, $caption='') {
    return suwcwam_send_wam($phone, $caption, '', $url, $mediaType);
}

function suwcwam_send_html_pdf($phone, $html, $prefix='', $filename='') {
    $url = suwcwam_generate_pdf($html, $prefix);
    return suwcwam_send_media($phone, $url, 'document', $filename);
}