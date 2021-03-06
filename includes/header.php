<?php
/*-----------------------------------------------------------------
* 	$Id: header.php 1124 2014-06-29 12:23:10Z akausch $
* 	Copyright (c) 2011-2021 commerce:SEO by Webdesign Erfurt
* 	http://www.commerce-seo.de
* ------------------------------------------------------------------
* 	based on:
* 	(c) 2000-2001 The Exchange Project  (earlier name of osCommerce)
* 	(c) 2002-2003 osCommerce - www.oscommerce.com
* 	(c) 2003     nextcommerce - www.nextcommerce.org
* 	(c) 2005     xt:Commerce - www.xt-commerce.com
* 	Released under the GNU General Public License
* ---------------------------------------------------------------*/

if($_GET['error'] == '404') {
	if (MODULE_COMMERCE_SEO_404_HANDLING === 'True') {
		header($_SERVER['SERVER_PROTOCOL']." 404 Not Found", true, 404 );
		header('Status: 404 Not Found');
	} else {
		header($_SERVER['SERVER_PROTOCOL']." 410 Gone", true, 410 );
		header('Status: 410 Gone');	
	}
	header('Content-type: text/html');
}
$request_type = (getenv('HTTPS') == '1' || getenv('HTTPS') == 'on') ? 'SSL' : 'NONSSL';
// $request_type = (isset($_SERVER['HTTP_VIA'])) ? 'NONSSL' : 'SSL';
header("Content-Type: text/html; charset=utf-8");
header('Connection: Keep-Alive'); 
header('Keep-Alive: timeout=300');
$max_age = 60 * 60 * 24 * 7;
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $max_age) . ' GMT');
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

$browser = new Browser();

/******** SHOPGATE **********/
if(strpos(MODULE_PAYMENT_INSTALLED, 'shopgate.php') !== false && strpos($_SESSION['customers_status']['customers_status_payment_unallowed'], 'shopgate') === false){
  include_once (DIR_FS_CATALOG.'includes/external/shopgate/base/includes/header.php');
}
/******** SHOPGATE **********/
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['language_code']; ?>" class="no-js" dir="ltr">
<head>
<meta charset="utf-8">
<?php
echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
echo '<meta name="generator" content="(c) by commerce:SEO v2Next">';
// if($browser->getBrowser() == Browser::BROWSER_IPHONE) {
echo '<link rel="stylesheet" href="'.(($request_type == 'SSL') ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG.'cseo_css.php" type="text/css" media="projection, screen">';
// }
if($browser->getBrowser() == Browser::BROWSER_IE && $browser->getVersion() <= 8 ) {
echo '<meta http-equiv="X-UA-Compatible" content="IE=8" />
<!--[if IE 7]>
<html lang="en" class="ie7 oldie"></html><![endif]-->
<!--[if IE 8]>
<html lang="en" class="ie8 oldie"></html><![endif]-->
<script src="//html5shim.googlecode.com/svn/trunk/html5.js"></script>
<script src="//css3-mediaqueries-js.googlecode.com/svn/trunk/css3-mediaqueries.js"></script>
';
}
if (file_exists(DIR_WS_MODULES.'metatags-USERMOD.php')) {
	include(DIR_WS_MODULES.'metatags-USERMOD.php');
} else {
	include(DIR_WS_MODULES.FILENAME_METATAGS);
}
echo '<base href="'.(($request_type == 'SSL') ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG.'" />';
// AmazonPayMents start
if (strstr($PHP_SELF, FILENAME_SHOPPING_CART) || strstr($PHP_SELF, 'checkout_amazon.php') || strstr($PHP_SELF, 'checkout_amazon_callback.php') || strstr($PHP_SELF, 'checkout_amazon_handler.php')) {
    if (file_exists('CheckoutByAmazon/javascripts.php')) {
		include_once('CheckoutByAmazon/javascripts.php');
	}
}
// AmazonPayMents end
if (GOOGLE_ANAL_ON == 'true' && GOOGLE_ANAL_CODE != '') {
    include(DIR_WS_INCLUDES . 'google_analytics.js.php');
}
echo '</head>'."\n".'<body>'."\n";
if (strstr($PHP_SELF, FILENAME_CHECKOUT_SUCCESS) && GOOGLE_CONVERSION == 'true') {
	require('includes/google_conversiontracking.js.php');
}

// include needed functions
require_once('inc/xtc_output_warning.inc.php');

// check if the configure.php file is writeable (or '/local'-version is being used)
if(WARN_CONFIG_WRITEABLE == 'true' && $_SESSION['customers_status']['customers_status_id'] == 0) {
	if ((file_exists(dirname($_SERVER['SCRIPT_FILENAME']) . '/includes/configure.php')) &&  (is_writeable(dirname($_SERVER['SCRIPT_FILENAME']) . '/includes/configure.php')) && !file_exists('includes/local/configure.php') ) {
		xtc_output_warning(WARNING_CONFIG_FILE_WRITEABLE);
	}
}

// check if the session folder is writeable
if(WARN_SESSION_DIRECTORY_NOT_WRITEABLE == 'true') {
	if (STORE_SESSIONS == '') {
		if (!is_dir(xtc_session_save_path())) {
			xtc_output_warning(WARNING_SESSION_DIRECTORY_NON_EXISTENT);
		} elseif (!is_writeable(xtc_session_save_path())) {
			xtc_output_warning(WARNING_SESSION_DIRECTORY_NOT_WRITEABLE);
		}
	}
}

// check session.auto_start is disabled
if((function_exists('ini_get')) && (WARN_SESSION_AUTO_START == 'true')) {
	if (ini_get('session.auto_start') == '1') {
		xtc_output_warning(WARNING_SESSION_AUTO_START);
	}
}

if((WARN_DOWNLOAD_DIRECTORY_NOT_READABLE == 'true') && (DOWNLOAD_ENABLED == 'true')) {
	if (!is_dir(DIR_FS_DOWNLOAD)) {
		xtc_output_warning(WARNING_DOWNLOAD_DIRECTORY_NON_EXISTENT);
	}
}

$smarty->assign('tpl_path', DIR_WS_CATALOG . 'templates/'.CURRENT_TEMPLATE.'/');
$smarty->assign('html_tpl_path', CURRENT_TEMPLATE.'/html');
$smarty->assign('navtrail',$breadcrumb->trail(' &raquo; '));
$smarty->assign('customer_name',cseo_get_customer_name());
$smarty->assign('cart',xtc_href_link(FILENAME_SHOPPING_CART, '', 'SSL'));	
$smarty->assign('wish_list',xtc_href_link(FILENAME_WISH_LIST, '', 'SSL'));
$smarty->assign('checkout2_stat', (CHECKOUT_AJAX_STAT == 'true') ? 1 : 0);
$smarty->assign('store_name',TITLE);
$smarty->assign('home',xtc_href_link(FILENAME_DEFAULT));
$smarty->assign('logo', xtc_image('templates/'.CURRENT_TEMPLATE.'/img/'.cseo_get_conf('CSEO_LOGO'), TITLE, STORE_OWNER, 'img-responsive'));
// $smarty->assign('logo', xtc_image('templates/'.CURRENT_TEMPLATE.'/img/logo.png', TITLE, STORE_OWNER, 'img-responsive'));

if (GOOGLEPLUS_URL != '') {
	$smarty->assign('GOOGLEPLUS_URL', GOOGLEPLUS_URL);
}
if (FACEBOOK_URL != '') {
	$smarty->assign('FACEBOOK_URL', FACEBOOK_URL);
}
if (XING_URL != '') {
	$smarty->assign('XING_URL', XING_URL);
}
if (TWITTER_URL != '') {
	$smarty->assign('TWITTER_URL', TWITTER_URL);
}
if (PINTEREST_URL != '') {
	$smarty->assign('PINTEREST_URL', PINTEREST_URL);
}
if (YOUTUBE_URL != '') {
	$smarty->assign('YOUTUBE_URL', YOUTUBE_URL);
}
if (TUMBLR_URL != '') {
	$smarty->assign('TUMBLR_URL', TUMBLR_URL);
}
if (STORE_NAME != '') {
	$smarty->assign('STORE_NAME', STORE_NAME);
}

if (isset($_SESSION['customer_id'])) {
	$smarty->assign('logoff',xtc_href_link(FILENAME_LOGOFF, '', 'SSL'));
} else {
	$smarty->assign('login',xtc_href_link(FILENAME_LOGIN, '', 'SSL'));
}

if ($_SESSION['account_type'] == '0') {
	$smarty->assign('account',xtc_href_link(FILENAME_ACCOUNT, '', 'SSL'));
}

if ($_SESSION['cart']->count_contents() > 0) {
	$smarty->assign('cart_count',$_SESSION['cart']->count_contents());
}

if ($_SESSION['wishList']->count_contents() > 0) {
	$smarty->assign('wish_list_count',$_SESSION['wishList']->count_contents());
}

if(file_exists(FILENAME_CHECKOUT) && CHECKOUT_AJAX_STAT == 'true') {
	$smarty->assign('checkout',xtc_href_link(FILENAME_CHECKOUT, '', 'SSL'));
} else {
	$smarty->assign('checkout',xtc_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
}

if (isset($_SESSION['error_message']) && xtc_not_null($_SESSION['error_message'])) {
	$smarty->assign('error','<span>'. urldecode($_SESSION['error_message']).'</span>');
}
	
unset($_SESSION['error_message']);

if (isset($_SESSION['info_message']) && xtc_not_null($_SESSION['info_message'])) {
	$smarty->assign('info_message','<span class="headerInfo">'.urldecode($_SESSION['info_message']).'</span>');
}

unset($_SESSION['info_message']);
include(DIR_WS_INCLUDES.FILENAME_BANNER);

$cseo_header_extender_component = cseohookfactory::create_object('HeaderExtenderComponent');
$cseo_header_extender_component->set_data('GET', $_GET);
$cseo_header_extender_component->set_data('POST', $_POST);
$cseo_header_extender_component->proceed();