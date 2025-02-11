CREATE FUNCTION `delete_product_by_sku`(_sku TINYTEXT, _delete_parent BIT, _delete_variation BIT)
RETURNS INT
BEGIN
	SET @count = 0;
	SET @id_product = (SELECT `post_id` FROM `wp_postmeta` WHERE `meta_key` = '_sku' AND `meta_value` = _sku LIMIT 1);
    
    WHILE(@id_product IS NOT NULL) DO
        SET @count = @count + (SELECT delete_product_by_id(@id_product, _delete_parent, _delete_variation));

        SET @id_product = (SELECT `post_id` FROM `wp_postmeta` WHERE `meta_key` = '_sku' AND `meta_value` = _sku LIMIT 1);
    END WHILE;

	-- DELETE FROM `codethue_sku_imported` WHERE `sku` = _sku;
    
    RETURN @count;
END;