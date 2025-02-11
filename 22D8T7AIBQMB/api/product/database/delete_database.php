<?php
include "../share_function_database.php";

class delete_database extends share_function_dabase{
    function delete_product_by_sku($db, $sku, $delete_parent, $delete_variation = false){
        $sql = "select delete_product_by_sku('$sku', $delete_parent, $delete_variation) as res;";
        if($result = @$db->query($sql)) {
            $res = $result->fetch_object()->res;
            $result->free_result();
            return $res;
        }else{
            $this->error($db, 'delete_product_by_sku', $sql);
        }
    }

    function delete_product_by_id($db, $id, $delete_parent, $delete_variation){
        if($id == null)
            $id = 'null';

        $sql = "select delete_product_by_id($id, $delete_parent, $delete_variation) as res;";
        // die($sql);
        if($result = @$db->query($sql)) {
            $res = $result->fetch_object()->res;
            $result->free_result();
            return $res;
        }else{
            $this->error($db, 'delete_product_by_id', $sql);
        }
    }
}

$db_delete = new delete_database();