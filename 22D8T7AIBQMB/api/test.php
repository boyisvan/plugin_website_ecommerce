<?php
require_once "base_request.php";
$request = new base_request();
$method = $_SERVER['REQUEST_METHOD'];
if($method == 'POST' or $method == 'post') {
    $json = file_get_contents('php://input');
    $item = json_decode($json, true);
    $request->success($item);
}