CREATE FUNCTION `update_product`(_id_product BIGINT(20),
_product_title TEXT, _price DOUBLE, _stock_status MEDIUMTEXT
, _description MEDIUMTEXT
, _idCategory TEXT
,  _lstTagId TEXT, _sale_price DOUBLE,
_stock_count INT
)
RETURNS BIGINT(20)
BEGIN
	DECLARE _next TEXT DEFAULT NULL;
	DECLARE _nextlen INT DEFAULT NULL;
	DECLARE _value TEXT DEFAULT NULL;
    DECLARE _count INT DEFAULT 0;

	IF(_product_title IS NOT NULL AND _product_title <> 'null') THEN
		UPDATE `wp_posts` SET `post_title` = _product_title
		WHERE `ID` = _id_product;
	END IF;

	IF(_description IS NOT NULL AND _description <> 'null') THEN
		UPDATE `wp_posts` SET `post_content` = _description
    	WHERE `ID` = _id_product;
    END IF;

    IF(_price IS NOT NULL) THEN
		UPDATE `wp_postmeta` SET `meta_value` = _price
		WHERE `post_id` = _id_product AND (`meta_key` = '_regular_price' OR `meta_key` = '_price');
    END IF;
    
    IF(_sale_price IS NOT NULL) THEN
		UPDATE `wp_postmeta` SET `meta_value` = _sale_price
		WHERE `post_id` = _id_product AND (`meta_key` = '_sale_price');
    END IF;

	IF(_stock_count IS NOT NULL) THEN
		IF NOT EXISTS(SELECT 1 FROM `wp_postmeta` WHERE `post_id` = _id_product AND `meta_key` = '_manage_stock' LIMIT 1) THEN
			INSERT INTO `wp_postmeta` (`post_id`, `meta_key`, `meta_value`) VALUES(_id_product, '_manage_stock', 'no');
		END IF;

		UPDATE `wp_postmeta` SET `meta_value` = _stock_count
		WHERE `post_id` = _id_product AND `meta_key` = '_stock';

		UPDATE `wp_postmeta` SET `meta_value` = 'yes'
		WHERE `post_id` = _id_product AND `meta_key` = '_manage_stock';
	ELSE
		UPDATE `wp_postmeta` SET `meta_value` = 'no'
		WHERE `post_id` = _id_product AND `meta_key` = '_manage_stock';
	END IF;

	IF(_stock_status IS NOT NULL) THEN
		UPDATE `wp_postmeta` SET `meta_value` = _stock_status
		WHERE `post_id` = _id_product AND `meta_key` = '_stock_status';
	END IF;
    
	IF(_idCategory IS NOT NULL AND _idCategory <> 'null') THEN
		SET @term_taxonomy_ids = (SELECT group_concat(tx.term_taxonomy_id) FROM `wp_term_relationships` AS tr
			INNER JOIN `wp_term_taxonomy` AS tx ON tx.term_taxonomy_id = tr.term_taxonomy_id
			WHERE tr.object_id = _id_product AND tx.taxonomy = 'product_cat');
			
		DELETE FROM `wp_term_relationships` WHERE find_in_set(term_taxonomy_id, @term_taxonomy_ids) > 0 AND `object_id` = _id_product;
		
		set _count = 0;
		iterator:
			LOOP
			IF CHAR_LENGTH(TRIM(_idCategory)) = 0 OR _idCategory IS NULL THEN
				LEAVE iterator;
			END IF;
			SET _next = SUBSTRING_INDEX(_idCategory,';',1);
			SET _nextlen = CHAR_LENGTH(_next);
			SET _value = TRIM(_next);

			SET @term_taxonomy_tag_id = (SELECT term_taxonomy_id FROM `wp_term_taxonomy` WHERE `term_id` = _value AND `taxonomy`='product_cat' LIMIT 1);
				IF(@term_taxonomy_tag_id IS NOT NULL)
				THEN
					INSERT IGNORE INTO `wp_term_relationships` (`object_id`, `term_taxonomy_id`, `term_order`)
					VALUES (_id_product, @term_taxonomy_tag_id, 0);

					UPDATE `wp_term_taxonomy` SET count=count+1 WHERE `term_taxonomy_id` = @term_taxonomy_tag_id;
				END IF;			
				
				IF(EXISTS(SELECT 1 FROM `wp_termmeta` WHERE `term_id`=_value and `meta_key`='product_count_product_cat' LIMIT 1) = '0')
				THEN
					INSERT IGNORE INTO `wp_termmeta`(`term_id`,`meta_key`, `meta_value`)
					VALUES                 (_value, 'product_count_product_cat', '1');
				ELSE
					UPDATE `wp_termmeta`
						SET `meta_value`=meta_value+1
					WHERE `term_id`=_value and `meta_key`='product_count_product_cat';
				END IF;
			
			SET _idCategory = INSERT(_idCategory,1,_nextlen + 1,'');
			SET _count = _count + 1;
			END LOOP;
	END IF;

	IF (_lstTagId IS NOT NULL AND _lstTagId <> 'null') THEN
		SET @term_taxonomy_ids = (SELECT group_concat(tx.term_taxonomy_id) FROM `wp_term_relationships` AS tr
			INNER JOIN `wp_term_taxonomy` AS tx ON tx.term_taxonomy_id = tr.term_taxonomy_id
			WHERE tr.object_id = _id_product AND tx.taxonomy = 'product_tag');
			
		DELETE FROM `wp_term_relationships` WHERE find_in_set(term_taxonomy_id, @term_taxonomy_ids) > 0 AND `object_id` = _id_product;
		set _count = 0;
		iterator:
			LOOP
			IF CHAR_LENGTH(TRIM(_lstTagId)) = 0 OR _lstTagId IS NULL THEN
				LEAVE iterator;
			END IF;
			SET _next = SUBSTRING_INDEX(_lstTagId,';',1);
			SET _nextlen = CHAR_LENGTH(_next);
			SET _value = TRIM(_next);

			SET @term_taxonomy_tag_id = (SELECT term_taxonomy_id FROM `wp_term_taxonomy` WHERE `term_id` = _value AND `taxonomy`='product_tag' LIMIT 1);
				IF(@term_taxonomy_tag_id IS NOT NULL)
				THEN
					INSERT IGNORE INTO `wp_term_relationships` (`object_id`, `term_taxonomy_id`, `term_order`)
					VALUES (_id_product, @term_taxonomy_tag_id, 0);
					
					UPDATE `wp_term_taxonomy` SET count=count+1 WHERE `term_taxonomy_id` = @term_taxonomy_tag_id;
				END IF;			
				
				IF(EXISTS(SELECT 1 FROM `wp_termmeta` WHERE `term_id`=_value and `meta_key`='product_count_product_tag' LIMIT 1) = '0')
				THEN
					INSERT IGNORE INTO `wp_termmeta`(`term_id`,`meta_key`, `meta_value`)
					VALUES                 (_value, 'product_count_product_tag', '1');
				ELSE
					UPDATE `wp_termmeta`
						SET `meta_value`=meta_value+1
					WHERE `term_id`=_value and `meta_key`='product_count_product_tag';
				END IF;
			
			SET _lstTagId = INSERT(_lstTagId,1,_nextlen + 1,'');
			SET _count = _count + 1;
			END LOOP;
	END IF;
    return _id_product;
END;