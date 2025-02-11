<?php
//echo 'hi2';
class systemConfig{
    function connectDB(){
        //echo 'hi3';
        $iniPath = 'database_config.ini';
        $ini = parse_ini_file($iniPath);
        $dbContext = new mysqli($ini['db_host'], $ini['db_user'], $ini['db_password'], $ini['db_name']);
        
        $charset = '';
        $collate = '';
        if(isset($ini['charset']))
            $charset = $ini['charset'];
        if(isset($ini['collate']))
            $collate = $ini['collate'];

        if ($dbContext->connect_errno) {
            die("connectDB() => Kết nối CSDL: db_name=" . $ini['db_name'] . " lỗi (" . $dbContext->connect_errno . "): " . $dbContext->connect_error . '\n');
            // return null;
        } else {
            $this->try_query($dbContext, "SET SESSION sql_mode='NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';");
            //$this->try_query($dbContext, "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");//Nếu database là utf8 thì cũng phải chạy code này
            $set_charset_succeeded = false;
            // die($charset);
            // if($charset != '' && substr($charset, 0, 7) == 'utf8mb4')
            //     $set_charset_succeeded = @$dbContext->set_charset($charset);
            if($set_charset_succeeded){
                $query = '';
                if($charset != '')
                    $query = "SET NAMES '$charset'";
                if($collate != '')
                    $query .= " COLLATE '$collate';";

                if($query != '')
                    $this->try_query($dbContext, $query);
            }
            return $dbContext;
        }
    }

    function try_query(mysqli $db, $query){
        try{
            @$db->query($query);
        }
        catch (\Exception $ex){
        }
        catch(\Error $e){
        }
        catch (\Throwable $t){
        }
    }
}

$systemConfig = new systemConfig();
$dbContext    = $systemConfig->connectDB();
