<?php
include "../share_function.php";
$iniPath = '../../../config/database_config.ini';
if(!file_exists($iniPath)){
    die('API_V2: Không tìm thấy file ini');
}
require_once "../share_function.php";

$ini = parse_ini_file($iniPath);
$separate_meta = '<->';

class database{
    
    function check_sku_imported_in_five_minute($db, $sku){
        $sql = "select check_sku_imported_in_five_minute('$sku') as id_product;";
        if($result = $db->query($sql)) {
            $id_product = $result->fetch_object()->id_product;
            $result->free_result();
            return $id_product;
        }else{
            die("function check_sku_imported_in_five_minute error (" . $db->errno . "): " . $db->error . " Query: " . $sql);
        }
    }

    function insert_custom_postmeta($db, $id_product, $custom_meta){
        foreach ($custom_meta as $key => $value){
            $goodKey = $db->real_escape_string($key);
            $goodValue = $db->real_escape_string($value);
            $sql = "select insert_postmeta_fast_api($id_product, '$goodKey', '$goodValue') as idPostMeta";
            if($result = $db->query($sql)){
                //$idPostMeta = $result->fetch_object()->idPostMeta;
                $result->free_result();
            }
            else{
                die("function insert_postmeta_fast_api error (" . $db->errno . "): " . $db->error . " Query: " . $sql);
            }
        }
    }

    function insert_simple_product_to_database($db, $item)
    {
        global $shareFunction;

        $slug = $db->real_escape_string($item['slug']);
        $sku = $db->real_escape_string($item['sku']);
        $title = $db->real_escape_string($item['title']);
        $description = $db->real_escape_string($item['description']);
        $short_description = $db->real_escape_string($item['short_description']);
        $price = $item['price'];
        $sale_price = $item['sale_price'];
        $inStock = $item['instock'];
        $stockStatus = $inStock ? "instock" : "outofstock";
        //$idCategory = $item['id_category'];
        //$tag = implode(';', $item['id_tags']);
        $listImage = $db->real_escape_string(implode(";", $item['images']));
        $custom_meta = $item['custom_meta'];
        //22/10/2020
        $image_meta = $this->create_image_meta($item['images'], $db);
        $tag = "";
        if(isset($item['tags'])){
            $arrTagIds = [];
            foreach ($item['tags'] as $tag_name){
                //$tag .= $this->insert_tag($db, $tag_name) . ";";
                $arrTagIds[] = $this->insert_tag($db, $tag_name);
            }
            $arrTagIds = array_unique($arrTagIds);
            $tag = implode(";", $arrTagIds);
        }
        // $idCategory = 0;
        $merge_name_sub_category = $item['merge_name_sub_category'] ?? true; // 09.4.2022 thêm cái này để tối ưu SEO (không join category trong sub category)
        $idCategory = '';
        // if(strlen($item['category']) > 0){
        //     $idCategory = $this->insert_category($db, $item['category']);
        // }
        if (isset($item['categories'])) {
            $arrayCategoryIds = [];
            foreach ($item['categories'] as $cat)
                if ($cat != '')
                    $arrayCategoryIds[] =  $this->insert_category($db, $cat, $merge_name_sub_category);
            $arrayCategoryIds = array_unique($arrayCategoryIds);
            $idCategory = implode(";", $arrayCategoryIds);
        }

        //19/7/2020
        $total_sales = $item['total_sales'];              //int - default

        $tax_status = $item['tax_status'];         //string - default
        $tax_class = $item['tax_class'];          //string - default

        $manage_stock = $item['manage_stock'];       //string - default
        $backorders = $item['backorders'];         //string - default
        $sold_individually = $item['sold_individually'];  //string - default
        $virtual = $item['virtual'];            //string - default
        $downloadable = $item['downloadable'];       //string - default

        $download_limit = $item['download_limit'];     //int - default
        $download_expiry = $item['download_expiry'];    //int - default
        $stock = $item['stock'];              //int - default
        // 03.3.2022 SP22_fbNguyenHieu yêu cầu thêm Stock, Weight, Width, Height, Length
        $weight = 'null';
        $height = 'null';
        $width = 'null';
        $length = 'null';
        if(isset($item['weight'])) $weight = $item['weight'];
        if(isset($item['height'])) $height = $item['height'];
        if(isset($item['width'])) $width = $item['width'];
        if(isset($item['length'])) $length = $item['length'];

        //27/8/2021
        $post_status = $item['post_status'];//'publish';

        if(isset($item['downloadable_files']))
            $custom_meta['_downloadable_files'] = $shareFunction->create_json_downloadable_file($db, $item['downloadable_files']);

        //21/5/2021
        $product_type = 'simple';
                if (isset($item['product_type']))
                    $product_type = $item['product_type'];

        $sql = "select insert_simple_product_fast_api('$sku', '$title', '$slug', '$description', '$short_description', $price, $sale_price,"
            . "'$stockStatus', $total_sales, '$tax_status', '$tax_class',"
            . "'$manage_stock', '$backorders', '$sold_individually', '$virtual', '$downloadable',"
            . "$download_limit, $download_expiry, $stock,"
            . "'$idCategory', '$listImage', '$image_meta', '$tag', '$product_type', '$post_status',"
            . "$weight, $height, $width, $length"
            . ") as idProduct;";

        //31/3/2020
        //return $this->run_sql_get_single_col($db, $sql);

        if($result = $db->query($sql)) {

            $idProduct = $result->fetch_object()->idProduct;
            $result->free_result();

            $this->insert_custom_postmeta($db, $idProduct, $custom_meta);

            return $idProduct;
        }else{
            die("function insert_simple_product_to_database error (" . $db->errno . "): " . $db->error . " Query: " . $sql);
        }
    }

    ///wp-content/uploads/...
    function create_image_meta($array_images_local_url, $db){
        $res = [];
        global $separate_meta;
        foreach($array_images_local_url as $url){
            $temp = $this->create_image_meta_signle($url, $db);
            if($temp != null)
            $res[] = $temp;
        }
        return implode($separate_meta ,$res);
    }

    ///wp-content/uploads/...
    function create_image_meta_signle($image_local_url, $db){
        //
        global $ini;
        $const_check = "/wp-content/uploads/";
        $site_url = $ini['site_url'];
        if($image_local_url != null && $image_local_url != '' && $site_url != null && $site_url != ''
            && (
                substr($image_local_url, 0, strlen($site_url)) == $site_url
             || substr($image_local_url, 0, strlen($const_check)) == $const_check
        )){
            $res = '';
            $local_url = str_replace($site_url, '', $image_local_url);
            $file_path = "../../.." . $local_url;
            if(file_exists($file_path)){
                list($width, $height) = getimagesize($file_path);
                $file_name_real = date('Y') . '/' . date('m') . '/' . $db->real_escape_string(basename($file_path));
                $file_name_length_real = strlen($file_name_real);
                $file_name = /*date('Y') . '/' . date('m') . '/' .*/ $db->real_escape_string(basename($file_path));
                $file_name_length = strlen($file_name);
                $res = 'a:5:{';
                $res .= "s:5:\"width\";i:$width;";
                $res .= "s:6:\"height\";i:$height;";
                $res .= "s:4:\"file\";s:$file_name_length_real:\"$file_name_real\";";
                $res .= 's:5:"sizes";';
                $res .= 'a:11:{';
                $res .= 's:6:"medium";';//Hien thi trong media (dang luoi) admin
                $res .= "a:4:{s:4:\"file\";s:$file_name_length:\"$file_name\";s:5:\"width\";i:$width;s:6:\"height\";i:$height;s:9:\"mime-type\";s:10:\"image/jpeg\";}";
                $res .= 's:5:\"large\";';//Hien thi khi an vao anh o media admin
                $res .= "a:4:{s:4:\"file\";s:$file_name_length:\"$file_name\";s:5:\"width\";i:$width;s:6:\"height\";i:$height;s:9:\"mime-type\";s:10:\"image/jpeg\";}";
                $res .= "s:9:\"thumbnail\";";//Thumbnail page product admin
                $res .= "a:4:{s:4:\"file\";s:$file_name_length:\"$file_name\";s:5:\"width\";i:$width;s:6:\"height\";i:$height;s:9:\"mime-type\";s:10:\"image/jpeg\";}";
                $res .= "s:12:\"medium_large\";";
                $res .= "a:4:{s:4:\"file\";s:$file_name_length:\"$file_name\";s:5:\"width\";i:$width;s:6:\"height\";i:$height;s:9:\"mime-type\";s:10:\"image/jpeg\";}";
                $res .= "s:9:\"1536x1536\";";
                $res .= "a:4:{s:4:\"file\";s:$file_name_length:\"$file_name\";s:5:\"width\";i:$width;s:6:\"height\";i:$height;s:9:\"mime-type\";s:10:\"image/jpeg\";}";
                $res .= "s:21:\"woocommerce_thumbnail\";";
                $res .= "a:5:{s:4:\"file\";s:$file_name_length:\"$file_name\";s:5:\"width\";i:$width;s:6:\"height\";i:$height;s:9:\"mime-type\";s:10:\"image/jpeg\";s:9:\"uncropped\";b:0;}";
                $res .= "s:18:\"woocommerce_single\";";//Hien thi trong danh sach san pham
                $res .= "a:4:{s:4:\"file\";s:$file_name_length:\"$file_name\";s:5:\"width\";i:$width;s:6:\"height\";i:$height;s:9:\"mime-type\";s:10:\"image/jpeg\";}";
                $res .= "s:29:\"woocommerce_gallery_thumbnail\";";//Hien thi trong media (danh danh sach)
                $res .= "a:4:{s:4:\"file\";s:$file_name_length:\"$file_name\";s:5:\"width\";i:$width;s:6:\"height\";i:$height;s:9:\"mime-type\";s:10:\"image/jpeg\";}";
                $res .= "s:12:\"shop_catalog\";";
                $res .= "a:4:{s:4:\"file\";s:$file_name_length:\"$file_name\";s:5:\"width\";i:$width;s:6:\"height\";i:$height;s:9:\"mime-type\";s:10:\"image/jpeg\";}";
                $res .= "s:11:\"shop_single\";";//Hien thi khi chua phong to
                $res .= "a:4:{s:4:\"file\";s:$file_name_length:\"$file_name\";s:5:\"width\";i:$width;s:6:\"height\";i:$height;s:9:\"mime-type\";s:10:\"image/jpeg\";}";
                $res .= "s:14:\"shop_thumbnail\";";
                $res .= "a:4:{s:4:\"file\";s:$file_name_length:\"$file_name\";s:5:\"width\";i:$width;s:6:\"height\";i:$height;s:9:\"mime-type\";s:10:\"image/jpeg\";}";
                $res .= "}";
                $res .= "s:10:\"image_meta\";";
                $res .= "a:12:{";
                $res .= "s:8:\"aperture\";s:1:\"0\";s:6:\"credit\";s:0:\"\";s:6:\"camera\";s:0:\"\";s:7:\"caption\";s:0:\"\";s:17:\"created_timestamp\";s:1:\"0\";s:9:\"copyright\";s:0:\"\";s:12:\"focal_length\";s:1:\"0\";s:3:\"iso\";s:1:\"0\";s:13:\"shutter_speed\";s:1:\"0\";s:5:\"title\";s:0:\"\";s:11:\"orientation\";s:1:\"0\";s:8:\"keywords\";a:0:{}}";
                $res .= "}";
                return $res;    
            }else{
                return 'null';
            }
        }else{
            return 'null';
        }
    }

    function insert_variable_product_to_database($db, $item)
    {
        global $shareFunction;

        $slug = $db->real_escape_string($item['slug']);
        $sku = $db->real_escape_string($item['sku']);
        $title = $db->real_escape_string($item['title']);
        $description = $db->real_escape_string($item['description']);
        $short_description = $db->real_escape_string($item['short_description']);
        $price = $item['price'];
        $sale_price = $item['sale_price'];
        $inStock = $item['instock'];
        $stockStatus = $inStock ? "instock" : "outofstock";
        //$idCategory = $item['id_category'];
        $listImage = $db->real_escape_string(implode(";", $item['images']));
        // die($listImage);
        //$tag = implode(';', $item['id_tags']);
        $attribute_json = $shareFunction->create_json_from_attribute_array($db, $item['attributes']);
        //22/10/2020
        $image_meta = $this->create_image_meta($item['images'], $db);
        //27/8/2021
        $post_status = $item['post_status'];//'publish';
        $tag = "";
        if(isset($item['tags'])){
            $arrTagIds = [];
            foreach ($item['tags'] as $tag_name){
                //$tag .= $this->insert_tag($db, $tag_name) . ";";
                $arrTagIds[] = $this->insert_tag($db, $tag_name);
            }
            $arrTagIds = array_unique($arrTagIds);
            $tag = implode(";", $arrTagIds);
        }
        // $idCategory = 0;
        // if(strlen($item['category']) > 0){
        //     $idCategory = $this->insert_category($db, $item['category']);
        // }
        $merge_name_sub_category = $item['merge_name_sub_category'] ?? true; // 09.4.2022 thêm cái này để tối ưu SEO (không join category trong sub category)
        $idCategory = '';
        if (isset($item['categories'])) {
            $arrayCategoryIds = [];
            foreach ($item['categories'] as $cat)
                if ($cat != '')
                    $arrayCategoryIds[] =  $this->insert_category($db, $cat, $merge_name_sub_category);
            $arrayCategoryIds = array_unique($arrayCategoryIds);
            $idCategory = implode(";", $arrayCategoryIds);
        }

        $defaultAttributes = '';
        if(isset($item['default_attributes'])) {
            //$defaultAttributes = $db->real_escape_string($item['default_attributes']);
            $defaultAttributes = $shareFunction->create_json_default_attribute($db, $item['default_attributes']);
        }

        $custom_meta = $item['custom_meta'];

        //19/7/2020
        $total_sales = $item['total_sales'];              //int - default

        $tax_status = $item['tax_status'];         //string - default
        $tax_class = $item['tax_class'];          //string - default

        $manage_stock = $item['manage_stock'];       //string - default
        $backorders = $item['backorders'];         //string - default
        $sold_individually = $item['sold_individually'];  //string - default
        $virtual = $item['virtual'];            //string - default
        $downloadable = $item['downloadable'];       //string - default

        $download_limit = $item['download_limit'];     //int - default
        $download_expiry = $item['download_expiry'];    //int - default
        $stock = $item['stock'];              //int - default
        if($stock == 'null' || $stock == null) $stock = "-1";

        // 03.3.2022 SP22_fbNguyenHieu yêu cầu thêm Stock, Weight, Width, Height, Length
        $weight = 'null';
        $height = 'null';
        $width = 'null';
        $length = 'null';
        if(isset($item['weight'])) $weight = $item['weight'];
        if(isset($item['height'])) $height = $item['height'];
        if(isset($item['width'])) $width = $item['width'];
        if(isset($item['length'])) $length = $item['length'];

        if(isset($item['downloadable_files']))
            $custom_meta['_downloadable_files'] = $shareFunction->create_json_downloadable_file($db, $item['downloadable_files']);
        $sql = "select insert_variable_product_fast_api('$sku', '$title', '$slug', '$description', '$short_description', $price, $sale_price,"
            . "'$stockStatus', $total_sales, '$tax_status', '$tax_class',"
            . "'$manage_stock', '$backorders', '$sold_individually', '$virtual', '$downloadable',"
            . "$download_limit, $download_expiry, $stock,"
            . "'$idCategory', '$listImage', '$image_meta', '$tag',"
            . "'$attribute_json',"
            ."'$defaultAttributes',"
            . "'$post_status',"
            . "$weight, $height, $width, $length"
            . ") as idProduct;";
// die($sql);
        //31/3/2020
        //return $this->run_sql_get_single_col($db, $sql);

        if($result = $db->query($sql)) {

            if ($db->connect_errno) {
                die("Lỗi: (" . $db->errno . ") " . $db->error . '\n');
            }
            $idProduct = $result->fetch_object()->idProduct;

            $result->free_result();

            $this->insert_custom_postmeta($db, $idProduct, $custom_meta);
            $this->insert_attribute_for_product($db, $idProduct, $item['attributes']);

            return $idProduct;
        }else{
            die("function insert_variable_product_to_database error (" . $db->errno . "): " . $db->error . " Query: " . $sql);
        }
    }

    function insert_variant_to_database($idParrent, $db, $item, $product_slug)
    {
        global $shareFunction;

        $slug = $db->real_escape_string($item['slug']);
        $sku = $db->real_escape_string($item['sku']);
        $title = @$db->real_escape_string($item['title']);
        $description = @$db->real_escape_string($item['description']);
        $price = $item['price'];
        $sale_price = $item['sale_price'];
        $inStock = $item['instock'];
        $stockStatus = $inStock ? "instock" : "outofstock";
        $listImage = '';
        $lstSlugAttribute_name = "";// $db->real_escape_string(implode(';', $item['attributes']));
        $lstSlugAttribute_value = "";//$db->real_escape_string(implode(';', $item['values']));
        $customTitle="";
        //22/10/2020
        $image_meta = $this->create_image_meta_signle($item['image'], $db);
        //16/8/2020
        // 05/02/2023
        if(isset($item['image'])){
            $listImage = $db->real_escape_string($item['image']);
        }

        $rebuild_slug = false;
        if($slug == ''){
            $slug = $product_slug;
            $rebuild_slug = true;
        }

        //30/7/2020
        foreach ($item['attributes'] as $attr){
            $attr_name = $shareFunction->remove_special_char($attr['name']);
            $attr_value = $shareFunction->remove_special_char($attr['value']);

            $lstSlugAttribute_name .= $shareFunction->slugify($attr_name) . ";";
            $lstSlugAttribute_value .= $shareFunction->slugify($attr_value) . ";";
            if($rebuild_slug){
                $slug = $slug . '-' . $shareFunction->slugify($attr_value);
            }
        }

        while(strpos($slug, '--'))
            $slug = str_replace('--', '-', $slug);

        //$slug = '';

        if(isset($item['variation_title']))
            $customTitle = $db->real_escape_string($item['variation_title']);

        //15/7/2020
        $custom_meta = $item['custom_meta'];

        //19/7/2020
        $total_sales = $item['total_sales'];              //int - default

        $tax_status = $item['tax_status'];         //string - default
        $tax_class = $item['tax_class'];          //string - default

        $manage_stock = $item['manage_stock'];       //string - default
        $backorders = $item['backorders'];         //string - default
        $sold_individually = $item['sold_individually'];  //string - default
        $virtual = $item['virtual'];            //string - default
        $downloadable = $item['downloadable'];       //string - default

        $download_limit = $item['download_limit'];     //int - default
        $download_expiry = $item['download_expiry'];    //int - default
        $stock = $item['stock'];              //int - default
        if($stock == null || $stock == 'null') $stock = -1;

        // 03.3.2022 SP22_fbNguyenHieu yêu cầu thêm Stock, Weight, Width, Height, Length
        $weight = 'null';
        $height = 'null';
        $width = 'null';
        $length = 'null';
        if(isset($item['weight'])) $weight = $item['weight'];
        if(isset($item['height'])) $height = $item['height'];
        if(isset($item['width'])) $width = $item['width'];
        if(isset($item['length'])) $length = $item['length'];

        if(isset($item['downloadable_files']))
            $custom_meta['_downloadable_files'] = $shareFunction->create_json_downloadable_file($db, $item['downloadable_files']);

        $sql = "select insert_variant_fast_api('$sku', '$title', '$slug', '$description', $price, $sale_price,"
            . "'$stockStatus', $total_sales, '$tax_status', '$tax_class',"
            . "'$manage_stock', '$backorders', '$sold_individually', '$virtual', '$downloadable',"
            . "$download_limit, $download_expiry, $stock,"
            . "'$listImage', '$image_meta', "
            . "$idParrent, '$lstSlugAttribute_name', '$lstSlugAttribute_value',"
            . "'$customTitle',"
            . "$weight, $height, $width, $length"
            . ") as idVariant;";

        if($result = $db->query($sql)) {
            $idVariant = $result->fetch_object()->idVariant;
            $result->free_result();

            $this->insert_custom_postmeta($db, $idVariant, $custom_meta);

            return $idVariant;
        }else{
            die("function insert_variant_to_database error (" . $db->errno . "): " . $db->error . " Query: " . $sql);
        }
    }

    function insert_review($db, $id_product, $item){
        $review_author = $db->real_escape_string($item['review_author']);
        $review_author_email = $db->real_escape_string($item['review_author_email']);
        $review_content = $item['review_content'];
        $review_content = $db->real_escape_string($review_content);
        $rating = $item['rating'];
        $time_create = $item['time_create'];

        $sql = "select insert_review_fast_api($id_product, '$review_author', '$review_author_email', '$review_content'"
            .", '$time_create', $rating)"
            ." as id_review;";

        if($result = $db->query($sql)) {
            $id_review = $result->fetch_object()->id_review;
            $result->free_result();
            return $id_review;
        }else{
            die("function insert_review_fast_api error (" . $db->errno . "): " . $db->error . " Query: " . $sql);
        }
    }

    function insert_term($db, $item, $id_parent=0){
        $term_name = $item['term_name'];
        //$term_name = mb_convert_encoding($term_name, "UTF-8");//utf8_encode($term_name);// iconv(mb_detect_encoding($term_name, mb_detect_order(), true), "UTF-8", $term_name);
        $term_name = $db->real_escape_string($term_name);

        $term_slug = $db->real_escape_string($item['term_slug']);
        $term_taxonomy = $db->real_escape_string($item['term_taxonomy']);//product_cat, product_tag

        $sql = "select insert_term_fast_api('$term_name', '$term_slug', '$term_taxonomy', $id_parent) as id_term;";

        if($result = $db->query($sql)) {
            $id_term = $result->fetch_object()->id_term;
            $result->free_result();
            return $id_term;
        }else{
            die("function insert_term_fast_api error (" . $db->errno . "): " . $db->error . " Query: " . $sql);
        }
    }

    function insert_tag(mysqli $db, $tag_name){
        global $shareFunction;
        //echo "current character: " . $db->character_set_name() . "<br/>";
        $tag_item = array();
        $tag_item['term_name'] = $tag_name;
        $tag_item['term_slug'] = $shareFunction->slugify($tag_name);
        $tag_item['term_taxonomy'] = "product_tag";
        return $this->insert_term($db, $tag_item);
    }

    // function insert_category($db, $cat_name){
    //     global $shareFunction;

    //     $tag_item = array();
    //     $tag_item['term_name'] = $cat_name;
    //     $tag_item['term_slug'] = $shareFunction->slugify($cat_name);
    //     $tag_item['term_taxonomy'] = "product_cat";
    //     return $this->insert_term($db, $tag_item);
    // }

    function insert_category($db, $cat_name_breadcrumb, $merge_name_sub_category=true)// 09.4.2022 Thêm biến merge_name để phục vụ tạo category SEO
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

            $id = $this->insert_term($db, $tag_item, $id);
        }
        return $id;
    }

    function insert_attribute($db, $item){
        $att_label = $db->real_escape_string($item['attribute_label']);
        $att_slug = $db->real_escape_string($item['attribute_slug']);
        $attribute_terms = '';
        if(isset($item['attribute_terms']))
            $attribute_terms = $db->real_escape_string(implode(';', $item['attribute_terms']));
        $sql = "select insert_attribute_fast_api('$att_label', '$att_slug', '$attribute_terms') as id_attribute;";

        if($result = $db->query($sql)) {
            $id_attribute = $result->fetch_object()->id_attribute;
            $result->free_result();
            return $id_attribute;
        }else{
            die("function insert_attribute_fast_api error (" . $db->errno . "): " . $db->error . " Query: " . $sql);
        }
    }

    function insert_attribute_for_product($db, $id_product, $arr_attribute){
        global $shareFunction;
        foreach ($arr_attribute as $attr) {
            if($attr['name'] != '') {
                $attr_slug = $shareFunction->slugify($db->real_escape_string($attr['name']));
                $attr_label = $db->real_escape_string($attr['name']);
                $attr_label = $shareFunction->remove_special_char($attr_label);
                $attribute_terms = "";
                $attribute_slugs = "";
                foreach ($attr['values'] as $val) {
                    $remove_specia_char = $shareFunction->remove_special_char($val);
                    $attribute_terms .= $db->real_escape_string($remove_specia_char) . ';';
                    //$attribute_slugs .= $shareFunction->slugify($db->real_escape_string($val)) . ';';
                    $attribute_slugs .= $shareFunction->slugify($db->real_escape_string($remove_specia_char)) . ';';
                }

                $sql = "select insert_attribute_for_product_fast_api($id_product ,'$attr_label', '$attr_slug', '$attribute_terms', '$attribute_slugs') as id_attribute;";
                //die($sql);
                if ($result = $db->query($sql)) {
                    $id_attribute = $result->fetch_object()->id_attribute;
                    $result->free_result();
                } else {
                    die("function insert_attribute_for_product_fast_api error (" . $db->errno . "): " . $db->error . " Query: " . $sql);
                }
            }
        }
    }

    function update_product($db, $id_product, $item){
        $product_title = $db->real_escape_string($item['title']);
        $price = $item['price'];
        $inStock = $item['instock'];
        $stock_status = $inStock ? "instock" : "outofstock";
        $product_type = $item['product_type'];
        $attribute_json = $db->real_escape_string($item['attributes']);

        //08/5/2020 Bổ sung update cả title, description để fix key word google report
        $product_description = $db->real_escape_string($item['description']);
        ////

        $sql = "select update_prodcut_fast_api($id_product, '$product_title', $price, '$stock_status', '$product_type', '$product_description', '$attribute_json') as id_product;";

        if($result = $db->query($sql)) {
            $id_product = $result->fetch_object()->id_product;
            $result->free_result();
            return $id_product;
        }else{
            die("function update_prodcut_fast_api error (" . $db->errno . "): " . $db->error . " Query: " . $sql);
        }
    }

    function update_variant($db, $id_variant, $item){
        $price = $item['price'];
        $inStock = $item['instock'];
        $stock_status = $inStock ? "instock" : "outofstock";
        $optionAttributes = $db->real_escape_string(implode(';', $item['attributes']));
        $optionValues = $db->real_escape_string(implode(';', $item['values']));
        //24/5/2020
        $description = $db->real_escape_string($item['description']);
        $customTitle="";
        if(isset($item['variation_title']))
            $customTitle = $db->real_escape_string($item['variation_title']);

        $sql = "select update_variant_fast_api($id_variant, $price, '$stock_status', '$optionAttributes', '$optionValues', '$customTitle', '$description') as id_variant;";

        if($result = $db->query($sql)) {
            $id_variant = $result->fetch_object()->id_variant;
            $result->free_result();
            return $id_variant;
        }else{
            die("function update_variant_fast_api error (" . $db->errno . "): " . $db->error . " Query: " . $sql);
        }
    }

    function delete_product($db, $sku){
        $sql = "select delete_product_fast_api('$sku') as res;";
        if($result = $db->query($sql)) {
            $res = $result->fetch_object()->res;
            $result->free_result();
            return $res;
        }else{
            die("function delete_product_fast_api error (" . $db->errno . "): " . $db->error . " Query: " . $sql);
        }
    }

    function delete_product_by_id($db, $from, $to){
        if($from == null)
            $from = 'null';
        if($to == null)
            $to = 'null';

        $sql = "select delete_product_by_id_fast_api($from, $to) as res;";
        if($result = $db->query($sql)) {
            $res = $result->fetch_object()->res;
            $result->free_result();
            return $res;
        }else{
            die("function delete_product_by_id_fast_api error (" . $db->errno . "): " . $db->error . " Query: " . $sql);
        }
    }
}

$db_v1 = new database();