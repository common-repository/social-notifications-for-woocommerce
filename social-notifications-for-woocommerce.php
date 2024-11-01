<?php
/*
Plugin Name: Social Notifications for WooCommerce
Version: 1.1.2
Plugin URI: http://mtalkz.com/
Description: Sends WhatsApp notifications to your clients for order status changes. You can also receive a WhatsApp message when a new order is received.
Author URI: http://skilsup.in/
Author: SkillsUp, mTalkz
Text Domain: suwcwam
Requires at least: 3.8
Tested up to: 6.1
*/

//Exit if accessed directly
if (!defined('ABSPATH')) {
    exit();
}

//Define text domain
$suwcwam_plugin_file = plugin_basename(__FILE__);
load_plugin_textdomain('suwcwam', false, dirname($suwcwam_plugin_file) . '/languages');

//Add links to plugin listing
add_filter("plugin_action_links_$suwcwam_plugin_file", 'suwcwam_add_action_links');
function suwcwam_add_action_links($links)
{
    $links[] = '<a href="' . admin_url("admin.php?page=suwcwam") . '">' . __('Settings', 'suwcwam') . '</a>';
    $links[] = '<a href="https://mtalkz.com/whatsapp-woocommerce-plugin/" target="_blank">' . __('Plugin Documentation', 'suwcwam') . '</a>';
    return $links;
}

//Add links to plugin settings page
add_filter('plugin_row_meta', "suwcwam_plugin_row_meta", 10, 2);
function suwcwam_plugin_row_meta($links, $file)
{
    global $suwcwam_plugin_file;
    if (strpos($file, $suwcwam_plugin_file) !== false) {
        $links[] = '<a href="https://mtalkz.com/whatsapp-business-api/" target="_blank">' . __('Get Credentials', 'suwcwam') . '</a>';
        $links[] = '<a href="https://mtalkz.com/whatsapp-woocommerce-plugin/" target="_blank">' . __('Plugin Documentation', 'suwcwam') . '</a>';
    }
    return $links;
}

//WooCommerce is required for the plugin to work
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    include('plugin-core.php');
} else {
    add_action('admin_notices', 'suwcwam_require_wc');
    function suwcwam_require_wc()
    {
        echo '<div class="error fade" id="message"><h3>' . __('Social Notifications for WooCommerce', 'suwcwam') . '</h3><h4>' . __("This plugin requires WooCommerce", 'suwcwam') . '</h4></div>';
        deactivate_plugins($suwcwam_plugin_file);
    }
}

//Handle uninstallation
register_uninstall_hook(__FILE__, 'suwcwam_uninstaller');
function suwcwam_uninstaller()
{
    delete_option('suwcwam_settings');
}
