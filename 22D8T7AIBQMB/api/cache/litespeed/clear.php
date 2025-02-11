<?php
require_once "../../../../../../wp-config.php";
include "../../base_request.php";

class clear_lite_speed_cache extends base_request
{
    function clear_cache_post($post_ids){
        foreach ($post_ids as $post_id){
            do_action( 'litespeed_purge_post', $post_id );
        }
        $this->success(array('post_ids' => $post_ids));
    }
    function clear_all(){
        do_action( 'litespeed_purge_all' );
        $this->success('clear_all');
    }
}

$request = new clear_lite_speed_cache();
$method = $_SERVER['REQUEST_METHOD'];
$setting_key = '';
if(isset($_GET['action']))
    $action = $_GET['action'];

if ($method == 'POST' || $method == 'post') {
    $json = file_get_contents('php://input');
    $item = json_decode($json, true);
    switch ($action) {
        case 'clear_all':
            $request->clear_all();
            break;
        case 'clear_cache_post':
        default:
            $request->clear_cache_post($item['ids']);
            break;
    }
}
