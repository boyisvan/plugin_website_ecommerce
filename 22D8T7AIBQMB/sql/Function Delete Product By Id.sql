CREATE FUNCTION `delete_product_by_id`(_from BIGINT(20), _delete_parent BIT, _delete_variation BIT)
RETURNS INT
BEGIN
	DECLARE _to BIGINT(20);
	SET _to = _from; -- về sau không cho delete theo khoảng nữa, nhưng giữ lại để phòng trường hợp cần dùng

	-- delete all
	IF(_from is null AND _to is null) THEN
		SET @count = (SELECT COUNT(*) FROM `wp_posts` WHERE post_type = 'product');
		DELETE FROM `wp_postmeta` WHERE post_id IN (SELECT ID FROM `wp_posts` WHERE post_type = 'product' or post_type='product_variation' or post_type='attachment');
		
		DELETE FROM `wp_commentmeta`;
		DELETE FROM `wp_comments`;
        
        DELETE FROM `wp_posts` WHERE post_type = 'product' or post_type = 'product_variation' or post_type = 'attachment';
		-- DELETE FROM codethue_sku_imported;
        
        UPDATE `wp_term_taxonomy` SET count = 0;
        DELETE FROM `wp_wc_product_meta_lookup`;
        RETURN @count;
    ELSEIF(_from = _to OR (_from IS NOT NULL AND _to IS NULL)) THEN -- delete one
		IF _delete_parent THEN -- tìm sản phẩm cha xong xóa (phục vụ các trường hợp GMC thông báo id variation, chứ không thông báo id product)
			SET @tempParent = (SELECT post_parent FROM `wp_posts` WHERE ID = _from LIMIT 1);
			IF @tempParent IS NOT NULL AND @tempParent <> 0 THEN
				SET _from = @tempParent;
			END IF;
		END IF;

		IF(EXISTS(SELECT ID FROM `wp_posts` WHERE ID = _from AND post_type = 'product' LIMIT 1)) THEN

			-- Xóa hàng con để giảm tải dữ liệu
			IF _delete_variation THEN
				DELETE FROM `wp_postmeta` WHERE post_id IN (SELECT ID FROM `wp_posts` WHERE post_parent = _from AND post_type = 'product_variation'); -- Xóa ở meta trước không, chứ xóa ở post trước thì WHERE không thấy
				DELETE FROM `wp_posts` WHERE post_parent = _from AND post_type = 'product_variation';
			END IF;

			DELETE FROM `wp_posts` WHERE ID = _from;
			DELETE FROM `wp_postmeta` WHERE post_id = _from; -- liệu có chậm không? chắc là không đâu :)))

			SET @term_taxonomy_id = (SELECT TX.term_taxonomy_id FROM `wp_term_relationships` as TR
			INNER JOIN `wp_term_taxonomy` as TX ON TR.term_taxonomy_id = TX.term_taxonomy_id
			WHERE TR.object_id = _from AND (TX.taxonomy='product_type' OR TX.taxonomy='product_cat') LIMIT 1);
            
			IF(@term_taxonomy_id IS NOT NULL) THEN
				UPDATE `wp_term_taxonomy` SET `wp_term_taxonomy`.count = `wp_term_taxonomy`.count - 1 WHERE `wp_term_taxonomy`.`term_taxonomy_id` = @term_taxonomy_id;
			END IF;
			RETURN 1;
        END IF;
        
        RETURN 0;
    ELSE -- delete range product
		SET @count = (SELECT COUNT(*) FROM `wp_posts` WHERE ID BETWEEN _from AND _to AND post_type = 'product');
        IF(@count > 0) THEN
			DELETE FROM `wp_posts` WHERE ID BETWEEN _from AND _to;
			DELETE FROM `wp_postmeta` WHERE post_id BETWEEN _from AND _to;
			
			UPDATE `wp_term_taxonomy`
			SET `wp_term_taxonomy`.count=`wp_term_taxonomy`.count - @count
            WHERE `wp_term_taxonomy`.`term_taxonomy_id` IN (SELECT term_taxonomy_id FROM (SELECT TX.term_taxonomy_id FROM `wp_term_relationships` as TR
																	INNER JOIN `wp_term_taxonomy` as TX ON TR.term_taxonomy_id = TX.term_taxonomy_id
																	WHERE TR.object_id BETWEEN _from AND _to AND (TX.taxonomy='product_type' OR TX.taxonomy='product_cat'))tblTmp);
		END IF;
        RETURN @count;
    END IF;
END;