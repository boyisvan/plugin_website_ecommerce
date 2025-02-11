<?php
class base_request
{
    public function __construct(){

    }

    public function response($status_code, $data = NULL)
    {
        header($this->_build_http_header_string($status_code));
        header("Content-Type: application/json");
        echo json_encode($data);
        die();
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

    public function success($data = NULL)
    {
        $this->send_response(200, "success", $data);
    }

    public function warning($warning = NULL, $data = null)
    {
        $this->send_response(200, "warning", $data, $warning);
    }

    public function error($error = NULL, $data = NULL)
    {
        $this->send_response(200, "error", $data, NULL, $error);
    }

    public function send_response($status_code, $status_text, $data = NULL, $warning = NULL, $error = NULL)
    {
        // $iniPath = '../../../config/database_config.ini';
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
        $json = json_encode($response_body, JSON_NUMERIC_CHECK);
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
                $json = json_encode(array(
                    "status" => "error",
                    "data" => NULL,
                    "warning" => NULL,
                    "error" => $this->create_error('JSON_ERROR_UTF8', 'Malformed UTF-8 characters, possibly incorrectly encoded')
                ), JSON_NUMERIC_CHECK);
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

    function process_input($item){

        if (isset($item['name']) === false)
            $item['name'] = '';

        if (!isset($item['status']))
            $item['status'] = 'publish';

        // Start migration supper ipmorter -> woo pod
        if(isset($item['product_type']) === false && isset($item['type']))
            $item['product_type'] = $item['type'];

        if(isset($item['title']) === false && isset($item['name']))
            $item['title'] = $item['name'];

        if(isset($item['post_status']) === false && isset($item['status']))
            $item['post_status'] = $item['status'];

        if(isset($item['variants']) === false && isset($item['variations']))
            $item['variants'] = $item['variations'];

        // End migration


        if (!isset($item['stock']))
            $item['stock'] = 'null';

        if (!isset($item['total_sales']))
            $item['total_sales'] = 0;

        if (!isset($item['id_tags']))
            $item['id_tags'] = array();

        if(!isset($item['custom_meta']))
            $item['custom_meta'] = array();

        if(!isset($item['short_description']))
            $item['short_description'] = '';

        // if(!isset($item['category']))
        //     $item['category'] = '';
        if (isset($item['categories']) === false)
            $item['categories'] = [];

        if(!isset($item['slug']))
            $item['slug'] = '';

        if (!isset($item['sale_price']))
            $item['sale_price'] = 'null';

        if (!isset($item['tax_status']))
            $item['tax_status'] = 'taxable';
        if (!isset($item['tax_class'])){
            if(isset($item['variants']))
                $item['tax_class'] = '';
            else
                $item['tax_class'] = 'parent'; // variant thì là parrent
        }
        if (!isset($item['backorders']))
            $item['backorders'] = 'no';

        if (!isset($item['sold_individually']))
            $item['sold_individually'] = 'no';

        if (!isset($item['virtual']))
            $item['virtual'] = 'no';

        if (!isset($item['downloadable']))
            $item['downloadable'] = 'no';

        if (!isset($item['download_limit']))
            $item['download_limit'] = '-1';
        if (!isset($item['download_expiry']))
            $item['download_expiry'] = -1;

        if (!isset($item['stock']) || $item['stock'] == '' || $item['stock'] == 'null') {
            $item['stock'] = null;
            $item['manage_stock'] = 'no';
        } else {
            $item['manage_stock'] = 'yes';
        }
        // die($item['manage_stock']);

        return $item;
    }
}