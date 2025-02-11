<?php

require_once "share_function.php";
require_once "share_function_database.php";

$iniPath = '../../../config/database_config.ini';
if(!file_exists($iniPath)){
    $iniPath = '../../config/database_config.ini';
}
if(!file_exists($iniPath)){
    $iniPath = '../config/database_config.ini';
}
if(!file_exists($iniPath)){
    $iniPath = 'config/database_config.ini';
}
if(!file_exists($iniPath)){
    global $share_function_database;
    $share_function_database->error(null, 'not_found_config_ini_database.php', '');
}

$ini = parse_ini_file($iniPath);
$table_prefix                             = $ini['table_prefix'];
$table_posts                              = "`$table_prefix"."posts`";
$table_post_meta                          = "`$table_prefix"."postmeta`";
$table_options                            = "`$table_prefix"."options`";
$table_terms                              = "`$table_prefix"."terms`";
$table_term_taxonomy                      = "`$table_prefix"."term_taxonomy`";
$table_term_relationships                 = "`$table_prefix"."term_relationships`";
$table_termmeta                           = "`$table_prefix"."termmeta`";
$table_wc_product_meta_lookup             = "`$table_prefix"."wc_product_meta_lookup`";
$table_comments                           = "`$table_prefix"."comments`";
$table_commentmeta                        = "`$table_prefix"."commentmeta`";
$table_woo_attribute_taxonomie            = "`$table_prefix"."woocommerce_attribute_taxonomies`";
$table_woo_order_product_lookup           = "`$table_prefix"."wc_order_product_lookup`";
$table_woo_woocommerce_order_items        = "`$table_prefix"."woocommerce_order_items`";
$table_woo_woocommerce_order_itemmeta     = "`$table_prefix"."woocommerce_order_itemmeta`";

$systemConfigPath = '../../config/systemConfig.php';
if(!file_exists($systemConfigPath)){
    $systemConfigPath = '../config/systemConfig.php';
}
if(!file_exists($systemConfigPath)){
    $systemConfigPath = 'config/systemConfig.php';
}
if(!file_exists($systemConfigPath)){
    global $share_function_database;
    $share_function_database->error(null, 'not_found_systemConfig.php', $systemConfigPath);
}
require_once $systemConfigPath;

class database extends share_function_dabase{
    function run_sql_get_single_col($query)
    {
        global $dbContext;
        if($result = @$dbContext->query($query)) {
            $res_array = $result->fetch_array();
            if ($res_array)
                $res = $res_array[0];
            else
                $res = null;
            $result->free_result();
            return $res;
        }else{
            $this->error($dbContext, 'run_sql_get_single_col', $query);
        }
    }

    function run_sql_return_array($query)
    {
        global $dbContext;
        if($result = @$dbContext->query($query)) {
            //$res_array = $result->fetch_array();
            $res_array = array();
            while($obj = $result->fetch_object()){
                $res_array[] = $obj;
            }
            $result->free_result();
            return $res_array == null ? null : $res_array;
        }else{
            $this->error($dbContext, 'run_sql_return_array', $query);
        }
    }

    function run_sql_return_object($query)
    {
        global $dbContext;
        if($result = @$dbContext->query($query)) {
            $res = $result->fetch_object();
                $result->free_result();
                return $res;
            
            //return null;
        }else{
            $this->error($dbContext, 'run_sql_return_array', $query);
        }
    }

    function run_sql($dbContext, $query){
        if(!@$dbContext->query($query)){
            $this->error($dbContext, 'error', $query);
        }
        
    }

    function last_insert_id($dbContext){
        $last_id = $dbContext->insert_id;
        //$last_id = run_sql_get_single_col($dbContext, 'SELECT last_insert_id();');
        return $last_id;
    }
}