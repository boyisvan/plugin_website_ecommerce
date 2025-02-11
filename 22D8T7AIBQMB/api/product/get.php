<?php

require_once "../../config/systemConfig.php";
require_once "../base_request.php";
require_once "../database.php";
require_once "functions.php";

class get_product extends base_request{
    function __construct(){
        $this->database = new database();
    }
    
    function get_product($id, $add_fields = null){
        global $table_posts;
        global $product_function;

        $product = $product_function->get_product(array($id), $add_fields);
        $res = null;
        if(count($product) >= 1)
            $this->success(array('product'=>$product[0]));
        else
            $this->warning($this->create_warning('not_found', 'Not found'), array('product'=>null));
    }
}

$request = new get_product();

$method = $_SERVER['REQUEST_METHOD'];
if ($method == 'GET' || $method == 'get') {
    $id = 0;
    $add_fields = null;
    if(isset($_GET['id']))
        $id = $_GET['id'];
    if(isset($_GET['add_fields']))
        $add_fields = $_GET['add_fields'];

    if(isset($_GET['action'])) {
        $action = $_GET['action'];

        switch($action){
            case 'get_list':
                $request->get_product($id, $add_fields);
            break;
            case 'test':
                $request->success('ok');
            break;
        }

        if(method_exists($list, $action)){
            $request->{$action}();
        }
        else{
            $request->error($list->create_error('not_found_action', 'Not found action you need'));
        }
    }else {
        $request->get_product($id, $add_fields);
    }
}else{
    $request->error($request->create_error('not_match_method', 'Must use GET method'));
}