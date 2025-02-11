<?php

class sharefunction{
    function create_json_from_attribute_array($db, $arr_attributes){
        $attribute_json = "a:" . count($arr_attributes) . ":{";
        $pos = 0;
        foreach ($arr_attributes as $attr){
            $attr_slug = "pa_" . $this->slugify($db->real_escape_string($attr['name']));

            /* 08.5.2022 Code thêm 1 attribute có hiển thị ở front page hay không */
            $visible_front_end = '1';
            if(isset($attr['visible']))
                $visible_front_end = $attr['visible'] ? 1 : 0;
            /* 08.5.2022 End */

            /* 27.3.2023 Attribute nào mà null hết thì ẩn*/
            $used_for_variation = '1';
            if(isset($attr['used_for_variation']))
                $used_for_variation = $attr['used_for_variation'] ? 1 : 0;
            /* 27.3.2023 End */

            $attribute_json .= "s:" . strlen($attr_slug) . ":\"" . $attr_slug . "\";";

            $attribute_json .= "a:6:{";

            $attribute_json .= "s:4:\"name\";" . "s:" . strlen($attr_slug) . ":\"" . $attr_slug . "\";";
            $attribute_json .= "s:5:\"value\";s:0:\"\";";
            $attribute_json .= "s:8:\"position\";s:1:\"$pos\";";
            $attribute_json .= "s:10:\"is_visible\";s:1:\"$visible_front_end\";";
            $attribute_json .= "s:12:\"is_variation\";s:1:\"$used_for_variation\";";
            $attribute_json .= "s:11:\"is_taxonomy\";s:1:\"1\";";

            $attribute_json .= "}";
            $pos++;
        }
        $attribute_json .= "}";
        return $attribute_json;
    }

    function create_json_default_attribute($db, $arr_attributes){
        $defaultAttributes = "a:" . count($arr_attributes) . ":{";
        foreach ($arr_attributes as $attr){
            $attr_slug_name = "pa_" . $this->slugify($db->real_escape_string($attr['name']));
            $attr_slug_value = $this->slugify($db->real_escape_string($attr['value']));
            $defaultAttributes .= "s:" . strlen($attr_slug_name) . ":\"" . $attr_slug_name . "\";";
            $defaultAttributes .= "s:" . strlen($attr_slug_value) . ":\"" . $attr_slug_value . "\";";
        }
        $defaultAttributes .= '}';
        return $defaultAttributes;
    }

    function create_json_downloadable_file($db, $arr_downloadable){
        $res = "a:" . count($arr_downloadable) . ":{";

        foreach ($arr_downloadable as $item){
            $guid = strtolower($this->create_new_guid());
            $file_name = $db->real_escape_string($item['name']);
            $file_link = $item['file'];

            $res .= "s:" . strlen($guid) . ":\"$guid\";a:3:{";

            $res .= "s:2:\"id\";s:". strlen($guid) . ":\"$guid\";";
            $res .= "s:4:\"name\";s:" . strlen($file_name) . ":\"$file_name\";";
            $res .= "s:4:\"file\";s:" . strlen($file_link) . ":\"$file_link\";";

            $res .= "}";
        }

        $res .= "}";

        return $res;
    }

    function remove_vietnamese_accents($str) {
        $accents_arr = array(
            'a' => array('á', 'à', 'ả', 'ã', 'ạ', 'ă', 'ắ', 'ằ', 'ẳ', 'ẵ', 'ặ', 'â', 'ấ', 'ầ', 'ẩ', 'ẫ', 'ậ'),
            'e' => array('é', 'è', 'ẻ', 'ẽ', 'ẹ', 'ê', 'ế', 'ề', 'ể', 'ễ', 'ệ'),
            'i' => array('í', 'ì', 'ỉ', 'ĩ', 'ị'),
            'o' => array('ó', 'ò', 'ỏ', 'õ', 'ọ', 'ô', 'ố', 'ồ', 'ổ', 'ỗ', 'ộ', 'ơ', 'ớ', 'ờ', 'ở', 'ỡ', 'ợ'),
            'u' => array('ú', 'ù', 'ủ', 'ũ', 'ụ', 'ư', 'ứ', 'ừ', 'ử', 'ữ', 'ự'),
            'y' => array('ý', 'ỳ', 'ỷ', 'ỹ', 'ỵ'),
            'd' => array('đ'),
            'A' => array('Á', 'À', 'Ả', 'Ã', 'Ạ', 'Ă', 'Ắ', 'Ằ', 'Ẳ', 'Ẵ', 'Ặ', 'Â', 'Ấ', 'Ầ', 'Ẩ', 'Ẫ', 'Ậ'),
            'E' => array('É', 'È', 'Ẻ', 'Ẽ', 'Ẹ', 'Ê', 'Ế', 'Ề', 'Ể', 'Ễ', 'Ệ'),
            'I' => array('Í', 'Ì', 'Ỉ', 'Ĩ', 'Ị'),
            'O' => array('Ó', 'Ò', 'Ỏ', 'Õ', 'Ọ', 'Ô', 'Ố', 'Ồ', 'Ổ', 'Ỗ', 'Ộ', 'Ơ', 'Ớ', 'Ờ', 'Ở', 'Ỡ', 'Ợ'),
            'U' => array('Ú', 'Ù', 'Ủ', 'Ũ', 'Ụ', 'Ư', 'Ứ', 'Ừ', 'Ử', 'Ữ', 'Ự'),
            'Y' => array('Ý', 'Ỳ', 'Ỷ', 'Ỹ', 'Ỵ'),
            'D' => array('Đ')
        );
    
        foreach ($accents_arr as $non_accent => $accents) {
            $str = str_replace($accents, $non_accent, $str);
        }
    
        return $str;
    }

    function slugify($text)
    {
        // $temp = $text;
        if($text != null) {
            $text = $this->remove_vietnamese_accents($text);
            // replace non letter or digits by -
            $text = preg_replace('~[^\pL\d]+~u', '-', $text);

            // transliterate
            if(function_exists( 'iconv' )) {
                $temp_iconv = @iconv('utf-8', 'us-ascii//TRANSLIT', $text);
                if($temp_iconv && $temp_iconv != null)
                    $text = $temp_iconv;
            }

            // remove unwanted characters
            $text = preg_replace('~[^-\w]+~', '', $text);

            // trim
            $text = trim($text, '-');

            // remove duplicate -
            $text = preg_replace('~-+~', '-', $text);

            // lowercase
            $text = strtolower($text);

            if (empty($text)) {
                //return 'n-a';
            }
        }
        if(empty($text)) {
            $text = 'na-' . substr(md5(mt_rand()), 0, 8);
        }
        // if($temp == "Đỏ")
        //     die($text);
        return $text;
    }

    function create_new_guid(){
        return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
    }

function convert_font($value){

    if($value != null)
        return str_replace(" ", " ", $value);
    return $value;

    // $use_mb          = function_exists( 'mb_convert_encoding' );
    // if ( $use_mb ) {
    //     $encoding = mb_detect_encoding( $value, mb_detect_order(), true );
    //     if ( $encoding ) {
    //         $value = mb_convert_encoding( $value, 'UTF-8', $encoding );
    //     } else {
    //         $value = mb_convert_encoding( $value, 'UTF-8', 'UTF-8' );
    //     }
    // } else {
    //     $value = $this->wp_check_invalid_utf8( $value, true );
    // }
    // return $value;
}

    function wp_check_invalid_utf8( $string, $strip = false ) {
        $string = (string) $string;
        if ( 0 === strlen( $string ) ) {
            return '';
        }
    
        // Store the site charset as a static to avoid multiple calls to get_option()
        static $is_utf8 = true;// null;
        
        // if ( ! isset( $is_utf8 ) ) {
        //     $is_utf8 = in_array( get_option( 'blog_charset' ), array( 'utf8', 'utf-8', 'UTF8', 'UTF-8' ) );
        // }
        // if ( ! $is_utf8 ) {
        //     return $string;
        // }

        // Check for support for utf8 in the installed PCRE library once and store the result in a static
        static $utf8_pcre = null;
        if ( ! isset( $utf8_pcre ) ) {
            $utf8_pcre = @preg_match( '/^./u', 'a' );
        }
        // We can't demand utf8 in the PCRE installation, so just return the string in those cases
        if ( !$utf8_pcre ) {
            return $string;
        }
    
        // preg_match fails when it encounters invalid UTF8 in $string
        if ( 1 === @preg_match( '/^./us', $string ) ) {
            return $string;
        }
    
        // Attempt to strip the bad chars if requested (not recommended)
        if ( $strip && function_exists( 'iconv' ) ) {
            return iconv( 'utf-8', 'utf-8', $string );
        }
    
        return '';
    }
}

$shareFunction = new sharefunction();   