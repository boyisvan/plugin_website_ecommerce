CREATE FUNCTION `insert_simple_product_fast_api`(_sku MEDIUMTEXT, _ten MEDIUMTEXT, _slug TEXT, _moTa TEXT, _moTaNgan LONGTEXT, _gia DOUBLE, _sale_price DOUBLE,
_stockStatus MEDIUMTEXT, _total_sales INT , _tax_status TINYTEXT, _tax_class TINYTEXT,
_manage_stock TINYTEXT, _backorders TINYTEXT, _sold_individually TINYTEXT, _virtual TINYTEXT, _downloadable TINYTEXT,
_download_limit INT, _download_expiry INT, _stock INT,
_idCategory TEXT, _listUrlImage MEDIUMTEXT, _list_image_meta LONGTEXT,_lstTagId TEXT, _product_type TEXT,
_post_status TINYTEXT,
_weight DOUBLE, _height DOUBLE, _width DOUBLE, _length DOUBLE)
RETURNS BIGINT(20)
BEGIN
	SET @res = insert_product_fast_api(_sku, _ten, _slug, _moTa, _moTaNgan, _gia, _sale_price,
	_stockStatus, _total_sales, _tax_status, _tax_class,
	_manage_stock, _backorders, _sold_individually, _virtual, _downloadable,
	_download_limit, _download_expiry, _stock,
	_idCategory, _product_type, _listUrlImage, _list_image_meta, _lstTagId,
	0, _post_status,
	_weight, _height, _width, _length);
    RETURN @res;
END;
