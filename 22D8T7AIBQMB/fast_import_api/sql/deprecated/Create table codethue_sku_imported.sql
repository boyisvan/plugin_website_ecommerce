CREATE TABLE `codethue_sku_imported` (
  `ID` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `sku` TINYTEXT NULL,
  `id_product` BIGINT(20) NULL,
  `time_import` DATETIME NULL,
  PRIMARY KEY (`ID`));
