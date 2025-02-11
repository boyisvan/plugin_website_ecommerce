<?php
$shareFunction = new sharefunction();

class sharefunction{
    function create_json_from_attribute_array($db, $arr_attributes){
        $attribute_json = "a:" . count($arr_attributes) . ":{";
        $pos = 0;
        foreach ($arr_attributes as $attr){
            $attr_slug = "pa_" . $this->slugify($db->real_escape_string($attr['name']));

            $attribute_json .= "s:" . strlen($attr_slug) . ":\"" . $attr_slug . "\";";

            $attribute_json .= "a:6:{";

            $attribute_json .= "s:4:\"name\";" . "s:" . strlen($attr_slug) . ":\"" . $attr_slug . "\";";
            $attribute_json .= "s:5:\"value\";s:0:\"\";";
            $attribute_json .= "s:8:\"position\";s:1:\"$pos\";";
            $attribute_json .= "s:10:\"is_visible\";s:1:\"1\";";
            $attribute_json .= "s:12:\"is_variation\";s:1:\"1\";";
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

    function slugify($text)
    {
        // replace non letter or digits by -
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);

        // transliterate
        if(function_exists('iconv'))
            $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

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

        return $text;
    }

    function remove_special_char($text){
        $res = str_replace(" ", " ", $text);
        $res = str_replace(" ", " ", $res);
        return $res;
    }

    function create_new_guid(){
        return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
    }

function convert_font($value){

    return str_replace(" ", " ", $value);

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