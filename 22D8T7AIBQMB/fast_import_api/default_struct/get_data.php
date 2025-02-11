<?php
include "../config/systemConfig.php";
include "base_request.php";

$iniPath = '../../config/database_config.ini';
if(!file_exists($iniPath)){
    die('GETDATA.php: Không tìm thấy file ini');
}

$ini = parse_ini_file($iniPath);
$table_prefix = $ini['table_prefix'];
$table_terms = "`$table_prefix"."terms`";
$table_term_taxonomy = "`$table_prefix"."term_taxonomy`";
$table_term_relationships = "`$table_prefix"."term_relationships`";
$table_termmeta = "`$table_prefix"."termmeta`";

class get_data extends base_request
{
    function getTerm($page, $perpage, $taxonomy)
    {
        global $table_terms;
        global $table_term_taxonomy;

        $res = array('data'=>array());
        $query = "SELECT T.term_id, T.name FROM $table_terms as T"
            . " INNER JOIN $table_term_taxonomy as TX ON T.term_id = TX.term_id"
            . " WHERE TX.taxonomy= '$taxonomy' LIMIT $page, $perpage;";

        global $dbContext;

        if ($result = $dbContext->query($query)) {
            $res_array = $result;//->fetch_array();
            if ($res_array) {
                foreach ($res_array as $item) {
                    //$res['data'][] = $item;//array('id'=>$item, 'name'=>$item[1]);
                    $res['data'][] = array('id'=>$item['term_id'], 'name'=>$item['name']);
                }
            } else
                $res = null;
            $result->free_result();
            $this->response(200, $res);
        } else {
            die("function run_sql_get_single_col error (" . $dbContext->errno . "): " . $dbContext->error . " Query: " . $query);
        }
    }

    function getCategory($page, $perpage){
        $this->getTerm($page, $perpage, 'product_cat');
    }

    function run_sql_get_single_col($dbContext, $query)
    {
        if ($result = $dbContext->query($query)) {
            $res_array = $result->fetch_array();
            if ($res_array)
                $res = $res_array[0];
            else
                $res = null;
            $result->free_result();
            return $res;
        } else {
            die("function run_sql_get_single_col error (" . $dbContext->errno . "): " . $dbContext->error . " Query: " . $query);
        }
    }

    function run_sql($dbContext, $query)
    {
        if (!$dbContext->query($query)) {
            die("function run_sql error (" . $dbContext->errno . "): " . $dbContext->error . " Query: " . $query);
        }
    }
}

$get_data = new get_data();

if(isset($_GET['action']) && isset($_GET['page']) && isset($_GET['perpage'])) {
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'];
    $page=$_GET['page'];
    $perpage=$_GET['perpage'];

    if ($action == 'get_category') {
        $get_data->getCategory($page, $perpage);
    }
    }else {
        $get_data->response(406, 'Thieu param: action, page, perpage (bat dau tu 0)');
}