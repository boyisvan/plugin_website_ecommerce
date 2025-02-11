<?php
include "../share_function.php";
include "../share_function_database.php";
require_once "create_database.php";
require_once "../database.php";
// require_once "../../database.php";

$database = new database();
class update_database extends share_function_dabase{
    function get_term_id_of_product($product_id, $taxonomy) { // 'product_cat', 'product_tag'
        global $database;
        global $table_term_relationships;
        global $table_term_taxonomy;
        $ids = $database->run_sql_return_array("SELECT TX.term_id FROM $table_term_relationships as TR "
                                      . "INNER JOIN $table_term_taxonomy as TX ON TR.term_taxonomy_id = TX.term_taxonomy_id "
                                      . "WHERE TR.object_id = $product_id AND TX.taxonomy='$taxonomy';");
        
        $res = [];
        if($ids != null && count($ids) > 0){
            $res = array_map(function($temp){
                return $temp->term_id;
            }, $ids);
        }
        // $termIdsString = implode(', ', $res);
        return $res;
    }

    function unique_string($inputString, $separate=';') {
        $array = explode($separate, $inputString);
        $uniqueArray = array_unique($array);
        return implode($separate, $uniqueArray);
    }

    function update_product($db, $id_product, $item){
        $product_title = isset($item['name']) ? $db->real_escape_string($item['name'] ?? 'null') : 'null';
        $price = $item['price'] ?? 'null';
        $sale_price = $item['sale_price'] ?? 'null';
        $stock = $item['stock'] ?? 'null';
        $inStock = $item['instock'] ?? true;
        if(isset($item['stock']))
            $inStock = $stock > 0;
        $stock_status = $inStock ? "instock" : "outofstock";

        //19/9/2020
        global $db_create;
        $tag = "";
        if(isset($item['mode_edit_tag']) && $item['mode_edit_tag'] == 'add') {
            $tag = implode(';', $this->get_term_id_of_product($id_product, 'product_tag')) . ';';
        }
        if(isset($item['tags'])){
            foreach ($item['tags'] as $tag_name){
                if($tag_name != '') {
                    $temp_id_tag = $db_create->create_tag($db, $tag_name);
                    if(strpos($tag, $temp_id_tag) == false)
                        $tag .= $temp_id_tag . ";";
                }
            }
        }else{
            $tag = 'null';
        }
        if($tag != 'null')
            $tag = $this->unique_string($tag, ';');

        $merge_name_sub_category = $item['merge_name_sub_category'] ?? true; // 09.4.2022 thêm cái này để tối ưu SEO (không join category trong sub category)
        $idCategory = "";
        if(isset($item['mode_edit_catgory']) && $item['mode_edit_catgory'] == 'add') {
            $idCategory = implode(';', $this->get_term_id_of_product($id_product, 'product_cat')) . ';';
        }
        if(isset($item['categories'])){
            foreach($item['categories'] as $cat)
                if($cat != '') {
                    $temp_id_category = $db_create->create_category($db, $cat, $merge_name_sub_category);
                    if(strpos($idCategory, $temp_id_category) == false)
                        $idCategory .= $temp_id_category . ";";
                }
        }else{
            $idCategory = 'null';
        }

        if($idCategory != 'null')
            $idCategory = $this->unique_string($idCategory, ';');

        //08/5/2020 Bổ sung update cả title, description để fix key word google report
        $product_description = isset($item['description']) ? $db->real_escape_string($item['description'] ?? 'null') : 'null';
        ////

        $sql = "select update_product($id_product, '$product_title', $price, '$stock_status', '$product_description', '$idCategory', '$tag', $sale_price, $stock) as id_product;";
        // die($sql);
        if($result = $db->query($sql)) {
            $id_product = $result->fetch_object()->id_product;
            $result->free_result();
            return $id_product;
        }else{
            $this->error($db, 'update_product', $sql);
        }
    }

    function update_variant($db, $id_variant, $item){
        $price = $item['price'];
        $inStock = $item['instock'];
        $stock_status = $inStock ? "instock" : "outofstock";
        
        $optionValues = $db->real_escape_string(implode(';', $item['values']));
        //24/5/2020
        $description = $db->real_escape_string($item['description']);

        $sql = "select update_variant($id_variant, $price, '$stock_status', '$optionValues', '$description') as id_variant;";

        if($result = $db->query($sql)) {
            $id_variant = $result->fetch_object()->id_variant;
            $result->free_result();
            return $id_variant;
        } else {
            $this->error($db, 'update_variant', $sql);
        }
    }

    function update_status($db, $id_product, $status){
        global $table_posts;
        $sql = "UPDATE $table_posts SET  post_status='$status' WHERE ID=$id_product";

        if(@$db->query($sql)) {
            // $result->free_result();
            return $id_product;
        }else{
            $this->error($db, 'update_status', $sql);
        }
    }

    function get_offset_from_timezone($timezone_string){
        $timezone_gmt_0 = new DateTimeZone("Africa/Abidjan");//GPM+0
        $timezone_need_check = new DateTimeZone($timezone_string);

        $chicago = new DateTime("now", $timezone_gmt_0);
        // $amsterdam = new DateTime("now", $timezone_need_check);

        $Offset = $timezone_need_check->getOffset($chicago);

        return $Offset/3600;
    }

    function schedule_product($db, $id_product, $date_publish_utc){
        global $table_posts;
        global $table_options;

        // $sql = "SELECT option_value as gmt_offset FROM $table_options WHERE option_name = 'gmt_offset' LIMIT 1";
        $sql = "SELECT option_value as gmt_offset_or_timezone_string FROM $table_options WHERE (option_name = 'gmt_offset' OR option_name = 'timezone_string') AND option_value IS NOT NULL AND option_value <> '' LIMIT 1";
        $gmt_offset = 0;
        if($result = @$db->query($sql)) {
            $temp = $result->fetch_object()->gmt_offset_or_timezone_string ?? 0;
            // die($temp);
            if(is_numeric($temp)){
                $gmt_offset =$temp;
                // die($gmt_offset);
            }
            else{
                $gmt_offset = $this->get_offset_from_timezone($temp);
                // die($gmt_offset);
            }

            $result->free_result();
        }else{
            $this->error($db, 'schedule_product_get_offset', $sql);
        }
        // die($gmt_offset);

        $date_publish = date_format(date_add(date_create($date_publish_utc), date_interval_create_from_date_string("$gmt_offset hours")), 'Y-m-d H:i:s');

        $sql = "UPDATE $table_posts SET post_status='future'";
        if($date_publish_utc != null)
            $sql .= ", post_date='$date_publish', post_date_gmt='$date_publish_utc', post_status='future'";
        $sql .= " WHERE ID=$id_product";

        if(@$db->query($sql)) {
            // $result->free_result();
            return $id_product;
        }else{
            $this->error($db, 'schedule_product', $sql);
        }
    }

    function update_price_bulk($db, $id_products_arr,
        $percent_regular_change, $value_regular_change,
        $percent_sale_change, $value_sale_change,
        $update_variant
    ) {
        global $table_post_meta;
        global $table_posts;

        $id_products_string = implode(",", $id_products_arr);

        // Update giá thường trước
        if($percent_regular_change != 0 || $value_regular_change != 0) {
            $query  = "UPDATE $table_post_meta"
                    . " SET meta_value = GREATEST(ROUND(meta_value * ($percent_regular_change + 100) / 100 + $value_regular_change, 2), 0)" // phần trăm thực hiện trước xong mới cộng trừ giá trị cố định
                    . " WHERE (meta_key='_regular_price' OR meta_key='_price') AND (post_id IN ($id_products_string)"
                    . "";

            if($update_variant) {
                $query .= " OR post_id IN (SELECT ID FROM $table_posts WHERE post_parent IN ($id_products_string) AND post_type='product_variation')";
            }
            $query .= ")";
            if(@$db->query($query)) {
                return $id_products_arr;
            } else {
                $this->error($db, 'update_price_bulk', $query);
                return $id_products_arr;
            }
        }

        if($percent_sale_change != 0 || $value_sale_change != 0) {
            $query  = "UPDATE $table_post_meta"
                    . " SET meta_value = GREATEST(ROUND(meta_value * ($percent_sale_change + 100)/ 100 + $value_sale_change, 2), 0)" // phần trăm thực hiện trước xong mới cộng trừ giá trị cố định
                    . " WHERE meta_key='_sale_price' AND (post_id IN ($id_products_string)"
                    . "";

            if($update_variant) {
                $query .= " OR post_id IN (SELECT ID FROM $table_posts WHERE post_parent IN ($id_products_string) AND post_type='product_variation')";
            }
            $query .= ")";
            if(@$db->query($query)) {
                return $id_products_arr;
            } else {
                $this->error($db, 'update_price_bulk', $query);
                return $id_products_arr;
            }
        }
    }
}

$db_update = new update_database();