<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://flht.ir
 * @since             1.0.0
 * @package           wc_excel_products_price_updater
 *
 * @wordpress-plugin
 * Plugin Name:       Woocommerce Excel Products Price Updater
 * Plugin URI:        https://flht.ir/wc_excel_priceupdater
 * Description:       Drag and drop xlsx (csv coming soon) files and update products list all together.

 * Version:           1.0.0
 * Author:            Mohammad Falahat
 * Author URI:        https://flht.ir
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wc-excel-products-price-updater
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

include __DIR__.'/'.'simplexlsx.class.php';

function getIndex($array, $needle){
    $reversedArray = array_flip(array_filter($array));
    return $reversedArray[$needle];
}
/** PSID : sku | PUPID : user price | PNID : name | PRATIO : ratio (zarib) */
function updateProductsPrice($array, $PSID, $PUPID, $PNID, $PRATIO, $startRow=1){
	global $table_prefix, $wpdb;
	$changes = 0;
	echo "<div class=\"price-update-box\">";
    for($I = $startRow ; $I <= (count($array) - $startRow) ; $I++){
		ob_start();
        $ProceedPrice = (intval(str_replace(',', '', $array[$I][$PUPID])) / 10 );

		// multiply to a number
		if (preg_match("/^\d+$/", $array[$I][$PRATIO])){
			$ProceedPrice = (intval(str_replace(',', '', $array[$I][$PUPID])) / 10  * intval($array[$I][$PRATIO]));
		}

        // UPDATE price in database and then verify: 
		$wpdb->query("UPDATE `".$table_prefix."wc_product_meta_lookup` SET `min_price` = '".$ProceedPrice.".0000' , `max_price` = '".$ProceedPrice.".0000' WHERE `sku` = '".$array[$I][$PSID]."'");
		$product_id = $wpdb->get_row("SELECT `post_id` FROM `".$table_prefix."postmeta`  WHERE `meta_key` = '_sku' AND `meta_value` = '".$array[$I][$PSID]."'")->post_id;
		if($product_id != null and $product_id > 0)
		{
			$wpdb->query("UPDATE `".$table_prefix."postmeta` SET `meta_value`='".$ProceedPrice."' WHERE `meta_key` LIKE '%_price' AND `post_id`=".intval($product_id));
			if ($wpdb->rows_affected >= 1)
			{
				$changes++;
				echo "<p class=\"price-update-paragraph green\">".$array[$I][$PNID] . " با قیمت جدید : " . ($ProceedPrice/1000) . " هزار تومان، به روز رسانی شد.</p>";
			}
		}
		else // DETECT WHICH PRODUCTS ARE NOT EXISTS IN WEBSITE
		{
        	echo "<p class=\"price-update-paragraph yellow\">".$array[$I][$PNID] . " با کد : " . $array[$I][$PSID] . " در وبسایت موجود نیست</p>";
		}
		ob_flush();
    }
	if($changes==0) echo "<p class=\"price-update-paragraph cadetblue\">تمام قیمت ها به روز می باشند.</p>";
	echo "</div>";
}
 
 function excel_importer_function(){


	try{
		if(isset($_FILES['xlsx']))
		{
			$workbook = new SimpleXLSX($_FILES['xlsx']['tmp_name']);

			$spreadsheet1=$workbook->rows();
	
			/** Extract ProductShortcut Index And ProductUserPrice Index */
			$PSID = getIndex($spreadsheet1[1], 'شناسه محصول');
			$PUPID = getIndex($spreadsheet1[1], 'قیمت مصرف کننده');
			$PNID = getIndex($spreadsheet1[1], 'نام محصول');
			$PRATIO = getIndex($spreadsheet1[1], 'ضریب');
			
			/** Start Updating Products Price By Their PSID (SKU) */
			updateProductsPrice($spreadsheet1, $PSID, $PUPID, $PNID, $PRATIO, 2);
		}
	
	} 
	catch(Exception $e)
	{
		echo 'Caught exception: ',  $e->getMessage(), "\n";
	}
	

	?>

	<div class="wrap">
		<form method="post" enctype="multipart/form-data" class="box" id="xlsx_form">
			<div class="drop-zone">
				<span class="drop-zone__prompt" id="dropzone__prompt">فایل اکسل را اینجا بکشید و رها کنید</span>
				<input type="file" name="xlsx"  accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" class="drop-zone__input" />
			</div>
		</form>
	</div>

	<script>

		document.querySelectorAll(".drop-zone__input").forEach((inputElement) => {
		const dropZoneElement = inputElement.closest(".drop-zone");

		dropZoneElement.addEventListener("click", (e) => {
			inputElement.click();
		});

		inputElement.addEventListener("change", (e) => {
			if (inputElement.files.length) {
				document.getElementById('dropzone__prompt').innerHTML = "<img class=\"loading\" src=\"https://shoniz.com/wp-content/uploads/2022/12/loading-11.gif\" /><br>لطفا کمی صبر کنید، فایل در حال بارگزاری است";
				document.getElementById('xlsx_form').submit();
			}
		});

		dropZoneElement.addEventListener("dragover", (e) => {
			e.preventDefault();
			dropZoneElement.classList.add("drop-zone--over");
		});

		["dragleave", "dragend"].forEach((type) => {
			dropZoneElement.addEventListener(type, (e) => {
			dropZoneElement.classList.remove("drop-zone--over");
			});
		});

		dropZoneElement.addEventListener("drop", (e) => {
			e.preventDefault();

			if (e.dataTransfer.files.length) {
			inputElement.files = e.dataTransfer.files;
				document.getElementById('dropzone__prompt').innerHTML = "<img class=\"loading\" src=\"https://shoniz.com/wp-content/uploads/2022/12/loading-11.gif\" /><br>لطفا کمی صبر کنید، فایل در حال بارگزاری است";	
				document.getElementById('xlsx_form').submit();
			}

			dropZoneElement.classList.remove("drop-zone--over");
		});
		});


	</script>
	<style>

		.drop-zone {
		max-width: 80%;
		height: 50vh;
		padding: 25px;
		display: flex;
		margin: 100px auto;
		align-items: center;
		justify-content: center;
		text-align: center;
		font-weight: 500;
		font-size: 20px;
		cursor: pointer;
		color: #cccccc;
		border: 4px dashed silver;
		border-radius: 10px;
		}
		
		.loading{
		    width: 100px;
		    padding: 50px;
		}

		.price-update-box .price-update-paragraph:first-child{
			margin: 80px 80px 0 !important;
		}
		.price-update-box .price-update-paragraph{
			margin:0 80px;
		}

		.price-update-box .price-update-paragraph.cadetblue{
			color: cadetblue;
		}

		.price-update-box .price-update-paragraph.green{
			color: darkseagreen;
		}

		.price-update-box .price-update-paragraph.yellow{
			color: darkgoldenrod;
		}

		.drop-zone--over {
		border-style: solid;
		}

		.drop-zone__input {
		display: none;
		}

		.drop-zone__thumb {
		width: 100%;
		height: 100%;
		border-radius: 10px;
		overflow: hidden;
		background-color: #cccccc;
		background-size: cover;
		position: relative;
		}

		.drop-zone__thumb::after {
		content: attr(data-label);
		position: absolute;
		bottom: 0;
		left: 0;
		width: 100%;
		padding: 5px 0;
		color: #ffffff;
		background: rgba(0, 0, 0, 0.75);
		font-size: 14px;
		text-align: center;
		}

	</style>
	<?
 }


 function excel_importer__menu(){

	add_menu_page(
		"به روزرسانی قیمت محصولات از اکسل",
		"ورود اکسل قیمت",
		"import", // capability
		"excel_importer_unique_name", // slug unique name
		"excel_importer_function",
        'dashicons-media-spreadsheet' 
	);

}

 add_action('admin_menu', 'excel_importer__menu');

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'WC_EXCEL_PRODUCTS_PRICE_UPDATER_VERSION', '1.0.0' );

