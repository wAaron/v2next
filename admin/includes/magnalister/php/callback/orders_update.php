<?php
/**
 * 888888ba                 dP  .88888.                    dP                
 * 88    `8b                88 d8'   `88                   88                
 * 88aaaa8P' .d8888b. .d888b88 88        .d8888b. .d8888b. 88  .dP  .d8888b. 
 * 88   `8b. 88ooood8 88'  `88 88   YP88 88ooood8 88'  `"" 88888"   88'  `88 
 * 88     88 88.  ... 88.  .88 Y8.   .88 88.  ... 88.  ... 88  `8b. 88.  .88 
 * dP     dP `88888P' `88888P8  `88888'  `88888P' `88888P' dP   `YP `88888P' 
 *
 *                          m a g n a l i s t e r
 *                                      boost your Online-Shop
 *
 * -----------------------------------------------------------------------------
 * $Id: orders_import.php 1410 2011-12-02 19:50:12Z derpapst $
 *
 * (c) 2010 RedGecko GmbH -- http://www.redgecko.de
 *     Released under the MIT License (Expat)
 * -----------------------------------------------------------------------------
 */

defined('_VALID_XTC') or die('Direct Access to this location is not allowed.');

require_once(DIR_MAGNALISTER_CALLBACK.'callbackFunctions.php');

function magnaInitOrderUpdate() {
	global $magnaInitOrderUpdateCalled, $_magnaLanguage;
	
	/* Prevent this funciton to be called twice. */
	if ($magnaInitOrderUpdateCalled === true) {
		return;
	}
	$magnaInitOrderUpdateCalled = true;
	#define('MAGNA_ORDERS_DATERANGE_BEGIN', time() - 60 * 60 * 24 * 30);

	/* Wegen include aus magnaCallback.php */
	if (defined('DIR_FS_LANGUAGES')) {
		define('DIR_MAGNA_LANGUAGES', DIR_FS_LANGUAGES);
	} else {
		if (strpos(DIR_WS_LANGUAGES, DIR_FS_DOCUMENT_ROOT) === false) {
			define('DIR_MAGNA_LANGUAGES', DIR_FS_DOCUMENT_ROOT.DIR_WS_LANGUAGES);
		} else {
			define('DIR_MAGNA_LANGUAGES', DIR_WS_LANGUAGES);
		}
	}
	if (defined('DIR_FS_CATALOG_MODULES')) {
		define('DIR_MAGNA_MODULES', DIR_FS_CATALOG_MODULES);
	} else {
		if (strpos(DIR_WS_MODULES, DIR_FS_DOCUMENT_ROOT) === false) {
			define('DIR_MAGNA_MODULES', DIR_FS_DOCUMENT_ROOT.DIR_WS_MODULES);
		} else {
			define('DIR_MAGNA_MODULES', DIR_WS_MODULES);
		}
	}

	/* Die Shop-Scripte werfen einige Notices aus. Ausgabe von Notices unterdruecken falls Sie aktiv sind. */
	$errorlevel = error_reporting(0);
	error_reporting($errorlevel & ~E_NOTICE);

	/* Labels fuer OrdersTotal */
	require_once(DIR_MAGNA_LANGUAGES . $_magnaLanguage . '/modules/order_total/ot_shipping.php');
	require_once(DIR_MAGNA_LANGUAGES . $_magnaLanguage . '/modules/order_total/ot_subtotal.php');
	require_once(DIR_MAGNA_LANGUAGES . $_magnaLanguage . '/modules/order_total/ot_total.php');
	if (file_exists(DIR_MAGNA_LANGUAGES . $_magnaLanguage . '/modules/order_total/ot_total_netto.php')) {
		# nur bei Gambio
		require_once(DIR_MAGNA_LANGUAGES . $_magnaLanguage . '/modules/order_total/ot_total_netto.php');
	}
	require_once(DIR_MAGNA_LANGUAGES . $_magnaLanguage . '/modules/order_total/ot_tax.php');

	if (file_exists(DIR_MAGNA_LANGUAGES . $_magnaLanguage . '/modules/order_total/ot_cod_fee.php')) {
		require_once(DIR_MAGNA_LANGUAGES . $_magnaLanguage . '/modules/order_total/ot_cod_fee.php');
		define('MAGNA_LABEL_ORDERS_COD_CHARGE', MODULE_ORDER_TOTAL_COD_FEE_TITLE.':');
	} else {
		define('MAGNA_LABEL_ORDERS_COD_CHARGE', ML_LABEL_ORDER_TOTAL_COD_FEE.':');
	}
	if (file_exists(DIR_MAGNA_LANGUAGES . $_magnaLanguage . '/modules/order_total/ot_coupon.php')) {
		require_once(DIR_MAGNA_LANGUAGES . $_magnaLanguage . '/modules/order_total/ot_coupon.php');
		define('MAGNA_LABEL_ORDERS_VOUCHER', MODULE_ORDER_TOTAL_COUPON_TITLE.':');
	} else {
		define('MAGNA_LABEL_ORDERS_VOUCHER', ML_LABEL_ORDER_TOTAL_COUPON.':');
	}
	if (file_exists(DIR_MAGNA_LANGUAGES . $_magnaLanguage . '/modules/order_total/ot_discount.php')) {
		require_once(DIR_MAGNA_LANGUAGES . $_magnaLanguage . '/modules/order_total/ot_discount.php');
		define('MAGNA_LABEL_ORDERS_DISCOUNT', MODULE_ORDER_TOTAL_DISCOUNT_TITLE.':');
	} else {
		define('MAGNA_LABEL_ORDERS_DISCOUNT', ML_LABEL_ORDER_TOTAL_DISCOUNT.':');
	}
	define('MAGNA_LABEL_ORDERS_SUBTOTAL', MODULE_ORDER_TOTAL_SUBTOTAL_TITLE.':');
	define('MAGNA_LABEL_ORDERS_TAX', MODULE_ORDER_TOTAL_TAX_TITLE.':');
	define('MAGNA_LABEL_ORDERS_SHIPPING', MODULE_ORDER_TOTAL_SHIPPING_TITLE.':');
	define('MAGNA_LABEL_ORDERS_TOTAL', MODULE_ORDER_TOTAL_TOTAL_TITLE.':');
	define('MAGNA_LABEL_ORDERS_COUNTRY_CHARGE', ML_LABEL_ORDER_TOTAL_COUNTRY_CHARGE.':');
	error_reporting($errorlevel);
}

function magnaUpdateAllOrders() {
	global $_MagnaShopSession, $magnaConfig;

	$verbose = isset($_GET['MLDEBUG']) && ($_GET['MLDEBUG'] == 'true');

	magnaInitOrderUpdate();

	/* Bitte nicht vor Lachen in Traenen ausbrechen, aber die naechsten Zeilen sollen so etwas wie ein "mutex" darstellen :-) */
	$doUpdates = false;
	usleep(rand(200, 800));
	$lockName = DIR_MAGNALISTER_FS.'OrderUpdateLock';
	if (!file_exists($lockName)) {
		$myTime = time();
		file_put_contents($lockName, $myTime);
		chmod($lockName, 0666);
		$doUpdates = true;
	} else {
		$time = (int)@file_get_contents($lockName);
		if (($time + 1200) < time()) {
			$myTime = time();
			file_put_contents($lockName, $myTime);
			chmod($lockName, 0666);
			$doUpdates = true;
		}
	}

	if ($doUpdates) {
		$modules = magnaGetInvolvedMarketplaces();

		ini_set('memory_limit', '512M');
		MagnaConnector::gi()->setTimeOutInSeconds(
			MAGNA_DEBUG && defined('MAGNALISTER_PLUGIN') && MAGNALISTER_PLUGIN 
				? 1 
				: 600
		);

		foreach ($modules as $marketplace) {
			$mpIDs = magnaGetInvolvedMPIDs($marketplace);
			if (empty($mpIDs)) continue;
			foreach ($mpIDs as $mpID) {
				if (!file_exists(DIR_MAGNALISTER_CALLBACK.'update_'.$marketplace.'_orders.php')) {
					continue;
				}
				require_once(DIR_MAGNALISTER_CALLBACK.'update_'.$marketplace.'_orders.php');
				$funcName = 'magnaUpdate'.ucfirst($marketplace).'Orders';
				
				if (!function_exists($funcName)) {
					continue;
				}

				if (!array_key_exists('db', $magnaConfig) || 
				    !array_key_exists($mpID, $magnaConfig['db'])
				) {
					loadDBConfig($mpID);
				}
				if (getDBConfigValue($marketplace.'.import', $mpID, 'false') != 'true') {
					continue;
				}

				$funcName($mpID);
				# echo print_m('Update :: '.$funcName.'['.$mpID.']'."\n");
			}
			# echo print_m($mpIDs, $marketplace);
			MagnaConnector::gi()->resetTimeOut();
		}
		@unlink($lockName);
	}
	$_MagnaShopSession['magnaCallbackLastCall'] = time();
	magnaFixOrders();

	if (defined('GM_SET_OUT_OF_STOCK_PRODUCTS') && (GM_SET_OUT_OF_STOCK_PRODUCTS == 'true')) {
		/* Set sold out products to inavtive. */
		MagnaDB::gi()->query('UPDATE '.TABLE_PRODUCTS.' SET products_status=0 WHERE products_quantity <= 0');
	}
	
	if ($verbose) {
		die();
	}
}
