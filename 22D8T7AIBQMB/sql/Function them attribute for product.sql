CREATE FUNCTION `create_attribute_for_product`(_id_product BIGINT(20), _label TINYTEXT, _slug TINYTEXT,
_list_terms MEDIUMTEXT, _list_term_slugs MEDIUMTEXT)
RETURNS BIGINT(20)
BEGIN
	DECLARE _next TEXT DEFAULT NULL;
	DECLARE _nextlen INT DEFAULT NULL;
	DECLARE _value TEXT DEFAULT NULL;
    DECLARE _count INT DEFAULT 0;
	DECLARE _id BIGINT(20);
    DECLARE _next2 TEXT DEFAULT NULL;
	DECLARE _nextlen2 INT DEFAULT NULL;
	DECLARE _value2 TEXT DEFAULT NULL;
    DECLARE _count2 INT DEFAULT 0;
    
    SET _id = (SELECT `attribute_id` FROM `wp_woocommerce_attribute_taxonomies` WHERE `attribute_name` = _slug LIMIT 1);
    IF _id IS NULL
    THEN
		INSERT INTO `wp_woocommerce_attribute_taxonomies`(`attribute_label`, `attribute_name`, `attribute_type`, `attribute_orderby`, `attribute_public`)
		VALUES (_label, _slug, 'select', 'menu_order', 0);
		SET _id = last_insert_id();
    END IF;
    
    iterator:
		LOOP
		  IF CHAR_LENGTH(TRIM(_list_terms)) = 0 OR _list_terms IS NULL THEN
			LEAVE iterator;
		  END IF;
		  SET _next = SUBSTRING_INDEX(_list_terms,';',1);
		  SET _nextlen = CHAR_LENGTH(_next);
		  SET _value = TRIM(_next);
          
          SET _next2 = SUBSTRING_INDEX(_list_term_slugs,';',1);
		  SET _nextlen2 = CHAR_LENGTH(_next2);
		  SET _value2 = TRIM(_next2);
          
        IF _value IS NULL OR CHAR_LENGTH(_value) = 0 OR _value2 IS NULL OR CHAR_LENGTH(_value2) = 0 THEN
           LEAVE iterator;
		END IF;
          
		SET @term_slug = _value2;-- slugify(_value); => tính bên php cho thống nhất thuật toán
        SET @check_exists = (SELECT tt.`term_taxonomy_id` FROM `wp_term_taxonomy` as tt
        INNER JOIN `wp_terms` as t ON t.`term_id` = tt.`term_id`
        WHERE tt.`taxonomy`  = concat('pa_', _slug) AND t.`slug` = @term_slug -- t.`name`  = _value
        LIMIT 1);
        
		IF(@check_exists is null)
		THEN
		  INSERT INTO `wp_terms` (`name`, `slug`, `term_group`)
		  VALUES (_value, @term_slug, 0);
		  
		  SET @term_id = last_insert_id();
		  
		  INSERT INTO `wp_termmeta`(`term_id`, `meta_key`, `meta_value`)
		  VALUES(@term_id, concat('order_pa_', _slug), 0);
		  
		  INSERT INTO `wp_term_taxonomy`(`term_id`, `taxonomy`, `description`, `parent`, `count`)
		  VALUES(@term_id, concat('pa_', _slug), '', 0, 0);
          SET @term_taxonomy_id = last_insert_id();
          
          INSERT INTO `wp_term_relationships`(`object_id`, `term_taxonomy_id`, `term_order`)
          VALUES(_id_product, @term_taxonomy_id, 0);
	  ELSE
		SET @check_ralationship = (SELECT `term_taxonomy_id`
                                   FROM `wp_term_relationships`
								   WHERE `object_id`=_id_product AND `term_taxonomy_id`=@check_exists
                                   LIMIT 1);
		IF(@check_ralationship is null)
        THEN
			INSERT INTO `wp_term_relationships`(`object_id`, `term_taxonomy_id`, `term_order`)
			VALUES(_id_product, @check_exists, 0);
        END IF;
	  END IF;
	  
	  SET _list_terms = INSERT(_list_terms, 1, _nextlen + 1, '');
      SET _list_term_slugs = INSERT(_list_term_slugs, 1, _nextlen2 + 1, '');
      
	  SET _count = _count + 1;
      SET _count2 = _count2 + 1;
	END LOOP;
    
    DELETE FROM `wp_options` WHERE `option_name` ='_transient_wc_attribute_taxonomies';
    RETURN _id;
END;