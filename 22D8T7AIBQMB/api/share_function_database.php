<?php
class share_function_dabase{
    function error($db, $code, $query){
        require_once "base_request.php";
        $base_request = new base_request();
        $error = array();
        if($db != null)
            $error = $base_request->create_error($code.'_'.$db->errno, "function $code error (" . $db->errno . "): " . $db->error);
        else
            $error['code'] = $code;
        if($query != null && $query != '')
            $error['query'] = $query;
        $base_request->error($error);
    }
}

$share_function_database = new share_function_dabase();