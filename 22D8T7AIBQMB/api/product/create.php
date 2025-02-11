<?php
include "../../config/systemConfig.php";
include "../base_request.php";
include "database/create_database.php";

class create extends base_request
{
    function test()
    {
        $systemConfig = new systemConfig();
        $dbContext = $systemConfig->connectDB();
        if ($dbContext == null) {
            $this->error($this->create_error('test', 'Can not connect database'));
        } else {
            $this->success('Ok');
        }
    }

    function create_product_bulk($item)
    {
        //$this->success(array('product' => $item));
        $code = 'create_product_bulk';
        global $dbContext;
        global $db_create;

        if ($dbContext == null) {
            $this->error($this->create_error($code . '_connection', 'Can not connect database'));
        } else {
            $item = $this->process_input_product($item);

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

            $product_type = $item['type'];
            if (isset($item['variations']) && count($item['variations']) > 0)
                $product_type = 'variable';

            $item['type'] = $product_type;

            $idProduct = 0;
            switch ($product_type) {
                case 'simple':
                case 'variable':
                case 'external':
                    $idProduct = $db_create->create_product_to_database($dbContext, $item);
                    $res_bulk = array('id' => $idProduct, 'sku' => $item['sku'] ?? '');
                    break;
                default:
                    $this->error($this->create_error('wrong_type', 'type must be: simple, variable, external'));
                    break;
            }
            $res_bulk['variations'] = array();

            if (isset($item['variations'])) {
                $variants = $item['variations'];
                $variant_with_id = array();
                $import_full_data = isset($item['mode_import']) && $item['mode_import'] == 'full_data';
                foreach ($variants as $variant) {
                    $variant = $this->process_input_variation($variant, $import_full_data);
                    $product_slug = $item['slug'] ?? '';
                    $product_name = $item['name'] ?? '';
                    //$id_variant = $db_create->create_variant_to_database($idProduct, $dbContext, $variant, $product_slug);
                    $id_variant = $db_create->create_variant_to_database($idProduct, $dbContext, $variant, $product_slug, $product_name);
                    $variant['ID'] = $id_variant;
                    $variant_with_id[]  = $variant;

                    $res_bulk['variations'][] = array('id' => $id_variant, 'sku' => $variant['sku'] ?? '');

                    // 14.10.2023 Thêm cái này để có những khách yêu cầu riêng
                    if(isset($item['custom_command'])) {
                        $arr_custom_command = $item['custom_command'];
                        
                        // 14.10.2023 Theme minimog: gallery_images
                        if(in_array('create_gallery_images_theme_minimog', $arr_custom_command))
                            $db_create->create_gallery_images_theme_minimog($idProduct, $id_variant, $dbContext);
                    }
                }
                // $db_create->create_transient_variable_product($dbContext, $idProduct, $variant_with_id);
            }

            $res_bulk['reviews'] = array();

            if (isset($item['reviews'])) {
                $reviews = $item['reviews'];
                foreach ($reviews as $review) {
                    $id_review = $db_create->create_review($dbContext, $idProduct, $review);
                    $res_bulk['reviews'][] = array('id' => $id_review);
                }
            }

            $this->success(array('product' => $res_bulk));
            //}
        }
    }

    function create_review($id_product, $item)
    {
        global $dbContext;
        global $db_create;
        if ($dbContext == null) {
            $this->error($this->create_error('create_review', 'Can not connect database'));
        } else {
            $id_review = $db_create->create_review($dbContext, $id_product, $item);
            $this->success(array('id_product' => $id_product, 'id_review' => $id_review));
        }
    }

    function create_term($item, $id_parent = 0)
    {
        global $dbContext;
        global $db_create;
        if ($dbContext == null) {
            $this->error($this->create_error('create_term', 'Can not connect database'));
        } else {
            $id_term = $db_create->create_term($dbContext, $item, $id_parent);
            $this->success(array('id_term' => $id_term));
        }
    }

    function create_attribute($item)
    {
        global $dbContext;
        global $db_create;
        if ($dbContext == null) {
            $this->error($this->create_error('create_attribute', 'Can not connect database'));
        } else {
            $id_attribute = $db_create->create_attribute($dbContext, $item);
            $this->success(array('id_attribute' => $id_attribute));
        }
    }
}

$import = new create();
$method = $_SERVER['REQUEST_METHOD'];
if ($method == 'POST' || $method == 'post') {
    $json = file_get_contents('php://input');
    $item = json_decode($json, true);
    if(isset($_GET['debug'])){
        error_reporting(E_ALL);
        ini_set('display_errors', 'On');
    }

    if (isset($_GET['action'])) {
        $action = $_GET['action'];

        if ($action == 'test') {
            $import->test();
        }

        switch ($action) {
            case 'create_bulk':
                $import->create_product_bulk($item);
                break;
            case 'create_review':
                $id_product = 0;
                if (isset($_GET['id_product'])) $id_product = $_GET['id_product'];
                $import->create_review($id_product, $item);
                break;
            case 'create_term':
                $id_parent = 0;
                if (isset($_GET['id_parent'])) $id_parent = $_GET['id_parent'];
                $import->create_term($item, $id_parent);
                break;
            case 'create_attribute':
                $import->create_attribute($item);
                break;
            default:
                $import->error($import->create_error('not_found_action', 'Not found ?action param'));
                break;
        }
    } else {
        //$import->warning(406, $import->create_warning('not_found_action', 'Not found ?action param'));
        $import->create_product_bulk($item);
    }
} else {
    $import->error($import->create_error('not_match_method', 'Must use POST method'));
}
