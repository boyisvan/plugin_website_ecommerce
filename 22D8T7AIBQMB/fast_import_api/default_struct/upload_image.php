<?php
include "base_request.php";

class upload_image extends base_request
{
    function from_url($list_url)
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

                $context = stream_context_create($opts);

                $content = @file_get_contents(str_replace(' ', '%20', $url['url']), false, $context);
                if($content != false){
                    $id_file = $this->build_file_name_from_url($url['url']);
                    $path_dir = "/wp-content/uploads/".date('Y').'/'.date('m');
                    if (!file_exists("../../$path_dir/")) {
                        mkdir("../../$path_dir/", 0755, true);
                    }

                    if (file_put_contents("../../$path_dir/$id_file", $content)) {
                        $res['data'][] = $path_dir . "/$id_file";
                    }
                }
                else{
                    $res['data'][] = $url['url'];
                }
            } catch (Exception $e) {
                $res['data'][] = $url['url'];
            }
        }

        $this->response(200, $res);
    }

    function from_base64($list_base64){
        $res = array('data' => array());

        foreach ($list_base64 as $base64) {
            try {
                $id_file = uniqid();
                $path_dir = "/wp-content/uploads";//Không tạo thư mục theo tháng, năm vì liên quan đến phân quyền
                $base64_string=$base64['base64'];
                $base64_string_of_image = '';
                $image_type = 'jpg';
                if (strpos($base64_string, ';base64,') && strpos($base64_string, 'image/')){
                    $image_parts = explode(";base64,", $base64_string);
                    if (!$image_type_aux = explode("image/", $image_parts[0]))
                        $image_type_aux[1] = array('', '.jpg');
                    $image_type = $image_type_aux[1];
                $base64_string_of_image = $image_parts[1];
                }
                else{
                    $image_type = 'jpg';
                    $base64_string_of_image = $base64_string;
                }

                if($image_base64 = base64_decode($base64_string_of_image)) {
                    $file = "../../$path_dir/$id_file." . $image_type;
                    if (file_put_contents($file, $image_base64)) {
                        $res['data'][] = $path_dir . "/$id_file." . $image_type;
                    }
                }

            } catch (Exception $e) {
                //$res['data'][] = $base64['url'];
            }
        }

        $this->response(200, $res);
    }

    function build_file_name_from_url($url){
        $array = explode('.', $url);
        $extension = end($array);
        $id_file = str_replace('%20', '-', $url);
        $id_file = str_replace(' ', '-', $id_file);
        $id_file = str_replace(';', '', $id_file);
        $id_file = str_replace("'", '', $id_file);
        $id_file = basename($id_file, '.'.$extension);//uniqid();
        $id_file = str_replace('#', '-', $id_file);
        $id_file .= '-'.rand(1,1000);
        // while(strpos($id_file, '--'))
        //     $id_file = str_replace('--', '-', $id_file);
        return "$id_file.$extension";
    }
}

$upload_image = new upload_image();

if(isset($_GET['action'])) {
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'];

    if ($method == 'POST' || $method == 'post') {

        $json = file_get_contents('php://input');
        $item = json_decode($json, true);

        if($action == 'from_url') {
            $upload_image->from_url($item);
        } else if($action == 'from_base64'){
            $upload_image->from_base64($item);
        }
    }else{
        $upload_image->response(406, 'Su dung POST method');
    }
}else {
    $upload_image->response(406, 'Thieu param: action');
}