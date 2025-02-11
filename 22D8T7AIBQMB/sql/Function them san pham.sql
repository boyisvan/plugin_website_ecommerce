CREATE FUNCTION `create_product`(_ten MEDIUMTEXT, _slug TEXT, _moTa LONGTEXT, _moTaNgan LONGTEXT,
_idCategory TEXT, _product_type TINYTEXT, _listUrlImage MEDIUMTEXT, _list_image_meta LONGTEXT, _lstTagId TEXT,
_parentProduct BIGINT(20),
_lst_meta_key MEDIUMTEXT, _lst_meta_value MEDIUMTEXT
, _comment_status TINYTEXT, _product_status TINYTEXT, _id_shipping_class TINYTEXT)
RETURNS BIGINT(20)
BEGIN
	DECLARE _product_image_gallery NVARCHAR(4000) DEFAULT '';
    -- Để xử lý link ảnh
	DECLARE _next TEXT DEFAULT NULL;
	DECLARE _nextlen INT DEFAULT NULL;
	DECLARE _value TEXT DEFAULT NULL;
    DECLARE _count INT DEFAULT 0;
    -- Các giá trị dùng về sau
    DECLARE _idTermProductType INT DEFAULT 0;
    DECLARE _product_count_product_cat INT DEFAULT 0;
    
    -- Để xử lý meta
    DECLARE _next2 TEXT DEFAULT NULL;
	DECLARE _nextlen2 INT DEFAULT NULL;
	DECLARE _value2 TEXT DEFAULT NULL;
    DECLARE _count2 INT DEFAULT 0;

    SET @nowTime = UTC_TIMESTAMP();-- Tí còn cộng time stame mà current_timestamp();--
	SET @nowTime_gmt = UTC_TIMESTAMP();
    SET @gmt_offset = (SELECT option_value FROM `wp_options` WHERE option_name = 'gmt_offset' LIMIT 1);
    IF(@gmt_offset IS NOT NULL) THEN
		SET @nowTime = DATE_ADD(UTC_TIMESTAMP(), INTERVAL @gmt_offset HOUR);
    END IF;
    -- SET @xx = (SELECT abc() as xx);
    
	-- Để cho vào meta_lookup
	SET @taxStatus = 'taxable';
	SET @taxClass = '';
	SET @onsale = 1; -- simple product = 1, variable = 0, variation = 0

	-- Bước 1: Thêm sản phẩm vào wp_posts và lấy ra Id
    SET @postType='product';
    IF(_parentProduct <> 0)
    THEN
		SET @taxClass = 'parent'; -- variation theo parent
		SET @onsale   = 0;        -- variation mặc định 0

		SET @try_count_slug_variant = 1;
        SET @orginal_slug = _slug;
		SET @postType='product_variation';
		-- FIXNE: Đoạn này có thể làm ảnh hưởng đến tộc độ
        WHILE(EXISTS(SELECT `post_name` FROM `wp_posts` WHERE `post_parent` = _parentProduct AND `post_name` = _slug LIMIT 1)) DO
			SET @try_count_slug_variant = @try_count_slug_variant + 1;
            SET _slug = CONCAT(CONCAT(@orginal_slug, '-'), @try_count_slug_variant);
		END WHILE;
    END IF;
    SET @siteurl = (SELECT option_value FROM `wp_options` WHERE `option_name` = 'siteurl' LIMIT 1);
	INSERT INTO `wp_posts`(`post_content`, `post_title`, `post_status`, `comment_status`, `ping_status`, `post_name`, `post_type`, `post_excerpt`, `to_ping`, `pinged`, `post_content_filtered`, `post_date`, `post_date_gmt`, `post_modified`, `post_modified_gmt`, `post_parent`, `post_author`, `guid`)
	VALUES (_moTa, _ten, _product_status, _comment_status, 'closed', _slug, @postType, _moTaNgan, '', '', '', @nowTime, @nowTime_gmt, @nowTime, @nowTime_gmt, _parentProduct, 1, concat(@siteurl, '/product/', _slug));
    
	SET @idProduct = last_insert_id();

	SET @checkImageLocal = (SELECT option_value FROM `wp_options` WHERE option_name = 'siteurl' LIMIT 1);
    SET @checkImageLocal = CONCAT(@checkImageLocal, '/wp-content/uploads/');

	-- Bước 2: Thêm ảnh và meta cho ảnh
	-- kiểm tra link ảnh xem có phải đã upload lên site hay không? Nếu là link ngoài thì tạo fifu
    IF(_listUrlImage not like CONCAT(@checkImageLocal, '%') AND _listUrlImage IS NOT NULL AND _listUrlImage <> '') THEN
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
		  SET _nextlen = CHAR_LENGTH(_next);
		  SET _value = TRIM(_next);
          
          SET _next2 = SUBSTRING_INDEX(_list_image_meta,'<->',1);
		  SET _nextlen2 = CHAR_LENGTH(_next2);
		  SET _value2 = TRIM(_next2);
		  SET @imgAuthor 		 = 77777;
		  IF(_value like CONCAT(@checkImageLocal, '%')) THEN SET @imgAuthor = 1; END IF;
          
		  SET @exists_img_id = NULL;
		  -- [v2.1.2 10.4.2024]
		  -- Nếu lầ variant thì check xem ảnh này đã có ở product cha hay chưa, nếu có thì dùng luôn
		  -- Vì ở code readCSV trên tool thì images của product sẽ bao gồm tất cả image của variant
		  IF(_parentProduct IS NOT NULL AND _parentProduct <> 0)
		  THEN
		    -- WHERE theo ID nên hy vọng không ảnh hưởng quá nhiều đến tốc dộ
		  	SET @exists_img_id = (SELECT ID FROM `wp_posts` WHERE post_parent = _parentProduct AND `post_type` = 'attachment' AND `guid` = _value LIMIT 1);
		  END IF;

		  SET @idImage = @exists_img_id;
		  IF(@exists_img_id IS NULL)
		  THEN
			INSERT INTO `wp_posts`(`post_parent`, `post_content`,  `post_title`, `post_status`, `comment_status`, `ping_status`, `post_type`, `post_mime_type`, `guid`, `post_excerpt`, `to_ping`, `pinged`, `post_content_filtered`, `post_date`, `post_date_gmt`, `post_modified`, `post_modified_gmt`, `post_author`, `post_name`)
			VALUES (@idProduct , '', _ten, 'inherit', 'open', 'open', 'attachment', 'image/jpeg', _value,  '', '', '', '', @nowTime, @nowTime_gmt, @nowTime, @nowTime_gmt, @imgAuthor, CONCAT(UUID(), '-', 'jpg'));
			SET @idImage = last_insert_id();
		  END IF;
       
          IF(_count = 0)
		  THEN
		       INSERT INTO `wp_postmeta` (`post_id`, `meta_key`, `meta_value`)
	           VALUES (@idProduct, '_thumbnail_id', @idImage);
          ELSE
               SET _product_image_gallery = concat(_product_image_gallery, @idImage);
		  END IF;
          
		  -- Tạo meta cho ảnh link ngoài
          IF(_value not like CONCAT(@checkImageLocal, '%')) THEN
		  	 IF(_count = 0) THEN
			   INSERT INTO `wp_postmeta` (`post_id`, `meta_key`, `meta_value`)
			   VALUES (@idProduct, 'fifu_image_url', _value);
			 ELSE
			   INSERT INTO `wp_postmeta` (`post_id`, `meta_key`, `meta_value`)
			   VALUES (@idProduct, concat('fifu_image_url_', (_count - 1)), _value);
			 END IF;
			 -- 18.01.2022: Fake để hiển thị ảnh khi bấm vào ảnh ở media
			 INSERT INTO `wp_postmeta` (`post_id`, `meta_key`, `meta_value`)
			 VALUES (@idImage, '_wp_attachment_metadata', 'a:1:{i:0;s:15:"giaiphapmmo.net";}');
			   /*
			   INSERT INTO `wp_postmeta` (`post_id`, `meta_key`, `meta_value`)
			   VALUES (@idProduct, 'fifu_image_alt', _ten);
			   */
		  ELSEIF _value2 <> 'null' THEN
			  INSERT INTO `wp_postmeta` (`post_id`, `meta_key`, `meta_value`)
			  VALUES (@idImage, '_wp_attachment_metadata', _value2);
		  END IF;
		  
		  -- Tạo meta _wp_attached_file cho ảnh link ngoài và ảnh upload lên site
          IF(_value not like CONCAT(@checkImageLocal, '%')) THEN
			  INSERT INTO `wp_postmeta` (`post_id`, `meta_key`, `meta_value`)
			  -- VALUES (@idImage, '_wp_attached_file', concat(';',_value));
			  VALUES (@idImage, '_wp_attached_file', _value);
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

	-- Bước 3: Thêm meta cho sản phẩm
	IF(CHAR_LENGTH(_product_image_gallery) > 0)
    THEN
		INSERT INTO `wp_postmeta` (`post_id`,   `meta_key`,      `meta_value`)
		VALUES                     (@idProduct, '_product_image_gallery', _product_image_gallery);
    END IF;
/*    
    SET @woo_version = (SELECT option_value FROM `wp_options` WHERE option_name = 'woocommerce_version' LIMIT 1);
    IF(@woo_version IS NULL) THEN SET @woo_version = '3.9.1'; END IF;
    INSERT INTO `wp_postmeta` (`post_id`,   `meta_key`,      `meta_value`)
	VALUES                     (@idProduct, '_product_version', @woo_version);
*/

	SET @sku = '';
    SET @gia = 0;
    SET @downloadable = 'no';
    SET @_virtual = 'no';
    SET @stockStatus = 'outofstock';
    SET @stockQuantity = null;
	
	-- custom meta
    iterator:
		LOOP
		  IF CHAR_LENGTH(TRIM(_lst_meta_key)) = 0 OR _lst_meta_key IS NULL OR CHAR_LENGTH(TRIM(_lst_meta_value)) = 0 OR _lst_meta_value IS NULL THEN
			LEAVE iterator;
		  END IF;
		  SET _next = SUBSTRING_INDEX(_lst_meta_key,'<->',1);
		  SET _nextlen = CHAR_LENGTH(_next);
		  SET _value = TRIM(_next);
          
          SET _next2 = SUBSTRING_INDEX(_lst_meta_value,'<->',1);
		  SET _nextlen2 = CHAR_LENGTH(_next2);
		  SET _value2 = TRIM(_next2);
          
          INSERT INTO `wp_postmeta` (`post_id`,   `meta_key`, `meta_value`)
		  VALUES                     (@idProduct, _value,     _value2);
          
          IF(_value     = '_sku')          THEN SET @sku          = _value2;
          ELSEIF(_value = '_price')        THEN SET @gia          = _value2;
          ELSEIF(_value = '_downloadable') THEN SET @downloadable = _value2;
          ELSEIF(_value = '_virtual')      THEN SET @_virtual     = _value2;
          ELSEIF(_value = '_stock_status') THEN SET @stockStatus  = _value2;
		  ELSEIF(_value = '_tax_class')    THEN SET @taxClass  = _value2;
		  ELSEIF(_value = '_tax_status')   THEN SET @taxStatus  = _value2;
		  ELSEIF(_value = '_stock')         THEN SET @stockQuantity  = _value2;
		  END IF;
          
          SET _lst_meta_key = INSERT(_lst_meta_key, 1, _nextlen + 3, '');
		  SET _count = _count + 1;
          
          SET _lst_meta_value = INSERT(_lst_meta_value, 1, _nextlen2 + 3, '');
		  SET _count2 = _count2 + 1;
		END LOOP;
    
    -- Bước 4: Thêm term cho sản phẩm type, category. Chỉ có parrent=0 mới thêm term
    IF(_parentProduct = 0)
	THEN
		-- Term simple, variable, downloadable
		SET @idTermProductType = (SELECT term_id FROM `wp_terms` WHERE  `name` = _product_type LIMIT 1);
		IF(@idTermProductType is null)
		THEN
			INSERT INTO `wp_terms` (`name`, `slug`, `term_group`) VALUES (_product_type, _product_type, 0);
			SET @idTermProductType = last_insert_id();
		END IF;
		
		-- Thêm term simple vào product taxonomy
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
		
        /* 06.02.2021 Update lại để thêm được nhiều term
		-- Category
        -- Lấy thông tin category trong term_taxonomy
		SET @term_taxonomy_category_id = (SELECT term_taxonomy_id FROM `wp_term_taxonomy` WHERE `term_id` = _idCategory AND `taxonomy`='product_cat' LIMIT 1);
		IF(@term_taxonomy_category_id IS NOT NULL)
		THEN
			INSERT INTO `wp_term_relationships` (`object_id`, `term_taxonomy_id`, `term_order`)
			VALUES (@idProduct, @term_taxonomy_category_id, 0);
            
            -- Tăng đếm số sản phẩm của 1 category
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
        -- end category
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
    
    
    -- Tag. Mệt vl để đây đã
    set _count = 0;
    iterator:
		LOOP-- https://stackoverflow.com/questions/37213789/split-a-string-and-loop-through-values-in-mysql-procedure
		  IF CHAR_LENGTH(TRIM(_lstTagId)) = 0 OR _lstTagId IS NULL THEN
			LEAVE iterator;
		  END IF;
		  SET _next = SUBSTRING_INDEX(_lstTagId,';',1);
		  SET _nextlen = CHAR_LENGTH(_next);
		  SET _value = TRIM(_next);
          
          -- thêm term
          SET @term_taxonomy_tag_id = (SELECT term_taxonomy_id FROM `wp_term_taxonomy` WHERE `term_id` = _value AND `taxonomy`='product_tag' LIMIT 1);
			IF(@term_taxonomy_tag_id IS NOT NULL)
			THEN
				INSERT INTO `wp_term_relationships` (`object_id`, `term_taxonomy_id`, `term_order`)
				VALUES (@idProduct, @term_taxonomy_tag_id, 0);
                
                -- Tăng đếm số sản phẩm của 1 category
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
        -- end Tag

		-- shipping class
		IF (_id_shipping_class IS NOT NULL AND _id_shipping_class <> '') THEN
			SET @term_taxonomy_shipping_class_id = (SELECT term_taxonomy_id FROM `wp_term_taxonomy` WHERE `term_id` = _id_shipping_class AND `taxonomy`='product_shipping_class' LIMIT 1);
			IF(@term_taxonomy_shipping_class_id IS NOT NULL) THEN
				INSERT INTO `wp_term_relationships` (`object_id`, `term_taxonomy_id`, `term_order`)
				VALUES (@idProduct, @term_taxonomy_shipping_class_id, 0);
                
                -- Tăng đếm số sản phẩm của 1 category
			    UPDATE `wp_term_taxonomy` SET count=count+1 WHERE `term_taxonomy_id` = @term_taxonomy_shipping_class_id;
			END IF;
		END IF;
		-- end shipping class
    
    -- 11/4/2020 Chống import duplicate
    /*
    IF(_parentProduct = 0)
    THEN
		INSERT INTO `codethue_sku_imported`(`sku`, `id_product`, `time_import`)
		VALUES (@sku, @idProduct, NOW());
    END IF;
    */
    -- 11/4/2020

    SET @downloadable_lookup = 0;
    IF(@downloadable <> 'no')
    THEN
		SET @downloadable_lookup = 1;
    END IF;
    
    SET @virtual_lookup = 0;
    IF(@_virtual <> 'no')
    THEN
		SET @virtual_lookup = 1;
    END IF;

	-- Có một số site clone data bị trùng ở bảng này
	DELETE FROM `wp_wc_product_meta_lookup` WHERE `product_id` = @idProduct;

	-- cả product và variation đều phải thêm vào đây
    INSERT INTO `wp_wc_product_meta_lookup`(`product_id`, `sku`, `virtual`, `downloadable`,
    `min_price`, `max_price`,
    `onsale`, `stock_quantity`, `stock_status`,
	`rating_count`, `average_rating`, `total_sales`,
	`tax_status`, `tax_class`)
    VALUES
    (@idProduct, @sku, @virtual_lookup, @downloadable_lookup,
    @gia, @gia,
    @onsale, @stockQuantity, @stockStatus,
	0, 0.00, 0,
	@taxStatus, @taxClass);

	IF(_parentProduct <> 0) THEN
		UPDATE `wp_wc_product_meta_lookup`
		SET    `min_price`  = IF(`min_price` = 0, @gia , IF(`min_price` > @gia, @gia, `min_price`)), -- nếu min_price = 0 => lấy luôn variation price
		       `max_price`  =                            IF(`max_price` < @gia, @gia, `max_price`),
			   `onsale`     = @onsale
		WHERE  `product_id` = _parentProduct;

		-- đắn đo quá nhưng thôi cứ thêm vào cho đúng cấu trúc
		INSERT INTO `wp_postmeta` (`post_id`,      `meta_key`, `meta_value`)
			VALUES                (_parentProduct, '_price',   @gia);
		END IF;

    RETURN @idProduct;
END;