CREATE PROCEDURE `clear_woocommeerce_transients_price`(_idParent INT)
BEGIN
	IF(_idParent IS NOT null)
    THEN
        DELETE FROM `wp_options` WHERE `option_name` = CONCAT('_transient_timeout_wc_var_prices_', _idParent);
        DELETE FROM `wp_options` WHERE `option_name` = CONCAT('_transient_wc_var_prices_', _idParent);
	ELSE
        DELETE FROM `wp_options` WHERE `option_name` like '_transient_timeout_wc_var_prices_%';
        DELETE FROM `wp_options` WHERE `option_name` like '_transient_wc_var_prices_%';
    END IF;
END;