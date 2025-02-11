CREATE FUNCTION `delete_product_fast_api`(_sku TINYTEXT)
RETURNS BIGINT(20)
BEGIN
	SET @id_product = (SELECT `post_id` FROM `wp_postmeta` WHERE `meta_key` = '_sku' AND `meta_value` collate utf8mb4_unicode_ci = _sku LIMIT 1);
    
    WHILE(@id_product is not null) DO
		DELETE FROM `wp_postmeta` WHERE `post_id` IN (SELECT ID FROM `wp_posts` WHERE `post_parent` = @id_product);
        DELETE FROM `wp_postmeta` WHERE `post_id` = @id_product;
        	
        DELETE FROM `wp_commentmeta` WHERE `comment_id` IN (SELECT `comment_id` FROM `wp_comments` WHERE `comment_post_ID` = @id_product);
        DELETE FROM `wp_comments` WHERE `comment_post_ID` = @id_product;
        
        DELETE FROM `wp_posts` WHERE ID = @id_product;

        SET @id_product = (SELECT `post_id` FROM `wp_postmeta` WHERE `meta_key` = '_sku' AND `meta_value` collate utf8mb4_unicode_ci = _sku LIMIT 1);
    END WHILE;

	DELETE FROM `codethue_sku_imported` WHERE `sku` = _sku;
    
    RETURN @id_product;
END;