CREATE FUNCTION `create_variant`(_ten MEDIUMTEXT, _slug TEXT, _moTa LONGTEXT, _post_excerpt TEXT, _gia DOUBLE,
_virtual TINYTEXT, _downloadable TINYTEXT, _stockStatus TINYTEXT,
_listUrlImage MEDIUMTEXT,  _list_image_meta LONGTEXT,
_idParent BIGINT(20),
_lst_meta_key MEDIUMTEXT, _lst_meta_value MEDIUMTEXT
,_comment_status TINYTEXT, _product_status TINYTEXT, _id_shipping_class TINYTEXT
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

	SET @res = create_product(_ten, _slug, _moTa, _post_excerpt,
	null, '', _listUrlImage, _list_image_meta, '',
	_idParent,
    _lst_meta_key, _lst_meta_value
    ,_comment_status, _product_status, _id_shipping_class);
    SET @siteurl = (SELECT option_value FROM `wp_options` WHERE `option_name` = 'siteurl' LIMIT 1);
    set @guid = concat(@siteurl, '/?post_type=product_variation&p=', @res);
    UPDATE `wp_posts` SET guid=@guid WHERE ID = @res;

	IF(_moTa IS NOT NULL AND _moTa <> '') THEN
		INSERT INTO `wp_postmeta` (`post_id`,   `meta_key`,      `meta_value`)
		VALUES                     (@res, '_variation_description', _moTa);
	END IF;
	SET @downloadable_lookup = 0;
    IF(_downloadable <> 'no')
    THEN
		SET @downloadable_lookup = 1;
    END IF;
    
    SET @virtual_lookup = 0;
    IF(_virtual <> 'no')
    THEN
		SET @virtual_lookup = 1;
    END IF;
    RETURN @res;
END;