<?php
include "../../config/systemConfig.php";
include "../base_request.php";
require_once "../../../../../wp-config.php";
require_once "../../../../../wp-includes/option.php";
class change_paypal extends base_request
{
    // PayPal Checkout: woocommerce_ppec_paypal_settings
    // PayPal Standard: woocommerce_paypal_settings
    function get_setting($setting_key)
    {
        $settings_array = (array) get_option($setting_key, array());
        $this->success(array('settings' => $settings_array));
    }
    function change_setting($setting_name, $new_setting)
    {
        $settings_array = (array) get_option($setting_name, array());

        foreach ($new_setting as $key => $val) {
            if (isset($settings_array[$key]))
                $settings_array[$key] = $val;
            else
                $settings_array[$key] = $val; // 02.3.2022 Nhỡ setting nó chưa có thì vẫn set chứ nhỉ
        }
        update_option($setting_name, $settings_array);
        $this->success(array('settings' => $settings_array));
    }
}

$request = new change_paypal();
$method = $_SERVER['REQUEST_METHOD'];
$setting_key = '';
if (isset($_GET['setting_key'])) {
    $setting_key = $_GET['setting_key'];
    if ($method == 'GET' || $method == 'get') {
        $request->get_setting($setting_key);
    } else if ($method == 'POST' || $method == 'post') {
        $json = file_get_contents('php://input');
        $item = json_decode($json, true);
        switch ($action) {
            default:
                $request->change_setting($setting_key, $item);
                break;
        }
    }
} else {
    $request->error($request->create_error('not_found_setting_key', 'Not found ?setting_key param'));
}
