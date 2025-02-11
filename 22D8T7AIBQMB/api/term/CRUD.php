<?php

require_once "../../config/systemConfig.php";
require_once "../base_request.php";
require_once "../database.php";
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
class list_category extends base_request{

    function __construct(){
        $this->database = new database();
    }

    function create($term) {
        global $dbContext;
// die('here');
        $term_name = $term['term_name'];

        $term_name = $dbContext->real_escape_string($term_name);
        $id_parent = 0;
        if(isset($term['id_parent']))
            $id_parent = $term['id_parent'];
        $term_slug = $dbContext->real_escape_string($term['term_slug']);
        $term_taxonomy = $dbContext->real_escape_string($term['term_taxonomy']); //product_cat, product_tag

        $sql = "select create_term('$term_name', '$term_slug', '$term_taxonomy', $id_parent) as id_term;";

        if ($result = $dbContext->query($sql)) {
            $id_term = $result->fetch_object()->id_term;
            $result->free_result();
            $this->success(array('id_term' => $id_term));
        } else {
            $this->error($dbContext, 'CRUD_create_term', $sql);
        }
    }

    function update($term) {
        global $table_terms;
        $name = $term['term_name'];
        $term_id = $term['term_id'];
        $query = "UPDATE $table_terms SET `name`='$name' WHERE term_id = $term_id";
        if(isset($term['term_slug'])){
            $slug = $term['term_slug'];
            $query = "UPDATE $table_terms SET `name`='$name', `slug`='$slug' WHERE term_id = $term_id";
        }
        $this->database->run_sql_without_return($query);

        if(isset($term['id_parent'])){
            global $table_term_taxonomy;
            $id_parent = $term['id_parent'];
            $query = "UPDATE $table_term_taxonomy SET `parent` = $id_parent WHERE term_id = $term_id";
            $this->database->run_sql_without_return($query);
        }

        $this->success(array('term_id' => $term_id, 'new_name'=>$name));
    }

    function delete($term_id){
        global $table_terms;
        global $table_termmeta;
        global $table_term_taxonomy;

        $query = "DELETE FROM $table_term_taxonomy WHERE term_id = $term_id";
        $this->database->run_sql_without_return($query);

        $query = "DELETE FROM $table_termmeta WHERE term_id = $term_id";
        $this->database->run_sql_without_return($query);

        $query = "DELETE FROM $table_terms WHERE term_id = $term_id";
        $this->database->run_sql_without_return($query);

        $this->success(array('term_id' => $term_id));
    }
}

$request = new list_category();

$method = $_SERVER['REQUEST_METHOD'];
if ($method == 'POST' || $method == 'post') {
    if(isset($_GET['action'])) {
        $action = $_GET['action'];
        $json = file_get_contents('php://input');
        $item = json_decode($json, true);
        if(isset($_GET['debug'])){
            error_reporting(E_ALL);
            ini_set('display_errors', 'On');
        }

        switch($action){
            case 'create':
                if(isset($item['term_name']) && isset($item['term_slug']))
                    $request->create($item);
                else
                    $request->error($request->create_error('wrong_data', 'Not found term_id, term_name in json body'));
                break;
            case 'update':
                if(isset($item['term_name']) && isset($item['term_id']))
                    $request->update($item);
                else
                    $request->error($request->create_error('wrong_data', 'Not found term_id, term_name in json body'));
                break;
            case 'delete':
                if(isset($item['term_id']))
                    $request->delete($item['term_id']);
                else
                    $request->error($request->create_error('wrong_data', 'Not found term_id in json body'));
                break;
        }
        if(method_exists($request, $action)){
            $request->{$action}();
        }
        else{
            $request->error($request->create_error('not_found_action', 'Not found action you need'));
        }
    } else {
        $request->error($request->create_error('not_found_action', 'Not found action you need'));
    }
} else {
    $request->error($request->create_error('not_match_method', 'Must use GET method'));
}