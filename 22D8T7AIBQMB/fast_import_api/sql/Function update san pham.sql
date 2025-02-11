CREATE FUNCTION `update_prodcut_fast_api`(_id_product BIGINT(20),
_product_title TEXT, _price DOUBLE, _stock_status MEDIUMTEXT, _product_type TINYTEXT,
_description MEDIUMTEXT,
_jsonAttribute LONGTEXT
)
RETURNS BIGINT(20)
BEGIN
	IF(_product_type <> 'simple' AND _product_type <> 'variable')
    THEN
		RETURN _id_product;
    END IF;

	UPDATE `wp_posts` SET
		`post_title` = _product_title,
        `post_content` = _description
    WHERE `ID` = _id_product;

	UPDATE `wp_postmeta` SET `meta_value` = _price
	WHERE `post_id` = _id_product AND (`meta_key` = '_regular_price' OR `meta_key` = '_price');

    UPDATE `wp_postmeta` SET `meta_value` = _stock_status
    WHERE `post_id` = _id_product AND `meta_key` = '_stock_status';

    IF(_product_type = 'variable')
    THEN
		IF(EXISTS(SELECT * FROM `wp_postmeta` WHERE `post_id` = _id_product AND `meta_key` = '_product_attributes' LIMIT 1))
        THEN
			UPDATE `wp_postmeta`
            SET    `meta_value` = _jsonAttribute
            WHERE  `post_id` = _id_product AND `meta_key` = '_product_attributes';
        ELSE
			INSERT INTO `wp_postmeta` (`post_id`,   `meta_key`,      `meta_value`)
			VALUES                     (_id_product, '_product_attributes', _jsonAttribute);
        END IF;
    END IF;

    SET @old_term_product_type_id = (SELECT T.term_id
		FROM wp_term_relationships as TR
		INNER JOIN wp_term_taxonomy as TX ON TX.term_taxonomy_id = TR.term_taxonomy_id
		INNER JOIN wp_terms AS T ON T.term_id = TX.term_id
		WHERE TR.`object_id`=_id_product AND TX.taxonomy='product_type');

	SET @idTermProductType = (SELECT term_id FROM `wp_terms` WHERE  `name` collate utf8mb4_unicode_ci = _product_type LIMIT 1);
	IF(@idTermProductType is null)
	THEN
		INSERT INTO `wp_terms` (`name`, `slug`, `term_group`) VALUES (_product_type, _product_type, 0);
		SET @idTermProductType = last_insert_id();
	END IF;

	SET @term_taxonomy_product_type_id = (SELECT term_taxonomy_id FROM `wp_term_taxonomy` WHERE `term_id` = @idTermProductType AND `taxonomy`='product_type' LIMIT 1);
	IF(@term_taxonomy_product_type_id is null)
	THEN
		INSERT INTO `wp_term_taxonomy`(`term_id`, `taxonomy`, `description`,`parent`,`count`)
		VALUES(@idTermProductType, 'product_type','', 0, 0);
		SET @term_taxonomy_product_type_id = last_insert_id();
	END IF;

	UPDATE `wp_term_relationships`
    SET `term_taxonomy_id` = @term_taxonomy_product_type_id
    WHERE `object_id` = _id_product and `term_taxonomy_id` =  @old_term_product_type_id;

    return _id_product;
END;