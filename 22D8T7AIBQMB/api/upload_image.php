<?php
include "base_request.php";

class upload_image extends base_request
{
    function from_url($list_url, $debug = false)
    {
        $res = array('data' => array());

        $iniPath = '../config/database_config.ini';
        if (!file_exists($iniPath)) {
            $this->error($this->create_error('not_found_ini', 'upload_image: Không tìm thấy file ini'));
        }
        $ini = parse_ini_file($iniPath);

        foreach ($list_url as $url) {
            try {
                // ini_set('user_agent','Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) coc_coc_browser/98.0.168 Chrome/92.0.4515.168 Safari/537.36');
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
                //die(json_encode($opts));

                $curUrl = trim($url['url']);
// die($curUrl);
                $context = stream_context_create($opts);

                // Lấy và in ra header của phản hồi
                // $response_headers = get_headers($curUrl, 1); // 1 để trả về dạng mảng với các header giống nhau được gộp lại
                // print_r($response_headers);

                $content = false;
                if($debug)
                    $content = file_get_contents(str_replace(' ', '%20', $curUrl), false, $context);
                else
                    $content = @file_get_contents(str_replace(' ', '%20', $curUrl), false, $context);
// die($content);
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

                    $id_multisite = '';// 'sites/19/';// 07.2.2022 Thêm link custom cho multisite SP22_fbTungBui https://www.facebook.com/messages/t/4605557142882227/
                    if(isset($url['id_multisite']))
                        $id_multisite = $url['id_multisite'] . '/';

                    $path_dir = str_replace("//", "/", "/wp-content/uploads/$id_multisite" . date('Y') . '/' . date('m'));
                    $full_path_upload_folder = str_replace("//", "/", "../../../../$path_dir/");
                    if (!file_exists($full_path_upload_folder)) {
                        mkdir($full_path_upload_folder, 0755, true); //Cần quyền với thưu mục 775 Chown -R www-data:www-data
                    }


                    if (!isset($url['file_name']) || $url['file_name'] == '') {
                        $count = 1;
                        $temp = $id_file;
                        while (file_exists("$full_path_upload_folder/$temp"))
                            $temp = $this->add_bumber($id_file, $count++);
                        $id_file = $temp;
                    }

                    if (file_put_contents("$full_path_upload_folder/$id_file", $content)) {
                        $res['data'][] = $ini['site_url'] . $path_dir . "/$id_file";
                    }
                } else {
                    if($debug){
                        $error = error_get_last();
                        var_dump($error);
                        die('content false');
                    }
                    else
                        $res['data'][] = $url['url'];
                }
            } catch (Exception $e) {
                if($debug)
                    die($e->getMessage()); // Test
                else
                    $res['data'][] = $url['url'];
            }
        }

        $this->success($res);
    }

    function from_base64($list_base64)
    {
        $res = array('data' => array());

        $iniPath = '../config/database_config.ini';
        if (!file_exists($iniPath)) {
            $this->error($this->create_error('not_found_ini', 'Không tìm thấy file ini'));
        }
        $ini = parse_ini_file($iniPath);

        foreach ($list_base64 as $base64) {
            try {
                $base64_string = $base64['base64'];
                $base64_string_of_image = '';

                $image_type = 'jpg';
                if (strpos($base64_string, ';base64,') && strpos($base64_string, 'image/')) {
                    $image_parts = explode(";base64,", $base64_string);
                    if (!$image_type_aux = explode("image/", $image_parts[0]))
                        $image_type_aux[1] = array('', '.jpg');
                    $image_type = $image_type_aux[1];
                    $base64_string_of_image = $image_parts[1];
                } else {
                    $image_type = 'jpg';
                    $base64_string_of_image = $base64_string;
                }

                $id_file = '';
                if (isset($base64['file_name']) && $base64['file_name'] != '')
                    $id_file = $base64['file_name'];
                else
                    $id_file = uniqid() . '.' . $image_type;

                $id_multisite = '';// 'sites/19/';// 07.2.2022 Thêm link custom cho multisite SP22_fbTungBui https://www.facebook.com/messages/t/4605557142882227/
                if(isset($base64['id_multisite']))
                    $id_multisite = $base64['id_multisite'] . '/';
                $path_dir = str_replace("//", "/", "/wp-content/uploads/$id_multisite" . date('Y') . '/' . date('m'));
                $full_path_upload_folder = str_replace("//", "/", "../../../../$path_dir/");
                if (!file_exists($full_path_upload_folder)) {
                    mkdir($full_path_upload_folder, 0755, true); //Cần quyền với thưu mục 775 Chown -R www-data:www-data
                }

                if ($image_base64 = base64_decode($base64_string_of_image)) {
                    $file = "$full_path_upload_folder/$id_file";
                    if (file_put_contents($file, $image_base64)) {
                        $res['data'][] = $ini['site_url'] . $path_dir . "/$id_file";
                    }
                }
            } catch (Exception $e) {
                $this->error($this->create_error('process_base64', 'Xảy ra lỗi khi xử lý base64. Chi tiết xem tại trường data'), $e);
            }
        }

        $this->success($res);
    }

    function add_bumber($id_file, $num)
    {
        $array = explode('.', $id_file);
        $extension = end($array);
        $id_file = basename($id_file, '.' . $extension);
        $id_file .= '-' . $num; //rand(1,1000);
        return "$id_file.$extension";
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
        $id_file = basename($id_file, '.' . $extension); //uniqid();
        return "$id_file.$extension";
    }
}

$upload_image = new upload_image();

if (isset($_GET['action'])) {
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'];
    $debug = false;
    if(isset($_GET['debug']))
        $debug = true;

    if ($method == 'POST' || $method == 'post') {

        $json = file_get_contents('php://input');
        $item = json_decode($json, true);

        if ($action == 'from_url') {
            $upload_image->from_url($item, $debug);
        } else if ($action == 'from_base64') {
            $upload_image->from_base64($item);
        }else{
            $upload_image->error($upload_image->create_error('not_support_action', 'Support action: from_url, from_base64'));
        }
    } else {
        $upload_image->error($upload_image->create_error('not_support_method', 'Please use POST method'));
    }
} else {
    $upload_image->error($upload_image->create_error('not_found_action', 'Not found action param in url'));
}