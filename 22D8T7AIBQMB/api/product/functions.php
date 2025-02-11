<?php
require_once '../database.php';

$map["_sku"]                   = "sku";
$map['total_sales']            = 'total_sales';
$map['_tax_status']            = 'tax_status';
$map['_tax_class']             = 'tax_class';
$map['_manage_stock']          = 'manage_stock';
$map['_backorders']            = 'backorders';
$map['_sold_individually']     = 'sold_individually';
$map['_virtual']               = 'virtual';
$map['_downloadable']          = 'downloadable';
$map['_download_limit']        = 'download_limit';
$map['_download_expiry']       = 'download_expiry';
$map['_stock']                 = 'stock';
$map['_regular_price']         = 'regular_price';
$map['_low_stock_amount']      = 'low_stock_amount';
$map['_weight']                = 'weight';
$map['_length']                = 'length';
$map['_width']                 = 'width';
$map['_height']                = 'height';
$map['_purchase_note']         = 'purchase_note';
$map['_sale_price']            = 'sale_price';
$map['_sale_price_dates_from'] = 'date_sale_start';
$map['_sale_price_dates_to']   = 'date_sale_end';
$map['_stock_status']          = 'stock_status';
$map['_wc_average_rating']     = 'average_rating';
$map['_wc_rating_count']       = 'rating_count';
$map['_wc_review_count']       = 'review_count';
$map['_variation_description'] = 'description';

$not_map = array('_product_version', '_product_attributes', '_thumbnail_id', '_product_image_gallery', '_edit_lock');

class functions
{
    //filter by json
    function get_product($arr_ids, $add_fields = null, $is_load_variation = false, $filter=null)
    {
        global $table_posts;
        $database = new database();

        if ($add_fields == null)
            $add_fields = '';
        $add_fields = '=,' . $add_fields;
        $add_fields = str_replace(' ', '', $add_fields);

        $arry_fields = explode(',', $add_fields);

        $arr_ids_string = implode(',', $arr_ids);

        if ($arr_ids_string == null || strlen($arr_ids_string) == 0) {
            return array();
            //die('ok');
        }

        $query = "SELECT post.ID, post.post_parent, post.post_title as `name`, post.post_name as slug, post.post_date_gmt as date_created_gmt, post.post_modified_gmt as date_modified_gmt, post.post_status as `status`";

        if (in_array('description', $arry_fields))
            $query .= ', post.post_content as `description`';
        if (in_array('short_description', $arry_fields))
            $query .= ', post.post_excerpt as `short_description`';
        $query .= " FROM $table_posts as post WHERE ID IN ($arr_ids_string);";
        $products = $database->run_sql_return_array($query); //30ms
        // die($query);

        if ($products != null) {
            if ($is_load_variation == false && in_array('order_count', $arry_fields)) {
                $this->load_order_count($database, $products, $arr_ids_string);
            }

            if ($is_load_variation == false && in_array('total_sale', $arry_fields)) {
                $this->load_total_sale($database, $products, $arr_ids_string);
            }

            if (in_array('meta_data', $arry_fields)) {
                $this->load_meta($database, $products, $arr_ids_string);
            }

            if (in_array('attachments', $arry_fields)) {
                $this->load_attachments($database, $products, $arr_ids_string);
            }
           
            if ($is_load_variation == false && in_array('categories', $arry_fields)) {
                $this->load_categories($database, $products, $arr_ids_string);
            }

            if (in_array('tags', $arry_fields)) {
                $this->load_tags($database, $products, $arr_ids_string);
            }
            
            //Chỉ load 1 trong 2 chế độ, ưu tiên chế độ nhẹ hơn
            if ($is_load_variation == false && (in_array('variations', $arry_fields) || in_array('variations_detail', $arry_fields))) {
                if (in_array('variations', $arry_fields))
                    $this->load_variation_ids($database, $products, $arr_ids_string, $filter);
                else if (in_array('variations_detail', $arry_fields))
                    $this->load_variation_detail($database, $products, $arr_ids_string, $add_fields, $filter);
            }

            if ($is_load_variation == false && in_array('attributes', $arry_fields)) {
                $this->load_attributes($database, $products, $arr_ids_string);
            }

            if (in_array('min_variation_price', $arry_fields)) {
                $this->load_variation_min_price($database, $products, $arr_ids_string);
            }

            if (in_array('max_variation_price', $arry_fields)) {
                $this->load_variation_max_price($database, $products, $arr_ids_string);
            }

            return $products;
        } else {
            return array();
        }
    }

    function load_meta(database $database, $products, $arr_product_ids_string)
    {
        global $table_post_meta;
        //Lấy meta
        $query = "SELECT post_id, meta_key, meta_value FROM $table_post_meta WHERE post_id IN ($arr_product_ids_string) AND meta_key NOT LIKE 'fifu%';";
        // die($query);
        $metas = $database->run_sql_return_array($query); //90ms
        if ($metas != null) {

            global $map;
            global $not_map;

            foreach ($products as $p) {
                $product_id = $p->ID;
                $p->min_price = null;
                $p->max_price = null;

                foreach ($metas as $m) {
                    if ($m->post_id == $product_id) {
                        $meta_key = $m->meta_key;
                        $meta_value = $m->meta_value;

                        if (isset($map[$meta_key])) {
                            $p->{$map[$meta_key]} = $meta_value;
                        } else if (!in_array($meta_key, $not_map)) {
                            if ($meta_key == "_price") {
                                if ($p->max_price == null) $p->max_price = $meta_value;
                                if ($p->min_price == null) $p->min_price = $meta_value;
                                if ($meta_value > $p->max_price) {
                                    $p->max_price = $meta_value;
                                }
                                if ($meta_value < $p->min_price) {
                                    $p->min_price = $meta_value;
                                }
                            } else if ($meta_key == "_default_attributes") {
                                $p->default_attributes = $this->process_default_attributes($meta_value);
                            } else if (substr($meta_key, 0, 13) == "attribute_pa_") { //Load attribute cho variation
                                if (!isset($p->attributes))
                                    $p->attributes = array();
                                $attr = new stdClass();
                                $attr->slug_name = str_replace('attribute_pa_', '', $meta_key);
                                $attr->slug_value = $meta_value;
                                $p->attributes[] = $attr;
                            } else if($meta_key == 'cloud_info') { // 02.01.2023 Bổ sung cloud_info để xóa ảnh trên s3, DO
                                try{
                                    $p->cloud_info = @json_decode($meta_value);
                                }
                                catch(Exception $e){}
                            } else {
                                $p->custom_meta[$meta_key] = $meta_value;
                            }
                        }
                    }
                }
            }
        }
    }

    function process_default_attributes($default_attributes)
    {
        $temp = $default_attributes;
        if($temp == "")// || count($temp) < 2) // 06.02.2023 fix loi khi chuyen từ supper import sang woo pod
            return;
        $attribute_count = $temp[2] - '0';
        $res = array();
        $temp = substr($temp, strlen(("a:$attribute_count:{")));
        $temp = str_replace('}', '', $temp);
        $temp = str_replace("\"", '', $temp);
        $temp = str_replace(';', ':', $temp);
        $data = explode(':', $temp);

        for ($i = 1; $i <= $attribute_count * 2; $i += 2) {
            $attr = new stdClass();
            $attr->slug_name  = $data[$i * 3 - 1];
            $attr->slug_value = $data[$i * 3  + 2];
            $res[] = $attr;
        }
        return $res;
    }

    function load_variation_ids(database $database, $products, $arr_product_ids_string, $filter=null)
    {
        global $table_posts;
        $query = "SELECT post.post_parent, post.ID  FROM $table_posts as post WHERE post.post_parent IN ($arr_product_ids_string) AND post.post_type = 'product_variation';";
        if($filter && $filter->first_variation_only)
            $query = "SELECT post.ID  AS post_parent, (SELECT post2.ID FROM $table_posts as post2 WHERE post2.post_parent = post.ID AND post2.post_type = 'product_variation' LIMIT 1) AS ID"
            . " FROM $table_posts AS post"
            . " WHERE post.ID IN ($arr_product_ids_string);";
        $variations = $database->run_sql_return_array($query);

        foreach ($products as $p) {
            $p->variations = array();
            $product_id = $p->ID;
            if ($variations != null) {
                foreach ($variations as $v) {
                    if ($v->post_parent == $product_id) {
                        if($v->ID != null)
                            $p->variations[] = $v->ID;
                    }
                }
            }
        }
    }

    function load_variation_detail(database $database, $products, $arr_product_ids_string, $add_fields, $filter=null)
    {
        
        global $table_posts;
        $query = "SELECT post.ID FROM $table_posts as post WHERE post.post_parent IN ($arr_product_ids_string) AND post.post_type = 'product_variation';";
        if(isset($filter->first_variation_only) && $filter->first_variation_only === true)
            $query = "SELECT post.ID  AS post_parent, (SELECT post2.ID FROM $table_posts as post2 WHERE post2.post_parent = post.ID AND post2.post_type = 'product_variation' LIMIT 1) AS ID"
            . " FROM $table_posts AS post"
            . " WHERE post.ID IN ($arr_product_ids_string);";
        $variations_id = $database->run_sql_return_array($query);
        if ($variations_id != null) {
            $ids = array();
            foreach ($variations_id as $id) {
                if ($id != null && $id->ID != null)
                    $ids[] = $id->ID;
            }
            $variations = $this->get_product($ids, $add_fields, true, $filter);

            foreach ($products as $p) {
                $p->variations = array();
                $product_id = $p->ID;
                if ($variations != null) {
                    foreach ($variations as $v) {
                        if ($v->post_parent == $product_id) {
                            if($v != null)
                                $p->variations[] = $v;
                        }
                    }
                }
            }
        }
    }

    function load_attachments(database $database, $products, $arr_product_ids_string)
    {
        global $table_posts;

        $query = "SELECT post.post_parent, post.ID, post.post_mime_type as mime, post.guid as src  FROM $table_posts as post WHERE post.post_parent IN ($arr_product_ids_string) AND post.post_type = 'attachment';";
        $attachments = $database->run_sql_return_array($query);

        foreach ($products as $p) {
            $p->attachments = array();
            $product_id = $p->ID;
            if ($attachments != null) {
                foreach ($attachments as $at) {
                    if ($at->post_parent == $product_id) {
                        $attacment = array(
                            'id' => $at->ID,
                            'mime' => $at->mime,
                            'src' => $at->src
                        );
                        $p->attachments[] = $attacment;
                    }
                }
            }
        }
    }

    function load_attributes(database $database, $products, $arr_product_ids_string)
    {
        global $table_terms;
        global $table_term_taxonomy;
        global $table_term_relationships;
        global $table_woo_attribute_taxonomie;

        $query = "SELECT tr.object_id, wc_attr.attribute_id, wc_attr.attribute_label AS label, wc_attr.attribute_name AS slug_name, t.`name` AS `value`, t.slug as slug_value
        FROM $table_term_relationships AS tr
        INNER JOIN $table_term_taxonomy AS tx ON tx.term_taxonomy_id = tr.term_taxonomy_id
        INNER JOIN $table_woo_attribute_taxonomie AS wc_attr ON wc_attr.attribute_name = REPLACE(tx.taxonomy, 'pa_', '')
        INNER JOIn $table_terms AS t on t.term_id = tx.term_id
        WHERE tr.object_id IN ($arr_product_ids_string) AND tx.taxonomy like 'pa_%';";
        $attributes = $database->run_sql_return_array($query);

        foreach ($products as $p) {
            $p->attributes = array();
            $product_id = $p->ID;
            if ($attributes != null) {
                foreach ($attributes as $attr) {
                    if ($attr->object_id == $product_id) {
                        $attribute = NULL;
                        if ($p->attributes != NULL) {
                            foreach ($p->attributes as $temp) {
                                if ($temp->name == $attr->label) {
                                    $attribute = $temp;
                                    break;
                                }
                            }
                        }
                        if ($attribute == NULL) {
                            $attribute = new stdClass();
                            $attribute->id = $attr->attribute_id;
                            $attribute->name = $attr->label;
                            $attribute->values = array($attr->value);
                            $p->attributes[] = $attribute;
                        } else {
                            $attribute->values[] = $attr->value;
                        }
                        
                        if (isset($p->default_attributes) && $p->default_attributes != null) {
                            foreach ($p->default_attributes as $da) {
                                if (isset($da->slug_name) && $da->slug_name == 'pa_' . $attr->slug_name) {
                                    $da->id = $attr->attribute_id;
                                    $da->name = $attr->label;
                                    unset($da->slug_name);
                                }
                                if (isset($da->slug_value) && $da->slug_value == $attr->slug_value) {
                                    $da->value = $attr->value;
                                    unset($da->slug_value);
                                }
                            }
                        }

                        if (isset($p->variations) && $p->variations != null) {
                            foreach ($p->variations as $variation) {
                                if (isset($variation->attributes) && $variation->attributes != null && $variation->attributes != array()) {
                                    foreach ($variation->attributes as $var_attr) {
                                        if (isset($var_attr->slug_name) && $var_attr->slug_name == $attr->slug_name) {
                                            $var_attr->id = $attr->attribute_id;
                                            $var_attr->name = $attr->label;
                                            //unset($var_attr->slug_name);
                                        }
                                        if (isset($var_attr->slug_value) && $var_attr->slug_value == $attr->slug_value) {
                                            $var_attr->id = $attr->attribute_id;
                                            $var_attr->value = $attr->value;
                                            //unset($var_attr->slug_value);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    function load_categories(database $database, $products, $arr_product_ids_string)
    {
        global $table_terms;
        global $table_term_taxonomy;
        global $table_term_relationships;

        $query = "SELECT tr.object_id, t.term_id, t.slug, t.name FROM $table_term_relationships AS tr
        INNER JOIN $table_term_taxonomy AS tx ON tx.term_taxonomy_id = tr.term_taxonomy_id
        INNER JOIn $table_terms AS t on t.term_id = tx.term_id
        WHERE tr.object_id IN ($arr_product_ids_string) AND tx.taxonomy = 'product_cat';";
        $categories = $database->run_sql_return_array($query);

        foreach ($products as $p) {
            $p->categories = array();
            $product_id = $p->ID;
            if ($categories != null) {
                foreach ($categories as $cat) {
                    if ($cat->object_id == $product_id) {
                        $category = array(
                            'id' => $cat->term_id,
                            'name' => $cat->name,
                            'slug' => $cat->slug
                        );
                        $p->categories[] = $category;
                    }
                }
            }
        }
    }

    function load_tags(database $database, $products, $arr_product_ids_string)
    {
        global $table_terms;
        global $table_term_taxonomy;
        global $table_term_relationships;

        $query = "SELECT tr.object_id, t.term_id, t.slug, t.name FROM $table_term_relationships AS tr
        INNER JOIN $table_term_taxonomy AS tx ON tx.term_taxonomy_id = tr.term_taxonomy_id
        INNER JOIn $table_terms AS t on t.term_id = tx.term_id
        WHERE tr.object_id IN ($arr_product_ids_string) AND tx.taxonomy = 'product_tag';";
        $tags = $database->run_sql_return_array($query);

        foreach ($products as $p) {
            $p->tags = array();
            $product_id = $p->ID;
            if ($tags != null) {
                foreach ($tags as $tag) {
                    if ($tag->object_id == $product_id) {
                        $temp = array(
                            'id' => $tag->term_id,
                            'name' => $tag->name,
                            'slug' => $tag->slug
                        );
                        $p->tags[] = $temp;
                    }
                }
            }
        }
    }

    function load_order_count(database $database, $products, $arr_product_ids_string)
    {
        //CHÚ Ý: Nếu order ở dạng trash sẽ vấn bị đếm phải
        //CHƯA XỬ LÝ ĐƯỢC TRƯỜNG HỢP NẾU 10k order có thể sẽ time out
        //global $table_woo_woocommerce_order_itemmeta;
        //global $table_woo_woocommerce_order_items;
        /*Query trí tuệ quá k nỡ xóa
        // $query = "SELECT product_id, COUNT(product_id) AS order_count FROM
        // (SELECT OI.order_id, OIM.meta_value AS product_id FROM $table_woo_woocommerce_order_itemmeta AS OIM
        // INNER JOIN $table_woo_woocommerce_order_items AS OI ON OIM.order_item_id = OI.order_item_id
        // WHERE (OIM.meta_key = '_product_id' OR OIM.meta_key = '_variation_id') AND OIM.meta_value IN($arr_product_ids_string)
        // GROUP BY meta_value, OI.order_id)
        // AS table_1
        // GROUP BY product_id;";
        */
        global $table_woo_order_product_lookup;
        $query = "SELECT product_id, COUNT(distinct order_id) AS order_count FROM $table_woo_order_product_lookup WHERE product_id IN($arr_product_ids_string) GROUP BY product_id;";
        $product_oder_counts = $database->run_sql_return_array($query);
        foreach ($products as $p) {
            $product_id = $p->ID;
            $p->order_count = 0;
            if ($product_oder_counts != null) {
                $index = 0;
                foreach ($product_oder_counts as $count) {
                    if ($count->product_id == $product_id) {
                        $p->order_count += $count->order_count;
                        \array_splice($product_oder_counts, $index, 1);
                        break;
                    }
                    $index++;
                }
            }
        }
    }

    function load_total_sale(database $database, $products, $arr_product_ids_string)
    {
        //CHÚ Ý: Nếu order ở dạng trash sẽ vấn bị đếm phải
        //CHƯA XỬ LÝ ĐƯỢC TRƯỜNG HỢP NẾU 10k order có thể sẽ time out
        //global $table_woo_woocommerce_order_itemmeta;

        /*Query trí tuệ quá k nỡ xóa

        // $query = "SELECT product_id,
        // (select sum(cast(meta_value as SIGNED)) FROM $table_woo_woocommerce_order_itemmeta WHERE meta_key='_qty' and FIND_IN_SET(order_item_id ,order_item_ids)) as total_sale
        // FROM
        // (SELECT meta_value as product_id, GROUP_CONCAT(order_item_id) as order_item_ids
        // FROM $table_woo_woocommerce_order_itemmeta
        // WHERE meta_key IN ('_product_id', '_variation_id') AND meta_value IN($arr_product_ids_string)
        // GROUP BY meta_value)
        // as table_1;";
        */
        global $table_woo_order_product_lookup;
        $query = "SELECT product_id, sum(product_qty) AS total_sale FROM $table_woo_order_product_lookup WHERE product_id IN($arr_product_ids_string) GROUP BY product_id;";
        $product_oder_counts = $database->run_sql_return_array($query);
        foreach ($products as $p) {
            $product_id = $p->ID;
            $p->total_sale = 0;
            if ($product_oder_counts != null) {
                $index = 0;
                foreach ($product_oder_counts as $count) {
                    if ($count->product_id == $product_id) {
                        $p->total_sale += $count->total_sale;
                        \array_splice($product_oder_counts, $index, 1);
                        break;
                    }
                    $index++;
                }
            }
        }
    }

    function load_variation_min_price(database $database, $products, $arr_product_ids_string)
    {
        global $table_post_meta;
        global $table_posts;
        $query = "SELECT post_id AS parent_id,"
        . " (SELECT MIN(meta_value) FROM $table_post_meta WHERE post_id IN (SELECT ID FROM $table_posts WHERE post_parent = parent_id) AND meta_key = '_price') AS min_price"
        . " FROM $table_post_meta"
        . " WHERE post_id IN ($arr_product_ids_string) AND meta_key='_stock_status';"; // AND nay co ve nhanh hon group by
        // die($query);
        $min_prices = $database->run_sql_return_array($query);
        foreach ($products as $p) {
            $product_id = $p->ID;
            if(!isset($p->min_price))
                $p->min_price = null;
            if ($min_prices != null) {
                $index = 0;
                foreach ($min_prices as $min_price) {
                    if ($min_price->parent_id == $product_id) {
                        if($min_price->min_price != null)
                            $p->min_price = $min_price->min_price;
                        \array_splice($min_prices, $index, 1);
                        break;
                    }
                    $index++;
                }
            }
        }
    }

    function load_variation_max_price(database $database, $products, $arr_product_ids_string)
    {
        global $table_post_meta;
        global $table_posts;
        $query = "SELECT post_id AS parent_id,"
        . " (SELECT MAX(meta_value) FROM $table_post_meta WHERE post_id IN (SELECT ID FROM $table_posts WHERE post_parent = parent_id) AND meta_key = '_price') AS min_price"
        . " FROM $table_post_meta"
        . " WHERE post_id IN ($arr_product_ids_string) AND meta_key='_stock_status';";
        // die($query);
        $max_prices = $database->run_sql_return_array($query);
        foreach ($products as $p) {
            $product_id = $p->ID;
            if(!isset($p->min_price))
                $p->max_price = null;
            if ($max_prices != null) {
                $index = 0;
                foreach ($max_prices as $max_price) {
                    if ($max_price->parent_id == $product_id) {
                        if($max_price->min_price != null)
                            $p->max_price = $max_price->min_price;
                        \array_splice($max_prices, $index, 1);
                        break;
                    }
                    $index++;
                }
            }
        }
    }
}

$product_function = new functions();
