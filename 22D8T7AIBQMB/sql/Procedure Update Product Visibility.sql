-- Cách sử dụng: CALL procedure_update_product_visibility(19060, 'catalog_search');-- ['hidden', 'search', 'catalog', 'catalog_search']

DROP PROCEDURE IF EXISTS procedure_update_product_visibility;
DELIMITER ;;

CREATE PROCEDURE procedure_update_product_visibility(_category_id BIGINT(20), _mode TEXT) -- _mode = ['hidden', 'search', 'catalog', 'catalog_search']
BEGIN
  DECLARE cursor_ID BIGINT(20);
  DECLARE done INT DEFAULT FALSE;
	
  DECLARE cursor_product_ids CURSOR FOR 	SELECT DISTINCT post.ID FROM `wp_term_relationships` AS tr
														INNER JOIN `wp_posts` AS post ON tr.object_id = post.ID
														INNER JOIN `wp_term_taxonomy` AS tx ON tx.term_taxonomy_id = tr.term_taxonomy_id
														WHERE post.post_type = 'product' AND tx.taxonomy = 'product_cat' AND tx.term_id = _category_id;
DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
  SET	@id_term_search = (SELECT term_id FROM `wp_terms` WHERE `name`= 'exclude-from-search' LIMIT 1);
  IF(@id_term_search IS NULL) THEN 
  		INSERT INTO `wp_terms` (`name`, `slug`, `term_group`) VALUES ('exclude-from-search', 'exclude-from-search', 0);
	  	SET @id_term_search = LAST_INSERT_ID();
	END IF;
	
	SET @id_term_catalog = (SELECT term_id FROM `wp_terms` WHERE `name` = 'exclude-from-catalog' LIMIT 1);
	IF(@id_term_catalog IS NULL) THEN 
  		INSERT INTO `wp_terms` (`name`, `slug`, `term_group`) VALUES ('exclude-from-catalog', 'exclude-from-catalog', 0);
	  	SET @id_term_catalog = LAST_INSERT_ID();
	END IF;
	
	SET @term_taxonomy_search_id = (SELECT term_taxonomy_id FROM `wp_term_taxonomy` WHERE `term_id` = @id_term_search AND `taxonomy`='product_visibility' LIMIT 1);
	IF(@term_taxonomy_search_id IS NULL) THEN
		INSERT INTO `wp_term_taxonomy`(`term_id`, `taxonomy`, `description`,`parent`,`count`)
		VALUES(@id_term_search, 'product_visibility','', 0, 0);
		SET @term_taxonomy_search_id = LAST_INSERT_ID();
	END IF;
	
	SET @term_taxonomy_catalog_id = (SELECT term_taxonomy_id FROM `wp_term_taxonomy` WHERE `term_id` = @id_term_catalog AND `taxonomy`='product_visibility' LIMIT 1);
	IF(@term_taxonomy_catalog_id IS NULL) THEN
		INSERT INTO `wp_term_taxonomy`(`term_id`, `taxonomy`, `description`,`parent`,`count`)
		VALUES(@id_term_catalog, 'product_visibility','', 0, 0);
		SET @term_taxonomy_catalog_id = LAST_INSERT_ID();
	END IF;
  
  -- 
  OPEN cursor_product_ids;
  read_loop: LOOP
    
     IF done THEN
       LEAVE read_loop;
   END IF;
     FETCH cursor_product_ids INTO cursor_ID;
     DELETE FROM `wp_term_relationships` WHERE object_id = cursor_ID AND term_taxonomy_id IN (@term_taxonomy_catalog_id, @term_taxonomy_search_id);
	  IF _mode = 'hidden' THEN
			IF(NOT EXISTS(SELECT 1 FROM `wp_term_relationships` WHERE object_id = cursor_ID AND term_taxonomy_id = @term_taxonomy_search_id))
			THEN
				INSERT INTO `wp_term_relationships` (`object_id`, `term_taxonomy_id`, `term_order`)
				VALUES (cursor_ID, @term_taxonomy_search_id, 0);
			END IF;
			
			IF(NOT EXISTS(SELECT 1 FROM `wp_term_relationships` WHERE object_id = cursor_ID AND term_taxonomy_id = @term_taxonomy_catalog_id))
			THEN
				INSERT INTO `wp_term_relationships` (`object_id`, `term_taxonomy_id`, `term_order`)
				VALUES (cursor_ID, @term_taxonomy_catalog_id, 0);
			END IF;
	   END IF;
	  
		IF _mode = 'search' THEN
			IF(NOT EXISTS(SELECT 1 FROM `wp_term_relationships` WHERE object_id = cursor_ID AND term_taxonomy_id = @term_taxonomy_catalog_id))
			THEN
				INSERT INTO `wp_term_relationships` (`object_id`, `term_taxonomy_id`, `term_order`)
				VALUES (cursor_ID, @term_taxonomy_catalog_id, 0);
			END IF;
	 	END IF;
	 	
	 	IF _mode = 'catalog' THEN
	 		IF(NOT EXISTS(SELECT 1 FROM `wp_term_relationships` WHERE object_id = cursor_ID AND term_taxonomy_id = @term_taxonomy_search_id))
			THEN
				INSERT INTO `wp_term_relationships` (`object_id`, `term_taxonomy_id`, `term_order`)
				VALUES (cursor_ID, @term_taxonomy_search_id, 0);
			END IF;
	 	END IF;
    
  END LOOP;
  CLOSE cursor_product_ids;
END;
;;