<?php
$iniPath           = './config/info.ini';
$local_version     = "01.8.2020";
if(file_exists($iniPath)) {
    $ini = parse_ini_file('./config/info.ini');
    $local_version = $ini['version'];
}

if(isset($_GET['action'])) {
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'];
    if($action == "get_version"){
        require_once "default_struct/base_request.php";
        $base_request = new base_request();
        $base_request->response(200, array("current_hook_version" => $local_version));
    }
    else {
        die("Support action: get_version");
    }
}
else{
    die("Not found param: action");
}