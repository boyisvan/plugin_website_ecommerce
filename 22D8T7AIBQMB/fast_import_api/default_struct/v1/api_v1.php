<?php

include "../../../config/systemConfig.php";
include "../base_request.php";
include "database.php";

class api_v1 extends base_request{

    function __construct()
    {
    }

    function __destruct()
    {

    }

    function test()
    {
        $systemConfig = new systemConfig();
        $dbContext = $systemConfig->connectDB();
        if ($dbContext == null) {
            $this->response(503, 'Không thể kết nối mysql. Vui lòng cấu hình và khởi động lại web');
        } else {
            $this->response(404, 'Kết nối thành công');
        }
    }


    //END POINT: codethue_importwoo/default_struct/v1/api.php/insert_product/{id: optional}
    function insert_product($item, $idParent=0)
    {
        global $dbContext;
        global $db_v1;
        if ($dbContext == null) {
            $this->response(503, 'Không thể kết nối mysql. Vui lòng cấu hình và khởi động lại web');
        } else {
            //$item = $this->inputJson;
            $item = $this->process_input($item);
            if ($idParent == 0) {
                $idProductImportedInFiveMinute = $db_v1->check_sku_imported_in_five_minute($dbContext, $item['sku']);
                if(strlen($item['sku']) > 0 && $idProductImportedInFiveMinute != null){
                    $this->response(200, array('sku' => $item['sku'],
                    'id_product' => $idProductImportedInFiveMinute,
                    'message' => 'trung sku'));
                }
                else {
                    $product_type = 'simple';
                    if (isset($item['product_type']))
                        $product_type = $item['product_type'];

                    switch ($product_type) {
                        case 'simple':
                        case 'external':
                            $idProduct = $db_v1->insert_simple_product_to_database($dbContext, $item);
                            $this->response(200, array('sku' => $item['sku'], 'id_product' => $idProduct));
                            break;
                        case 'variable':
                            $idProduct = $db_v1->insert_variable_product_to_database($dbContext, $item);
                            $this->response(200, array('sku' => $item['sku'], 'id_product' => $idProduct));
                            break;
                        default:
                            $this->response(406, 'type must be: simple or variable');
                            break;
                    }
                }
            } else {
                $product_slug = $item['slug'];
                $idVariant = $db_v1->insert_variant_to_database($idParent, $dbContext, $item, $product_slug);
                $this->response(200, array('sku' => $item['sku'], 'id_variant' => $idVariant));
            }
        }
    }

    //END POINT: /codethue_importwoo/default_struct/v1/api.php/clear_woocommeerce_transients/{id: optional}
    function clear_woocommeerce_transients($id_product = null)
    {
        global $dbContext;
        if ($dbContext == null) {
            $this->response(503, 'Can not connect mysql. Please config and reset server');
        } else {
            $param = $id_product == null ? 'null' : $id_product;
            $sql = "call clear_woocommeerce_transients_fast_api($param)";
            $dbContext->query($sql);;
            if ($id_product == null)
                $this->response(200, "clear all transients success $sql");
            else
                $this->response(200, "clear transients of product have ID=$id_product success");
        }
    }

    function insert_product_bulk($item)
    {
        //$this->response(200, $item);
        global $dbContext;
        global $db_v1;
        $code = 'create_product_bulk';

        if ($dbContext == null) {
            $this->response(503, 'Không thể kết nối mysql. Vui lòng cấu hình và khởi động lại web');
        } else {
            //$this->response(200, $item);

            if(false){//strlen($item['sku']) > 0 && $idProductImportedInFiveMinute != null){
                $idProductImportedInFiveMinute = $db_v1->check_sku_imported_in_five_minute($dbContext, $item['sku']);
                $this->response(200, array('sku' => $item['sku'],
                'id_product' => $idProductImportedInFiveMinute,
                'message' => 'trung sku'));
            }
            else {
                //$xx = $dbContext->real_escape_string($item['downloadable_files']);
                //$this->response(200, $xx);

                $item = $this->process_input($item);
                $product_type = 'simple';
                if (isset($item['product_type']))
                    $product_type = $item['product_type'];

            //     $this->response(200, $item['attributes']);
            //    foreach ($item['attributes'] as $attr){
            //        $this->response(200, $attr['name']);
            //    }
                $res_bulk = array();

                // Check trung sku
                $check_sku = $item['check_sku'] ?? false;
                if(isset($item['sku']) && $item['sku'] != '' && $check_sku == true){
                    $sku = $item['sku'];
                    $sql = "SELECT get_productid_from_sku('$sku') AS id_product;";
                    if($result = @$dbContext->query($sql)) {
                        $item['id'] = @$result->fetch_object()->id_product;
                        $result->free_result();
                        if(isset($item['id']) && $item['id'] && $item['id'] != ''){
                            $idProduct = $item['id'];
                            $res_bulk = array('id' => $idProduct, 'sku' => $sku);
                            if (isset($item['variations'])) {
                                $variants = $item['variations'];
                                foreach ($variants as $variant) {
                                    $res_bulk['variations'][] = array('id' => -1, 'sku' => 'check_sku_exists');
                                }
                                $this->success(array('product' => $res_bulk));
                            }
                        }
                    } else {
                        $this->error($this->create_error($code . 'error_database_check_sku', 'Can not connect database'));
                    }
                }

                $idProduct = 0;
                // die($product_type);
                switch ($product_type) {
                    case 'simple':
                    case 'external':
                        $idProduct = $db_v1->insert_simple_product_to_database($dbContext, $item);
                        $res_bulk = array('id' => $idProduct, 'sku' => $item['sku']);
                        break;
                    case 'variable':
                        $idProduct = $db_v1->insert_variable_product_to_database($dbContext, $item);
                        $res_bulk = array('id' => $idProduct, 'sku' => $item['sku']);
                        break;
                    default:
                        // $this->response(406, 'type must be: simple, variable, external');
                        $this->error($this->create_error('wrong_type', 'type must be: simple, variable, external'));
                        break;
                }

                if (isset($item['variants'])) {
                    $variants = $item['variants'];
                    foreach ($variants as $variant) {
                        $variant = $this->process_input($variant);
                        $product_slug = $item['slug'];
                        $id_variant = $db_v1->insert_variant_to_database($idProduct, $dbContext, $variant, $product_slug);
                        $res_bulk['variations'][] = array('id' => $id_variant, 'sku' => $variant['sku']);
                    }
                }

                if (isset($item['reviews'])) {
                    $reviews = $item['reviews'];
                    foreach ($reviews as $review) {
                        $id_review = $db_v1->insert_review($dbContext, $idProduct, $review);
                        $res_bulk['reviews'][] = array('id_review' => $id_review);
                    }
                }
                
                // $this->response(200, $res_bulk);
            $this->success(array('product' => $res_bulk));
            }
        }
    }

    function insert_review($id_product, $item){
        global $dbContext;
        global $db_v1;
        if ($dbContext == null) {
            $this->response(503, 'Không thể kết nối mysql. Vui lòng cấu hình và khởi động lại web');
        } else {
            $id_review = $db_v1->insert_review($dbContext, $id_product, $item);
            $this->response(200, array('id_product' => $id_product, 'id_review' => $id_review));
        }
    }

    function insert_term($item, $id_parent=0){
        global $dbContext;
        global $db_v1;
        if ($dbContext == null) {
            $this->response(503, 'Không thể kết nối mysql. Vui lòng cấu hình và khởi động lại web');
        } else {
            $id_term = $db_v1->insert_term($dbContext, $item, $id_parent);
            $this->response(200, array('id_term' => $id_term));
        }
    }

    function insert_attribute($item){
        global $dbContext;
        global $db_v1;
        if ($dbContext == null) {
            $this->response(503, 'Không thể kết nối mysql. Vui lòng cấu hình và khởi động lại web');
        } else {
            $id_attribute = $db_v1->insert_attribute($dbContext, $item);
            $this->response(200, array('id_attribute' => $id_attribute));
        }
    }

    function update_product($id_product, $item){
        global $dbContext;
        global $db_v1;
        if ($dbContext == null) {
            $this->response(503, 'Không thể kết nối mysql. Vui lòng cấu hình và khởi động lại web');
        } else {
            $id_product = $db_v1->update_product($dbContext, $id_product, $item);
            $this->response(200, array('id_product' => $id_product));
        }
    }

    function update_variant($id_variant, $item){
        global $dbContext;
        global $db_v1;
        if ($dbContext == null) {
            $this->response(503, 'Không thể kết nối mysql. Vui lòng cấu hình và khởi động lại web');
        } else {
            $id_variant = $db_v1->update_variant($dbContext, $id_variant, $item);
            $this->response(200, array('id_variant' => $id_variant));
        }
    }

    function update_product_bulk($id_product, $item){
        global $dbContext;
        global $db_v1;
        if ($dbContext == null) {
            $this->response(503, 'Không thể kết nối mysql. Vui lòng cấu hình và khởi động lại web');
        } else {
            $id_product = $db_v1->update_product($dbContext, $id_product, $item);
        }

        if (isset($item['variants'])) {
            $variants = $item['variants'];
            foreach ($variants as $variant) {
                $id_variant = $db_v1->update_variant($dbContext, $variant['id_variant'], $variant);
            }
        }

        $this->response(200, array('id_product' => $id_product));
    }

    function delete_product($sku){
        global $dbContext;
        global $db_v1;
        if ($dbContext == null) {
            $this->response(503, 'Không thể kết nối mysql. Vui lòng cấu hình và khởi động lại web');
        } else {
            $id_product = $db_v1->delete_product($dbContext, $sku);
        }

        $this->response(200, array('id_product' => $id_product));
    }

    function delete_product_by_id($from, $to){
        global $dbContext;
        global $db_v1;
        if ($dbContext == null) {
            $this->response(503, 'Không thể kết nối mysql. Vui lòng cấu hình và khởi động lại web');
        } else {
            $count = $db_v1->delete_product_by_id($dbContext, $from, $to);
            $this->response(200, array('count' => $count));
        }
    }

}

$api_v1 = new api_v1();

if(isset($_GET['action'])) {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'];

    if ($action == 'test') {
        $api_v1->test();
    }

    if ($method == 'POST' || $method == 'post') {

        $json = file_get_contents('php://input');
        $item = json_decode($json, true);

        if ($action == 'insert_product') {
            $id_parent = 0;
            if(isset($_GET['id_parent']))
                $id_parent=$_GET['id_parent'];
            $api_v1->insert_product($item, $id_parent);
        } else if ($action == 'insert_product_bulk') {
            $api_v1->insert_product_bulk($item);
        } else if ($action == 'insert_review') {
            $id_product = 0;
            if(isset($_GET['id_product']))
                $id_product=$_GET['id_product'];
            $api_v1->insert_review($id_product, $item);
        }else if ($action == 'insert_term') {
            $id_parent = 0;
            if(isset($_GET['id_parent']))
                $id_parent=$_GET['id_parent'];
            $api_v1->insert_term($item, $id_parent);
        }else if ($action == 'insert_attribute') {
            $api_v1->insert_attribute($item);
        }else if($action == 'update_product'){
            $id_product=$_GET['id_product'];
            $api_v1->update_product($id_product, $item);
        }else if($action == 'update_variant'){
            $id_variant=$_GET['id_variant'];
            $api_v1->update_variant($id_variant, $item);
        } else if($action == 'update_product_bulk'){
            $id_product=$_GET['id_product'];
            $api_v1->update_product_bulk($id_product, $item);
        } else if($action == 'delete_product'){
            $sku=$_GET['sku'];
            $api_v1->delete_product($sku);
        }else if($action == 'delete_product_by_id'){
            $from = $item['from'];
            $to = $item['to'];
            $api_v1->delete_product_by_id($from, $to);
        }else{
            $api_v1->response(406, 'Không tìm thấy action');
        }
    }
    else if($method=='DELETE' || $method=='delete') {

        $json = file_get_contents('php://input');
        $item = json_decode($json, true);
        
        if ($action == 'clear_woocommeerce_transients') {
            $id_product = null;
            if(isset($_GET['id_product']))
                $id_product=$_GET['id_product'];
            $api_v1->clear_woocommeerce_transients($id_product);
        }else if($action == 'delete_product_by_id'){
            $from = $item['from'];
            $to = $item['to'];
            $api_v1->delete_product_by_id($from, $to);
        }
        else{
            $api_v1->response(406, 'Không tìm thấy action (clear_woocommeerce_transients, delete_product)');
        }
    }
    else{
        $api_v1->response(406, 'Sử dụng POST, DELETE method');
    }
}else {
    $api_v1->response(406, 'Thiếu param action');
}
