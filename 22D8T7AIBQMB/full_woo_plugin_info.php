<?php

/*
Plugin Name: Woo POD Master - Hooking
Description:  Kết nối và cung cấp dữ liệu cho các chức năng trên tool Woo POD Master Supper (AKA: Super Fast Woocommerce API)
Version: 2.1.4 (new: Update new order key)
Plugin URI: https://www.facebook.com/giaiphapmmodotnet
Author: GiaiPhapMMO.VN
License: GPL2
*/

define('CODETHUE_FULLWOO_PLUGIN_URL', plugin_dir_url(__FILE__));

add_filter('plugin_action_links_'.plugin_basename(__FILE__), 'codethue_fullwoo_add_plugin_page_settings_link_v2_0');
function codethue_fullwoo_add_plugin_page_settings_link_v2_0( $links ) {
    $links[] = '<a href="' . CODETHUE_FULLWOO_PLUGIN_URL . 'setup.php' . '" ' . 'target="_blank"' .'>' . __('Setup Woo POD Master') . '</a>';
    $links[] = '<a href="' . CODETHUE_FULLWOO_PLUGIN_URL . 'update.php' . '" ' . 'target="_blank"' .'>' . __('Update') . '</a>';
	$links[] = '<br><center style="width:275px;color:white;background-color:#31d773;border-radius:0px 30px;"><a style="color:white" href="https://giaiphapmmo.vn">giaiphapmmo.vn</a></center>';
	return $links;
}

function codethuegiare_fullwoo_active_v2_0() {
	require_once 'init_file_ini_config.php';
	$currentDir = dirname(__FILE__);
	if(!init_file_ini_config($currentDir . '/config/database_config.ini'))
		die("Gặp lỗi trong quá trình tạo cấu hình, <a href='" . CODETHUE_FULLWOO_PLUGIN_URL . "init_file_ini_config.php?action=create_ini_config' target='_blank'><b>BẤM ĐÂY</b></a> để xem chi tiết");
	// require_once $currentDir . '/api/product/custom_cron_schedule_post.php';
	// wpms_init();
}
register_activation_hook( __FILE__, 'codethuegiare_fullwoo_active_v2_0' );

// create custom plugin settings menu
add_action('admin_menu', 'giaiphapmmo_setting_woopodmaster_create_menu_v2_0');
function giaiphapmmo_setting_woopodmaster_create_menu_v2_0() {
    add_menu_page('WOO POD Master Hooking Setting', 'WOO POD Master', 'administrator', __FILE__, 'giaiphapmmo_woopodmaster_settings_page_v2_0' , plugins_url('/icon.png', __FILE__) );
}
function giaiphapmmo_woopodmaster_settings_page_v2_0() {
	// $hide_range_price = get_option( 'code_thue_moneysite_hide_price_range' );
	$cur_charset = 'Chưa cài hooking';
	$cur_collate = 'Chưa cài hooking';
	$currentDir = dirname(__FILE__);
	$keyHooking = basename(dirname( __FILE__ ));
	$pinCode = '';
	// if(str_contains($keyHooking, '_')){
	if(strpos($keyHooking, '_') != false){
		$pinCode = explode('_', $keyHooking)[1];
		$keyHooking = explode('_', $keyHooking)[0];
	}
	$iniPath = $currentDir . '/config/database_config.ini';
	if(file_exists($iniPath)){
		$ini = parse_ini_file($iniPath);
		$cur_charset = $ini['charset'];
		$cur_collate = $ini['collate'];
	}
?>
    <div class="wrap">
		<h1>Cài đặt Hooking Woo POD Master V2</h1>
		<hr/>
		<h4>Key hiện tại:
			<span style="color:#047e13; font-size:120%">
				<b>
					<?php echo $keyHooking; ?>
				</b>
			</span>
		</h4>
		<h4>PIN hiện tại:
			<span style="color:#047e13; font-size:120%">
				<b>
					<?php echo $pinCode; ?>
				</b>
			</span>
		</h4>
		<hr/>
		<!-- <h3 style="background-color: #e7e7e7;padding: 8px; color:red;">Chú ý: Các mục dưới đây chỉ thực hiện khi có hướng dẫn</h3> -->
		<h3 style="background-color: #e7e7e7;padding: 8px;">Các chế độ cài đặt Hooking</h3>

		<button><a style="text-decoration:none;" href="<?php echo(CODETHUE_FULLWOO_PLUGIN_URL) . 'setup.php' ?>" target="_blank">Setup Hooking <b style="color:red">(tự động xóa file config tạo lại)</b></a></button>
		<hr/>
		<button><a style="text-decoration:none;" href="<?php echo(CODETHUE_FULLWOO_PLUGIN_URL) . 'setup.php?action=skip_check_wp_config' ?>" target="_blank">Setup Hooking <b style="color:#047e13">(đã tạo file config thủ công)</b></a></button>
		<hr/>
		<button><a style="text-decoration:none;" href="<?php echo(CODETHUE_FULLWOO_PLUGIN_URL) . 'init_file_ini_config.php?action=create_ini_config' ?>" target="_blank">Tạo file ini config</a></button>
		<hr/>
		<button><a style="text-decoration:none;" href="<?php echo(CODETHUE_FULLWOO_PLUGIN_URL) . 'update.php' ?>" target="_blank">Cập nhật hooking</a></button>
		<hr/>
		<!-- Chuyển đổi bảng mã -->
		<h3 style="background-color: #e7e7e7;padding: 8px;">Chuyển đổi bảng mã (<a href='https://youtu.be/mF1P3MIr-zc' target="_blank">Bấm để xem hướng dẫn</a>)</h3>

		<form action="<?php echo(CODETHUE_FULLWOO_PLUGIN_URL) . 'add_on.php?action=convert_database_and_table' ?>" method="post" target="_blank">
			<table class="form-table">
				<label>Cấu hình hiện tại: <b>Character set=<?php echo $cur_charset ?>, collate=<?php echo $cur_collate ?></b><hr/></label>
				<label>Character set:</label>
				<input type="text" name="char_set" value="utf8mb4"></input>
				<label>Collate:</label>
				<input type="text" name="collate" value="utf8mb4_general_ci"></input>
				<input type="submit" value="Xác nhận chuyển đổi (database + table)">
			</table>
		</form>
		<hr/>
		<!-- Chuyển đổi bảng mã -->
		<form action="<?php echo(CODETHUE_FULLWOO_PLUGIN_URL) . 'add_on.php?action=convert_database' ?>" method="post" target="_blank">
			<table class="form-table">
				<label>Character set:</label>
				<input type="text" name="char_set" value="utf8mb4"></input>
				<label>Collate:</label>
				<input type="text" name="collate" value="utf8mb4_general_ci"></input>
				<input type="submit" value="Xác nhận chuyển đổi (riêng database)">
			</table>
		</form>
		<hr/>
		<!-- Fix min max price big update v2 -->
		<h3 style="background-color: #e7e7e7;padding: 8px;color:red">Tính năng sửa một số lỗi (chỉ sử dụng khi có hướng dẫn)</h3>
		<form action="<?php echo(CODETHUE_FULLWOO_PLUGIN_URL) . 'add_on.php?action=fix_min_max_price_big_update_v2' ?>" method="post" target="_blank">
			<table class="form-table">
				<input type="submit" value="Sửa lỗi lọc dữ liệu theo khoảng giá">
			</table>
		</form>
		<hr/>
    </div>
<?php } ?>