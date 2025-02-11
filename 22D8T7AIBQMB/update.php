<?php
/*Note: Full permission for this folder => sudo chown -R www-data:www-data {path}*/

$zip_file          = './config/php.zip';
$iniPath           = './config/info.ini';
$local_version     = "v2.0.10.15.10.2023.create_gallery_images_theme_minimog";
if(file_exists($iniPath)) {
    $ini = parse_ini_file('./config/info.ini');
    $local_version = $ini['version'];
}

$base_request = new base_request();
$log = array();

$log[] = "Local vertsion: " . $local_version;

// $url_check_version = "http://teentuc.online/woo-pod-api/version.txt";
// $url_download = "http://teentuc.online/woo-pod-api/php.zip";

$url_check_version = "https://github.com/ngochoaitn/update-gpm-woocommerce-hooking/releases/download/latest/version.txt";
$url_download = "https://github.com/ngochoaitn/update-gpm-woocommerce-hooking/releases/download/latest/hooking-update.zip";

$newest_version = getHtml($url_check_version);

$log[] = "Newest vertsion: " . $newest_version;
if ($local_version == $newest_version) {
    //echo "Dont't need update" . "<br/>";
    $base_request->success(array('update_info' => create_update_info('Dont\'t need update', $local_version)));
} else {
    $log[] = "Need update";
    $update_ok = true;
    try {
        if (!file_exists('config')) {
            @mkdir('config', 0777);
        }
        if(file_exists($zip_file)){
            @unlink($zip_file);
        }
        
        if (download_file_from_url($url_download, $zip_file)) {
            require_once('libs/pclzip/pclzip.lib.php');

            //Vì có host không cài extension ArchiveZIP nên cần dùng thư viện ngoài
            $archive = new PclZip($zip_file);

            $dirs = array("sql", "api");
           
            foreach($dirs as $dir) {
                    try {
                        if(!deleteDir($dir)){
                            $update_ok = false;
                            $log[] = "Can not update folder $dir. Please set full permission (777)";
                        }
                    }
                    catch (Exception $ex){
                        $update_ok = false;
                        $log[] = "Exception: $dir";
                    }
            }

            $files = array("setup.php", "info.php", "config/systemConfig.php", "add_on.php", "full_woo_plugin_info.php");
            foreach($files as $file){
                if(file_exists($file)){
                    try {
                        $delete_success = @unlink($file);
                        if(!$delete_success){
                            $update_ok = false;
                            $log[] = "Can not update file $file. Please set full permission (777)";
                        }
                    }
                    catch (Exception $ex){
                        $update_ok = false;
                        $log[] = "Exception: $file";
                    }
                }
            }

            if($update_ok or true){
                if (@$archive->extract(PCLZIP_OPT_PATH, dirname(__FILE__)) == 0) {
                    $update_ok = false;
                    $log[] = 'Faild unzip: ' . $archive->errorInfo(true);
                } else {
                    $update_ok = true;
                    $log[] = 'Update success!';
                    if (file_exists($iniPath))
                        unlink($iniPath);
                    write_php_ini(array('info' => array('version' => $newest_version)), $iniPath);
                }
            }else{}
        } else {
            $update_ok = false;
            $log[] = "Can't download update file";
        }
    } catch (Exception $e) {
        $update_ok = false;
        $log[] = "Can't download update file (Exception)";
        $log[] = "$e";
    }
    if($update_ok){
        $base_request->success(array('update_info' => create_update_info("Update success $local_version to $newest_version", $local_version), 'log'=>$log));
    }else{
        $base_request->error($base_request->create_error('auto_update', 'Update fail. Please check log'), array('log'=>$log));
    }
}

function create_update_info($message, $version){
    return array('message' => $message, 'version' => $version);
}

function deleteDir($dirPath) {
    if (!is_dir($dirPath)) {
        //throw new InvalidArgumentException("$dirPath must be a directory");
        return true;
    }
    if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
        $dirPath .= '/';
    }
    $files = glob($dirPath . '*', GLOB_MARK);
    foreach ($files as $file) {
        if (is_dir($file)) {
            deleteDir($file);
        } else {
            $delete_file_success = @unlink($file);
            if(!$delete_file_success)
                return false;
        }
    }
    $delete_dir_success = @rmdir($dirPath);
    if(!$delete_dir_success)
        return false;
    return true;
}

function download_file_from_url($url, $file_name){
    $opts = [
        "http" => [
            "method" => "GET",
            "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) coc_coc_browser/87.0.152 Chrome/81.0.4044.152 Safari/537.36\r\n" .
                "Accept: */*\r\n"
                ."Accept: */*\r\n"
                ."Accept-Encoding: gzip, deflate, br\r\n"
        ],
        "https" => [
            "method" => "GET",
            "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) coc_coc_browser/87.0.152 Chrome/81.0.4044.152 Safari/537.36\r\n" .
                "Accept: */*\r\n"
                ."Accept: */*\r\n"
                ."Accept-Encoding: gzip, deflate, br\r\n"
        ],
        'ssl' => [
            // set some SSL/TLS specific options
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ];

    $context = stream_context_create($opts);

    $content = @file_get_contents($url, false, $context);
    if($content != false) {
        file_put_contents($file_name, $content);
        return true;
    }else{
        return false;
    }
}


function getHtml3($url)
{
    return file_get_contents($url);
    /*
    $path = "temp.txt";
    $newfname = $path;
    $file = fopen ($url, 'rb');
    if ($file) {
        $newf = fopen ($newfname, 'wb');
        if ($newf) {
            while(!feof($file)) {
                fwrite($newf, fread($file, 1024 * 8), 1024 * 8);
            }
        }
    }
    if ($file) {
        fclose($file);
    }
    if ($newf) {
        fclose($newf);
    }
    return read_file("temp.txt");
    */
}

function getHtml2($url){
    if(download_file_from_url($url, "temp.txt")){
        return read_file("temp.txt");
    }
    else{
        return "Can't download version";
    }
}

function read_file($filePath){
    $fh = fopen($filePath,'r');
    $res = '';
    while ($line = fgets($fh)) {
        $res=$res . $line;
    }
    fclose($fh);
    return $res;
}

function getHtml($url, $post = null) {
    if(function_exists('curl_init') && function_exists('curl_setopt')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        if (!empty($post)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
    else{
        return getHtml3($url);
    }
}

function write_php_ini($array, $file)
{
    $res = array();
    foreach($array as $key => $val)
    {
        if(is_array($val))
        {
            $res[] = "[$key]";
            foreach($val as $skey => $sval) $res[] = "$skey = ".(is_numeric($sval) ? $sval : '"'.$sval.'"');
        }
        else $res[] = "$key = ".(is_numeric($val) ? $val : '"'.$val.'"');
    }
    return safefilerewrite($file, implode("\r\n", $res));
}

function safefilerewrite($fileName, $dataToSave)
{
    if ($fp = fopen($fileName, 'w')) {
        $startTime = microtime(TRUE);
        do {
            $canWrite = flock($fp, LOCK_EX);
            // If lock not obtained sleep for 0 - 100 milliseconds, to avoid collision and CPU load
            if (!$canWrite) usleep(round(rand(0, 100) * 1000));
        } while ((!$canWrite) and ((microtime(TRUE) - $startTime) < 5));

        //file was locked so now we can store information
        if ($canWrite) {
            fwrite($fp, $dataToSave);
            flock($fp, LOCK_UN);
        }
        fclose($fp);
        return true;
    } else {
        //console_log('Khong ghi duoc file ini: '.$fileName);
        //console_log('Chi tiet loi: ' . json_encode(error_get_last()));
        return false;
    }
}
class base_request
{
    public function __construct(){

    }

    public function success($data = NULL)
    {
        $this->send_response(200, "success", $data);
    }

    public function warning($warning = NULL, $data=null)
    {
        $this->send_response(200, "Warning", $data, $warning);
    }

    public function error($error = NULL, $data=NULL)
    {
        $this->send_response(200, "Error", $data, NULL, $error);
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
        $encoded = json_encode($value, $options, $depth);
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
                    return 'UTF8 encoding error'; // or trigger_error() or throw new Exception()
                }
                return $this->safe_json_encode($clean, $options, $depth, true);
            default:
                return 'Unknown error'; // or trigger_error() or throw new Exception()
    
        }
    }

    public function send_response($status_code, $status_text = "success", $data = NULL, $warning = NULL, $error = NULL)
    {
        header($this->_build_http_header_string($status_code));
        header("Content-Type: application/json");
        $data_response =array(
            "status" => $status_text,
            "data" => $data,
            "warning" => $warning,
            "error" => $error
        );
        $response = json_encode($data_response, JSON_INVALID_UTF8_IGNORE);
        $last_error = json_last_error();
        switch ($last_error) {
            case JSON_ERROR_UTF8:
                $clean = $this->utf8ize($data_response);
                $response = $this->safe_json_encode($clean, 0, 512, true);
                break;
            // default:
            //     $response = 'Unknown error'; // or trigger_error() or throw new Exception()
        }

        echo $response;
        die();
    }

    public function create_error($code, $message){
        return array(
            'code' => $code,
            'message' => $message
        );
    }

    public function create_warning($code, $message){
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
}