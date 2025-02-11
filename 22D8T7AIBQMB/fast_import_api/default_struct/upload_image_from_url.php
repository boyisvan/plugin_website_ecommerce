<?php
include "base_request.php";

class upload_image_from_url extends base_request
{
    function download_image($list_url)
    {
        $res = array('data' => array());

        foreach ($list_url as $url) {
            try {
                // Create a stream
                $opts = [
                    "http" => [
                        "method" => "GET",
                        "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.5005.63 Safari/537.36\r\n"
                            . "Accept: */*\r\n"
                            . "Accept-Encoding: gzip, deflate, br\r\n"
                            . "referer:". $url['url'],
                            // 'protocol_version' => '1.1'
                    ],
                    "https" => [
                        "method" => "GET",
                        "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.5005.63 Safari/537.36\r\n"
                            . "Accept: */*\r\n"
                            . "Accept-Encoding: gzip, deflate, br\r\n"
                            . "referer:". $url['url'],
                            // 'protocol_version' => '1.1'
                    ],
                    'ssl' => [
                        // set some SSL/TLS specific options
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ];

                $curUrl = trim($url['url']);

                $context = stream_context_create($opts);

                $content = @file_get_contents(str_replace(' ', '%20', $curUrl), false, $context);

                if ($content != false) {

                    $paramInUrl = '';
                    if (strpos($curUrl, '?') != false && strpos($curUrl, '=') != false) {
                        $temp = explode('?', $curUrl);
                        $paramInUrl = end($temp);
                        $curUrl = str_replace('?' . $paramInUrl, '', $curUrl);
                    }

                    $id_file = $this->build_file_name_from_url($curUrl);
                    $path_dir = "/wp-content/uploads/" . date('Y') . '/' . date('m');
                    if (!file_exists("../../$path_dir/")) {
                        mkdir("../../$path_dir/", 0755, true); //Cần quyền với thưu mục 775 Chown -R www-data:www-data
                    }

                    $iniPath = '../../config/database_config.ini';
                    if (!file_exists($iniPath)) {
                        die('upload_image_from_url: Không tìm thấy file ini');
                    }
                    $ini = parse_ini_file($iniPath);

                    $count = 1;
                    $temp = $id_file;
                    while (file_exists("../../$path_dir/$temp"))
                        $temp = $this->add_bumber($id_file, $count++);
                    $id_file = $temp;

                    if (file_put_contents("../../$path_dir/$id_file", $content)) {
                        $res['data'][] = $ini['site_url'] . $path_dir . "/$id_file";
                    }
                } else {

                    $res['data'][] = $url['url'];
                }
            } catch (Exception $e) {
                $res['data'][] = $url['url'];
            }
        }

        $this->response(200, $res);
    }

    function build_file_name_from_url($url)
    {
        $base = basename($url);

        $array = explode('.', $base);
        $extension = end($array);
        $allowExtension = array('jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff', 'svg');
        if ($extension == $base || !in_array($extension, $allowExtension))
            $extension = "jpg";
        $id_file = str_replace('%20', '-', $url);
        $id_file = str_replace(' ', '-', $id_file);
        $id_file = str_replace(';', '', $id_file);
        $id_file = str_replace('%', '-', $id_file);
        $id_file = str_replace("'", '', $id_file);
        $id_file = str_replace('#', '-', $id_file);
        $id_file = basename($id_file, '.' . $extension); //uniqid();
        return "$id_file.$extension";
    }

    function add_bumber($id_file, $num)
    {
        $array = explode('.', $id_file);
        $extension = end($array);
        $id_file = basename($id_file, '.' . $extension);
        $id_file .= '-' . $num; //rand(1,1000);
        return "$id_file.$extension";
    }

    function downloadFile($url, $filename) {
        $cURL = curl_init($url);
        curl_setopt_array($cURL, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FILE           => fopen("$filename", "w+"),
            CURLOPT_USERAGENT      => $_SERVER["HTTP_USER_AGENT"]
        ]);
    
        $data = curl_exec($cURL);
        curl_close($cURL);
        header("Content-Disposition: attachment; filename=\"$filename\"");
    }

    function downloadFile2($url, $filename){
        $ch = curl_init($url);
        // curl_setopt($ch, CURLOPT_URL, $source);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.5005.132 Safari/537.36');
        curl_setopt($ch, CURLOPT_FILE, fopen("$filename", "w+"));
        $data = curl_exec ($ch);
        curl_close ($ch);

        // $destination =  $filename;
        // $file = fopen($destination, "w+");
        // fputs($file, $data);
        // fclose($file);
    }

    function downloadFile3($url, $filename){
        $session = curl_init($url); 
    
        // Initialize the directory name where the file will be saved
          
        // Save file
        $save = $filename; 
          
        // Open file
        $file = fopen($save, 'wb'); 
          
        // defines the options for the transfer
        curl_setopt($session, CURLOPT_FILE, $file); 
        curl_setopt($session, CURLOPT_HEADER, 0); 
          
        curl_exec($session); 
          
        curl_close($session); 
          
        fclose($file); 
    }

    function download_image_custom_name($list_url, $debug = false)
    {
        $res = array('data' => array());

        foreach ($list_url as $url) {
            try {
                // Create a stream
                $opts = [
                    "http" => [
                        "method" => "GET",
                        "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.5005.63 Safari/537.36\r\n"
                            . "Accept: */*\r\n"
                            . "Accept-Encoding: gzip, deflate, br\r\n"
                            . "referer:". $url['url'],
                            // 'protocol_version' => '1.1'
                    ],
                    "https" => [
                        "method" => "GET",
                        "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.5005.63 Safari/537.36\r\n"
                            . "Accept: */*\r\n"
                            . "Accept-Encoding: gzip, deflate, br\r\n"
                            . "referer:". $url['url'],
                            // 'protocol_version' => '1.1'
                    ],
                    'ssl' => [
                        // set some SSL/TLS specific options
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ];

                $curUrl = trim($url['url']);

                $context = stream_context_create($opts);


                $content = false;
                if(!$debug)
                    $content = @file_get_contents(str_replace(' ', '%20', $curUrl), false, $context);
                else
                    $content = file_get_contents(str_replace(' ', '%20', $curUrl), false, $context);

                if ($content != false) {

                    $paramInUrl = '';
                    if (strpos($curUrl, '?') != false && strpos($curUrl, '=') != false) {
                        $temp = explode('?', $curUrl);
                        $paramInUrl = end($temp);
                        $curUrl = str_replace('?' . $paramInUrl, '', $curUrl);
                    }

                    $id_file = '';
                    if (isset($url['file_name']) && $url['file_name'] != '')
                        $id_file = $url['file_name'];
                    else
                        $id_file = $this->build_file_name_from_url($curUrl);
                    $id_file = str_replace('#', '-', $id_file);
                    $path_dir = "/wp-content/uploads/" . date('Y') . '/' . date('m');
                    if (!file_exists("../../$path_dir/")) {
                        mkdir("../../$path_dir/", 0755, true); //Cần quyền với thưu mục 775 Chown -R www-data:www-data
                    }

                    $iniPath = '../../config/database_config.ini';
                    if (!file_exists($iniPath)) {
                        die('upload_image_from_url: Không tìm thấy file ini');
                    }
                    $ini = parse_ini_file($iniPath);
                    if (!isset($url['file_name']) || $url['file_name'] == '') {
                        $count = 1;
                        $temp = $id_file;
                        while (file_exists("../../$path_dir/$temp"))
                            $temp = $this->add_bumber($id_file, $count++);
                        $id_file = $temp;
                    }
                    // $this->downloadFile2('https://ganebet.com/wp-content/uploads/bori_2/162202317504ab39eed9.jpeg', "oklaa.jpg");

                    if (file_put_contents("../../$path_dir/$id_file", $content)) {
                        $res['data'][] = $ini['site_url'] . $path_dir . "/$id_file";
                    }
                } else {
                    if($debug){
                        $error = error_get_last();
                        var_dump($error);
                        die('content false');
                    }
                    $res['data'][] = $url['url'];
                }
            } catch (Exception $e) {
                if($debug)
                    die($e->getMessage()); // Test
                else 
                    $res['data'][] = $url['url'];
            }
        }

        $this->response(200, $res);
    }
}

$dowload_image = new upload_image_from_url();

//if(isset($_GET['action'])) {
$method = $_SERVER['REQUEST_METHOD'];
//$action = $_GET['action'];
$debug = false;
if(isset($_GET['debug']))
    $debug = true;
if ($method == 'POST' || $method == 'post') {
    $json = file_get_contents('php://input');
    $list_url = json_decode($json, true);

    $dowload_image->download_image_custom_name($list_url, $debug);
} else {
    $dowload_image->response(406, 'Su dung POST method');
}
//}else {
  //  $dowload_image->response(406, 'Thieu param: action');
//}