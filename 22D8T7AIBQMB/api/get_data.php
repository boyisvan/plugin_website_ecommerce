<?php
include "../config/systemConfig.php";
include "base_request.php";
require_once 'database.php';
$iniPath = '../config/database_config.ini';
if (!file_exists($iniPath)) {
    $response_body = array(
        "status" => 'error',
        "data" => "",
        "warning" => "",
        "error" => array("code" => "not_found_ini", "exception" => "Không tìm thấy file ini")
    );
    $json = json_encode($response_body);
    die($json);
}

$ini = parse_ini_file($iniPath);
$table_prefix = $ini['table_prefix'];
$table_terms = "`$table_prefix" . "terms`";
$table_term_taxonomy = "`$table_prefix" . "term_taxonomy`";
$table_term_relationships = "`$table_prefix" . "term_relationships`";
$table_termmeta = "`$table_prefix" . "termmeta`";

class get_data extends base_request
{
    function __construct()
    {
        $this->database = new database();
    }

    function getTerm($page, $perpage, $taxonomy)
    {
        global $table_terms;
        global $table_term_taxonomy;
        $offset = $page * $perpage;

        $res = array('data' => array());
        $query = "SELECT T.term_id, T.name FROM $table_terms as T"
            . " INNER JOIN $table_term_taxonomy as TX ON T.term_id = TX.term_id"
            . " WHERE TX.taxonomy= '$taxonomy' LIMIT $offset, $perpage;";

        global $dbContext;

        if ($result = $dbContext->query($query)) {
            $res_array = $result; //->fetch_array();
            if ($res_array) {
                foreach ($res_array as $item) {
                    //$res['data'][] = $item;//array('id'=>$item, 'name'=>$item[1]);
                    $res['data'][] = array('id' => $item['term_id'], 'name' => $item['name']);
                }
            } else
                $res = null;
            $result->free_result();
            $this->success($res);
        } else {
            $this->error_db($dbContext, 'getTerm', $query);
        }
    }

    function getCategory($page, $perpage)
    {
        $this->getTerm($page, $perpage, 'product_cat');
    }

    function getTags($page, $perpage)
    {
        $this->getTerm($page, $perpage, 'product_tag');
    }

    function get_attribute($page, $perpage)
    {
        global $table_woo_attribute_taxonomie;
        $offset = $page * $perpage;
        $query = "SELECT SQL_CALC_FOUND_ROWS attribute_id, attribute_name, attribute_label FROM $table_woo_attribute_taxonomie LIMIT $offset, $perpage;";
        global $dbContext;
        if ($result = $dbContext->query($query)) {
            $res_array = $result; //->fetch_array();
            $total_rows = $this->database->run_sql_get_single_col("SELECT FOUND_ROWS();");
            $res['total'] = $total_rows;
            if ($res_array) {
                foreach ($res_array as $item) {
                    $res['attributes'][] = array('id' => $item['attribute_id'], 'label' => $item['attribute_label'], 'slug' => $item['attribute_name']);
                }
            } else
                $res = null;
            $result->free_result();
            $this->success($res);
        } else {
            $this->error_db($dbContext, 'get_attribute', $query);
        }
    }

    function get_attribute_term($id_attribute, $page, $perpage)
    {
        global $table_woo_attribute_taxonomie;
        global $table_terms;
        global $table_term_taxonomy;
        
        $query = "SELECT attribute_name FROM $table_woo_attribute_taxonomie WHERE attribute_id = $id_attribute;";
        $attribute_name = 'pa_' . $this->database->run_sql_get_single_col($query);
        if ($attribute_name != 'pa_') {
            $offset = $page * $perpage;
            $query = "SELECT SQL_CALC_FOUND_ROWS t.name, t.slug FROM $table_terms as t
                    INNER JOIN $table_term_taxonomy as tx ON t.term_id = tx.term_id
                    WHERE tx.taxonomy = '$attribute_name' LIMIT $offset, $perpage;";
            global $dbContext;
            if ($result = $dbContext->query($query)) {
                $res_array = $result; //->fetch_array();
                $total_rows = $this->database->run_sql_get_single_col("SELECT FOUND_ROWS();");
                $res['total'] = $total_rows;
                if ($res_array) {
                    foreach ($res_array as $item) {
                        $res['terms'][] = array('name' => $item['name'], 'slug' => $item['slug']);
                    }
                } else
                    $res = null;
                $result->free_result();
                $this->success($res);
            } else {
                $this->error_db($dbContext, 'get_attribute_term', $query);
            }
        } else {
            $this->success(array());
        }
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
            $this->error_db($dbContext, 'run_sql_get_single_col', $query);
        }
    }

    function run_sql($dbContext, $query)
    {
        if (!$dbContext->query($query)) {
            $this->error_db($dbContext, 'run_sql', $query);
        }
    }
}

$get_data = new get_data();

if (isset($_GET['action']) && isset($_GET['page']) && isset($_GET['perpage'])) {
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'];
    $page = $_GET['page'];
    $perpage = $_GET['perpage'];

    switch ($action) {
        case 'get_category':
            $get_data->getCategory($page, $perpage);
            break;
        case 'get_tag':
            $get_data->getTags($page, $perpage);
            break;
        case 'get_attribute':
            $get_data->get_attribute($page, $perpage);
            break;
        case 'get_attribute_term':
            $id_attribute = $_GET['id'] ?? 0;
            $get_data->get_attribute_term($id_attribute, $page, $perpage);
            break;
    }

    // if ($action == 'get_category') {
    //     $get_data->getCategory($page, $perpage);
    // }
} else {
    $get_data->error($get_data->create_error('not_found_param', 'Thieu param: action, page, perpage (bat dau tu 0)'));
}
