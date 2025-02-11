CREATE FUNCTION `insert_postmeta_fast_api`(_post_id BIGINT(20), _meta_key VARCHAR(255), _meta_value LONGTEXT
 )
RETURNS BIGINT(20)
BEGIN
	INSERT INTO `wp_postmeta` (`post_id`, `meta_key`, `meta_value`)
	VALUES                    (_post_id, _meta_key, _meta_value);
	RETURN last_insert_id();
END;