<?php
class base_request
{
    public function __construct()
    {
    }

    public function success($data = NULL, $beauty_json = false)
    {
        $this->send_response(200, "success", $data, NULL, NULL, $beauty_json);
    }

    public function warning($warning = NULL, $data = null)
    {
        $this->send_response(200, "warning", $data, $warning);
    }

    public function error($error = NULL, $data = NULL, $beauty_json = false)
    {
        $this->send_response(200, "error", $data, NULL, $error, $beauty_json);
    }

    public function utf8ize($mixed) {
        if (is_array($mixed)) {
            foreach ($mixed as $key => $value) {
                $mixed[$key] = $this->utf8ize($value);
            }
        } else if (is_string ($mixed)) {
            return utf8_encode($mixed);
        }
        return $mixed;
    }

    function safe_json_encode($value, $options = 0, $depth = 512, $utfErrorFlag = false) {
        // $encoded = json_encode($value, $options, $depth);
        $encoded = json_encode($value, JSON_INVALID_UTF8_IGNORE);
        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                return $encoded;
            case JSON_ERROR_DEPTH:
                return 'Maximum stack depth exceeded'; // or trigger_error() or throw new Exception()
            case JSON_ERROR_STATE_MISMATCH:
                return 'Underflow or the modes mismatch'; // or trigger_error() or throw new Exception()
            case JSON_ERROR_CTRL_CHAR:
                return 'Unexpected control character found';
            case JSON_ERROR_SYNTAX:
                return 'Syntax error, malformed JSON'; // or trigger_error() or throw new Exception()
            case JSON_ERROR_UTF8:
                $clean = $this->utf8ize($value);
                if ($utfErrorFlag) {
                    return json_encode(array(
                        "status" => "error",
                        "data" => NULL,
                        "warning" => NULL,
                        "error" => $this->create_error('JSON_ERROR_UTF8', 'Malformed UTF-8 characters, possibly incorrectly encoded (2)')
                    ), JSON_NUMERIC_CHECK);
                    // or trigger_error() or throw new Exception()
                }
                return $this->safe_json_encode($clean, $options, $depth, true);
            default:
                return 'Unknown error'; // or trigger_error() or throw new Exception()

        }
    }

    public function send_response($status_code, $status_text, $data = NULL, $warning = NULL, $error = NULL, $beauty_json = false)
    {
        $iniPath = '../../config/database_config.ini';
        // $ini = array();
        // if (file_exists($iniPath))
        //     $ini = parse_ini_file($iniPath);
        // $charset = '';
        // $collate = '';
        // if (isset($ini['charset']))
        //     $charset = $ini['charset'];

        header($this->_build_http_header_string($status_code));
        header("Content-Type: application/json; charset=utf-8");

        // if ($charset != '' && substr($charset, 0, 4) != 'utf8')
        //     $data = $this->convert_from_latin1_to_utf8_recursively($data);

        $response_body = array(
            "status" => $status_text,
            "data" => $data,
            "warning" => $warning,
            "error" => $error
        );
        $json = json_encode($response_body, $beauty_json ? JSON_PRETTY_PRINT : JSON_NUMERIC_CHECK);
        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                //$this->error($this->create_error('JSON_ERROR_NONE', 'No errors'));
                break;
            case JSON_ERROR_DEPTH:
                $json = json_encode(array(
                    "status" => "error",
                    "data" => NULL,
                    "warning" => NULL,
                    "error" => $this->create_error('JSON_ERROR_DEPTH', 'Maximum stack depth exceeded')
                ), JSON_NUMERIC_CHECK);
                break;
            case JSON_ERROR_STATE_MISMATCH:
                $json = json_encode(array(
                    "status" => "error",
                    "data" => NULL,
                    "warning" => NULL,
                    "error" => $this->create_error('JSON_ERROR_STATE_MISMATCH', 'Underflow or the modes mismatch')
                ), JSON_NUMERIC_CHECK);
                break;
            case JSON_ERROR_CTRL_CHAR:
                $json = json_encode(array(
                    "status" => "error",
                    "data" => NULL,
                    "warning" => NULL,
                    "error" => $this->create_error('JSON_ERROR_CTRL_CHAR', 'Unexpected control character found')
                ), JSON_NUMERIC_CHECK);
                break;
            case JSON_ERROR_SYNTAX:
                $json = json_encode(array(
                    "status" => "error",
                    "data" => NULL,
                    "warning" => NULL,
                    "error" => $this->create_error('JSON_ERROR_SYNTAX', 'Syntax error, malformed JSON')
                ), JSON_NUMERIC_CHECK);
                break;
            case JSON_ERROR_UTF8:
                // $json = json_encode(array(
                //     "status" => "error",
                //     "data" => NULL,
                //     "warning" => NULL,
                //     "error" => $this->create_error('JSON_ERROR_UTF8', 'Malformed UTF-8 characters, possibly incorrectly encoded')
                // ), JSON_NUMERIC_CHECK);
                $json = $this->safe_json_encode($response_body, 0, 512, false);
                break;
            default:
                //echo ' - Unknown error';
                break;
        }
        echo $json;
        die();
    }

    function error_db($db, $code, $query)
    {
        $error = array();
        if ($db != null)
            $error = $this->create_error($code . '_' . $db->errno, "function $code error (" . $db->errno . "): " . $db->error);
        else
            $error['code'] = $code;
        if ($query != null && $query != '')
            $error['query'] = $query;
        $this->error($error);
    }

    public static function convert_from_latin1_to_utf8_recursively($dat)
    {
        if (is_string($dat)) {
            return utf8_encode($dat);
        } elseif (is_array($dat)) {
            $ret = [];
            foreach ($dat as $i => $d) $ret[$i] = self::convert_from_latin1_to_utf8_recursively($d);

            return $ret;
        } elseif (is_object($dat)) {
            foreach ($dat as $i => $d) $dat->$i = self::convert_from_latin1_to_utf8_recursively($d);

            return $dat;
        } else {
            return $dat;
        }
    }

    public function create_error($code, $exception)
    {
        return array(
            'code' => $code,
            'exception' => $exception
        );
    }

    public function create_warning($code, $message)
    {
        return array(
            'code' => $code,
            'message' => $message
        );
    }

    private function _build_http_header_string($status_code)
    {
        $status = array(
            100 => 'Continue',
            101 => 'Switching Protocols',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            306 => '(Unused)',
            307 => 'Temporary Redirect',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported'
        );
        return "HTTP/1.1 " . $status_code . " " . $status[$status_code];
    }

    function process_input_product($item)
    {
        //Bắt buộc
        if (isset($item['instock']) === false || $item['instock'] === '')
            $item['instock'] = false;

        if (isset($item['slug']) === false || $item['slug'] === '')
            $item['slug'] = '';

        while ($this->codethue_str_contains($item['slug'], '--'))
            $item['slug'] = str_replace("--", "-", $item['slug']);

        if (isset($item['categories']) === false)
            $item['categories'] = [];

        if (isset($item['price']) === false || $item['price'] === '')
            $item['price'] = 'null';

        if (isset($item['regular_price']) === false || $item['regular_price'] === '')
            $item['regular_price'] = $item['price'];

        if (isset($item['sale_price']) === true && $item['price'] != $item['sale_price'])
            $item['price'] = $item['sale_price'];

        if (isset($item['name']) === false)
            $item['name'] = '';

        if (isset($item['short_description']) === false)
            $item['short_description'] = '';

        if (isset($item['description']) === false)
            $item['description'] = '';

        if (isset($item['custom_meta']) === false || $item['custom_meta'] === '')
            $item['custom_meta'] = array();

        if (isset($item['reviews_allowed']) === false || $item['reviews_allowed'] === '')
            $item['reviews_allowed'] = true;

        if (isset($item['type']) === false || $item['type'] === '')
            $item['type'] = 'simple';
        // Hết bắt buộc

        /* Start Không bắt buộc */
        if(isset($item['mode_import']) && $item['mode_import'] == 'full_data') {
            if (!isset($item['tax_status']) || $item['tax_status'] == '')
                $item['tax_status'] = 'taxable';

            if (!isset($item['tax_class']) || $item['tax_class'] == '')
                $item['tax_class'] = ''; // 'parent'; 31.01.2023 đổi lại thành rỗng

            if (!isset($item['backorders']) || $item['backorders'] == '')
                $item['backorders'] = 'no';

            if (!isset($item['sold_individually']) || $item['sold_individually'] == '')
                $item['sold_individually'] = 'no';

            if (!isset($item['virtual']) || $item['virtual'] == '')
                $item['virtual'] = 'no';

            if (!isset($item['downloadable']) || $item['downloadable'] == '')
                $item['downloadable'] = 'no';

            if (!isset($item['download_limit']) || $item['download_limit'] == '')
                $item['download_limit'] = '-1';

            if (!isset($item['download_expiry']) || $item['download_expiry'] == '')
                $item['download_expiry'] = -1;

            if (!isset($item['average_rating']) || $item['average_rating'] == '')
                $item['average_rating'] = 0;

            if (!isset($item['review_count']) || $item['review_count'] == '')
                $item['review_count'] = 0;

            if (!isset($item['stock']) || $item['stock'] == '') {
                // $item['downloadable'] = 'no';
                $item['stock'] = null;
                $item['manage_stock'] = 'no';
            } else {
                $item['manage_stock'] = 'yes';
            }

            if (!isset($item['total_sales']) || $item['total_sales'] == '')
                $item['total_sales'] = 0;
        }
        /* End không bắt buộc */

        return $item;
    }

    function process_input_variation($item, $import_full_data = false)
    {
        //Bắt buộc
        if (isset($item['instock']) === false || $item['instock'] === '')
            $item['instock'] = false;

        if (isset($item['slug']) === false || $item['slug'] === '')
            $item['slug'] = '';

        while ($this->codethue_str_contains($item['slug'], '--'))
            $item['slug'] = str_replace("--", "-", $item['slug']);

        // if (isset($item['categories']) === false)
        //     $item['categories'] = [];

        if (isset($item['price']) === false || $item['price'] === '')
            $item['price'] = 'null';

        if (isset($item['regular_price']) === false || $item['regular_price'] === '')
            $item['regular_price'] = $item['price'];

        if (isset($item['sale_price']) === true && $item['price'] != $item['sale_price'])
            $item['price'] = $item['sale_price'];

        // if (isset($item['name']) === false)
        //     $item['name'] = '';

        // if (isset($item['short_description']) === false)
        //     $item['short_description'] = '';

        if (isset($item['description']) === false)
            $item['description'] = '';

        if (isset($item['custom_meta']) === false || $item['custom_meta'] === '')
            $item['custom_meta'] = array();

        if (isset($item['reviews_allowed']) === false || $item['reviews_allowed'] === '')
            $item['reviews_allowed'] = true;

        // if (isset($item['type']) === false || $item['type'] === '')
        //     $item['type'] = 'simple';
        // Hết bắt buộc

        /* Start Không bắt buộc */
        if($import_full_data) {
            if (!isset($item['tax_status']) || $item['tax_status'] == '')
                $item['tax_status'] = 'taxable';

            if (!isset($item['tax_class']) || $item['tax_class'] == '')
                $item['tax_class'] = 'parent';

            if (!isset($item['backorders']) || $item['backorders'] == '')
                $item['backorders'] = 'no';

            if (!isset($item['sold_individually']) || $item['sold_individually'] == '')
                $item['sold_individually'] = 'no';

            if (!isset($item['virtual']) || $item['virtual'] == '')
                $item['virtual'] = 'no';

            if (!isset($item['downloadable']) || $item['downloadable'] == '')
                $item['downloadable'] = 'no';

            if (!isset($item['download_limit']) || $item['download_limit'] == '')
                $item['download_limit'] = '-1';

            if (!isset($item['download_expiry']) || $item['download_expiry'] == '')
                $item['download_expiry'] = -1;

            if (!isset($item['average_rating']) || $item['average_rating'] == '')
                $item['average_rating'] = 0;

            if (!isset($item['review_count']) || $item['review_count'] == '')
                $item['review_count'] = 0;

            if (!isset($item['stock']) || $item['stock'] == '') {
                $item['downloadable'] = 'no';
                $item['stock'] = null;
                $item['manage_stock'] = 'no';
            } else {
                $item['manage_stock'] = 'yes';
            }

            if (!isset($item['total_sales']) || $item['total_sales'] == '')
                $item['total_sales'] = 0;
        }
        /* End không bắt buộc */

        return $item;
    }

    function codethue_str_contains(string $haystack, string $needle): bool
    {
        return '' === $needle || false !== strpos($haystack, $needle);
    }
}
