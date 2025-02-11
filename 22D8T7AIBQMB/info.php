<?php
$iniPath           = './config/info.ini';
$local_version     = "v2.1.2.10.4.2024.fix_duplicate_img_in_media";
$build_version    = 212; // 15.6.2023 -> 10.4.2024
if (file_exists($iniPath)) {
    $ini = parse_ini_file('./config/info.ini');
    $local_version = $ini['version'];
}
require_once "api/base_request.php";
$base_request = new base_request();
if (isset($_GET['action'])) {
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'];
    if ($action == "get_version") {
        $base_request->send_response(200, "success", array("current_hook_version" => $local_version, "build_version" => $build_version));
    } else {
        $base_request->error($base_request->create_error('not_support_action', 'Support action: get_version'));
    }
} else {
    $base_request->error($base_request->create_error('not_found_action', 'Support action: get_version'));
}
