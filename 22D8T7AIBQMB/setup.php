<?php

//test: 23:22 22/8/2020
$iniPath = 'config/database_config.ini';

require_once 'api/base_request.php';

$base_request = new base_request();
$log = array();

function insert_log($output, $with_script_tags = true)
{
    global $log;
    $log[] = $output;
}

function read_file($filePath)
{
    $fh = fopen($filePath, 'r');
    $res = '';
    while ($line = fgets($fh)) {
        $res = $res . $line;
    }
    fclose($fh);
    return $res;
}

$charset = '';
$collate = '';

//https://stackoverflow.com/questions/40240111/php-write-to-ini-file
//https://stackoverflow.com/questions/5695145/how-to-read-and-write-to-an-ini-file-with-php
/* Sửa lỗi không ghi được file ini: https://stackoverflow.com/questions/9200557/permission-denied-when-opening-or-creating-files-with-php
 * chown -R www-data:www-data folder
 * chmod -R g+w folder
 */
function write_php_ini($array, $file)
{
    $res = array();
    foreach ($array as $key => $val) {
        if (is_array($val)) {
            $res[] = "[$key]";
            foreach ($val as $skey => $sval) $res[] = "$skey = " . (is_numeric($sval) ? $sval : '"' . $sval . '"');
        } else $res[] = "$key = " . (is_numeric($val) ? $val : '"' . $val . '"');
    }
    return safefilerewrite($file, implode("\r\n", $res));
}

function safefilerewrite($fileName, $dataToSave)
{
    if ($fp = fopen($fileName, 'w')) {
        $startTime = microtime(TRUE);
        do {
            $canWrite = flock($fp, LOCK_EX);
            // If lock not obtained sleep for 0 - 100 milliseconds, to avoid collision and CPU load
            if (!$canWrite) usleep(round(rand(0, 100) * 1000));
        } while ((!$canWrite) and ((microtime(TRUE) - $startTime) < 5));

        //file was locked so now we can store information
        if ($canWrite) {
            fwrite($fp, $dataToSave);
            flock($fp, LOCK_UN);
        }
        fclose($fp);
        return true;
    } else {
        insert_log('Can not write file ini: ' . $fileName);
        insert_log('Detail: ' . json_encode(error_get_last()));
        return false;
    }
}

function run_file_sql($file_path, $conn, $prefix, $function_name)
{
    $default_prefix = 'wp_';
    if (file_exists($file_path)) {
        $query = @read_file($file_path);
        $query = str_replace('`' . $default_prefix, '`' . $prefix, $query);

        if ($conn->query($query)) {
            insert_log("-Create $function_name success");
            return true;
        } else {
            insert_log("-Create $function_name fail: (" . $conn->errno . ") " . $conn->error);
            return false;
        }
    } else {
        insert_log("Not found $file_path");
        return false;
    }
}

function convert_charset_database($conn, $db_name, $table_prefix)
{
    global $base_request;
    global $charset;
    global $collate;
    insert_log('Convert database charset, collate');
    if($charset && $collate) {
        $query = "ALTER DATABASE `$db_name` DEFAULT CHARACTER SET = $charset DEFAULT COLLATE = $collate;";
        if (@$conn->query($query)) {
            // $res = $result->fetch_object();
            // $result->free_result();
        } else {
            $code = 'convert_charset_database';
            $error = $base_request->create_error($code . '_' . $conn->errno, "function $code error (" . $conn->errno . "): " . $conn->error);
            $error['query'] = $query;
            $base_request->error($error);
        }
    }
}

function create_procedure_and_function_sql($conn, $prefix = 'wp_')
{
    insert_log('Create procedure, function sql:');
    global $base_request;
    global $log;
    if ($conn != null) {
        if (!$conn->connect_errno) {
            $is_config_faild = false;

            //Ghi test thử
            $conn->query('DROP PROCEDURE IF EXISTS `clear_woocommeerce_transients`;');
            $conn->query('DROP FUNCTION IF EXISTS `create_variant`;');
            $conn->query('DROP FUNCTION IF EXISTS `create_product`;');
            $conn->query('DROP FUNCTION IF EXISTS `create_review`;');
            $conn->query('DROP FUNCTION IF EXISTS `create_term`;');
            //$conn->query('DROP FUNCTION IF EXISTS `check_sku_imported_in_five_minute`;');
            //26/4/2020
            $conn->query('DROP FUNCTION IF EXISTS `update_product`;');

            $conn->query('DROP FUNCTION IF EXISTS `update_variant`;');
            //08/5/2020
            $conn->query('DROP FUNCTION IF EXISTS `delete_product_by_sku`;');
            $conn->query('DROP FUNCTION IF EXISTS `delete_product_by_id`;');

            //03/7/2020
            $conn->query('DROP FUNCTION IF EXISTS `slugify`;');
            $conn->query('DROP FUNCTION IF EXISTS `create_attribute`;');
            //16/7/2020
            $conn->query('DROP FUNCTION IF EXISTS `create_postmeta`;');
            //31/7/2020
            $conn->query('DROP FUNCTION IF EXISTS `create_attribute_for_product`;');
            //15/8/2020
            // 23/12/2021 Chuan bi bo sung chuc nang update san pham cho Dropship
            $conn->query('DROP FUNCTION IF EXISTS `get_productid_from_sku`;');
            $conn->query('DROP PROCEDURE IF EXISTS `clear_woocommeerce_transients_price`;');

            $conn->query('DROP FUNCTION IF EXISTS `insert_review_fast_api`;');
            $conn->query('DROP FUNCTION IF EXISTS `insert_term_fast_api`;');
            $conn->query('DROP FUNCTION IF EXISTS `delete_product_by_id_fast_api`;');
            $conn->query('DROP FUNCTION IF EXISTS `slugify_fast_api`;');
            $conn->query('DROP FUNCTION IF EXISTS `insert_attribute_for_product_fast_api`;');
            $conn->query('DROP FUNCTION IF EXISTS `insert_attribute_fast_api`;');
            $conn->query('DROP FUNCTION IF EXISTS `insert_postmeta_fast_api`;');
            $conn->query('DROP FUNCTION IF EXISTS `insert_product_fast_api`;');
            $conn->query('DROP FUNCTION IF EXISTS `insert_simple_product_fast_api`;');
            $conn->query('DROP FUNCTION IF EXISTS `insert_variable_product_fast_api`;');
            $conn->query('DROP FUNCTION IF EXISTS `insert_variant_fast_api`;');
            $conn->query('DROP FUNCTION IF EXISTS `update_prodcut_fast_api`;');
            $conn->query('DROP FUNCTION IF EXISTS `update_variant_fast_api`;');
            $conn->query('DROP FUNCTION IF EXISTS `delete_product_fast_api`;');
            $conn->query('DROP PROCEDURE IF EXISTS `clear_woocommeerce_transients_fast_api`;');
            $conn->query('DROP PROCEDURE IF EXISTS `fix_min_max_price_upgrade_v2_0`;');



            // $queryTrustBinLog = 'SET GLOBAL log_bin_trust_function_creators = 1;';

            // if (@$conn->query($queryTrustBinLog)) {
            //     insert_log("-SET GLOBAL log_bin_trust_function_creators success");
            // } else {
            //     insert_log("-SET GLOBAL log_bin_trust_function_creators fail: (" . $conn->errno . ") " . $conn->error);
            // }

            $file_functionNames = array();
            $file_functionNames[] = array("path" => './sql/function clear woocommerce transients.sql',                 'name' => "clear_woocommeerce_transients",          "require" => true);
            $file_functionNames[] = array("path" => './sql/Function them san pham.sql',                                'name' => "create_product",                         "require" => true);
            $file_functionNames[] = array("path" => './sql/Function Them Variant.sql',                                 'name' => "create_variant",                         "require" => true);
            $file_functionNames[] = array("path" => './sql/Funciton Them Review.sql',                                  'name' => "create_review",                          "require" => true);
            $file_functionNames[] = array("path" => './sql/Funciton Them Term.sql',                                    'name' => "create_term",                            "require" => true);
            //$file_functionNames[] = array("path"=>'./sql/Create table codethue_sku_imported.sql',                    'name'=>"codethue_sku_imported",                    "require"=>false);
            //$file_functionNames[] = array("path"=>'./sql/Funciton Check Sku Imported.sql',                           'name'=>"check_sku_imported_in_five_minute",        "require"=>true);
            $file_functionNames[] = array("path" => './sql/Function update san pham.sql',                              'name' => "update_product",                         "require" => true);
            $file_functionNames[] = array("path" => './sql/Function update variant.sql',                               'name' => "update_variant",                         "require" => true);
            $file_functionNames[] = array("path" => './sql/Function slugify.sql',                                      'name' => "slugify",                                "require" => true);
            $file_functionNames[] = array("path" => './sql/Function them attribute.sql',                               'name' => "create_attribute",                       "require" => true);
            $file_functionNames[] = array("path" => './sql/Function them postmeta.sql',                                'name' => "create_postmeta",                        "require" => true);
            $file_functionNames[] = array("path" => './sql/Function them attribute for product.sql',                   'name' => "create_attribute_for_product",           "require" => true);
            $file_functionNames[] = array("path" => './sql/Function Delete product by sku.sql',                        'name' => "delete_product_by_sku",                  "require" => true);
            $file_functionNames[] = array("path" => './sql/Function Delete Product By Id.sql',                         'name' => "delete_product_by_id",                   "require" => true);
            $file_functionNames[] = array("path" => './sql/Function Get ProductId From Sku.sql',                       'name' => "get_productid_from_sku",                 "require" => true);
            $file_functionNames[] = array("path" => './sql/function clear woocommerce transients_price.sql',           'name' => "clear_woocommeerce_transients_price",    "require" => true);


            $file_functionNames[] = array("path" => './fast_import_api/sql/Funciton Them Review.sql',                  'name' => "insert_review_fast_api",                 "require" => true);
            $file_functionNames[] = array("path" => './fast_import_api/sql/Funciton Them Term.sql',                    'name' => "insert_term_fast_api",                   "require" => true);
            $file_functionNames[] = array("path" => './fast_import_api/sql/function clear woocommerce transients.sql', 'name' => "clear_woocommeerce_transients_fast_api", "require" => true);
            $file_functionNames[] = array("path" => './fast_import_api/sql/Function Delete Product By Id.sql',         'name' => "delete_product_by_id_fast_api",          "require" => true);
            $file_functionNames[] = array("path" => './fast_import_api/sql/Function slugify.sql',                      'name' => "slugify_fast_api",                       "require" => true);
            $file_functionNames[] = array("path" => './fast_import_api/sql/Function them attribute for product.sql',   'name' => "insert_attribute_for_product_fast_api",  "require" => true);
            $file_functionNames[] = array("path" => './fast_import_api/sql/Function them attribute.sql',               'name' => "insert_attribute_fast_api",              "require" => true);
            $file_functionNames[] = array("path" => './fast_import_api/sql/Function them postmeta.sql',                'name' => "insert_postmeta_fast_api",               "require" => true);
            $file_functionNames[] = array("path" => './fast_import_api/sql/Function them san pham.sql',                'name' => "insert_product_fast_api",                "require" => true);
            $file_functionNames[] = array("path" => './fast_import_api/sql/Function Them Simple Product.sql',          'name' => "insert_simple_product_fast_api",         "require" => true);
            $file_functionNames[] = array("path" => './fast_import_api/sql/Function Them variable Product.sql',        'name' => "insert_variable_product_fast_api",       "require" => true);
            $file_functionNames[] = array("path" => './fast_import_api/sql/Function Them Variant.sql',                 'name' => "insert_variant_fast_api",                "require" => true);
            $file_functionNames[] = array("path" => './fast_import_api/sql/Function update san pham.sql',              'name' => "update_prodcut_fast_api",                "require" => true);
            $file_functionNames[] = array("path" => './fast_import_api/sql/Function update variant.sql',               'name' => "update_variant_fast_api",                "require" => true);
            $file_functionNames[] = array("path" => './fast_import_api/sql/Function xoa san pham.sql',                 'name' => "delete_product_fast_api",                "require" => true);
            $file_functionNames[] = array("path" => './sql/Procedure_fix_min_max_price_upgrade_v2_0.sql',              'name' => "fix_min_max_price_upgrade_v2_0",         "require" => true);

            foreach ($file_functionNames as $function) {
                if (!run_file_sql($function['path'], $conn, $prefix, $function['name']) && $function['require']) {
                    $is_config_faild = true;
                }
            }

            if ($is_config_faild == false) {
                $base_request->success(file_ini_json(), true);
            } else {
                $base_request->error($base_request->create_error('create_procedure_and_function_sql', 'Setup fail. Please check log'), array('log' => $log), true);
            }
        } else {
            $base_request->error($base_request->create_error('create_procedure_and_function_sql', "Connection error: (" . $conn->errno . ") " . $conn->error), array('log' => $log), true);
            //echo "Connection error: (" . $conn->errno . ") " . $conn->error;
        }
    } else {
        $base_request->error($base_request->create_error('connection_null', 'Connection'), NULL, true);
        //echo "Connection null";
    }
}

function init_charset($file_path_config)
{
    require_once $file_path_config;
    $charset = '';
    $collate = '';

    if (function_exists('is_multisite') && is_multisite()) {
        $charset = 'utf8';
        if (defined('DB_COLLATE') && DB_COLLATE) {
            $collate = DB_COLLATE;
        } else {
            $collate = 'utf8_general_ci';
        }
    } elseif (defined('DB_COLLATE')) {
        $collate = DB_COLLATE;
    }

    if (defined('DB_CHARSET')) {
        $charset = DB_CHARSET;
    }

    $charset_collate = determine_charset($charset, $collate);

    global $charset;
    global $collate;

    $charset = $charset_collate['charset'];
    $collate = $charset_collate['collate'];
}

function determine_charset($charset, $collate)
{
    if ('utf8' === $charset && has_cap('utf8mb4')) {
        $charset = 'utf8mb4';
    }

    if ('utf8mb4' === $charset && !has_cap('utf8mb4')) {
        $charset = 'utf8';
        $collate = str_replace('utf8mb4_', 'utf8_', $collate);
    }

    if ('utf8mb4' === $charset) {
        // _general_ is outdated, so we can upgrade it to _unicode_, instead.
        if (!$collate || 'utf8_general_ci' === $collate) {
            $collate = 'utf8mb4_unicode_ci';
        } else {
            $collate = str_replace('utf8_', 'utf8mb4_', $collate);
        }
    }

    // _unicode_520_ is a better collation, we should use that when it's available.
    if (has_cap('utf8mb4_520') && 'utf8mb4_unicode_ci' === $collate) {
        $collate = 'utf8mb4_unicode_520_ci';
    }

    return compact('charset', 'collate');
}

function has_cap($db_cap)
{
    $version = "5.5.5"; //$this->db_version();

    switch (strtolower($db_cap)) {
        case 'collation':    // @since 2.5.0
        case 'group_concat': // @since 2.7.0
        case 'subqueries':   // @since 2.7.0
            return version_compare($version, '4.1', '>=');
        case 'set_charset':
            return version_compare($version, '5.0.7', '>=');
        case 'utf8mb4':      // @since 4.1.0
            if (version_compare($version, '5.5.3', '<')) {
                return false;
            }

            $client_version = mysqli_get_client_info();
            if (false !== strpos($client_version, 'mysqlnd')) {
                $client_version = preg_replace('/^\D+([\d.]+).*/', '$1', $client_version);
                return version_compare($client_version, '5.0.9', '>=');
            } else {
                return version_compare($client_version, '5.5.3', '>=');
            }
        case 'utf8mb4_520': // @since 4.6.0
            return version_compare($version, '5.6', '>=');
    }

    return false;
}

function detect_charset_collate($conn, $db_name, $table_prefix)
{
    global $base_request;
    global $charset;
    global $collate;

    $query = "SELECT T.table_name, CCSA.character_set_name, CCSA.collation_name FROM information_schema.`TABLES` T, information_schema.`COLLATION_CHARACTER_SET_APPLICABILITY` CCSA WHERE CCSA.collation_name = T.table_collation AND T.table_schema = '$db_name' AND T.table_name = '$table_prefix" . "posts';";
    if ($result = @$conn->query($query)) {
        $res = $result->fetch_object();
        if(isset($res->character_set_name))
            $charset = $res->character_set_name;
        else if(isset($res->CHARACTER_SET_NAME))
            $charset = $res->CHARACTER_SET_NAME;

        if(isset($res->collation_name))
            $collate = $res->collation_name;
        else if(isset($res->COLLATION_NAME))
            $collate = $res->COLLATION_NAME;
        $result->free_result();
    } else {
        $code = 'detect_charset_collate';
        $error = $base_request->create_error($code . '_' . $conn->errno, "function $code error (" . $conn->errno . "): " . $conn->error);
        $error['query'] = $query;
        $base_request->error($error, NULL, true);
    }
}

function get_setting_site_url($conn, $table_prefix)
{
    if(isset($_SERVER['REQUEST_SCHEME']) && isset($_SERVER['HTTP_HOST'])){
        $scheme = $_SERVER['REQUEST_SCHEME'];
        // if($scheme != "https" && isset($_SERVER['HTTP_CF_VISITOR']) && is_string($_SERVER['HTTP_CF_VISITOR']) && str_contains($_SERVER['HTTP_CF_VISITOR'], "https")){ // cloudflare
        if($scheme != "https" && isset($_SERVER['HTTP_CF_VISITOR']) && is_string($_SERVER['HTTP_CF_VISITOR']) && strpos($_SERVER['HTTP_CF_VISITOR'], "https") != false){ // cloudflare
            $scheme = "https";
        }
        return $scheme . "://" . $_SERVER['HTTP_HOST']; // nhiều site không update siteurl do clone site
    }
    $query = "SELECT * FROM `$table_prefix" . "options` WHERE option_name = 'siteurl' LIMIT 1;";
    if ($result = @$conn->query($query)) {
        $res = $result->fetch_object();
        $result->free_result();
        return $res->option_value;
    } else {
        global $base_request;
        $base_request->error($base_request->create_error('get_setting_site_url', 'Fail get site url'), NULL, true);
    }
}

function file_ini_json()
{
    global $iniPath;
    global $log;
    if (file_exists($iniPath)) {
        $ini['database'] = parse_ini_file($iniPath);
        $ini['database']['db_password'] = '********';
        $ini['log'] = $log;
        if($_GET != null && isset($_GET['debug']))
            $ini['server'] = $_SERVER;
        return $ini;
    } else {
        return null;
    }
}



try {
    $action = '';
    if (isset($_GET['action']))
        $action = $_GET['action'];
    if(isset($_GET['debug'])){
        error_reporting(E_ALL);
        ini_set('display_errors', 'On');
    }
    $iniPath = 'config/database_config.ini';
    if($action != 'skip_check_wp_config') {
            if (file_exists($iniPath)) {
                if (unlink($iniPath)) {
                    insert_log('Delete file ini');
                } else {
                    $base_request->error($base_request->create_error('codethue_setup', 'Can not delete file ini. Please set full permission (777) with \'config\' folder'), array('log' => $log), true);
                }
            }

            insert_log('Create file ini:');

            $file_wp_config = '../wp-config.php';
            if (!file_exists($file_wp_config)) {

                $file_wp_config = '../wordpress/wp-config.php';

                if (!file_exists($file_wp_config)) {
                    $file_wp_config = '../../wordpress/wp-config.php';
                }

                if (!file_exists($file_wp_config)) {
                    $file_wp_config = '../../wp-config.php';
                }

                if (!file_exists($file_wp_config)) {
                    $file_wp_config = '../../../wp-config.php';
                }
            }

            if (file_exists($file_wp_config)) {
                insert_log('-Found wp-config.php at ' . $file_wp_config);

                require_once $file_wp_config;

                global $table_prefix;
                global $charset;
                global $collate;

                $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
                detect_charset_collate($conn, DB_NAME, $table_prefix);

                //init_charset($file_wp_config);


            if (write_php_ini(array('database' => array(
                'db_host'      => DB_HOST,
                'db_user'      => DB_USER,
                'db_password'  => DB_PASSWORD,
                'db_name'      => DB_NAME,
                'table_prefix' => $table_prefix,
                'charset'      => $charset,
                'collate'      => $collate,
                'site_url'     => get_setting_site_url($conn, $table_prefix)
            )), $iniPath)) {
                insert_log('-Create file ini success: db_name=' . DB_NAME);

                require_once 'config/systemConfig.php';

                global $dbContext;
                // if(isset($_GET['convert_char_set']))
                if(!isset($_GET['skip_convert_char_set'])) // Vì nhiều lỗi convert database quá nên để mặc định là convert
                    convert_charset_database($dbContext, DB_NAME, $table_prefix);
                create_procedure_and_function_sql($dbContext, $table_prefix);
            } else {
                $base_request->error($base_request->create_error('codethue_setup', 'Setup fail. Can not write file ini. Please set full permission (777) with \'config\' folder'), array('log' => $log), true);
            }
        } else {
            $base_request->error($base_request->create_error('codethue_setup', 'Not found wp-config.php'), array('log' => $log), true);
        }
    } else if ($action == 'skip_check_wp_config') {
        // setup thu cong
        if (file_exists($iniPath)) {
            require_once 'config/systemConfig.php';
            $config = parse_ini_file($iniPath);
            $conn = new mysqli($config['db_host'], $config['db_user'], $config['db_password'], $config['db_name']);
            
            global $dbContext;
            // if(isset($_GET['convert_char_set'])){
            if(!isset($_GET['skip_convert_char_set'])){ // Vì nhiều lỗi convert database quá nên để mặc định là convert
                detect_charset_collate($conn, $config['db_name'], $config['table_prefix']);
                convert_charset_database($dbContext, $config['db_name'], $config['table_prefix']);
            }
            create_procedure_and_function_sql($dbContext, $config['table_prefix']);
        } else {
            $log = 'Not found file config/database_config.ini';
            $base_request->error($base_request->create_error('codethue_setup', 'Not found wp-config.php'), array('log' => $log), true);
        }
    }
} catch (Exception $e) {
    $base_request->error($base_request->create_error('codethue_setup', 'Exception: '. $e->getMessage()), array('log' => $log), true);
}
