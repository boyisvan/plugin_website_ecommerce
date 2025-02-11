CREATE PROCEDURE `clear_woocommeerce_transients`(_idParent INT)
BEGIN
	IF(_idParent IS NOT null)
    THEN
		DELETE FROM `wp_options` WHERE `option_name` like CONCAT('%_', _idParent);
	ELSE
        DELETE FROM `wp_options` WHERE `option_name` like '_transient_timeout_wc_child_has_dimensions_%';
        DELETE FROM `wp_options` WHERE `option_name` like '_transient_timeout_wc_child_has_weight_%';
        DELETE FROM `wp_options` WHERE `option_name` like '_transient_timeout_wc_product_children_%';
        DELETE FROM `wp_options` WHERE `option_name` like '_transient_timeout_wc_related_%';
        DELETE FROM `wp_options` WHERE `option_name` like '_transient_timeout_wc_var_prices_%';
        DELETE FROM `wp_options` WHERE `option_name` like '_transient_wc_child_has_dimensions_%';
        DELETE FROM `wp_options` WHERE `option_name` like '_transient_wc_child_has_weight_%';
        DELETE FROM `wp_options` WHERE `option_name` like '_transient_wc_product_children_%';
        DELETE FROM `wp_options` WHERE `option_name` like '_transient_wc_related_%';
        DELETE FROM `wp_options` WHERE `option_name` like '_transient_wc_var_prices_%';
    END IF;    
END;