<?php

require_once 'api/base_request.php';
$iniPath = 'config/database_config.ini';
$base_request = new base_request();
$log = array();
function insert_log($output, $with_script_tags = true)
{
    global $log;
    $log[] = $output;
}

function get_setting_site_url($conn, $table_prefix)
{
    $query = "SELECT * FROM `$table_prefix" . "options` WHERE option_name = 'siteurl' LIMIT 1;";
    if ($result = @$conn->query($query)) {
        $res = $result->fetch_object();
        $result->free_result();
        return $res->option_value;
    } else {
        global $base_request;
        $base_request->error($base_request->create_error('get_setting_site_url', 'Fail get site url'));
    }
}

function detect_charset_collate($conn, $db_name, $table_prefix)
{
    global $base_request;
    global $charset;
    global $collate;

    $query = "SELECT T.table_name, CCSA.character_set_name, CCSA.collation_name FROM information_schema.`TABLES` T, information_schema.`COLLATION_CHARACTER_SET_APPLICABILITY` CCSA WHERE CCSA.collation_name = T.table_collation AND T.table_schema = '$db_name' AND T.table_name = '$table_prefix" . "posts';";
    if ($result = @$conn->query($query)) {
        $res = $result->fetch_object();
        $result->free_result();
        // $charset = $res->character_set_name;
        // $collate = $res->collation_name;

        if(isset($res->character_set_name))
            $charset = $res->character_set_name;
        else if(isset($res->CHARACTER_SET_NAME))
            $charset = $res->CHARACTER_SET_NAME;
        // die($charset);

        if(isset($res->collation_name))
            $collate = $res->collation_name;
        else if(isset($res->COLLATION_NAME))
            $collate = $res->COLLATION_NAME;

    } else {
        $code = 'convert_charset_database';
        $error = $base_request->create_error($code . '_' . $conn->errno, "function $code error (" . $conn->errno . "): " . $conn->error);
        $error['query'] = $query;
        $base_request->error($error);
    }
}

function init_file_ini_config($iniPath = null){
    global $base_request;
    if($iniPath == null)
        $iniPath = 'config/database_config.ini';
    if (file_exists($iniPath)) {
        if (unlink($iniPath)) {
            insert_log('Delete file ini');
        } else {
            $base_request->error($base_request->create_error('codethue_setup', 'Can not delete file ini. Please set full permission (777) with \'config\' folder'), array('log' => $log));
        }
    }

    insert_log('Create file ini');

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

        if( write_php_ini(array('database' => array(
            'db_host'      => DB_HOST,
            'db_user'      => DB_USER,
            'db_password'  => DB_PASSWORD,
            'db_name'      => DB_NAME,
            'table_prefix' => $table_prefix,
            'charset'      => $charset,
            'collate'      => $collate,
            'site_url'     => get_setting_site_url($conn, $table_prefix)
        )), $iniPath)){
            return true;
        }else{
            $base_request->error($base_request->create_error('codethue_setup', 'Setup fail. Can not write file ini. Please set full permission (777) with \'config\' folder'), array('log' => $log));
        }
    }else{
        $base_request->error($base_request->create_error('codethue_setup', 'Not found wp-config.php'), array('log' => $log));
        return false;
    }
}

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

function file_ini_json()
{
    global $iniPath;
    global $log;
    if (file_exists($iniPath)) {
        $ini['database'] = parse_ini_file($iniPath);
        $ini['database']['db_password'] = '********';
        $ini['log'] = $log;
        return $ini;
    } else {
        return null;
    }
}

if (isset($_GET['action'])) {
    $action = $_GET['action'];
    switch($action){
        case 'create_ini_config':
            if(init_file_ini_config()){
                $base_request->success(file_ini_json());
            }else{
                $base_request->error($base_request->create_error('config_faild', 'Setup fail. Please check log'), array('log' => $log));
            }
            break;
    }
}