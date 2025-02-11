<?php
/*Note: Full permission for this folder => sudo chown -R www-data:www-data {path}*/

$zip_file          = './config/php.zip';
$iniPath           = './config/info.ini';
$local_version     = "v2.1.4.16.1.2025.update_new_order_key";
if(file_exists($iniPath)) {
    $ini = parse_ini_file('./config/info.ini');
    $local_version = $ini['version'];
}

echo "Local vertsion: " . $local_version . "<br/>";

// $url_check_version = "https://sites.google.com/site/supperwooimporter/home/version.txt?attredirects=0&d=1";
// $url_download = "https://sites.google.com/site/supperwooimporter/home/php.zip?attredirects=0&d=1";

$url_check_version = "https://github.com/ngochoaitn/update-gpm-woocommerce-hooking/releases/download/latest/version.txt";
$url_download = "https://github.com/ngochoaitn/update-gpm-woocommerce-hooking/releases/download/latest/hooking-update.zip";

$newest_version = getHtml($url_check_version);

echo "Newest vertsion: " . $newest_version . "<br/>";
if ($local_version == $newest_version) {
    echo "Dont't need update" . "<br/>";
} else {
    echo "Need update" . "<br/>";

    try {
        if (download_file_from_url($url_download, $zip_file)) {
            require_once('libs/pclzip/pclzip.lib.php');

            //Vì có host không cài extension ArchiveZIP nên cần dùng thư viện ngoài
            $archive = new PclZip($zip_file);

            $dirs = array("sql", "default_struct");
            foreach($dirs as $dir) {
                    try {
                        deleteDir($dir);
                    }
                    catch (Exception $ex){}
            }

            $files = array("setup.php", "info.php", "config/systemConfig.php");
            foreach($files as $file){
                if(file_exists($file)){
                    try {
                        @unlink($file);
                    }
                    catch (Exception $ex){}
                }
            }

            if ($archive->extract(PCLZIP_OPT_PATH, dirname(__FILE__)) == 0) {
                echo 'Faild unzip: ' . $archive->errorInfo(true) . "<br/>";
            } else {
                echo 'Update success!';
                if (file_exists($iniPath))
                    unlink($iniPath);
                write_php_ini(array('info' => array('version' => $newest_version)), $iniPath);
            }
        } else {
            echo "Can't download update file" . "<br/>";
        }
    } catch (Exception $e) {
        echo "Can't download update file (Exception)" . "<br/>";
    }

}

function deleteDir($dirPath) {
    if (! is_dir($dirPath)) {
        //throw new InvalidArgumentException("$dirPath must be a directory");
    }
    if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
        $dirPath .= '/';
    }
    $files = glob($dirPath . '*', GLOB_MARK);
    foreach ($files as $file) {
        if (is_dir($file)) {
            deleteDir($file);
        } else {
            @unlink($file);
        }
    }
    @rmdir($dirPath);
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
        console_log('Khong ghi duoc file ini: '.$fileName);
        console_log('Chi tiet loi: ' . json_encode(error_get_last()));
        return false;
    }
}
