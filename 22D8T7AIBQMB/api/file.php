<?php
    include "base_request.php";



    class file extends base_request {
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
                    $this->deleteDir($file);
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

        function upload_theme(){
            $iniPath = '../config/database_config.ini';
            if (!file_exists($iniPath)) {
                $this->error($this->create_error('not_found_ini', 'upload_theme: Không tìm thấy file ini'));
            }
            $ini = parse_ini_file($iniPath);

            // die(dirname(__FILE__));
            // Kiểm tra có dữ liệu fileupload trong $_FILES không
            // Nếu không có thì dừng
            if (!isset($_FILES["fileupload"]))
                $this->error($this->create_error('invalid_data_struct', 'Không đọc được $_FILES'));

            // Kiểm tra dữ liệu có bị lỗi không
            if ($_FILES["fileupload"]['error'] != 0)
                $this->error($this->create_error('invalid_data', 'Dữ liệu upload bị lỗi'));

            //Thư mục bạn sẽ lưu file upload
            $target_dir    = "../../../themes/";
            //Vị trí file lưu tạm trong server (file sẽ lưu trong uploads, với tên giống tên ban đầu)
            $file_name = basename($_FILES["fileupload"]["name"]);
            $target_file   = $target_dir . $file_name;
            //Lấy phần mở rộng của file (jpg, png, ...)
            $fileType = pathinfo($target_file,PATHINFO_EXTENSION);
            $file_name_without_extension = str_replace('.'.$fileType, '', $file_name);
            if($fileType != "zip")
                $this->error($this->create_error('not_allow', 'Định dạng tệp không được phép'));

            // Xử lý di chuyển file tạm ra thư mục cần lưu trữ, dùng hàm move_uploaded_file
            if (move_uploaded_file($_FILES["fileupload"]["tmp_name"], $target_file)){
                require_once('../libs/pclzip/pclzip.lib.php');
                $archive = new PclZip($target_file);
                $this->deleteDir($target_dir . $file_name_without_extension);
                if ($archive->extract(PCLZIP_OPT_PATH, $target_dir . $file_name_without_extension) == 0) {
                    $this->error($this->create_error('fail_unzip', 'Giải nén themes thất bại'));
                } else {
                    $url_file = $ini['site_url'] . '/wp-content/themes/' . $file_name;
                    $this->success(array('url_file' => $url_file));
                }
            }
            else
                $this->error($this->create_error('move_uploaded_file_err', 'Xử lý tải lên thất bại'));
        }
    }

    $request = new file();
    if (isset($_GET['action'])) {
        $action = $_GET['action'];
        $method = $_SERVER['REQUEST_METHOD'];
        if ($method == 'POST' || $method == 'post') {
            switch($action){
                case 'upload_theme':
                    $request->upload_theme();
                    break;
                default:
                    $request->error($request->create_error('not_support_action', 'Support action: upload_theme'));
                break;
            }
        } else {
            $request->error($request->create_error('not_support_method', 'Please use POST method'));
        }
    } else {
        $request->error($request->create_error('not_found_action', 'Not found action param in url'));
    }
?>