CREATE PROCEDURE `fix_min_max_price_upgrade_v2_0`()
BEGIN
	DROP TABLE IF EXISTS giaiphapmmo_temp_min_max_product_price;
    CREATE TABLE `giaiphapmmo_temp_min_max_product_price` (
    `p_id` BIGINT(20) UNSIGNED NOT NULL,
    `mi_price` DOUBLE NULL,
    `ma_price` DOUBLE NULL,
    PRIMARY KEY (`p_id`));
    
    INSERT INTO giaiphapmmo_temp_min_max_product_price (p_id)
    SELECT ID FROM `wp_posts` p2 WHERE p2.post_type = 'product';

    UPDATE giaiphapmmo_temp_min_max_product_price
    SET mi_price = (SELECT MIN(lu1.min_price) FROM `wp_wc_product_meta_lookup` lu1
                    WHERE lu1.product_id IN (SELECT ID FROM `wp_posts` p1 WHERE p1.post_parent = p_id)),
        ma_price = (SELECT MAX(lu2.max_price) FROM `wp_wc_product_meta_lookup` lu2
                    WHERE lu2.product_id IN (SELECT ID FROM `wp_posts` p2 WHERE p2.post_parent = p_id));
                    
    UPDATE giaiphapmmo_temp_min_max_product_price
    SET mi_price = (SELECT lu1.min_price FROM `wp_wc_product_meta_lookup` lu1
                    WHERE lu1.product_id = p_id LIMIT 1),
        ma_price = (SELECT lu2.max_price FROM `wp_wc_product_meta_lookup` lu2
                    WHERE lu2.product_id = p_id LIMIT 1)
    WHERE mi_price IS NULL;

    -- select * from giaiphapmmo_temp_min_max_product_price where mi_price = 0; select product_id, min_price, max_price from wp_wc_product_meta_lookup where min_price = 0 and product_id in (select ID from wp_posts where post_type = 'product');
    -- select * from giaiphapmmo_temp_min_max_product_price order by p_id; select product_id, min_price, max_price from wp_wc_product_meta_lookup order by product_id;

    UPDATE `wp_wc_product_meta_lookup`
    SET min_price = (SELECT mi_price FROM giaiphapmmo_temp_min_max_product_price gp1 WHERE gp1.p_id = product_id),
        max_price = (SELECT ma_price FROM giaiphapmmo_temp_min_max_product_price gp2 WHERE gp2.p_id = product_id);

    DROP TABLE giaiphapmmo_temp_min_max_product_price;
END;