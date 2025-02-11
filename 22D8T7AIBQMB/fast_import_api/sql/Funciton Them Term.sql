CREATE FUNCTION `insert_term_fast_api`(_name TEXT, _slug TEXT, _taxonomy TEXT, _parent BIGINT(20))
RETURNS BIGINT(20)
BEGIN
	SET @id_term = (SELECT T.`term_id` FROM `wp_terms` as T INNER JOIN `wp_term_taxonomy` as TX ON T.term_id = TX.term_id WHERE T.`slug` = _slug AND TX.taxonomy = _taxonomy LIMIT 1);
    IF @id_term IS NULL THEN
		INSERT INTO `wp_terms` (`name`, `slug`, `term_group`)
		VALUES (_name, _slug, 0);
		
		SET @id_term = last_insert_id();
		
		INSERT INTO `wp_termmeta` (`term_id`, `meta_key`, `meta_value`)
		VALUES (@id_term, 'display_type', '');
        
        INSERT INTO `wp_termmeta` (`term_id`, `meta_key`, `meta_value`)
        VALUES (@id_term, 'order', '0');
		
		SET @meta_key = 'product_count_product_cat';
		IF(_taxonomy = 'product_tag')
		THEN
			SET @meta_key = 'product_count_product_tag';
		END IF;
		INSERT INTO `wp_termmeta` (`term_id`, `meta_key`, `meta_value`)
		VALUES (@id_term, @meta_key, 0);
		
		INSERT INTO `wp_term_taxonomy` (`term_id`, `taxonomy`, `description`, `parent`, `count`)
		VALUES (@id_term, _taxonomy, '', _parent, 0);

		IF(_parent <> 0) THEN
			DELETE FROM `wp_options` WHERE option_name = 'product_cat_children';
        END IF;
    END IF;
    return @id_term;
END;