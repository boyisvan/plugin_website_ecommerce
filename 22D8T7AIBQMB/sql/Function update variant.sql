CREATE FUNCTION `update_variant`(_id_variant BIGINT(20),
_price DOUBLE, _stock_status MEDIUMTEXT
 ,_description TEXT -- 24/5/5020: Thêm để hiển thị title, description
 )
RETURNS BIGINT(20)
BEGIN
	DECLARE _next TEXT DEFAULT NULL;
	DECLARE _nextlen INT DEFAULT NULL;
	DECLARE _value TEXT DEFAULT NULL;
    DECLARE _count INT DEFAULT 0;
    
    DECLARE _next2 TEXT DEFAULT NULL;
	DECLARE _nextlen2 INT DEFAULT NULL;
	DECLARE _value2 TEXT DEFAULT NULL;
    DECLARE _count2 INT DEFAULT 0;

	UPDATE `wp_postmeta` SET `meta_value` = _price
	WHERE `post_id` = _id_variant AND (`meta_key` = '_regular_price' OR `meta_key` = '_price');

    UPDATE `wp_postmeta` SET `meta_value` = _stock_status
    WHERE `post_id` = _id_variant AND `meta_key` = '_stock_status';
    
    UPDATE `wp_postmeta` SET `meta_value` = _variation_title
    WHERE `post_id` = _id_variant AND `meta_key` = '_title';
    
    UPDATE `wp_postmeta` SET `meta_value` = _description
    WHERE `post_id` = _id_variant AND `meta_key` = '_variation_description';
    return _id_variant;
END;