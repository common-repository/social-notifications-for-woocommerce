<?php

if (!defined('ABSPATH')) exit;

global $suwcwam_settings, $wpml_active;

function suwcwam_gets_value($var, $check = false)
{
    global $suwcwam_settings;
    $retval = '';
    if (isset($suwcwam_settings[$var])) {
        if ($check) {
            if ($suwcwam_settings[$var] == 1) {
                $retval = 'checked="checked"';
            }
        } else {
            $retval = $suwcwam_settings[$var];
        }
    }
    return is_array( $retval ) ? array_map( 'esc_attr', $retval ) : esc_attr( $retval );
}
?>
<style>
h3.title {
	background-color: #ddd !important;
	padding: 10px;
}
#template_settings {
    width: 70%;
    display: inline-block;
    margin: 0;
}
#edit_instructions {
    width: 25%;
    display: inline-block;
    margin: 0;
    padding: 0 10px;
    background-color: #ddd;
    vertical-align: top;
    margin-top: 1rem;
}
.setting-table {
    padding: 6px 12px;
}
.template-toggle {
    padding: 0 0 1em;
}
a.del-input {
    font-weight: bold;
    cursor: pointer;
    font-size: x-large;
    vertical-align: middle;
    color: red;
}
table.msg-templates tr {
    border-top: 1px solid #ddd;
}
</style>
<div class="wrap woocommerce">
  <?php settings_errors(); ?>

  <h2>Social Notifications for WooCommerce</h2>
  <?php _e('Allows WooCommerce to send WhatsApp notifications on each order status change. It can also notify the owner when a new order is received. You can also send notifications for custom status, and use custom variables.', 'suwcwam'); ?>
  <br/>
  
  <form method="post" action="options.php" id="mainform">
    <?php settings_fields('suwcwam_settings_group'); ?>
    
    <h3 class="title">Account Credentials</h3>
    <?php _e('You can obtain credentials by registering at <a href="http://mtalkz.com/product/woocommerce-wa-plugin/" target="_blank">our site</a>', 'suwcwam'); ?>
    <br/>
    <table class="form-table">
    <?php
    $reg_fields = array(
        'token' => ['API Token', 'as provided by'],
        'sender' => ['Sender WhatsApp Number', 'as provided by'],
        'mnumber' => ['Shop Owner WhatsApp Number', 'registered with'],
    );

    foreach ($reg_fields as $k => $v) {
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $v[0] ); ?></label>
                <?php _e(wc_help_tip(esc_html(sprintf(__("%s %s mTalkz", 'suwcwam'), __($v[0], 'suwcwam'), __($v[1], 'suwcwam')))), 'suwcwam'); ?>
            </th>
            <td class="forminp">
                <input type="text" id="<?php echo esc_attr( $k ); ?>" name="suwcwam_settings[<?php echo esc_attr( $k ); ?>]" size="50" value="<?php echo esc_attr( suwcwam_gets_value($k) ); ?>" <?php echo ($k != 'mnumber') ? 'required="required"' : ''; ?>/>
            </td>
        </tr>
    <?php
}
?>    
    </table>
    <span id="template_settings">
    <h3 class="title">WhatsApp Templates</h3>
    <ol>
        <li>
            <b><?php _e('All WhatsApp template changes need to be whitelisted. Please do not modify the templates below unless you receive approval mail for a change.', 'suwcwam'); ?></b>
        </li>
        <li>
            <?php
            _e('You can use following variables in your templates:', 'suwcwam');

            $vars = array('id', 'order_key', 'billing_first_name', 'billing_last_name', 'billing_company', 'billing_address_1', 'billing_address_2', 'billing_city', 'billing_postcode', 'billing_country', 'billing_state', 'billing_email', 'billing_phone', 'shipping_first_name', 'shipping_last_name', 'shipping_company', 'shipping_address_1', 'shipping_address_2', 'shipping_city', 'shipping_postcode', 'shipping_country', 'shipping_state', 'shipping_method', 'shipping_method_title', 'payment_method', 'payment_method_title', 'order_discount', 'cart_discount', 'order_tax', 'order_shipping', 'order_shipping_tax', 'order_total', 'status', 'prices_include_tax', 'tax_display_cart', 'display_totals_ex_tax', 'display_cart_ex_tax', 'order_date', 'modified_date', 'customer_message', 'customer_note', 'post_status', 'shop_name', 'order_product');

            foreach ($vars as $var) {
                echo ' <code>%' . esc_html( $var ) . '%</code>';
            }
            ?>
        </li>
        <li>
            <?php _e('<b>CAUTION:</b> Any undefined variable will be included as it is upon its use.', 'suwcwam'); ?>
        </li>
        <li>
            <?php _e('You can also add custom variables which are created by other plugins, and are part of order meta. Each variable must be entered onto a new line without percentage character ( % ). Example: <code>_custom_variable_name</code> <code>_another_variable_name</code>.', 'suwcwam'); ?>
        </li>
        <li>
            <?php _e('You can use WhatsApp formatting characters in text messages: <code>*text*</code> for <b>bold text</b>, <code>_text_</code> for <i>italicized text</i>, <code>~text~</code> for <del>strikethrough text</del>, and <code>```text```</code> for <span style="font-family:monospace">monospace text</span>.', 'suwcwam'); ?>
        </li>
        <li>
            <?php _e('You can use <code>%nl%</code> for line breaks in the message template.', 'suwcwam'); ?>
        </li>
    </ol>
    
    <table class="form-table">
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="variables"><?php _e('Custom variables', 'suwcwam'); ?></label>
            </th>
            <td class="forminp forminp-number">
                <textarea id="variables" name="suwcwam_settings[variables]" cols="50" rows="5" ><?php echo stripcslashes(suwcwam_gets_value('variables')); ?></textarea>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="signature"><?php _e('Signature', 'suwcwam'); ?></label>
                <?php _e(wc_help_tip('Text to append to all client messages. E.g., Reach us at support@yoursite.com'), 'suwcwam'); ?>
            </th>
            <td class="forminp">
                <input type="text" id="signature" name="suwcwam_settings[signature]" size="50" value="<?php echo esc_attr( suwcwam_gets_value('signature') ); ?>"/>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="addnumber"><?php _e('Additional Numbers', 'suwcwam'); ?></label>
                <?php _e(wc_help_tip('Additional Numbers for New Order Notifications: comma-separated'), 'suwcwam'); ?>
            </th>
            <td class="forminp">
                <input type="text" id="addnumber" name="suwcwam_settings[addnumber]" size="50" value="<?php echo esc_attr( suwcwam_gets_value('addnumber') ); ?>"/>
            </td>
        </tr>
    </table>

    <table class="form-table msg-templates">
    <?php
    $templates = array(
        'msg_new_order' => array(
            'New Order message',
            'Message sent to you on receipt of a new order',
            isset($suwcwam_settings['msg_new_order']) ? $suwcwam_settings['msg_new_order'] : "Order %id% has been received on %shop_name%."
        ),
        'msg_pending' => array(
            'Pending Payment message',
            'Message sent to the client when a new order is awaiting payment',
            isset($suwcwam_settings['msg_pending']) ? $suwcwam_settings['msg_pending'] : "Dear %billing_first_name%, your order on %shop_name% is awaiting payment. %signature%"
        ),
        'msg_on_hold' => array(
            'On-Hold message',
            'Message sent to the client when an order goes on-hold',
            isset($suwcwam_settings['msg_on_hold']) ? $suwcwam_settings['msg_on_hold'] : "Dear %billing_first_name%, your order %id% on %shop_name% is on-hold. %signature%"
        ),
        'msg_processing' => array(
            'Order Processing message',
            'Message sent to the client when an order is under process',
            isset($suwcwam_settings['msg_processing']) ? $suwcwam_settings['msg_processing'] : "Dear %billing_first_name%, your order %id% on %shop_name% is being processed. %signature%"
        ),
        'msg_completed' => array(
            'Order Completed message',
            'Message sent to the client when an order is completed',
            isset($suwcwam_settings['msg_completed']) ? $suwcwam_settings['msg_completed'] : "Dear %billing_first_name%, your order %id% on %shop_name% has been completed. %signature%"
        ),
        'msg_cancelled' => array(
            'Order Cancelled message',
            'Message sent to the client when an order is cancelled',
            isset($suwcwam_settings['msg_cancelled']) ? $suwcwam_settings['msg_cancelled'] : "Dear %billing_first_name%, your order %id% on %shop_name% has been cancelled. %signature%"
        ),
        'msg_refunded' => array(
            'Payment Refund message',
            'Message sent to the client when an order payment is refunded',
            isset($suwcwam_settings['msg_refunded']) ? $suwcwam_settings['msg_refunded'] : "Dear %billing_first_name%, payment for your order %id% on %shop_name% has been refunded. It may take a few business days to reflect in your account. %signature%"
        ),
        'msg_failed' => array(
            'Payment Failure message',
            'Message sent to the client when a payment fails',
            isset($suwcwam_settings['msg_failed']) ? $suwcwam_settings['msg_failed'] : "Dear %billing_first_name%, recent attempt for payment towards your order on %shop_name% has failed. Please retry by visiting order history in My Account section. %signature%"
        ),
        'msg_custom' => array(
            'Custom Status message',
            'Message sent to the client when order moves to a custom status (defined by other plugins)',
            isset($suwcwam_settings['msg_custom']) ? $suwcwam_settings['msg_custom'] : "Dear %billing_first_name%, your order %id% on %shop_name% has been %status%. Please review your order. %signature%"
        )
    );

    foreach ($templates as $k => $a) :
        $names = [ "send_{$k}_text", "{$k}_text_template", "send_{$k}_stored_template", "{$k}_template_id", "{$k}_template_variables", "send_{$k}_media", "{$k}_media_url", "send_{$k}_custom_pdf", "{$k}_custom_pdf_template", "{$k}_media_type", "{$k}_media_caption", "{$k}_filename" ];
        $send_text = suwcwam_gets_value($names[0]) ?: 0;
        $text_template = suwcwam_gets_value($names[1]) ?: $a[2];
        $send_stored_template = suwcwam_gets_value($names[2]) ?: 0;
        $stored_template_id = suwcwam_gets_value($names[3]) ?: '';
        $template_variables = suwcwam_gets_value($names[4]) ?: [''];
        $send_media = suwcwam_gets_value($names[5]) ?: 0;
        $media_url = suwcwam_gets_value($names[6]) ?: '';
        $send_custom_pdf = suwcwam_gets_value($names[7]) ?: 0;
        $custom_pdf_template = suwcwam_gets_value($names[8]) ?: '';
        $media_type = suwcwam_gets_value($names[9]) ?: '';
        $media_caption = suwcwam_gets_value($names[10]) ?: '';
        $filename = suwcwam_gets_value($names[11]) ?: '';
    ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label><?php _e(esc_html($a[0]), 'suwcwam'); ?></label>
                <?php _e(wc_help_tip(esc_html($a[1])), 'suwcwam'); ?>
            </th>
            <td class="forminp">
              <div class="row">
                <input type="checkbox" class="template_cb" name="suwcwam_settings[<?php echo esc_attr( $names[0] ); ?>]" id="<?php echo esc_attr( $names[0] ); ?>" value="1" <?php checked($send_text, 1) ?>/>
                <label for="<?php echo esc_attr( $names[0] ); ?>">Send Text Message</label>
                <div class="template-toggle">
                    <input type="text" name="suwcwam_settings[<?php echo esc_attr( $names[1] ); ?>]" id="<?php echo esc_attr( $names[1] ); ?>" value="<?php echo esc_attr( $text_template ); ?>" required="required" placeholder="Message Text"/>
                </div>
              </div>

              <div class="row">
                <input type="checkbox" class="template_cb" name="suwcwam_settings[<?php echo esc_attr( $names[2] ); ?>]" id="<?php echo esc_attr( $names[2] ); ?>" value="1" <?php checked($send_stored_template, 1) ?>/>
                <label for="<?php echo esc_attr( $names[2] ); ?>">Send Stored Template</label>
                <div class="template-toggle">
                    <input type="text" name="suwcwam_settings[<?php echo esc_attr( $names[3] ); ?>]" id="<?php echo esc_attr( $names[3] ); ?>" value="<?php echo esc_attr( $stored_template_id ); ?>" required="required" placeholder="Template ID"/>
                    <p><b>Template Variable Values</b></p>
                    <div id="<?php echo esc_attr( $names[4] ); ?>" class="setting-table">
                    <ol start="0">
                    <?php foreach( $template_variables as $i => $v ) { ?>
                        <li class="setting-row">
                            <input name="suwcwam_settings[<?php echo esc_attr( $names[4] ); ?>][]" type="text" size="30" value="<?php echo esc_attr( $v ); ?>"/>
                            <a class="del-input"> &times; </a>
                        </li>
                    <?php } ?>
                    </ol>
                    </div>
                    <a class="add_link button"><?php _e('Add a Value', 'suwcwam'); ?></a>
                </div>
              </div>

              <div class="row">
                <input type="checkbox" class="template_cb" name="suwcwam_settings[<?php echo esc_attr( $names[5] ); ?>]" id="<?php echo esc_attr( $names[5] ); ?>" value="1" <?php checked($send_media, 1) ?>/>
                <label for="<?php echo esc_attr( $names[5] ); ?>">Send Media</label>
                <div class="template-toggle">
                    <select id="<?php echo esc_attr( $names[9] ); ?>" name="suwcwam_settings[<?php echo esc_attr( $names[9] ); ?>]">
                        <option value='document' <?php echo esc_html( $media_type == 'document' ? 'selected' : '' ); ?>>Document</option>
                        <option value='image' <?php echo esc_html( $media_type == 'image' ? 'selected' : '' ); ?>>Image</option>
                        <option value='video' <?php echo esc_html( $media_type == 'video' ? 'selected' : '' ); ?>>Video</option>
                        <option value='audio' <?php echo esc_html( $media_type == 'audio' ? 'selected' : '' ); ?>>Audio</option>
                    </select>
                    <input type="url" id="<?php echo esc_attr( $names[6] ); ?>" name="suwcwam_settings[<?php echo esc_attr( $names[6] ); ?>]" value="<?php echo esc_attr( $media_url ); ?>" required="required" placeholder="Media URL"/>
                    <input type="text" id="<?php echo esc_attr( $names[10] ); ?>" name="suwcwam_settings[<?php echo esc_attr( $names[10] ); ?>]" value="<?php echo esc_attr( $media_caption ); ?>" placeholder="Media Caption/File Name"/>
                </div>
              </div>

              <div class="row">
                <input type="checkbox" class="template_cb" name="suwcwam_settings[<?php echo esc_attr( $names[7] ); ?>]" id="<?php echo esc_attr( $names[7] ); ?>" value="1" <?php checked($send_custom_pdf, 1) ?>/>
                <label for="<?php echo esc_attr( $names[7] ); ?>">Send Custom PDF</label>
                <div class="template-toggle">
                    <?php wp_editor($custom_pdf_template, $names[8], [
                        'textarea_name' => "suwcwam_settings[{$names[8]}]",
                        'media_buttons' => false,
                    ]); ?>
                    <b>Supported Tags:</b> <em>a, b, blockquote, br, dd, del, div, dl, dt, em, font, h1, h2, h3, h4, h5, h6, hr, i, img, li, ol, p, pre, small, span, strong, sub, sup, table, td, th, thead, tr, tt, u, ul</em>
                </div>
                <input type="text" id="<?php echo esc_attr( $names[11] ); ?>" name="suwcwam_settings[<?php echo esc_attr( $names[11] ); ?>]" value="<?php echo esc_attr( $filename ); ?>" placeholder="PDF Filename"/>
              </div>
            </td>
        </tr>
    <?php endforeach; ?>
    </table>
    
    <h3 class="title">Additional Settings</h3>
    <table class="form-table">
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="log_wam"><?php _e('Keep A Log', 'suwcwam'); ?></label>
            </th>
            <td class="forminp">
                <input id="log_wam" name="suwcwam_settings[log_wam]" type="checkbox" value="1" <?php echo suwcwam_gets_value('log_wam', true); ?> /> <?php _e('Maintain a log of all WhatsApp activities', 'suwcwam'); ?>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="require_otp"><?php _e('Require OTP Verification', 'suwcwam'); ?></label>
            </th>
            <td class="forminp">
                <input id="require_otp" name="suwcwam_settings[require_otp]" type="checkbox" value="1" <?php echo suwcwam_gets_value('require_otp', true); ?> /> <?php _e('Require OTP verification for opted-in WhatsApp Number', 'suwcwam'); ?> <small>(Requires <a href="//wordpress.org/plugins/sms-notifications-for-woocommerce/">WooCommerce SMS Notifications</a> plugin)</small>
            </td>
        </tr>
    </table>
    </span>
    <span id="edit_instructions">
    <h2>Instructions for Template Editing</h2>
    <ol>
        <li>Enable the "Send ..." checkbox for an event, for which you wish to edit the template.</li>
        <li>You can send a text message, a media file, or a custom PDF file for any order event.</li>
        <li>You can also send any combination of the above for any order event.</li>
        <li>All the message templates of all types (text, media, PDF) require to be whitelisted before they can be used by WhatsApp notifications.</li>
        <li>If you wish to modify a template, drop a mail to <a href="mailto:support@mtalkz.com">support@mtalkz.com</a> with the message template.</li>
        <li>When a template is approved/rejected, you will receive a notification for the same on email.</li>
        <li>After the message template has been approved, you need to update the template in corresponding input box for it to be used.</li>
        <li>Once all desired templates have been modified, click on "Save Changes" button.</li>
    </ol>
    </span>
    <p class="submit">
        <input class="button-primary" type="submit" value="<?php _e('Save Changes', 'suwcwam'); ?>"  name="submit" id="submit" />
    </p>
  </form>
</div>
<script type="text/javascript">
    jQuery(document).ready(function($){
        $(".forminp").on("click","a.del-input",function(){
            $(this).closest("li.setting-row").remove();
        });
        $("input.template_cb").change(function(){
            var $toggle = $(this).siblings("div.template-toggle"),
                $input = $toggle.find(">input:first");
            console.log(this.id, $toggle.length, $input.legnth);
            if(this.checked){
                $toggle.show();
                $input.attr("required",true).focus();
            } else {
                $toggle.hide();
                $input.attr("required",false);
            }
        }).change();
        $("a.add_link.button").click(function(){
            var $prev = $(this).siblings("div.setting-table"),
                $last = $prev.find("li.setting-row:last"),
                $dup = $last.clone();
            $dup.insertAfter($last);
        });
        if ( $('#key').val() == '' || $('#sender').val() == '' )
            $('#template_settings, #edit_instructions').hide();
    });
</script>