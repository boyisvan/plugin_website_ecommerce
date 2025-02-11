<?php
include "../../config/systemConfig.php";
include "../base_request.php";
include "database/delete_database.php";

class delete extends base_request{
    function delete_product_by_sku($sku, $delete_parent, $delete_variation){
        global $dbContext;
        global $db_delete;
        if ($dbContext == null) {
            $this->success('Không thể kết nối mysql. Vui lòng cấu hình và khởi động lại web');
        } else {
            $count = $db_delete->delete_product_by_sku($dbContext, $sku, $delete_parent, $delete_variation);
        }

        $this->success(array('count' => $count));
    }

    function delete_product_by_id($id, $delete_parent, $delete_variation){
        global $dbContext;
        global $db_delete;
        
        if ($dbContext == null) {
            $this->success('Không thể kết nối mysql. Vui lòng cấu hình và khởi động lại web');
        } else {
            $count = $db_delete->delete_product_by_id($dbContext, $id, $delete_parent, $delete_variation);
            $this->success(array('count' => $count));
        }
    }

    function clear_woocommeerce_transients($id_product = null)
    {
        global $dbContext;
        if ($dbContext == null) {
            $this->error($this->create_error('clear_woocommeerce_transients', 'Can not connect database'));
        } else {
            $param = $id_product == null ? 'null' : $id_product;
            $sql = "call clear_woocommeerce_transients($param)";
            $dbContext->query($sql);;
            if ($id_product == null)
                $this->success("clear all transients success $sql");
            else
                $this->success("clear transients of product have ID=$id_product success");
        }
    }

    function clear_woocommeerce_transients_price($id_product = null)
    {
        global $dbContext;
        if ($dbContext == null) {
            $this->error($this->create_error('clear_woocommeerce_transients', 'Can not connect database'));
        } else {
            $param = $id_product == null ? 'null' : $id_product;
            $sql = "call clear_woocommeerce_transients_price($param)";
            $dbContext->query($sql);;
            if ($id_product == null)
                $this->success("clear all transients price success $sql");
            else
                $this->success("clear transients price of product have ID=$id_product success");
        }
    }
}

$delete = new delete();
$method = $_SERVER['REQUEST_METHOD'];
if($method=='DELETE' || $method=='delete' || $method=='post' || $method=='POST') {
    $json = file_get_contents('php://input');
    $item = json_decode($json, true);
    if(isset($_GET['action'])) {
        $action = $_GET['action'];
        switch($action){
            case 'delete_product':
                $delete_parent = false;
                $delete_variation = false;
                if(isset($item['delete_parent']) && $item['delete_parent'] == true)
                    $delete_parent = true;
                if(isset($item['delete_variation']) && $item['delete_variation'] == true)
                    $delete_variation = true;

                if($delete_parent == false)
                    $delete_parent = '0';
                if($delete_variation == false)
                    $delete_variation = '0';

                if(isset($item['sku'])){
                    $sku  = $item['sku'];
                    $delete->delete_product_by_sku($sku, $delete_parent, $delete_variation);
                }else if(isset($item['id'])){
                    $id = $item['id'];
                    $delete->delete_product_by_id($id, $delete_parent, $delete_variation);
                }else{+
                    $import->error(406, $import->create_error('not_found_param', 'Not found sku or from and to'));
                }
                break;
            case 'delete_transients':
                $id_product = null;
                if(isset($item['id']))
                    $id_product=$item['id'];
                $delete->clear_woocommeerce_transients($id_product);
                break;
            case 'delete_transients_price':
                $id_product = null;
                if(isset($item['id']))
                    $id_product=$item['id'];
                $delete->clear_woocommeerce_transients_price($id_product);
                break;
        }
    }
    else{
        $delete_parent = false;
        $delete_variation = false;
        if(isset($item['delete_parent']) && $item['delete_parent'] == true)
            $delete_parent = true;
        if(isset($item['delete_variation']) && $item['delete_variation'] == true)
            $delete_variation = true;
        if($delete_parent == false)
            $delete_parent = '0';
        if($delete_variation == false)
            $delete_variation = '0';

        if(isset($item['sku'])){
            $sku  = $item['sku'];
            $delete->delete_product_by_sku($sku, $delete_parent, $delete_variation);
        }else if(isset($item['id'])){
            $id = $item['id'];
            $delete->delete_product_by_id($id, $delete_parent, $delete_variation);
        }else{
            $delete->error(406, $delete->create_error('not_found_param', 'Not found sku or from and to'));
        }
    }
}else{
    $delete->error(406, $delete->create_error('not_match_method', 'Must use DELETE or POST method'));
}