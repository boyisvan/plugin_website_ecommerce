CREATE FUNCTION `insert_variant_fast_api`(_sku MEDIUMTEXT, _ten MEDIUMTEXT, _slug TEXT, _moTa LONGTEXT, _gia DOUBLE, _sale_price DOUBLE,
_stockStatus MEDIUMTEXT, _total_sales INT , _tax_status TINYTEXT, _tax_class TINYTEXT,
_manage_stock TINYTEXT, _backorders TINYTEXT, _sold_individually TINYTEXT, _virtual TINYTEXT, _downloadable TINYTEXT,
_download_limit INT, _download_expiry INT, _stock INT,
_listUrlImage MEDIUMTEXT, _list_image_meta LONGTEXT,
_idParent BIGINT(20), _lst_slug_attribute_name MEDIUMTEXT, _lst_slug_attribute_name_value MEDIUMTEXT,
_variation_title TEXT,
_weight DOUBLE, _height DOUBLE, _width DOUBLE, _length DOUBLE)
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

	SET @res = insert_product_fast_api(_sku, _ten, _slug, _moTa, '', _gia,  _sale_price,
	_stockStatus, _total_sales, _tax_status, _tax_class,
	_manage_stock, _backorders, _sold_individually, _virtual, _downloadable,
	_download_limit, _download_expiry, _stock,
	null, '', _listUrlImage, _list_image_meta, '',
	_idParent, 'publish',
    _weight, _height, _width, _length);
    
    INSERT INTO `wp_postmeta` (`post_id`,   `meta_key`,      `meta_value`)
	VALUES                     (_idParent, '_price', _gia);
    
    -- INSERT INTO `wp_postmeta` (`post_id`,   `meta_key`,      `meta_value`)
	-- VALUES                     (@res, '_weight', 0);
    
    SET @siteurl = (SELECT option_value FROM `wp_options` WHERE `option_name` = 'siteurl' LIMIT 1);
    set @guid = concat(@siteurl, '/?post_type=product_variation&p=', @res);
    UPDATE `wp_posts` SET guid=@guid WHERE ID = @res;

    INSERT INTO `wp_postmeta` (`post_id`,   `meta_key`,      `meta_value`)
	VALUES                     (@res, '_variation_description', _moTa);
    
    INSERT INTO `wp_postmeta` (`post_id`,   `meta_key`,      `meta_value`)
	VALUES                     (@res, '_title', _variation_title);

    SET @post_excertpt = '';
    iterator:
		LOOP
		  IF CHAR_LENGTH(TRIM(_lst_slug_attribute_name)) = 0 OR _lst_slug_attribute_name IS NULL THEN
			LEAVE iterator;
		  END IF;
		  SET _next = SUBSTRING_INDEX(_lst_slug_attribute_name,';',1);
		  SET _nextlen = CHAR_LENGTH(_next);
		  SET _value = TRIM(_next);
          
          SET _next2 = SUBSTRING_INDEX(_lst_slug_attribute_name_value,';',1);
		  SET _nextlen2 = CHAR_LENGTH(_next2);
		  SET _value2 = TRIM(_next2);
          
          INSERT INTO `wp_postmeta` (`post_id`,   `meta_key`,      `meta_value`)
		  VALUES                     (@res,       concat('attribute_pa_', REPLACE(_value, ' ', '-')), _value2);
          
          SET @new_excertpt = CONCAT(CONCAT(_value, ': '), _value2);
          IF(CHAR_LENGTH(@post_excertpt) > 0) THEN
			SET @post_excertpt = CONCAT(@post_excertpt, ', ');
          END IF;
          SET @post_excertpt = CONCAT(@post_excertpt, @new_excertpt);

          SET _lst_slug_attribute_name = INSERT(_lst_slug_attribute_name,1,_nextlen + 1,'');
		  SET _count = _count + 1;
          
          SET _lst_slug_attribute_name_value = INSERT(_lst_slug_attribute_name_value,1,_nextlen2 + 1,'');
		  SET _count2 = _count2 + 1;
		END LOOP;

        UPDATE `wp_posts` SET `post_excerpt` = @post_excertpt WHERE ID=@res;
        
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
    
	UPDATE `wp_wc_product_meta_lookup`
    SET `virtual` = @virtual_lookup,
        `downloadable` = @downloadable_lookup,
        `min_price`=(SELECT MIN(meta_value) FROM `wp_postmeta` WHERE post_id=_idParent AND `meta_key` = '_price' LIMIT 1),
        `max_price` = (SELECT MAX(meta_value) FROM `wp_postmeta` WHERE post_id=_idParent AND `meta_key` = '_price' LIMIT 1),
        `stock_status` = _stockStatus
    WHERE `product_id` = _idParent;
    
    RETURN @res;
END;