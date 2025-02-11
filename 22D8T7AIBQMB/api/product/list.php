<?php

require_once "../../config/systemConfig.php";
require_once "../base_request.php";
require_once "../database.php";
require_once "functions.php";

#[AllowDynamicProperties]
class list_product extends base_request
{
    //var database $database;

    function __construct()
    {
        $this->database = new database();
    }

    function get_list_product($page, $perpage, $add_fields = null, $mode = 'all_product', $filter=null)
    {
        global $table_posts;
        global $product_function;
        global $table_woo_order_product_lookup;
        global $table_term_relationships;
        global $table_term_taxonomy;
        $query = "";
        $offset = $page * $perpage;
        switch ($mode) {
            case 'have_sale':
                $query = "SELECT SQL_CALC_FOUND_ROWS DISTINCT product_id AS ID FROM $table_woo_order_product_lookup LIMIT $offset, $perpage;";
                break;
            case 'have_sale_sort':
                $query = "SELECT SQL_CALC_FOUND_ROWS product_id AS ID, sum(product_qty) AS total_sale FROM $table_woo_order_product_lookup GROUP BY product_id ORDER BY total_sale DESC LIMIT $offset, $perpage;";
                break;
            case 'unpublish':
                $query = "SELECT SQL_CALC_FOUND_ROWS DISTINCT post.ID FROM $table_posts as post
                WHERE post.post_type = 'product' AND (post.post_status = 'future' OR post.post_status = 'draft' OR post.post_status = 'pending')";
                break;
            case 'with_status':
                $status = "'publish', 'future', 'draft', 'pending', 'private'";
                if(isset($filter->status) === true)
                    $status = join(',', $filter->status);//array
                $query = "SELECT SQL_CALC_FOUND_ROWS DISTINCT post.ID FROM $table_posts as post
                WHERE post.post_type = 'product' AND post.post_status in ($status)";
                break;
            case 'all_product':
                $query = "SELECT SQL_CALC_FOUND_ROWS DISTINCT post.ID FROM $table_posts as post
                WHERE post.post_type = 'product' AND (post.post_status = 'publish' OR post.post_status = 'future' OR post.post_status = 'draft' OR post.post_status = 'pending' OR post.post_status = 'private')";
            default:
                $query = "SELECT SQL_CALC_FOUND_ROWS DISTINCT post.ID, post.post_date FROM $table_posts as post
                WHERE post.post_type = 'product' AND (post.post_status = 'publish' OR post.post_status = 'future' OR post.post_status = 'draft' OR post.post_status = 'pending' OR post.post_status = 'private')";

                if(isset($filter->category_id) === true){
                    $query = "SELECT SQL_CALC_FOUND_ROWS DISTINCT post.ID FROM $table_term_relationships AS tr
                    INNER JOIN $table_posts AS post ON tr.object_id = post.ID
                    INNER JOIN $table_term_taxonomy AS tx ON tx.term_taxonomy_id = tr.term_taxonomy_id
                    WHERE post.post_type = 'product' AND tx.taxonomy = 'product_cat' AND tx.term_id IN($filter->category_id)";
                }

                if(isset($filter->start_publish_time) === true)
                    $query .= " AND post.post_date >= '$filter->start_publish_time'";
                if(isset($filter->end_publish_time) === true)
                    $query .= " AND post.post_date <= '$filter->end_publish_time'";
                if(!(isset($filter->not_sort) === true && $filter->not_sort === true))
                   $query .= " ORDER BY post.post_date DESC"; // Sắp xếp theo ngày tháng chậm đi 3 lần
                $query .= " LIMIT $offset, $perpage;";
                // die($query);
                break;
        }
        // die($query);
        try{
            //throw new Exception("hi");
            $arr_ids = $this->database->run_sql_return_array($query); //10ms

            $total_rows = $this->database->run_sql_get_single_col("SELECT FOUND_ROWS();");
            $lists = array();
            if ($arr_ids != null) {
                $ids   = array_column($arr_ids, 'ID');
                // 19.01.2024: Để update giá theo ID (chọn toàn bộ store)
                if($mode == "id_only") 
                    $this->success(array('total' => $total_rows, 'ids' => $ids));

                $temp = $product_function->get_product($ids, $add_fields, false, $filter);
                $lists = $this->order_product($ids, $temp);
            }
            if($mode == "id_only") 
                $this->success(array('total' => $total_rows, 'ids' => []));
            $this->success(array('total' => $total_rows, 'products' => $lists));
        }
        catch (\Exception $ex){
            $this->success(array('errors' => $ex, 'pos' => 'get_list_product_line_84'));
        }
    }

    function get_all_product($page, $perpage, $add_fields = null, $filter=null)
    {
        $this->get_list_product($page, $perpage, $add_fields, 'all_product', $filter);
    }

    function get_product_have_sale($page, $perpage, $add_fields = null, $sort=false, $filter=null)
    {
        if($sort == false)
            $this->get_list_product($page, $perpage, $add_fields, 'have_sale', $filter);
        else
            $this->get_list_product($page, $perpage, $add_fields, 'have_sale_sort', $filter);
    }

    function order_product($ids, $lists)
    {
        $res = array();
        foreach ($ids as $id) {
            $index = 0;
            foreach ($lists as $t) {
                if ($t->ID == $id) {
                    $res[] = $t;
                    \array_splice($lists, $index, 1);
                    break;
                }
                $index++;
            }
        }
        return $res;
    }

    function get_ids_by_offset($page, $perpage, $start_id)
    {
        global $table_posts;
        global $product_function;

        $offset = $page * $perpage;
        $query = "SELECT SQL_CALC_FOUND_ROWS post.ID FROM $table_posts as post
        WHERE post.post_type = 'product' AND (post.post_status = 'publish' OR post.post_status = 'future' OR post.post_status = 'draft' OR post.post_status = 'pending' OR post.post_status = 'private')
        AND post.ID >= $start_id
        GROUP BY post.ID LIMIT $offset, $perpage;"; // Sắp xếp theo ngày tháng chậm đi 3 lần
        $ids = array();
        $total_rows = 0;
        $arr_product_ids = $this->database->run_sql_return_array($query); //10ms
        if ($arr_product_ids != null) {
            $ids = array_column($arr_product_ids, 'ID');
            $total_rows = $this->database->run_sql_get_single_col("SELECT FOUND_ROWS();");
        }
        $this->success(array('total' => $total_rows, 'ids' => $ids));
    }

    function get_total_count()
    {
        $query = "SELECT COUNT( 1 )"
        . " FROM wp_posts"
        . " WHERE post_type = 'product'"
        . " -- AND post_status NOT IN ( 'trash','auto-draft','inherit','request-pending','request-confirmed','request-failed','request-completed' )"
        . " -- AND post_author = 1";
        $this->success(array('total_count' => $this->database->run_sql_get_single_col($query)));
    }

    function statistic_status()
    {
        global $table_posts;

        $query = "SELECT post_status, COUNT(*) AS num_posts FROM $table_posts WHERE post_type = 'product' GROUP BY post_status;";
        $this->success(array('statistic_tatus' => $this->database->run_sql_return_array($query)));
    }
}

$request = new list_product();

$method = $_SERVER['REQUEST_METHOD'];
if ($method == 'GET' || $method == 'get') {
    $page = 0;
    $per_page = 20;
    $add_fields = null;
    $mode = 'all_product';
    $action = 'get_list';
    $filter = null;

    if (isset($_GET['page']))
        $page = $_GET['page'];
    if (isset($_GET['per_page']))
        $per_page = $_GET['per_page'];
    if (isset($_GET['add_fields']))
        $add_fields = $_GET['add_fields'];
    if(isset($_GET['filter']))
        $filter = json_decode($_GET['filter']);
    //if($filter != null)
       // die($filter->first_variation_only);
    if (isset($_GET['action'])) {
        $action = $_GET['action'];

        switch ($action) {
            case 'get_list':
            case 'all_product';
                $request->get_all_product($page, $per_page, $add_fields, $filter);
                break;
            case 'have_sale':
                $request->get_product_have_sale($page, $per_page, $add_fields, false, $filter);
                break;
            case 'have_sale_sort':
                $request->get_product_have_sale($page, $per_page, $add_fields, true, $filter);
                break;
            case 'unpublish':
                $request->get_list_product($page, $per_page, $add_fields, 'unpublish', $filter);
                break;
            case 'with_status':
                $request->get_list_product($page, $per_page, $add_fields, 'with_status', $filter);
                break;
            case 'id_only':
                $request->get_list_product($page, $per_page, $add_fields, 'id_only', $filter);
                break;
            case 'test':
                $request->success('ok');
                break;
            case 'count':
                $request->get_total_count();
                break;
            case 'get_ids_by_offset':
                $start_id = 0;
                if (isset($_GET['offset']))
                    $start_id = $_GET['offset'];
                $request->get_ids_by_offset($page, $per_page, $start_id);
                break;
            default:
                break;
        }

        if (method_exists($request, $action)) {
            $request->{$action}();
        } else {
            $request->error($request->create_error('not_found_action', 'Not found action you need'));
        }
    } else {
        $request->get_all_product($page, $per_page, $add_fields, $filter);
    }
} else {
    $request->error($request->create_error('not_match_method', 'Must use GET method'));
}
