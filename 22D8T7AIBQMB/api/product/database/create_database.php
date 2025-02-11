<?php
require_once "../share_function.php";
require_once "../share_function_database.php";
require_once "../database.php";
$separate_meta = '<->';
class create_database extends share_function_dabase
{

    // function check_sku_imported_in_five_minute($db, $sku)
    // {
    //     $sql = "select check_sku_imported_in_five_minute('$sku') as id_product;";
    //     if ($result = $db->query($sql)) {
    //         $id_product = $result->fetch_object()->id_product;
    //         $result->free_result();
    //         return $id_product;
    //     } else {
    //         $this->error($db, 'check_sku_imported_in_five_minute', $sql);
    //     }
    // }

    function default_map()
    {
        $map_meta_key_with_json_property = array();
        $map_meta_key_with_json_property[] = array('json_property' => 'sku',               'meta_key' => '_sku');
        $map_meta_key_with_json_property[] = array('json_property' => 'total_sales',       'meta_key' => 'total_sales');
        $map_meta_key_with_json_property[] = array('json_property' => 'tax_status',        'meta_key' => '_tax_status');
        $map_meta_key_with_json_property[] = array('json_property' => 'tax_class',         'meta_key' => '_tax_class');
        $map_meta_key_with_json_property[] = array('json_property' => 'manage_stock',      'meta_key' => '_manage_stock');
        $map_meta_key_with_json_property[] = array('json_property' => 'backorders',        'meta_key' => '_backorders');
        $map_meta_key_with_json_property[] = array('json_property' => 'sold_individually', 'meta_key' => '_sold_individually');
        $map_meta_key_with_json_property[] = array('json_property' => 'virtual',           'meta_key' => '_virtual');
        $map_meta_key_with_json_property[] = array('json_property' => 'downloadable',      'meta_key' => '_downloadable');
        $map_meta_key_with_json_property[] = array('json_property' => 'download_limit',    'meta_key' => '_download_limit');
        $map_meta_key_with_json_property[] = array('json_property' => 'download_expiry',   'meta_key' => '_download_expiry');
        $map_meta_key_with_json_property[] = array('json_property' => 'stock',             'meta_key' => '_stock');
        $map_meta_key_with_json_property[] = array('json_property' => 'regular_price',     'meta_key' => '_regular_price');
        $map_meta_key_with_json_property[] = array('json_property' => 'price',             'meta_key' => '_price');
        $map_meta_key_with_json_property[] = array('json_property' => 'low_stock_amount',  'meta_key' => '_low_stock_amount');
        $map_meta_key_with_json_property[] = array('json_property' => 'weight',            'meta_key' => '_weight');
        $map_meta_key_with_json_property[] = array('json_property' => 'length',            'meta_key' => '_length');
        $map_meta_key_with_json_property[] = array('json_property' => 'width',             'meta_key' => '_width');
        $map_meta_key_with_json_property[] = array('json_property' => 'height',            'meta_key' => '_height');
        $map_meta_key_with_json_property[] = array('json_property' => 'purchase_note',     'meta_key' => '_purchase_note');
        $map_meta_key_with_json_property[] = array('json_property' => 'sale_price',        'meta_key' => '_sale_price');
        $map_meta_key_with_json_property[] = array('json_property' => 'date_sale_start',   'meta_key' => '_sale_price_dates_from');
        $map_meta_key_with_json_property[] = array('json_property' => 'date_sale_end',     'meta_key' => '_sale_price_dates_to');
        return $map_meta_key_with_json_property;
    }

    function create_custom_postmeta($db, $id_product, $custom_meta)
    {
        foreach ($custom_meta as $key => $value) {
            $goodKey = $db->real_escape_string($key);
            $goodValue = $db->real_escape_string($value);
            $sql = "select create_postmeta($id_product, '$goodKey', '$goodValue') as idPostMeta";
            if ($result = $db->query($sql)) {
                $result->free_result();
            } else {
                $this->error($db, 'create_custom_postmeta', $sql);
            }
        }
    }

    function build_meta_product($db, $item, $is_variation = false)
    {
        global $shareFunction;
        global $separate_meta;

        $inStock = isset($item['instock']) ? $item['instock'] : false;
        $stockStatus = $inStock ? "instock" : "outofstock";

        $lst_meta_key = array();
        $lst_meta_value = array();

        $map_meta_key_with_json_property = $this->default_map();

        foreach ($map_meta_key_with_json_property as $map) {
            $json_property = $map['json_property'];
            $meta_key      = $map['meta_key'];
            if (isset($item[$json_property])) {
                $temp = $item[$json_property];
                if ($temp !== null && $temp !== 'null') {
                    $lst_meta_key[]   = $db->real_escape_string(str_replace($separate_meta, ' ', $meta_key));
                    $lst_meta_value[] = $db->real_escape_string(str_replace($separate_meta, ' ', $item[$json_property]));
                }
            }
        }

        //Bắt buộc
        $lst_meta_key[] = '_stock_status';
        $lst_meta_value[] = $stockStatus;
        //Hết bắt buộc

        /* Không bắt buộc
        $lst_meta_key[] = '_wc_average_rating';
        $lst_meta_value[] = 0;

        $lst_meta_key[] = '_wc_review_count';
        $lst_meta_value[] = 0;

        $lst_meta_key[] = '_wc_rating_count';
        $lst_meta_value[] = '';
        */

        if (isset($item['attributes']) && $is_variation == false) {
            $lst_meta_key[] = '_product_attributes';
            $lst_meta_value[] = $shareFunction->create_json_from_attribute_array($db, $item['attributes']);
        }

        if (isset($item['default_attributes']) && $is_variation == false) {
            $lst_meta_key[] = '_default_attributes';
            $lst_meta_value[] = $shareFunction->create_json_default_attribute($db, $item['default_attributes']);
        }

        if (isset($item['downloadable_files'])) {
            $lst_meta_key[] = '_downloadable_files';
            $lst_meta_value[] = $shareFunction->create_json_downloadable_file($db, $item['downloadable_files']);
        }

        $_gpf_data_gtin = '';
        $_gpf_data_google_product_category = '';
        if (isset($item['custom_meta'])) {
            foreach ($item['custom_meta'] as $key => $value) {
                if($key == '_gpf_data_gtin'){
                    $_gpf_data_gtin = $value;
                }else if($key == '_gpf_data_product_category'){
                    $_gpf_data_google_product_category = $value;
                } else if($key == '_wpca_form_id'){         // 23.12.2021 SP21_fbDucDungLe :0;i:862;}
                    $array_wpca_ids = explode(';', $value); // a:2:{i:0;i:1431981;i:1;i:792152;}
                    $length_wpca_ids = count($array_wpca_ids);
                    $meta_value_wpca_ids = "a:$length_wpca_ids:{";
                    
                    for($i=0; $i < $length_wpca_ids; $i++) {
                        $temp = trim($array_wpca_ids[$i]);
                        $meta_value_wpca_ids .= "i:$i;i:$temp;";
                    }
                    $meta_value_wpca_ids .= "}";
                    $lst_meta_key[] = '_wcpa_product_meta';
                    $lst_meta_value[] = $meta_value_wpca_ids;
                } else {
                    if(strpos($key, '_pa_') != false && $value == null) // attribute_pa_size
                        continue;
                    $lst_meta_key[] = $db->real_escape_string(str_replace($separate_meta, ' ', $key));
                    if($value != null)
                        $lst_meta_value[] = $db->real_escape_string(str_replace($separate_meta, ' ', $value));
                    else
                        $lst_meta_value[] = '';
                }
            }
        }
        // Custom riêng thêm cho một số bác
        if($_gpf_data_gtin != '' || $_gpf_data_google_product_category != ''){
            $_gpf_data_gtin_len = strlen($_gpf_data_gtin);
            $_gpf_data_google_product_category_len = strlen($_gpf_data_google_product_category);
            $lst_meta_key[] = '_woocommerce_gpf_data';
            $lst_meta_value[] = "a:3:{s:4:\"gtin\";s:$_gpf_data_gtin_len:\"$_gpf_data_gtin\";s:23:\"google_product_category\";s:$_gpf_data_google_product_category_len:\"$_gpf_data_google_product_category\";s:15:\"exclude_product\";s:0:\"\";}";
        }

        //02.01.2023 Thêm cloud_info để sau này biết xóa ảnh trên cloud
        $key_cloud_info = 'cloud_info';
        if(isset($item[$key_cloud_info])){
            $lst_meta_key[] = $key_cloud_info;
            $lst_meta_value[] = json_encode($item[$key_cloud_info]);
        }

        return array(
            'meta_key' => implode($separate_meta, $lst_meta_key),
            'meta_value' => implode($separate_meta, $lst_meta_value)
        );
    }

    function create_product_to_database($db, $item)
    {
        // die('huhu');
        $slug = $db->real_escape_string($item['slug']);
        $title = @$db->real_escape_string($item['name']);
        // echo @$db->real_escape_string(@mb_convert_encoding(($item['description']), "UTF-8"));
        $description = @$db->real_escape_string($item['description']);
        $short_description = @$db->real_escape_string($item['short_description']);
        $reviews_allowed = $item['reviews_allowed'] ?? true;
        $string_review_allowed = $reviews_allowed ? "open" : "close";

        // 05.3.2022
        $product_status = 'publish';
        if(isset($item['status'])) $product_status = $item['status'];
        $listImage = $db->real_escape_string(implode(";", $item['images']));
        //$custom_meta = $item['custom_meta'];
        $image_meta = $this->create_image_meta($item['images'], $db);
        $tag = "";
        if (isset($item['tags'])) {
            $arrTagIds = [];
            foreach ($item['tags'] as $tag_name) {
                if ($tag_name != '')
                    $arrTagIds[] = $this->create_tag($db, $tag_name);
            }
            $arrTagIds = array_unique($arrTagIds);
            $tag = implode(";", $arrTagIds);
        }
        $merge_name_sub_category = $item['merge_name_sub_category'] ?? true; // 09.4.2022 thêm cái này để tối ưu SEO (không join category trong sub category)
        $idCategory = '';
        if (isset($item['categories'])) {
            $arrayCategoryIds = [];
            foreach ($item['categories'] as $cat)
                if ($cat != '')
                    $arrayCategoryIds[] =  $this->create_category($db, $cat, $merge_name_sub_category);
            $arrayCategoryIds = array_unique($arrayCategoryIds);
            $idCategory = implode(";", $arrayCategoryIds);
        }

        // 26.3.2022
        $id_shipping_class = '';
        if(isset($item['shipping_class']))
            $id_shipping_class = $this->create_shipping_class($db, $item['shipping_class']);

        $type = $item['type'];

        $product_meta = $this->build_meta_product($db, $item);

        $meta_key   = $product_meta['meta_key'];
        $meta_value = $db->real_escape_string($product_meta['meta_value']);
        // die($meta_value);
        $sql = "select create_product('$title', '$slug', '$description', '$short_description'"
            . ", '$idCategory', '$type', '$listImage', '$image_meta', '$tag'"
            . ", 0"
            . ", '$meta_key', '$meta_value'"
            . ", '$string_review_allowed', '$product_status', '$id_shipping_class'"
            . ") as idProduct;";
        //die($type);
        //die($sql);
        if ($result = @$db->query($sql)) {
            if ($db->connect_errno) {
                $this->error($db, 'create_product_to_database', $sql);
            }
            $idProduct = $result->fetch_object()->idProduct;

            $result->free_result();

            if (isset($item['attributes'])) {
                $this->create_attribute_for_product($db, $idProduct, $item['attributes']);
            }

            //$this->create_custom_postmeta($db, $idProduct, $custom_meta);

            return $idProduct;
        } else {
            $this->error($db, 'create_product_to_database', $sql);
        }
    }

    function create_variant_to_database($idParrent, $db, $item, $product_slug, $product_name)
    {
        global $shareFunction;

        $slug = $db->real_escape_string($item['slug']);
        $title = $db->real_escape_string($product_name); //$db->real_escape_string($item['name']);
        $description = $db->real_escape_string($item['description']);
        $price = $item['price'];
        $inStock = $item['instock'];
        $stockStatus = $inStock ? "instock" : "outofstock";
        $listImage = '';
        $image_meta = '';
        $variant_status = 'publish';
        if(isset($item['status'])) $variant_status = $item['status'];

        if (isset($item['image']) === true) {
            $listImage = $db->real_escape_string($item['image']);
            $image_meta = $this->create_image_meta_signle($item['image'], $db);
        }

        if (isset($item['images']) === true && count($item['images']) > 0) {
            $listImage = $db->real_escape_string(implode(";", $item['images']));
            $image_meta = $this->create_image_meta($item['images'], $db);
        }

        $reviews_allowed = $item['reviews_allowed'] ?? true;
        $string_review_allowed = $reviews_allowed ? "open" : "close";

        //16/8/2020
        $rebuild_slug = false;
        if ($slug == '') {
            $slug = $product_slug;
            $rebuild_slug = true;
        }
        $tempSaveSlug = $slug;

        //30/7/2020
        $post_excerpts = array();
        foreach ($item['attributes'] as $attr) {
            $attr_name = str_replace('--', '-', $shareFunction->slugify($attr['name']));
            $attr_value = $shareFunction->slugify($attr['value']);

            $item['custom_meta']['attribute_pa_' . $attr_name] = $attr_value;
            //Style: T Shirt, Color: White, Size: S
            $post_excerpts[] = $db->real_escape_string($attr['name'] . ': ' . $attr['value']);

            if ($rebuild_slug) {
                $slug = $slug . '-' . $shareFunction->slugify($attr['value']);
                $lasPos = strlen($product_slug);
                while(strlen($slug) > 200){
                    $slug = substr($tempSaveSlug, 0, $lasPos - 1) . '-' . $shareFunction->slugify($attr['value']);
                    $lasPos--;
                }
            }
        }
        $post_excerpt = implode(", ", $post_excerpts);

        // 26.3.2022
        $id_shipping_class = '';
        if(isset($item['shipping_class']))
            $id_shipping_class = $this->create_shipping_class($db, $item['shipping_class']);

        while (strpos($slug, '--'))
            $slug = str_replace('--', '-', $slug);

        $product_meta = $this->build_meta_product($db, $item, true);

        $meta_key   = $product_meta['meta_key'];
        $meta_value = $product_meta['meta_value'];

        $virtual = $item['virtual'] ?? 'no';            //string - default
        $downloadable = $item['downloadable'] ?? 'no';       //string - default
        $sql = "select create_variant('$title', '$slug', '$description', '$post_excerpt', $price,"
            . "'$virtual', '$downloadable', '$stockStatus',"
            . "'$listImage', '$image_meta', "
            . "$idParrent, " // '$lstSlugAttribute_name', '$lstSlugAttribute_value',"
            . "'$meta_key', '$meta_value'"
            . ", '$string_review_allowed', '$variant_status', '$id_shipping_class'"
            . ") as idVariant;";
// die($sql);
        if ($result = @$db->query($sql)) {
            $idVariant = $result->fetch_object()->idVariant;
            $result->free_result();

            return $idVariant;
        } else {
            $this->error($db, 'create_variant_to_database', $sql);
        }
    }

    function create_gallery_images_theme_minimog($idProduct, $idVariant, $db) {
        $database = new database();
        global $table_post_meta;

        // $variant_image_id        = $database->run_sql_get_single_col("SELECT meta_value FROM $table_post_meta WHERE post_id = $idVariant AND meta_key = '_thumbnail_id';");
        $product_thumbnail_id    = $database->run_sql_get_single_col("SELECT meta_value FROM $table_post_meta WHERE post_id = $idProduct AND meta_key = '_thumbnail_id';");
        $product_gallery_id_list = $database->run_sql_get_single_col("SELECT meta_value FROM $table_post_meta WHERE post_id = $idProduct AND meta_key = '_product_image_gallery';");
        $minimog_gallery_images  = '';
       
        // if($variant_image_id != null)        $minimog_gallery_images .= $variant_image_id;
        if($product_thumbnail_id != null)    $minimog_gallery_images .= "," . $product_thumbnail_id;
        if($product_gallery_id_list != null) $minimog_gallery_images .= "," . $product_gallery_id_list;
        
        if($minimog_gallery_images != '') {
            while (strpos($minimog_gallery_images, ',,'))
                $minimog_gallery_images = str_replace(',,', ',', $minimog_gallery_images);
            $database->run_sql_without_return("INSERT INTO $table_post_meta(post_id, meta_key, meta_value) VALUE($idVariant, 'gallery_images', '$minimog_gallery_images')");
        }
    }
    ///wp-content/uploads/...
    function create_image_meta($array_images_local_url, $db)
    {
        $res = [];
        global $separate_meta;
        foreach ($array_images_local_url as $url) {
            $temp = $this->create_image_meta_signle($url, $db);
            if ($temp != null)
                $res[] = $temp;
        }
        return implode($separate_meta, $res);
    }

    ///wp-content/uploads/...
    function create_image_meta_signle($image_local_url, $db)
    {
        //
        global $ini;
        $const_check = "/wp-content/uploads/";
        $site_url = $ini['site_url'];
        // Nếu là ảnh được upload lên site
        if (
            $site_url  != ""
            && $image_local_url != ""
            && strlen($image_local_url) >= strlen($site_url) + strlen($const_check)
            && (substr($image_local_url, 0, strlen($site_url)) == $site_url
                || substr($image_local_url, strlen($site_url), strlen($const_check)) == $const_check)
        ) {
            $res = '';
            $local_url = str_replace($site_url, '', $image_local_url);
            $file_path = "../../../../.." . $local_url;
            //die($file_path);

            // Chỗ này là kiểu serial của php, có thể code lại để tương minh hơn
            if (file_exists($file_path)) {
                list($width, $height) = getimagesize($file_path);
                $file_name =  $db->real_escape_string(basename($file_path));
                $file_name_length = strlen($file_name);
                $res = 'a:5:{';
                $res .= "s:5:\"width\";i:$width;";
                $res .= "s:6:\"height\";i:$height;";
                $res .= "s:4:\"file\";s:$file_name_length:\"$file_name\";";
                $res .= 's:5:"sizes";';
                $res .= 'a:11:{';
                $res .= 's:6:"medium";'; //Hien thi trong media (dang luoi) admin
                $res .= "a:4:{s:4:\"file\";s:$file_name_length:\"$file_name\";s:5:\"width\";i:$width;s:6:\"height\";i:$height;s:9:\"mime-type\";s:10:\"image/jpeg\";}";
                $res .= 's:5:\"large\";'; //Hien thi khi an vao anh o media admin
                $res .= "a:4:{s:4:\"file\";s:$file_name_length:\"$file_name\";s:5:\"width\";i:$width;s:6:\"height\";i:$height;s:9:\"mime-type\";s:10:\"image/jpeg\";}";
                $res .= "s:9:\"thumbnail\";"; //Thumbnail page product admin
                $res .= "a:4:{s:4:\"file\";s:$file_name_length:\"$file_name\";s:5:\"width\";i:$width;s:6:\"height\";i:$height;s:9:\"mime-type\";s:10:\"image/jpeg\";}";
                $res .= "s:12:\"medium_large\";";
                $res .= "a:4:{s:4:\"file\";s:$file_name_length:\"$file_name\";s:5:\"width\";i:$width;s:6:\"height\";i:$height;s:9:\"mime-type\";s:10:\"image/jpeg\";}";
                $res .= "s:9:\"1536x1536\";";
                $res .= "a:4:{s:4:\"file\";s:$file_name_length:\"$file_name\";s:5:\"width\";i:$width;s:6:\"height\";i:$height;s:9:\"mime-type\";s:10:\"image/jpeg\";}";
                $res .= "s:21:\"woocommerce_thumbnail\";";
                $res .= "a:5:{s:4:\"file\";s:$file_name_length:\"$file_name\";s:5:\"width\";i:$width;s:6:\"height\";i:$height;s:9:\"mime-type\";s:10:\"image/jpeg\";s:9:\"uncropped\";b:0;}";
                $res .= "s:18:\"woocommerce_single\";"; //Hien thi trong danh sach san pham
                $res .= "a:4:{s:4:\"file\";s:$file_name_length:\"$file_name\";s:5:\"width\";i:$width;s:6:\"height\";i:$height;s:9:\"mime-type\";s:10:\"image/jpeg\";}";
                $res .= "s:29:\"woocommerce_gallery_thumbnail\";"; //Hien thi trong media (danh danh sach)
                $res .= "a:4:{s:4:\"file\";s:$file_name_length:\"$file_name\";s:5:\"width\";i:$width;s:6:\"height\";i:$height;s:9:\"mime-type\";s:10:\"image/jpeg\";}";
                $res .= "s:12:\"shop_catalog\";";
                $res .= "a:4:{s:4:\"file\";s:$file_name_length:\"$file_name\";s:5:\"width\";i:$width;s:6:\"height\";i:$height;s:9:\"mime-type\";s:10:\"image/jpeg\";}";
                $res .= "s:11:\"shop_single\";"; //Hien thi khi chua phong to
                $res .= "a:4:{s:4:\"file\";s:$file_name_length:\"$file_name\";s:5:\"width\";i:$width;s:6:\"height\";i:$height;s:9:\"mime-type\";s:10:\"image/jpeg\";}";
                $res .= "s:14:\"shop_thumbnail\";";
                $res .= "a:4:{s:4:\"file\";s:$file_name_length:\"$file_name\";s:5:\"width\";i:$width;s:6:\"height\";i:$height;s:9:\"mime-type\";s:10:\"image/jpeg\";}";
                $res .= "}";
                $res .= "s:10:\"image_meta\";";
                $res .= "a:12:{";
                $res .= "s:8:\"aperture\";s:1:\"0\";s:6:\"credit\";s:0:\"\";s:6:\"camera\";s:0:\"\";s:7:\"caption\";s:0:\"\";s:17:\"created_timestamp\";s:1:\"0\";s:9:\"copyright\";s:0:\"\";s:12:\"focal_length\";s:1:\"0\";s:3:\"iso\";s:1:\"0\";s:13:\"shutter_speed\";s:1:\"0\";s:5:\"title\";s:0:\"\";s:11:\"orientation\";s:1:\"0\";s:8:\"keywords\";a:0:{}}";
                $res .= "}";
                return $res;
            } else {
                return 'null';
            }
        } else {
            return 'null';
        }
    }

    function create_review($db, $id_product, $item)
    {
        $review_author = $db->real_escape_string($item['review_author']);
        $review_author_email = $db->real_escape_string($item['review_author_email']);
        $review_content = $item['review_content'];
        $review_content = $db->real_escape_string($review_content);
        $rating = $item['rating'];
        $time_create = $item['time_create'];

        $sql = "select create_review($id_product, '$review_author', '$review_author_email', '$review_content'"
            . ", '$time_create', $rating)"
            . " as id_review;";

        if ($result = $db->query($sql)) {
            $id_review = $result->fetch_object()->id_review;
            $result->free_result();
            return $id_review;
        } else {
            $this->error($db, 'create_review', $sql);
        }
    }

    function create_term($db, $item, $id_parent = 0)
    {
        // die('huhu');
        $term_name = $item['term_name'];
        //$term_name = mb_convert_encoding($term_name, "UTF-8");//utf8_encode($term_name);// iconv(mb_detect_encoding($term_name, mb_detect_order(), true), "UTF-8", $term_name);
        $term_name = $db->real_escape_string($term_name);

        $term_slug = $db->real_escape_string($item['term_slug']);
        $term_taxonomy = $db->real_escape_string($item['term_taxonomy']); //product_cat, product_tag

        $sql = "select create_term('$term_name', '$term_slug', '$term_taxonomy', $id_parent) as id_term;";
// die($sql);
        if ($result = $db->query($sql)) {
            $id_term = $result->fetch_object()->id_term;
            $result->free_result();
            return $id_term;
        } else {
            $this->error($db, 'create_term', $sql);
        }
    }

    function create_tag(mysqli $db, $tag_name)
    {
        global $shareFunction;
        //echo "current character: " . $db->character_set_name() . "<br/>";
        $tag_item = array();
        $tag_item['term_name'] = $tag_name;
        $tag_item['term_slug'] = $shareFunction->slugify($tag_name);
        $tag_item['term_taxonomy'] = "product_tag";
        return $this->create_term($db, $tag_item);
    }

    function create_category($db, $cat_name_breadcrumb, $merge_name_sub_category=true)// 09.4.2022 Thêm biến merge_name để phục vụ tạo category SEO
    {
        global $shareFunction;
        $cat_names = explode('>', $cat_name_breadcrumb);
        $id = 0;
        $all_scrumb = '';
        foreach ($cat_names as $cat_name) {
            $tag_item = array();
            $cat_name_trim = trim($cat_name);//$db->real_escape_string(

            if ($all_scrumb != '')
                $all_scrumb .= ' ';
            $all_scrumb .= $cat_name_trim;

            if($merge_name_sub_category == false)
                $all_scrumb = $cat_name_trim;

            $tag_item['term_name'] = $cat_name_trim;
            $tag_item['term_slug'] = $shareFunction->slugify($all_scrumb);
            $tag_item['term_taxonomy'] = "product_cat";

            $id = $this->create_term($db, $tag_item, $id);
        }
        return $id;
    }

    function create_attribute($db, $item)
    {
        $att_label = $db->real_escape_string($item['attribute_label']);
        $att_slug = $db->real_escape_string($item['attribute_slug']);
        $attribute_terms = '';
        if (isset($item['attribute_terms']))
            $attribute_terms = $db->real_escape_string(implode(';', $item['attribute_terms']));
        $sql = "select create_attribute('$att_label', '$att_slug', '$attribute_terms') as id_attribute;";

        if ($result = $db->query($sql)) {
            $id_attribute = $result->fetch_object()->id_attribute;
            $result->free_result();
            return $id_attribute;
        } else {
            $this->error($db, 'create_attribute', $sql);
        }
    }

    function create_shipping_class(mysqli $db, $shipping_class_name)
    {
        if($shipping_class_name == '')
            return '';
        global $shareFunction;
        //echo "current character: " . $db->character_set_name() . "<br/>";
        $tag_item = array();
        $tag_item['term_name'] = $shipping_class_name;
        $tag_item['term_slug'] = $shareFunction->slugify($shipping_class_name);
        $tag_item['term_taxonomy'] = "product_shipping_class";
        return $this->create_term($db, $tag_item);
    }

    function create_attribute_for_product($db, $id_product, $arr_attribute)
    {
        global $shareFunction;
        foreach ($arr_attribute as $attr) {
            if ($attr['name'] != '') {
                $attr_slug = $shareFunction->slugify($db->real_escape_string($attr['name']));
                $attr_label = $db->real_escape_string($attr['name']);
                $attribute_terms = "";
                $attribute_slugs = "";
                foreach ($attr['values'] as $val) {
                    $attribute_terms .= $db->real_escape_string($val) . ';';
                    $attribute_slugs .= $shareFunction->slugify($db->real_escape_string($val)) . ';';
                }

                $sql = "select create_attribute_for_product($id_product ,'$attr_label', '$attr_slug', '$attribute_terms', '$attribute_slugs') as id_attribute;";
// die($sql);
                if ($result = @$db->query($sql)) {
                    $id_attribute = @$result->fetch_object()->id_attribute;
                    @$result->free_result();

                } else {
                    $this->error($db, 'create_attribute_for_product', $sql);
                }
            }
        }
    }

    function create_transient_variable_product($db, $idProduct, $variants)
    {
        $unix_time_current_time = time();
        $unix_time_9_hour_ago =  strtotime('-9 hour', $unix_time_current_time); // time();
        $unix_time_next_month = strtotime('+1 month', $unix_time_current_time); // time();
        $unix_time_next_day = strtotime('+1 day', $unix_time_current_time); // time();

        $hash_price = 'd98aa595501f712ed1287f2f81acb0df'; //plugin money site
        global $table_options;
        $database_func = new database();

        // $transient_version = $unix_time_9_hour_ago;
        // $transient_version_obj = $database_func->run_sql_return_object("SELECT option_value FROM wp_options WHERE option_name = '_transient_product-transient-version' LIMIT 1;");
        // if ($transient_version_obj != null)
        //     $transient_version = $transient_version_obj->option_value;

        // $woocommerce_price_num_decimals = 2;
        // $woocommerce_price_num_decimals_obj = $database_func->run_sql_return_object("SELECT option_value FROM wp_options WHERE option_name = 'woocommerce_price_num_decimals' LIMIT 1;");
        // if ($woocommerce_price_num_decimals_obj != null)
        //     $woocommerce_price_num_decimals = $woocommerce_price_num_decimals_obj->option_value;

        $wc_product_children_a1 = 'a:2:{s:3:"all";a:' . count($variants) . ':{';
        $wc_product_children_a2 = 's:7:"visible";a:' . count($variants) . ':{';


        // $wc_var_prices = '{"version":"' . $transient_version . '","' . $hash_price . '"';
        // $wc_var_prices_price = ''; //"116135":"8","116136":"9","116137":"10"
        // $wc_var_prices_regular_price = '';
        // $wc_var_prices_sale_price = '';


        //die( json_encode($variants) );
        $index = 0;
        foreach ($variants as $variant) {
            $ID = $variant['ID'];

            $wc_product_children_a1 .= "i:$index;i:$ID;";
            $wc_product_children_a2 .= "i:$index;i:$ID;";


            // $regular_price = number_format($variant['price'] ?? 0, $woocommerce_price_num_decimals);
            // $sale_price    = number_format($variant['sale_price'] ?? $regular_price, $woocommerce_price_num_decimals);
            // if (strlen($wc_var_prices_price) > 0)
            //     $wc_var_prices_price .= ',';
            // $wc_var_prices_price .= "\"$ID\":\"$sale_price\"";

            // if (strlen($wc_var_prices_regular_price) > 0)
            //     $wc_var_prices_regular_price .= ',';
            // $wc_var_prices_regular_price .= "\"$ID\":\"$regular_price\"";

            // if (strlen($wc_var_prices_sale_price) > 0)
            //     $wc_var_prices_sale_price .= ',';
            // $wc_var_prices_sale_price .= "\"$ID\":\"$sale_price\"";

            $index++;
        }
        //die('ok');
        $wc_product_children_a1 .= '}';
        $wc_product_children_a2 .= '}';

        $wc_product_children = $wc_product_children_a1 . $wc_product_children_a2 . '}';

        $query = "INSERT INTO $table_options (`option_name`, `option_value`, `autoload`) VALUES ('_transient_timeout_wc_product_children_$idProduct', $unix_time_next_month, 'no')  ON DUPLICATE KEY UPDATE `option_name` = VALUES(`option_name`), `option_value` = VALUES(`option_value`), `autoload` = VALUES(`autoload`);";
        $database_func->run_sql($db, $query);
        $query = "INSERT INTO $table_options (`option_name`, `option_value`, `autoload`) VALUES ('_transient_wc_product_children_$idProduct', '$wc_product_children', 'no')  ON DUPLICATE KEY UPDATE `option_name` = VALUES(`option_name`), `option_value` = VALUES(`option_value`), `autoload` = VALUES(`autoload`);";
        $database_func->run_sql($db, $query);

        /*
        $temp = ':{"price":{' . $wc_var_prices_price . '},"regular_price":{' . $wc_var_prices_regular_price . '},"sale_price":{' . $wc_var_prices_sale_price . '}}';
        $wc_var_prices .= $temp;
        $wc_var_prices .= '}';
        $query = "INSERT INTO $table_options (`option_name`, `option_value`, `autoload`) VALUES ('_transient_timeout_wc_var_prices_$idProduct', $unix_time_next_month, 'no')  ON DUPLICATE KEY UPDATE `option_name` = VALUES(`option_name`), `option_value` = VALUES(`option_value`), `autoload` = VALUES(`autoload`);";
        $database_func->run_sql($db, $query);
        $query = "INSERT INTO $table_options (`option_name`, `option_value`, `autoload`) VALUES ('_transient_wc_var_prices_$idProduct', '$wc_var_prices', 'no')  ON DUPLICATE KEY UPDATE `option_name` = VALUES(`option_name`), `option_value` = VALUES(`option_value`), `autoload` = VALUES(`autoload`);";
        $database_func->run_sql($db, $query);
        */

        /*
        $wc_related_html = '';
        $wc_related_html_len = strlen($$wc_related_html);
        $wc_related = "a:1:{s:$wc_related_html_len:\"$wc_related_html\";a:1:{i:0;s:6:\"116274\";}}";

        $query = "INSERT INTO $table_options (`option_name`, `option_value`, `autoload`) VALUES ('_transient_timeout_wc_related_$idProduct', $unix_time_next_month, 'no')  ON DUPLICATE KEY UPDATE `option_name` = VALUES(`option_name`), `option_value` = VALUES(`option_value`), `autoload` = VALUES(`autoload`);";
        $database_func->run_sql($db, $query);
        */
    }
}
$db_create = new create_database();
