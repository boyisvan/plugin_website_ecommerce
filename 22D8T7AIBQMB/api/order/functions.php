<?php

require_once '../database.php';

$map["_order_currency"] = "currency";
$map["_cart_discount"] = "discount_total";
$map["_cart_discount_tax"] = "discount_tax";
$map["_order_shipping"] = "shipping_total";
$map["_order_shipping_tax"] = "shipping_tax";
$map["_order_tax"] = "cart_tax";
$map["_order_total"] = "total";
$map["_prices_include_tax"] = "prices_include_tax";
$map["_customer_user"] = "customer_id";
$map["_customer_ip_address"] = "customer_ip_address";
$map["_payment_method"] = "payment_method";
$map["_payment_method_title"] = "payment_method_title";
$map["_date_paid_gmt"] = "date_paid_gmt";
$map["_date_completed_gmt"] = "date_completed_gmt";
$map["_customer_user_agent"] = "customer_user_agent";
$map["_created_via"] = "created_via";

$map["_cart_hash"] = "cart_hash";
$map["_completed_date"] = "date_completed_gmt";
$map["_paid_date"] = "date_paid_gmt";
$map["_created_via"] = "created_via";
$map['_paypal_transaction_fee'] = 'paypal_fee';
$map['_stripe_fee'] = 'stripe_fee';
$map['_refund_amount'] = 'refund_amount';

$not_map = array('_order_version', '_billing_address_index', '_shipping_address_index', '_download_permissions_granted', '_recorded_sales', '_order_stock_reduced'
, '_order_key', '_recorded_coupon_usage_counts', '_edit_lock', '_edit_last', '_date_completed', '_date_paid');


$map_item_meta['_product_id'] = 'product_id';
$map_item_meta['_variation_id'] = 'variation_id';
$map_item_meta['_qty'] = 'quantity';
$map_item_meta['_tax_class'] = 'tax_class';
$map_item_meta['_line_subtotal'] = 'subtotal';
$map_item_meta['_line_subtotal_tax'] = 'subtotal_tax';
$map_item_meta['_line_total'] = 'total';
$map_item_meta['_line_tax'] = 'total_tax';


$not_map_item_meta = array('_order_version');

class order_functions{
    function get_oder($arr_ids, $sort_by_date, $add_fields = null){
        global $table_posts;
        $database = new database();

        if($add_fields==null)
            $add_fields = '';
        $add_fields = '=,' . $add_fields;
        $add_fields = str_replace(' ', '', $add_fields);
        $arry_fields = explode(',', $add_fields);
        $arr_ids_string = implode(',', $arr_ids);
        // die($arr_ids_string);

        if($arr_ids_string == null || strlen($arr_ids_string) == 0){
            return array();
        }

        $query = "SELECT post.ID, post.post_date_gmt AS date_created_gmt, post.post_modified_gmt AS date_modified_gmt, post.post_parent as parent_id, REPLACE(post.post_status, 'wc-', '') AS `status`
        FROM $table_posts AS post
        WHERE ID IN ($arr_ids_string)";

        if($sort_by_date == true)
            $query .= " ORDER BY post.post_date DESC"; // Sắp xếp theo ngày tháng chậm đi 3 lần

        $query .= ';';
        // die($query);

        $orders = $database->run_sql_return_array($query);
        if($orders != null){
            if(in_array('meta_data', $arry_fields)){
                $this->load_meta($database, $orders, $arr_ids_string);
                $this->load_refund_item($database, $orders, $arr_ids_string); // Phai load meta truoc
            }

            if(in_array('products', $arry_fields)){
                $load_category = false;
                if(in_array('category_product', $arry_fields))
                    $load_category = true; // sẽ chậm hơn khoàng 10-20%

                $this->load_product($database, $orders, $arr_ids_string, $load_category);
            }

            if(in_array('count_item', $arry_fields)){
                $this->load_count_item($database, $orders, $arr_ids_string);
            }
        }
        else{
            $orders = array();
        }
        return $orders;
    }

    function load_meta(database $database, $orders, $arr_ids_string){
        global $table_post_meta;
        //Lấy meta
        $query = "SELECT post_id, meta_key, meta_value FROM $table_post_meta WHERE post_id IN ($arr_ids_string);";
        $metas = $database->run_sql_return_array($query);
        if($metas != null){

            global $map;
            global $not_map;

            foreach($orders as $o){
                $product_id = $o->ID;
                $o->date_paid_gmt = null;
                $o->date_completed_gmt = null;
                $billing = new stdClass();
                $shipping = new stdClass();
                $billing->address_2 = "";
                $shipping->address_2 = "";

                foreach($metas as $m){
                    if($m->post_id == $product_id){
                        $meta_key = $m->meta_key;
                        $meta_value = $m->meta_value;

                        if(isset($map[$meta_key])){
                            $o->{$map[$meta_key]} = $meta_value;
                        }else if(!in_array($meta_key, $not_map)){
                            if(substr($meta_key, 0, 9) == '_billing_'){
                                $pros_bill_name = str_replace('_billing_', '', $meta_key);
                                $billing->{$pros_bill_name} = $meta_value;
                                //die($meta_key);
                            }else if(substr($meta_key, 0, 10) == '_shipping_'){
                                $pros_ship_name = str_replace('_shipping_', '', $meta_key);
                                $shipping->{$pros_ship_name} = $meta_value;
                            }else{
                                $o->custom_meta[$meta_key] = $meta_value;
                            }
                        }
                    }
                }
                $o->billing = $billing;
                $o->shipping = $shipping;
            }
        }
    }

    function load_product(database $database, $orders, $arr_ids_string, $load_category){
        global $table_woo_woocommerce_order_items;
        global $table_woo_woocommerce_order_itemmeta;
        global $map_item_meta;
        global $not_map_item_meta;
        global $table_term_taxonomy;
        global $table_terms;
        global $table_term_relationships;

        // Lấy danh sách tên và id product
        $query = "SELECT order_item_id, order_item_name, order_id FROM $table_woo_woocommerce_order_items WHERE order_id IN ($arr_ids_string) AND order_item_type = 'line_item';";
        $products = $database->run_sql_return_array($query);

        if($products != null){
            foreach($orders as $o){
                $o->products = array();

                foreach($products as $p){
                    if($p->order_id == $o->ID){
                        // Tim lai product dang duoc gan du lieu
                        $product = null;
                        if($o->products != array()){
                            foreach($o->products as $temp){
                                if($temp->id == $p->order_item_id){
                                    $product = $temp;
                                    break;
                                }
                            }
                        }   
                        // Neu khong tim thay thi khoi tao moi
                        if($product == null){
                            $product = new stdClass();
                            $o->products[] = $product;
                        }

                        $product->id = $p->order_item_id;
                        $product->name = $p->order_item_name;
                    }
                }

                //Load meta của product (lấy từ order meta)
                $arr_order_item_ids = array_column($products, 'order_item_id');
                $arr_order_item_ids_string = implode(',', $arr_order_item_ids); // danh sách order id
                $query = "SELECT order_item_id, meta_key, meta_value FROM $table_woo_woocommerce_order_itemmeta WHERE order_item_id IN ($arr_order_item_ids_string);";
                $metas = $database->run_sql_return_array($query);
                if($metas != null){
                    foreach($o->products as $product){
                        foreach($metas as $m){
                            if($m->order_item_id == $product->id){
                                $meta_key = $m->meta_key;
                                $meta_value = $m->meta_value;
                                if(isset($map_item_meta[$meta_key])){
                                    $product->{$map_item_meta[$meta_key]} = $meta_value; // load product_id ở đây
                                }else if(substr($meta_key, 0, 3) == 'pa_'){
                                    $attribute = new stdClass();
                                    $attribute->slug_name = str_replace('pa_', '', $meta_key);
                                    $attribute->slug_value = $meta_value;
                                    $product->attribute = $attribute;
                                }else if(!in_array($meta_key, $not_map_item_meta)){
                                    $product->meta_data[$meta_key] = $meta_value;
                                }
                            }
                        }
                    }
                }

                // Load category của product
                if($load_category){
                    $arr_product_ids = array_column($o->products, 'product_id');
                    $arr_product_ids_string = implode(',', $arr_product_ids); // danh sách order id

                    $query = "SELECT tr.object_id, t.term_id, t.slug, t.name FROM $table_term_relationships AS tr
                    INNER JOIN $table_term_taxonomy AS tx ON tx.term_taxonomy_id = tr.term_taxonomy_id
                    INNER JOIn $table_terms AS t on t.term_id = tx.term_id
                    WHERE tr.object_id IN ($arr_product_ids_string) AND tx.taxonomy = 'product_cat';";
                    // die($query);
                    $categories = $database->run_sql_return_array($query);
                    foreach ($o->products as $p) {
                        $p->categories = array();
                        $product_id = $p->product_id;
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
            }
        }
    }

    function load_count_item(database $database, $orders, $arr_ids_string){
        global $table_woo_woocommerce_order_items;

        $query = "SELECT order_id, COUNT(order_id) AS count_item FROM $table_woo_woocommerce_order_items WHERE order_id IN ($arr_ids_string) AND order_item_type = 'line_item' GROUP BY order_id;";
        $products = $database->run_sql_return_array($query);

        if($products != null){
            foreach($orders as $o){
                if(!isset($o->count_item)) $o->count_item = 0;
                
                foreach($products as $p){
                    if($p->order_id == $o->ID){
                        $o->count_item += $p->count_item;
                    }
                }
            }
        }
    }

    function load_refund_item(database $database, $orders, $arr_ids_string){
        global $table_posts;
        global $table_post_meta;

        $query = "SELECT post.post_parent AS parent_product_id, meta.post_id, meta.meta_key, meta.meta_value FROM $table_post_meta as meta
        INNER JOIN $table_posts AS post ON post.ID = meta.post_id
        WHERE meta.post_id IN (SELECT ID FROM $table_posts WHERE post_parent IN ($arr_ids_string) AND post_type = 'shop_order_refund');";
        // die($query);
        $metas = $database->run_sql_return_array($query);
        if($metas != null){

            global $map;
            global $not_map;

            foreach($orders as $o){
                $order_id = $o->ID;
                $o->refunds = array();

                foreach($metas as $m){
                    if($m->parent_product_id == $order_id){
                        //Tim lai object refund
                        $refund = null;
                        foreach($o->refunds as $temp){
                            if($temp->post_id == $m->post_id){
                                $refund = $temp;
                                break;
                            }
                        }
                        // Neu khong tim thay thi khoi tao moi
                        if($refund == null){
                            $refund = new stdClass();
                            $refund->post_id = $m->post_id;
                            $o->refunds[] = $refund;
                        }
                        
                        $meta_key = $m->meta_key;
                        $meta_value = $m->meta_value;

                        if(isset($map[$meta_key])){
                            $refund->{$map[$meta_key]} = $meta_value;
                            // echo($meta_key);
                        }
                    }
                }
            }
        }
    }
}

$order_function = new order_functions();