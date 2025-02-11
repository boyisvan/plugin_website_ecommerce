<?php

require_once "../../config/systemConfig.php";
require_once "../base_request.php";
require_once "../database.php";

class list_category extends base_request{

    function __construct(){
        $this->database = new database();
    }

    function get_list($page, $perpage, $arr_taxonomy_string, $keyword){
        global $table_posts;
        global $table_terms;
        global $table_term_taxonomy;
        global $order_function;
        // $arr_ids_string = implode(',', $arr_taxonomy);
        $offset = $page*$perpage;
        $query = "SELECT SQL_CALC_FOUND_ROWS terms.term_id, terms.name, terms.slug, taxonomy.taxonomy, terms.term_group, taxonomy.parent, taxonomy.count FROM $table_terms as terms
        INNER JOIN $table_term_taxonomy AS taxonomy ON terms.term_id = taxonomy.term_id";

        if($arr_taxonomy_string != null && $arr_taxonomy_string != '')
            $query .= " WHERE taxonomy.taxonomy IN ($arr_taxonomy_string)";

        if($keyword != null && $keyword != '') {
            if($arr_taxonomy_string != null && $arr_taxonomy_string != '')
                $query .= " AND (terms.name like '%$keyword%' or terms.slug like '%$keyword%')";
            else
                $query .= " WHERE terms.name like '%$keyword%' or terms.slug like '%$keyword%'";
        }

        $query .= " LIMIT $offset, $perpage;";

        // die($query);
        $lst_categories = $this->database->run_sql_return_array($query);
        $total_rows = $this->database->run_sql_get_single_col("SELECT FOUND_ROWS();");
        $this->success(array('total' => $total_rows, 'terms'=>$lst_categories));
    }
}

$request = new list_category();

$method = $_SERVER['REQUEST_METHOD'];
if ($method == 'GET' || $method == 'get') {
    $page = 0;
    $per_page = 20;
    $add_fields = null;
    $action = 'get_list';
    $taxonomy = '';
    if(isset($_GET['page']))
        $page = $_GET['page'];
    if(isset($_GET['per_page']))
        $per_page = $_GET['per_page'];
    if(isset($_GET['taxonomy']))
        $taxonomy = $_GET['taxonomy'];
    $keyword = '';
    if(isset($_GET['keyword']))
        $keyword = $_GET['keyword'];

    // if(str_contains($taxonomy, ",")){
    if(strpos($taxonomy, ",") != false) {// php before v8
        $arr_ids_string = explode(',', $taxonomy);
        foreach($arr_ids_string as &$temp)
            $temp = "'$temp'";
        $taxonomy = join(",", $arr_ids_string);
    }else{
        $taxonomy = "'$taxonomy'";
    }

    if(isset($_GET['action'])) {
        $action = $_GET['action'];

        switch($action){
            case 'get_list':
                $request->get_list($page, $per_page, $taxonomy, $keyword);
                break;
            case 'get_categories':
                $request->get_list($page, $per_page, "'product_cat'", $keyword);
                break;
            case 'get_tags':
                $request->get_list($page, $per_page, "'product_tag'", $keyword);
                break;
        }

        if(method_exists($request, $action)){
            $request->{$action}();
        }
        else{
            $request->error($request->create_error('not_found_action', 'Not found action you need'));
        }
    }else {
        $request->get_list($page, $per_page, $taxonomy, $keyword);
    }
} else {
    $request->error($request->create_error('not_match_method', 'Must use GET method'));
}