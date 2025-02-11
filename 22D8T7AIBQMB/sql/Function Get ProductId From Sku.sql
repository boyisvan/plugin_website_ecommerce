CREATE FUNCTION `get_productid_from_sku`(_sku TINYTEXT)
RETURNS BIGINT(20)
BEGIN
	SET @id_product = (SELECT `post_id` FROM `wp_postmeta` WHERE `meta_key` = '_sku' AND `meta_value` = _sku LIMIT 1);
    RETURN @id_product;
END;