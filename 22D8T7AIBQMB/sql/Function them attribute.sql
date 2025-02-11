CREATE FUNCTION `create_attribute`(_label TINYTEXT, _slug TINYTEXT, _list_terms MEDIUMTEXT)
RETURNS BIGINT(20)
BEGIN
	DECLARE _next TEXT DEFAULT NULL;
	DECLARE _nextlen INT DEFAULT NULL;
	DECLARE _value TEXT DEFAULT NULL;
    DECLARE _count INT DEFAULT 0;
	DECLARE _id BIGINT(20);
    
	IF(NOT EXISTS(SELECT * FROM `wp_woocommerce_attribute_taxonomies` WHERE `attribute_label` = _label))
    THEN
		INSERT INTO `wp_woocommerce_attribute_taxonomies`(`attribute_label`, `attribute_name`, `attribute_type`, `attribute_orderby`, `attribute_public`)
		VALUES (_label, _slug, 'select', 'menu_order', 0);
		SET _id = last_insert_id();
	ELSE
		SET _id = (SELECT `attribute_id` FROM `wp_woocommerce_attribute_taxonomies` WHERE `attribute_label` = _label LIMIT 1);
        SET _slug = (SELECT `attribute_name` FROM `wp_woocommerce_attribute_taxonomies` WHERE `attribute_label` = _label LIMIT 1);
    END IF;
    
    iterator:
		LOOP
		  IF CHAR_LENGTH(TRIM(_list_terms)) = 0 OR _list_terms IS NULL THEN
			LEAVE iterator;
		  END IF;
		  SET _next = SUBSTRING_INDEX(_list_terms,';',1);
		  SET _nextlen = CHAR_LENGTH(_next);
		  SET _value = TRIM(_next);
		SET @term_slug = slugify(_value);
        SET @check_exists = (SELECT t.`term_id` FROM `wp_term_taxonomy` as tt
        INNER JOIN `wp_terms` as t ON t.`term_id` = tt.`term_id`
        WHERE tt.`taxonomy` = concat('pa_', _slug) AND t.`name` = _value
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
	  END IF;
	  
	  SET _list_terms = INSERT(_list_terms, 1, _nextlen + 1, '');
	  SET _count = _count + 1;
	END LOOP;
    
    DELETE FROM `wp_options` WHERE `option_name` ='_transient_wc_attribute_taxonomies';
    RETURN _id;
END;