<?php
include "../../config/systemConfig.php";
include "../base_request.php";
require_once "../../../wp-includes/option.php";
class change_paypal extends base_request
{

}

$request = new change_paypal();
$method = $_SERVER['REQUEST_METHOD'];
if ($method == 'GET' || $method == 'get') {
    $request->success('ok');
}