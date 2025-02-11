CREATE FUNCTION `insert_product_fast_api`(_sku MEDIUMTEXT, _ten MEDIUMTEXT, _slug TEXT, _moTa LONGTEXT, _moTaNgan LONGTEXT, _gia DOUBLE, _sale_price DOUBLE,
_stockStatus MEDIUMTEXT, _total_sales INT , _tax_status TINYTEXT, _tax_class TINYTEXT,
_manage_stock TINYTEXT, _backorders TINYTEXT, _sold_individually TINYTEXT, _virtual TINYTEXT, _downloadable TINYTEXT,
_download_limit INT, _download_expiry INT, _stock INT,
_idCategory TEXT, _product_type TINYTEXT, _listUrlImage MEDIUMTEXT, _list_image_meta LONGTEXT, _lstTagId TEXT,
_parentProduct BIGINT(20), _post_status TINYTEXT,
_weight DOUBLE, _height DOUBLE, _width DOUBLE, _length DOUBLE)
RETURNS BIGINT(20)
BEGIN
	DECLARE _product_image_gallery NVARCHAR(4000) DEFAULT '';

	DECLARE _next TEXT DEFAULT NULL;
	DECLARE _nextlen INT DEFAULT NULL;
	DECLARE _value TEXT DEFAULT NULL;
    DECLARE _count INT DEFAULT 0;
    
    DECLARE _next2 TEXT DEFAULT NULL;
	DECLARE _nextlen2 INT DEFAULT NULL;
	DECLARE _value2 TEXT DEFAULT NULL;
    DECLARE _count2 INT DEFAULT 0;

    DECLARE _idTermProductType INT DEFAULT 0;
    DECLARE _product_count_product_cat INT DEFAULT 0;

    SET @nowTime = current_timestamp();-- UTC_TIMESTAMP();-- 
	SET @nowTime_gmt = UTC_TIMESTAMP();-- current_timestamp();-- 
	SET @gmt_offset = (SELECT option_value FROM `wp_options` WHERE option_name = 'gmt_offset');
    IF(@gmt_offset IS NOT NULL) THEN
		SET @nowTime = DATE_ADD(UTC_TIMESTAMP(), INTERVAL @gmt_offset HOUR);
        -- SET @nowTime_gmt = DATE_ADD(UTC_TIMESTAMP(), INTERVAL @gmt_offset HOUR);
    END IF;

	IF(_post_status IS NULL OR _post_status = '') THEN SET _post_status = 'publish'; END IF;

    SET @postType='product';
    IF(_parentProduct <> 0)
    THEN
		SET @try_count_slug_variant = 1;
        SET @orginal_slug = _slug;
		 SET @postType='product_variation';
         WHILE(EXISTS(SELECT `post_name` FROM `wp_posts` WHERE `post_parent` = _parentProduct AND `post_name` = _slug LIMIT 1)) DO
			SET @try_count_slug_variant = @try_count_slug_variant + 1;
            SET _slug = CONCAT(CONCAT(@orginal_slug, '-'), @try_count_slug_variant);
		END WHILE;
    END IF;
    SET @siteurl = (SELECT option_value FROM `wp_options` WHERE `option_name` = 'siteurl' LIMIT 1);
	INSERT INTO `wp_posts`(`post_content`, `post_title`, `post_status`, `comment_status`, `ping_status`, `post_name`, `post_type`, `post_excerpt`, `to_ping`, `pinged`, `post_content_filtered`, `post_date`, `post_date_gmt`, `post_modified`, `post_modified_gmt`, `post_parent`, `post_author`, `guid`)
	VALUES (_moTa, _ten, _post_status, 'open', 'closed', _slug, @postType, _moTaNgan, '', '', '', @nowTime, @nowTime_gmt, @nowTime, @nowTime_gmt, _parentProduct, 1, concat(@siteurl, '/product/', _slug));
    
	SET @idProduct = last_insert_id();

    IF(_parentProduct = 0)
    THEN
		INSERT INTO `codethue_sku_imported`(`sku`, `id_product`, `time_import`)
		VALUES (_sku, @idProduct, NOW());
    END IF;

	SET @checkImageLocal = (SELECT option_value FROM `wp_options` WHERE option_name = 'siteurl' LIMIT 1);
    SET @checkImageLocal = CONCAT(@checkImageLocal, '/wp-content/uploads/');

    IF(_listUrlImage not like CONCAT(@checkImageLocal, '%')) THEN
		INSERT INTO `wp_postmeta` (`post_id`,   `meta_key`,      `meta_value`)
		VALUES                     (@idProduct, 'fifu_list_url', _listUrlImage);
    END IF;
    iterator:
		LOOP
		  IF _listUrlImage IS NULL OR CHAR_LENGTH(TRIM(_listUrlImage)) = 0  THEN
			LEAVE iterator;
		  END IF;
          
          SET @lenGalery = CHAR_LENGTH(_product_image_gallery);
          
          IF(@lenGalery > 0)
          THEN
			SET _product_image_gallery = CONCAT(_product_image_gallery, ',');
          END IF;
          
		  SET _next = SUBSTRING_INDEX(_listUrlImage,';',1);
		  SET _nextlen = CHAR_LENGTH(_next);-- LENGTH(_next); -- 08.4.2022 doi sang CHAR_LENGTH xu ly ten anh co ky tu dac biet
		  SET _value = TRIM(_next);
          
          SET _next2 = SUBSTRING_INDEX(_list_image_meta,'<->',1);
		  SET _nextlen2 = CHAR_LENGTH(_next2);  -- LENGTH(_next2); 08.4.2022 doi sang CHAR_LENGTH xu ly ten anh co ky tu dac biet
		  SET _value2 = TRIM(_next2);
          
          INSERT INTO `wp_posts`(`post_parent`, `post_content`,  `post_title`, `post_status`, `comment_status`, `ping_status`, `post_type`, `post_mime_type`, `guid`, `post_excerpt`, `to_ping`, `pinged`, `post_content_filtered`, `post_date`, `post_date_gmt`, `post_modified`, `post_modified_gmt`, `post_author`, `post_name`)
		  VALUES (@idProduct , '', _ten, 'inherit', 'open', 'open', 'attachment', 'image/jpeg', _value,  '', '', '', '', @nowTime, @nowTime_gmt, @nowTime, @nowTime_gmt, 7777, CONCAT(UUID(), '-', 'jpg'));
		  SET @idImage = last_insert_id();
       
          IF(_count = 0)
		  THEN
		       INSERT INTO `wp_postmeta` (`post_id`, `meta_key`, `meta_value`)
	           VALUES (@idProduct, '_thumbnail_id', @idImage);
          ELSE
               SET _product_image_gallery = concat(_product_image_gallery, @idImage);
		  END IF;
          
          IF(_value not like CONCAT(@checkImageLocal, '%')) THEN
			   IF(_count = 0) THEN

				   INSERT INTO `wp_postmeta` (`post_id`, `meta_key`, `meta_value`)
				   VALUES (@idProduct, 'fifu_image_url', _value);
			   ELSE 
				   INSERT INTO `wp_postmeta` (`post_id`, `meta_key`, `meta_value`)
				   VALUES (@idProduct, concat('fifu_image_url_', _count - 1), _value);
			   END IF;

		  ELSEIF _value2 <> 'null' THEN

			  INSERT INTO `wp_postmeta` (`post_id`, `meta_key`, `meta_value`)
			  VALUES (@idImage, '_wp_attachment_metadata', _value2);
		  END IF;

          IF(_value not like CONCAT(@checkImageLocal, '%')) THEN
			  INSERT INTO `wp_postmeta` (`post_id`, `meta_key`, `meta_value`)
			  VALUES (@idImage, '_wp_attached_file', concat(';',_value));
		  ELSE
			  INSERT INTO `wp_postmeta` (`post_id`, `meta_key`, `meta_value`)
			  VALUES (@idImage, '_wp_attached_file', REPLACE(_value, @checkImageLocal, ''));
          END IF;
          
          INSERT INTO `wp_postmeta` (`post_id`, `meta_key`, `meta_value`)
	      VALUES (@idImage, '_wp_attachment_image_alt', '');

          SET _listUrlImage = INSERT(_listUrlImage,1,_nextlen + 1,'');
		  SET _count = _count + 1;
          
          SET _list_image_meta = INSERT(_list_image_meta,1,_nextlen2 + 3,'');
		  SET _count2 = _count2 + 1;
          
		END LOOP;

    INSERT INTO `wp_postmeta` (`post_id`,   `meta_key`,      `meta_value`)
	VALUES                     (@idProduct, '_sku', _sku);
        
    INSERT INTO `wp_postmeta` (`post_id`,   `meta_key`,      `meta_value`)
	VALUES                     (@idProduct, 'total_sales', _total_sales);
    
    INSERT INTO `wp_postmeta` (`post_id`,   `meta_key`,      `meta_value`)
	VALUES                     (@idProduct, '_tax_status', _tax_status);
    
    INSERT INTO `wp_postmeta` (`post_id`,   `meta_key`,      `meta_value`)
	VALUES                     (@idProduct, '_tax_class', _tax_class);
    
    INSERT INTO `wp_postmeta` (`post_id`,   `meta_key`,      `meta_value`)
	VALUES                     (@idProduct, '_manage_stock', _manage_stock);
    
    INSERT INTO `wp_postmeta` (`post_id`,   `meta_key`,      `meta_value`)
	VALUES                     (@idProduct, '_backorders', _backorders);
    
    INSERT INTO `wp_postmeta` (`post_id`,   `meta_key`,      `meta_value`)
	VALUES                     (@idProduct, '_sold_individually', _sold_individually);
    
    INSERT INTO `wp_postmeta` (`post_id`,   `meta_key`,      `meta_value`)
	VALUES                     (@idProduct, '_virtual', _virtual);
    
    INSERT INTO `wp_postmeta` (`post_id`,   `meta_key`,      `meta_value`)
	VALUES                     (@idProduct, '_downloadable', _downloadable);
    
    INSERT INTO `wp_postmeta` (`post_id`,   `meta_key`,      `meta_value`)
	VALUES                     (@idProduct, '_download_limit', _download_limit);
    
    INSERT INTO `wp_postmeta` (`post_id`,   `meta_key`,      `meta_value`)
	VALUES                     (@idProduct, '_download_expiry', _download_expiry);
    
    INSERT INTO `wp_postmeta` (`post_id`,   `meta_key`,      `meta_value`)
	VALUES                     (@idProduct, '_stock', _stock);
    
	INSERT INTO `wp_postmeta` (`post_id`,   `meta_key`,      `meta_value`)
	VALUES                     (@idProduct, '_stock_status', _stockStatus);

	INSERT INTO `wp_postmeta` (`post_id`,   `meta_key`,      `meta_value`)
	VALUES                     (@idProduct, '_regular_price', _gia);

	IF _sale_price IS NOT NULL THEN
		INSERT INTO `wp_postmeta` (`post_id`,   `meta_key`,      `meta_value`)
		VALUES                    (@idProduct,  '_price', _sale_price);
	ELSE
		INSERT INTO `wp_postmeta` (`post_id`,   `meta_key`,      `meta_value`)
		VALUES                    (@idProduct,  '_price', _gia);
	END IF;

	IF _sale_price IS NOT NULL THEN
		INSERT INTO `wp_postmeta` (`post_id`,   `meta_key`,      `meta_value`)
		VALUES                    (@idProduct,  '_sale_price', _sale_price);
	END IF;

	IF _weight IS NOT NULL THEN
		INSERT INTO `wp_postmeta` (`post_id`,   `meta_key`,      `meta_value`)
		VALUES                    (@idProduct,  '_weight', _weight);
	END IF;

	IF _length IS NOT NULL THEN
		INSERT INTO `wp_postmeta` (`post_id`,   `meta_key`,      `meta_value`)
		VALUES                    (@idProduct,  '_length', _length);
	END IF;

	IF _width IS NOT NULL THEN
		INSERT INTO `wp_postmeta` (`post_id`,   `meta_key`,      `meta_value`)
		VALUES                    (@idProduct,  '_width', _width);
	END IF;

	IF _height IS NOT NULL THEN
		INSERT INTO `wp_postmeta` (`post_id`,   `meta_key`,      `meta_value`)
		VALUES                    (@idProduct,  '_height', _height);
	END IF;

    
	IF(CHAR_LENGTH(_product_image_gallery) > 0)
    THEN
		INSERT INTO `wp_postmeta` (`post_id`,   `meta_key`,      `meta_value`)
		VALUES                     (@idProduct, '_product_image_gallery', _product_image_gallery);
    END IF;
    
    SET @woo_version = (SELECT option_value FROM `wp_options` WHERE option_name = 'woocommerce_version' LIMIT 1);
    IF(@woo_version IS NULL) THEN SET @woo_version = '3.9.1'; END IF;
    INSERT INTO `wp_postmeta` (`post_id`,   `meta_key`,      `meta_value`)
	VALUES                     (@idProduct, '_product_version', @woo_version);
    
    INSERT INTO `wp_postmeta` (`post_id`,   `meta_key`,      `meta_value`)
	VALUES                     (@idProduct, '_wc_average_rating', '0');
    
    INSERT INTO `wp_postmeta` (`post_id`,   `meta_key`,      `meta_value`)
	VALUES                     (@idProduct, '_wc_review_count', '0');
    
    INSERT INTO `wp_postmeta` (`post_id`,   `meta_key`,      `meta_value`)
	VALUES                     (@idProduct, '_wc_rating_count', '');

    IF(_parentProduct = 0)
	THEN

		SET @idTermProductType = (SELECT term_id FROM `wp_terms` WHERE  `name` =_product_type LIMIT 1);
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

		INSERT INTO `wp_term_relationships` (`object_id`, `term_taxonomy_id`, `term_order`)
		VALUES (@idProduct, @term_taxonomy_product_type_id, 0);
		UPDATE `wp_term_taxonomy` SET count=count+1 WHERE `term_taxonomy_id` = @term_taxonomy_product_type_id;

		/*  06.02.2021 Update lại để thêm được nhiều term
		SET @term_taxonomy_category_id = (SELECT term_taxonomy_id FROM `wp_term_taxonomy` WHERE `term_id` = _idCategory AND `taxonomy`='product_cat' LIMIT 1);
		IF(@term_taxonomy_category_id IS NOT NULL)
		THEN
			INSERT INTO `wp_term_relationships` (`object_id`, `term_taxonomy_id`, `term_order`)
			VALUES (@idProduct, @term_taxonomy_category_id, 0);

		    UPDATE `wp_term_taxonomy` SET count=count+1 WHERE `term_taxonomy_id` = @term_taxonomy_category_id;
		END IF;
		
		IF(EXISTS(SELECT 1 FROM `wp_termmeta` WHERE `term_id`=_idCategory and `meta_key`='product_count_product_cat' LIMIT 1) = '0')
		THEN
			INSERT INTO `wp_termmeta`(`term_id`,`meta_key`, `meta_value`)
			VALUES                 (_idCategory, 'product_count_product_cat', '1');
		ELSE
			UPDATE `wp_termmeta`
				SET `meta_value`=meta_value+1
			WHERE `term_id`=_idCategory and `meta_key`='product_count_product_cat';
		END IF;
		*/

		-- 06.02.2021 Update lại để thêm được nhiều term
		set _count = 0;
		iterator:
			LOOP-- https://stackoverflow.com/questions/37213789/split-a-string-and-loop-through-values-in-mysql-procedure
			IF CHAR_LENGTH(TRIM(_idCategory)) = 0 OR _idCategory IS NULL THEN
				LEAVE iterator;
			END IF;
			SET _next = SUBSTRING_INDEX(_idCategory,';',1);
			SET _nextlen = CHAR_LENGTH(_next);
			SET _value = TRIM(_next);
			
			SET @term_taxonomy_category_id = (SELECT term_taxonomy_id FROM `wp_term_taxonomy` WHERE `term_id` = _value AND `taxonomy`='product_cat' LIMIT 1);
			IF(@term_taxonomy_category_id IS NOT NULL)
			THEN
				INSERT INTO `wp_term_relationships` (`object_id`, `term_taxonomy_id`, `term_order`)
				VALUES (@idProduct, @term_taxonomy_category_id, 0);
				
				-- Tăng đếm số sản phẩm của 1 category
				UPDATE `wp_term_taxonomy` SET count=count+1 WHERE `term_taxonomy_id` = @term_taxonomy_category_id;
			END IF;
			
			IF(EXISTS(SELECT 1 FROM `wp_termmeta` WHERE `term_id`=_value and `meta_key`='product_count_product_cat' LIMIT 1) = '0')
			THEN
				INSERT INTO `wp_termmeta`(`term_id`,`meta_key`, `meta_value`)
				VALUES                 (_value, 'product_count_product_cat', '1');
			ELSE
				UPDATE `wp_termmeta`
					SET `meta_value`=meta_value+1
				WHERE `term_id`=_value and `meta_key`='product_count_product_cat';
			END IF;
			
			SET _idCategory = INSERT(_idCategory,1,_nextlen + 1,'');
			SET _count = _count + 1;
			END LOOP;
        -- end Category
    END IF;



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
				INSERT INTO `wp_term_relationships` (`object_id`, `term_taxonomy_id`, `term_order`)
				VALUES (@idProduct, @term_taxonomy_tag_id, 0);

			    UPDATE `wp_term_taxonomy` SET count=count+1 WHERE `term_taxonomy_id` = @term_taxonomy_tag_id;
			END IF;			
			
			IF(EXISTS(SELECT 1 FROM `wp_termmeta` WHERE `term_id`=_value and `meta_key`='product_count_product_tag' LIMIT 1) = '0')
			THEN
				INSERT INTO `wp_termmeta`(`term_id`,`meta_key`, `meta_value`)
				VALUES                 (_value, 'product_count_product_tag', '1');
			ELSE
				UPDATE `wp_termmeta`
					SET `meta_value`=meta_value+1
				WHERE `term_id`=_value and `meta_key`='product_count_product_tag';
			END IF;
          
          SET _lstTagId = INSERT(_lstTagId,1,_nextlen + 1,'');
		  SET _count = _count + 1;
		END LOOP;
    
    SET @downloadable_lookup = 0;
    IF(_downloadable <> 'no')
    THEN
		SET @downloadable_lookup = 1;
    END IF;
    
    SET @virtual_lookup = 0;
    IF(_virtual <> 'no')
    THEN
		SET @virtual_lookup = 1;
    END IF;
    
    INSERT INTO `wp_wc_product_meta_lookup`(`product_id`, `sku`, `virtual`, `downloadable`,
    `min_price`, `max_price`,
    `onsale`, `stock_quantity`, `stock_status`, `rating_count`, `average_rating`, `total_sales`)
    VALUES
    (@idProduct, _sku, @virtual_lookup, @downloadable_lookup,
    _gia, _gia,
    0, null, _stockStatus, 0, 0.00, 0);

    RETURN @idProduct;
END;