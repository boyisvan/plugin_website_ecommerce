<?php

require_once "../../config/systemConfig.php";
require_once "../base_request.php";
require_once "../database.php";
require_once "functions.php";

class search_product extends base_request{
    //var database $database;

    function __construct(){
        $this->database = new database();
    }
    
    function search_by_keyword($page, $perpage, $add_fields, $keyword, $sort_by_date){
        global $table_posts;
        global $product_function;

        $offset = $page*$perpage;
        $query = "SELECT SQL_CALC_FOUND_ROWS post.ID FROM $table_posts as post
        WHERE post.post_type = 'product' AND (post.post_status = 'publish' OR post.post_status = 'future' OR post.post_status = 'draft' OR post.post_status = 'pending' OR post.post_status = 'private')
        AND LOWER(post.post_title) like LOWER('%$keyword%')
        /*GROUP BY post.ID*/ "; // Không nhớ group by id này để làm gì?
        if($sort_by_date)
            $query .= " ORDER BY post.post_date DESC "; // Sắp xếp theo ngày tháng chậm đi 3 lần
        $query .= " LIMIT $offset, $perpage;";
        $lists = array();
        $arr_ids = $this->database->run_sql_return_array($query); //10ms
        $total_rows = $this->database->run_sql_get_single_col("SELECT FOUND_ROWS();");
        //$this->success(array('total' => $total_rows, 'products'=>$arr_ids));
        if($arr_ids != null){
            $ids   = array_column($arr_ids, 'ID');
            $lists = $product_function->get_product($ids, $add_fields);
        }
        $this->success(array('total' => $total_rows, 'products'=>$lists));
    }

    function search_by_price($page, $perpage, $add_fields, $sort_by_date, $price_from, $price_to){
        global $table_posts;
        global $product_function;
        global $table_wc_product_meta_lookup;

        $offset = $page*$perpage;
        $query = "SELECT SQL_CALC_FOUND_ROWS post.ID FROM $table_wc_product_meta_lookup as lu
        INNER JOIN $table_posts as post ON post.ID = lu.product_id
        WHERE post.post_type = 'product' AND (post.post_status = 'publish' OR post.post_status = 'future' OR post.post_status = 'draft' OR post.post_status = 'pending' OR post.post_status = 'private')";

        if($price_from != null) $query .= " AND lu.min_price >= $price_from";
        if($price_to   != null) $query .= " AND lu.max_price <= $price_to";
        
        if($sort_by_date)
            $query .= " ORDER BY post.post_date DESC "; // Sắp xếp theo ngày tháng chậm đi 3 lần
        $query .= " LIMIT $offset, $perpage;";
        // die($query);

        $lists = array();
        $arr_ids = $this->database->run_sql_return_array($query); //10ms
        $total_rows = $this->database->run_sql_get_single_col("SELECT FOUND_ROWS();");
        //$this->success(array('total' => $total_rows, 'products'=>$arr_ids));
        if($arr_ids != null){
            $ids   = array_column($arr_ids, 'ID');
            $lists = $product_function->get_product($ids, $add_fields);
        }
        $this->success(array('total' => $total_rows, 'products'=>$lists));
    }

    function search_by_categories_and_tags($page, $perpage, $add_fields, $sort_by_date, $lst_ids_cagegory_string, $lst_ids_tag_string, $keyword, $filter_and){
        global $table_posts;
        global $table_term_taxonomy;
        global $product_function;
        global $table_term_relationships;
        $arr_term_id_string = $lst_ids_cagegory_string;
        if($lst_ids_cagegory_string != '' && $lst_ids_tag_string != '')
            $arr_term_id_string .= ",";
        $arr_term_id_string .= $lst_ids_tag_string;

        $offset = $page*$perpage;
        $query = "SELECT DISTINCT post.ID FROM $table_term_relationships AS tr
        INNER JOIN $table_posts AS post ON tr.object_id = post.ID
        INNER JOIN $table_term_taxonomy AS tx ON tx.term_taxonomy_id = tr.term_taxonomy_id
        WHERE post.post_type = 'product' AND (post.post_status = 'publish' OR post.post_status = 'future' OR post.post_status = 'draft' OR post.post_status = 'pending' OR post.post_status = 'private') AND LOWER(post.post_title) like LOWER('%$keyword%')
        AND tx.taxonomy IN('product_cat','product_tag') AND tx.term_id IN($arr_term_id_string)";

        if($filter_and && $lst_ids_cagegory_string != '' && $lst_ids_tag_string != '')
            $query = "SELECT DISTINCT tb1.ID FROM
            (SELECT DISTINCT post.ID FROM $table_term_relationships AS tr
            INNER JOIN $table_posts AS post ON tr.object_id = post.ID
            INNER JOIN $table_term_taxonomy AS tx ON tx.term_taxonomy_id = tr.term_taxonomy_id
            WHERE post.post_type = 'product' AND (post.post_status = 'publish' OR post.post_status = 'future' OR post.post_status = 'draft' OR post.post_status = 'pending' OR post.post_status = 'private') AND LOWER(post.post_title) like LOWER('%$keyword%')
            AND tx.taxonomy IN('product_cat') AND tx.term_id IN($arr_term_id_string)) as tb1
            INNER JOIN
            (SELECT DISTINCT post.ID FROM $table_term_relationships AS tr
            INNER JOIN $table_posts AS post ON tr.object_id = post.ID
            INNER JOIN $table_term_taxonomy AS tx ON tx.term_taxonomy_id = tr.term_taxonomy_id
            WHERE post.post_type = 'product' AND (post.post_status = 'publish' OR post.post_status = 'future' OR post.post_status = 'draft' OR post.post_status = 'pending' OR post.post_status = 'private') AND LOWER(post.post_title) like LOWER('%$keyword%')
            AND tx.taxonomy IN('product_tag') AND tx.term_id IN($arr_term_id_string)) as tb2
            ON tb1.ID = tb2.ID";
        if($lst_ids_cagegory_string == '-1' || $lst_ids_tag_string == '-1') {    // uncategory or untag
            $taxpnomy = '';
            if($lst_ids_cagegory_string == '-1') // uncategory
                $taxpnomy = "'product_cat'";
            if($lst_ids_tag_string == '-1')      // untag
                $taxpnomy = "'product_tag'";
            if($lst_ids_cagegory_string == '-1' && $lst_ids_tag_string == '-1') // uncategory and untag
                $taxpnomy = "'product_cat','product_tag'";
            $query = "SELECT DISTINCT post.ID FROM $table_posts AS post
            WHERE post.ID NOT IN (SELECT tr.object_id FROM $table_term_relationships AS tr INNER JOIN $table_term_taxonomy AS tx ON tx.term_taxonomy_id = tr.term_taxonomy_id WHERE tx.taxonomy IN ($taxpnomy))
            AND post.post_type = 'product' AND (post.post_status = 'publish' OR post.post_status = 'future' OR post.post_status = 'draft' OR post.post_status = 'pending' OR post.post_status = 'private') AND LOWER(post.post_title) like LOWER('%$keyword%')";
        }

        // if($sort_by_date)
        //     $query .= " ORDER BY post.post_date DESC "; // Sắp xếp theo ngày tháng chậm đi 3 lần và không kết hợp với DISTINCT được
        // die($query);
        $lists = array();
        $total_rows = $this->database->run_sql_get_single_col("SELECT COUNT(*) FROM ($query) as gpm;");
        $query .= " LIMIT $offset, $perpage;";
        $arr_ids = $this->database->run_sql_return_array($query); //10ms

        //$this->success(array('total' => $total_rows, 'products'=>$arr_ids));
        if($arr_ids != null){
            $ids   = array_column($arr_ids, 'ID');
            $lists = $product_function->get_product($ids, $add_fields);
        }
        $this->success(array('total' => $total_rows, 'products'=>$lists));
    }
}

$request = new search_product();

$method = $_SERVER['REQUEST_METHOD'];
if ($method == 'GET' || $method == 'get') {
    $page = 0;
    $per_page = 20;
    $add_fields = null;
    $keyword = '';
    if(isset($_GET['page']))
        $page = $_GET['page'];
    if(isset($_GET['per_page']))
        $per_page = $_GET['per_page'];
    if(isset($_GET['add_fields']))
        $add_fields = $_GET['add_fields'];
    if(isset($_GET['keyword']))
        $keyword = $_GET['keyword'];

    $sort_by_date = true;
    if(isset($_GET['not_sort']))
        $sort_by_date = false;


    if(isset($_GET['action'])) {
        $action = $_GET['action'];

        switch($action){
            case 'search_by_keyword':
                $request->search_by_keyword($page, $per_page, $add_fields, $keyword, $sort_by_date);
            case 'search_by_price':
                    $price_from = null; $price_to = null;
                    if(isset($_GET['price_from'])) $price_from = $_GET['price_from'];
                    if(isset($_GET['price_to'])) $price_to = $_GET['price_to'];

                    $request->search_by_price($page, $per_page, $add_fields, $sort_by_date, $price_from, $price_to);
            break;
            case 'search_by_ids_category_and_tags':
                $ids_category = '';
                if(isset($_GET['ids_category'])) $ids_category = $_GET['ids_category'];
                $ids_tag = '';
                if(isset($_GET['ids_tag'])) $ids_tag = $_GET['ids_tag'];
                $filter_and = false;
                if(isset($_GET['filter_and']))
                    $filter_and = true;
                $request->search_by_categories_and_tags($page, $per_page, $add_fields, $sort_by_date, $ids_category, $ids_tag, $keyword, $filter_and);
                break;
        }
        if(method_exists($request, $action)){
            $request->{$action}();
        }
        else{
            $request->error($request->create_error('not_found_action', 'Not found action you need'));
        }
    }else {
        $request->search_by_keyword($page, $per_page, $add_fields, $keyword, $sort_by_date);
    }
}else{
    $request->error($request->create_error('not_match_method', 'Must use GET method'));
}