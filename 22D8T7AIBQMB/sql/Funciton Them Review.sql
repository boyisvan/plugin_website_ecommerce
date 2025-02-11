CREATE FUNCTION `create_review`(_id_product BIGINT(20), _review_author TINYTEXT, _review_author_email TINYTEXT, _review_content TEXT,
_time_reate DATETIME, _rating FLOAT)
RETURNS BIGINT(20)
BEGIN
	IF(_time_reate = '' OR _time_reate is null)
    THEN
		SET _time_reate = UTC_TIMESTAMP();
    END IF;
    INSERT INTO `wp_comments` (`comment_post_ID`, `comment_author`, `comment_author_email`, `comment_content`,
    `comment_date`, `comment_date_gmt`, `comment_type`,
    `comment_karma`, `comment_approved`, `comment_parent`, `user_id`,
    `comment_author_url`, `comment_author_IP`, `comment_agent`)
    VALUES(_id_product, _review_author, _review_author_email, _review_content,
    _time_reate, _time_reate, 'review',
    0, 1, 0, 0,
    '', '', '');
    SET @id_review = last_insert_id();
    
    INSERT INTO `wp_commentmeta` (`comment_id`, `meta_key`, `meta_value`)
    VALUES (@id_review, 'rating', _rating);
    
    INSERT INTO `wp_commentmeta` (`comment_id`, `meta_key`, `meta_value`)
    VALUES (@id_review, 'verified', 0);
    
    SET @count_review = (SELECT COUNT(*) FROM `wp_comments` WHERE `comment_post_ID` = _id_product);
    
    SET @sum_rating = (SELECT SUM(`meta_value`) FROM `wp_commentmeta`
    WHERE `comment_id` IN (SELECT `comment_ID` FROM `wp_comments` WHERE `comment_post_ID` = _id_product));
    
    SET @average_rating = @sum_rating/@count_review;
    
    UPDATE `wp_postmeta` SET `meta_value` = @average_rating WHERE `post_id` = _id_product AND `meta_key` = '_wc_average_rating';
    
    UPDATE `wp_postmeta` SET `meta_value` = @count_review WHERE `post_id` = _id_product AND `meta_key` = '_wc_review_count';
    
    UPDATE `wp_postmeta` SET `meta_value` = 'a:1:{i:1;i:5;}' WHERE `post_id` = _id_product AND `meta_key` = '_wc_rating_count';
    
    UPDATE `wp_wc_product_meta_lookup`
    SET 
        `rating_count` = `rating_count`+1,
        `average_rating` = @average_rating
    WHERE `product_id` = _id_product;
    
    RETURN @id_review;
END;