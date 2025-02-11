<?php
require_once "api/database.php";
require_once "api/base_request.php";

$database = new database();
class add_on extends base_request{
    function run_sql_return_single_col($query){
        global $database;
        $res = $database->run_sql_get_single_col($query);
        $this->success($res);
    }

    function run_sql_return_array($query){
        global $database;
        $res = $database->run_sql_return_array($query);
        $this->success($res);
    }

    function run_sql_return_object($query){
        global $database;
        $res = $database->run_sql_return_object($query);
        $this->success($res);
    }

    function run_sql_without_return($query){
        //require_once "config/systemConfig.php";
        global $dbContext;
        global $database;
        $database->run_sql($dbContext, $query);
        $this->success(null);
    }

    function convert_database_and_table($char_set='utf8mb4', $collate='utf8mb4_general_ci'){
        global $database_name;
        global $table_posts;
        global $table_post_meta;
        global $table_options;
        global $table_terms;
        global $table_term_taxonomy;
        global $table_term_relationships;
        global $table_termmeta;
        global $table_wc_product_meta_lookup;
        global $table_comments;
        global $table_commentmeta;
        global $table_woo_attribute_taxonomie;
        global $table_woo_order_product_lookup;
        global $table_woo_woocommerce_order_items;
        global $table_woo_woocommerce_order_itemmeta;

        $tables = array($table_posts, $table_post_meta, $table_options, $table_terms, $table_term_taxonomy, $table_term_relationships, $table_termmeta, $table_wc_product_meta_lookup, $table_comments, $table_commentmeta, $table_woo_attribute_taxonomie, $table_woo_order_product_lookup, $table_woo_woocommerce_order_items, $table_woo_woocommerce_order_itemmeta);

        global $dbContext;
        global $database;
        $database->run_sql($dbContext, "SET SESSION sql_mode='NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';");
        $database->run_sql($dbContext, "ALTER TABLE $table_posts MODIFY guid varchar(2048) null;");
        $database->run_sql($dbContext, "ALTER DATABASE `$database_name` DEFAULT CHARACTER SET = $char_set DEFAULT COLLATE = $collate;");
        foreach($tables as $t){
            $database->run_sql($dbContext, "ALTER TABLE $t convert to character set $char_set collate $collate;");
        }
        $this->success(null);
    }

    function convert_database($char_set='utf8mb4', $collate='utf8mb4_general_ci'){
        global $database_name;
        global $table_posts;
        

        global $dbContext;
        global $database;
        $database->run_sql($dbContext, "SET SESSION sql_mode='NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';");
        $database->run_sql($dbContext, "ALTER TABLE $table_posts MODIFY guid varchar(2048) null;");
        $database->run_sql($dbContext, "ALTER DATABASE `$database_name` DEFAULT CHARACTER SET = $char_set DEFAULT COLLATE = $collate;");
        $this->success(null);
    }
    function fix_min_max_price_big_update_v2(){
        global $dbContext;
        global $database;
        $database->run_sql($dbContext, "call fix_min_max_price_upgrade_v2_0();");
        $this->success(null);
    }
    function delete_file($file_path, $secret_code){
        if(md5($file_path) === $secret_code){
            if(file_exists($file_path)){
                if(unlink($file_path)){
                    $this->success(null);
                }else{
                    $this->error(null, 'file can not delete');
                }
            }else{
                $this->error(null, 'file not exists');
            }
        }else{
            $this->error(null, 'secret code not match');
        }
    }
}


$request = new add_on();
$method = $_SERVER['REQUEST_METHOD'];
if($method == 'POST' or $method == 'post') {
    $json = file_get_contents('php://input');
    $item = json_decode($json, true);
    $query = '';
    if(isset($_GET['action'])) {
        $action = $_GET['action'];
        if(isset($item['query']))
            $query = $item['query'];
        else if($action == 'run_sql_return_single_col' || $action == 'run_sql_return_array' || $action == 'run_sql_return_object' || $action == 'run_sql_without_return') // Các chế độ bắt buộc có query
            $request->warning($request->create_warning('not_found_query', 'Not found query'));
        switch($action){
            case 'run_sql_return_single_col':
                $request->run_sql_return_single_col($query);
                break;
            case 'run_sql_return_array':
                $request->run_sql_return_array($query);
                break;
            case 'run_sql_return_object':
                $request->run_sql_return_object($query);
                break;
            case 'run_sql_without_return':
                $request->run_sql_without_return($query);
                break;
            case 'convert_database_and_table':
                $char_set='utf8mb4';
                $collate='utf8mb4_general_ci';
                
                if(isset($item['char_set']))
                    $char_set = $item['char_set'];
                else if(isset($_POST['char_set']))
                    $char_set = $_POST['char_set'];

                if(isset($item['collate']))
                    $collate = $item['collate'];
                else if(isset($_POST['collate']))
                    $collate = $_POST['collate'];

                $request->convert_database_and_table($char_set, $collate);
                break;
            case 'convert_database':
                $char_set='utf8mb4';
                $collate='utf8mb4_general_ci';

                if(isset($item['char_set']))
                    $char_set = $item['char_set'];
                else if(isset($_POST['char_set']))
                    $char_set = $_POST['char_set'];

                if(isset($item['collate']))
                    $collate = $item['collate'];
                else if(isset($_POST['collate']))
                    $collate = $_POST['collate'];

                $request->convert_database($char_set, $collate);
                break;
            case 'fix_min_max_price_big_update_v2':
                $request->fix_min_max_price_big_update_v2();
                break;
            case 'delete_file':
                $file_path = '';
                $secret_code_delete = '';
                if(isset($item['file_path']))
                    $file_path = $item['file_path'];
                if(isset($item['secret_code_delete']))
                    $secret_code_delete = $item['secret_code_delete'];
                $request->delete_file($file_path, $secret_code_delete);
                break;
            default:
                $request->error($request->create_error('not_found_action', 'Not found ?action param: run_sql_return_single_col, run_sql_return_array, run_sql_return_object, run_sql_without_return'));
                break;
            }
        }else{
            $request->error($request->create_error('not_found_action', 'Not found ?action param: run_sql_return_single_col, run_sql_return_array, run_sql_return_object, run_sql_without_return'));
        }
}else {
    $request->error($request->create_error( 'not_match_method', 'Must use POST method'));
}

