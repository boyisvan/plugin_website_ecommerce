<?php

require_once "../../config/systemConfig.php";
require_once "../base_request.php";
require_once "../database.php";
require_once "functions.php";

class list_order extends base_request{

    function __construct(){
        $this->database = new database();
    }

    function get_list($page, $perpage, $add_fields = null, $sort_by_date = true, $date_from_gmt = null, $date_to_gmt = null, $load_trash = false){ // datetime: 2022-01-20 14:23:57 load_trash Để thống kê dashboard
        global $table_posts;
        global $order_function;
        $offset = $page*$perpage;
        $query = "SELECT SQL_CALC_FOUND_ROWS post.ID FROM $table_posts as post
        WHERE post.post_type in ('shop_order', 'shop_order_placehold')";// AND ((post.post_status <> 'trash' AND post.post_status <> 'auto-draft'))";

        if($load_trash == false)
            $query .= " AND ((post.post_status <> 'trash' AND post.post_status <> 'auto-draft'))";

        if($date_from_gmt != null)
            $query .= " AND post.post_date_gmt >= '$date_from_gmt'"; // 2022-01-20 14:23:57
        if($date_to_gmt != null)
            $query .= " AND post.post_date_gmt <= '$date_to_gmt'"; // 2022-01-20 14:23:57
        // $query .= " GROUP BY post.ID ";
        if($sort_by_date == true)
            $query .= " ORDER BY post.post_date_gmt DESC "; // Sắp xếp theo ngày tháng chậm đi 3 lần
        else
            $query .= " ORDER BY post.ID DESC ";

        $query .= " LIMIT $offset, $perpage;";
        // die($query);
        $lists = array();
        $arr_ids = $this->database->run_sql_return_array($query); //10ms
        $total_rows = $this->database->run_sql_get_single_col("SELECT FOUND_ROWS();");
        if($arr_ids != null){
            $ids   = array_column($arr_ids, 'ID');
            $lists = $order_function->get_oder($ids, $sort_by_date, $add_fields);
        }
        $this->success(array('total' => $total_rows, 'orders'=>$lists));
    }
}

$request = new list_order();

$method = $_SERVER['REQUEST_METHOD'];
if ($method == 'GET' || $method == 'get') {
    $page = 0;
    $per_page = 20;
    $add_fields = null;
    $action = 'get_list';
    $sort_by_date = true;
    $date_from_gmt = null;
    $date_to_gmt = null;
    $load_trash = false; // Để thống kê dashboard
    if(isset($_GET['page']))
        $page = $_GET['page'];
    if(isset($_GET['per_page']))
        $per_page = $_GET['per_page'];
    if(isset($_GET['add_fields']))
        $add_fields = $_GET['add_fields'];
    if(isset($_GET['not_sort']))
        $sort_by_date = false;
    if(isset($_GET['date_from_gmt']))
        $date_from_gmt = $_GET['date_from_gmt'];
    if(isset($_GET['date_from_to']))
        $date_to_gmt = $_GET['date_from_to'];
    if(isset($_GET['load_trash']))
        $load_trash = true;

    if(isset($_GET['action'])) {
        $action = $_GET['action'];

        switch($action){
            case 'get_list':
                $request->get_list($page, $per_page, $add_fields, $sort_by_date, $date_from_gmt, $date_to_gmt, $load_trash);
            break;
        }

        if(method_exists($request, $action)){
            $request->{$action}();
        }
        else{
            $request->error($request->create_error('not_found_action', 'Not found action you need'));
        }
    }else {
        $request->get_list($page, $per_page, $add_fields, $sort_by_date, $date_from_gmt, $date_to_gmt, $load_trash);
    }
}else{
    $request->error($request->create_error('not_match_method', 'Must use GET method'));
}