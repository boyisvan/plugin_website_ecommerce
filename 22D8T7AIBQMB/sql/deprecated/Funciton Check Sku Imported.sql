CREATE FUNCTION `check_sku_imported_in_five_minute`(_sku TINYTEXT)
RETURNS BIGINT(20)
BEGIN
	SET @five_minute = SUBTIME(NOW(), '0:5');
	SET @id_product = (SELECT `id_product` FROM `codethue_sku_imported` WHERE `sku` = _sku AND `time_import` >= @five_minute ORDER BY `time_import` DESC LIMIT 1);
    RETURN @id_product;--
END;