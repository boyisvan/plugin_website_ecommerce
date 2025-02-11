CREATE FUNCTION `update_variant_fast_api`(_id_variant BIGINT(20),
_price DOUBLE, _stock_status MEDIUMTEXT,
 _lstAttribute MEDIUMTEXT, _lstValue MEDIUMTEXT,
 _variation_title TEXT, _description TEXT 
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

    iterator:
		LOOP
		  IF CHAR_LENGTH(TRIM(_lstAttribute)) = 0 OR _lstAttribute IS NULL THEN
			LEAVE iterator;
		  END IF;
		  SET _next = SUBSTRING_INDEX(_lstAttribute,';',1);
		  SET _nextlen = CHAR_LENGTH(_next);
		  SET _value = TRIM(_next);
          
          SET _next2 = SUBSTRING_INDEX(_lstValue,';',1);
		  SET _nextlen2 = CHAR_LENGTH(_next2);
		  SET _value2 = TRIM(_next2);
          
          SET @metKey = concat('attribute_', REPLACE(_value, ' ', '-'));
          IF(EXISTS(SELECT * FROM `wp_postmeta` WHERE `post_id` = _id_variant AND `meta_key` collate utf8mb4_unicode_ci = @metKey LIMIT 1))
          THEN
			  UPDATE `wp_postmeta`
              SET    `meta_value` = _value2
              WHERE  `post_id` = _id_variant AND `meta_key` collate utf8mb4_unicode_ci = @metKey;
		  ELSE
			  INSERT INTO `wp_postmeta` (`post_id`,   `meta_key`,      `meta_value`)
			  VALUES                     (_id_variant, @metKey,         _value2);
          END IF;
          
		  UPDATE `wp_posts` SET `post_excerpt` = CONCAT(CONCAT(_value, ': '), _value2) WHERE ID=_id_variant;
          
          SET _lstAttribute = INSERT(_lstAttribute,1,_nextlen + 1,'');
		  SET _count = _count + 1;
          
          SET _lstValue = INSERT(_lstValue,1,_nextlen2 + 1,'');
		  SET _count2 = _count2 + 1;
		END LOOP;
    
    return _id_variant;
END;