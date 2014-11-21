<?php
#########################################################
#                                                       #
#  iDEAL / iDEAL payment method                         #
#  This module is used for real time processing         #
#                                                       #
#  Copyright (c) Novalnet AG                            #
#                                                       #
#  Released under the GNU General Public License        #
#  This free contribution made by request.              #
#  If you have found this script usefull a small        #
#  recommendation as well as a comment on merchant form #
#  would be greatly appreciated.                        #
#                                                       #
#  Script : novalnet_ideal.php                          #
#                                                       #
#########################################################

include_once 'novalnet_common.php';

define('MODULE_PAYMENT_NOVALNET_IDEAL_TEXT_TITLE', 'iDEAL');
define('MODULE_PAYMENT_NOVALNET_IDEAL_TEXT_PUBLIC_TITLE', 'iDEAL');

define('MODULE_PAYMENT_NOVALNET_IDEAL_LOGO_TITLE', NOVALNET_TEXT_LOGO_IMAGE . '&nbsp;');
define('MODULE_PAYMENT_NOVALNET_IDEAL_PAYMENT_LOGO_TITLE', '<a href="http://www.novalnet.com" target="_new"><img src="'.$img_src.'iDeal.png" alt="iDEAL" height = "25px;" title="iDEAL" border="0"></a>');

if (MODULE_PAYMENT_NOVALNET_IDEAL_NOVALNET_LOGO_ACTIVE_MODE == 'True') {
        define('MODULE_PAYMENT_NOVALNET_IDEAL_LOGO_STATUS',  MODULE_PAYMENT_NOVALNET_IDEAL_LOGO_TITLE);
} else {
        define('MODULE_PAYMENT_NOVALNET_IDEAL_LOGO_STATUS', '' );
}

if (MODULE_PAYMENT_NOVALNET_IDEAL_PAYMENT_LOGO_ACTIVE_MODE == 'True') {
        define('MODULE_PAYMENT_NOVALNET_IDEAL_PAYMENT_LOGO_STATUS', MODULE_PAYMENT_NOVALNET_IDEAL_PAYMENT_LOGO_TITLE);
} else {
        define('MODULE_PAYMENT_NOVALNET_IDEAL_PAYMENT_LOGO_STATUS', '');
}

define('MODULE_PAYMENT_NOVALNET_IDEAL_TEXT_DESCRIPTION', '<span style="float:left;clear:both;">' . NOVALNET_REDIRECT_TEXT_DESCRIPTION . '</span>');
define('MODULE_PAYMENT_NOVALNET_IDEAL_TEXT_LANG', NOVALNET_TEXT_LANG);
define('MODULE_PAYMENT_NOVALNET_IDEAL_TEXT_INFO', NOVALNET_TEXT_INFO);
define('MODULE_PAYMENT_NOVALNET_IDEAL_STATUS_TITLE', NOVALNET_STATUS_TITLE);
define('MODULE_PAYMENT_NOVALNET_IDEAL_STATUS_DESC', NOVALNET_STATUS_DESC);
define('MODULE_PAYMENT_NOVALNET_IDEAL_VENDOR_ID_TITLE', NOVALNET_VENDOR_ID_TITLE);
define('MODULE_PAYMENT_NOVALNET_IDEAL_VENDOR_ID_DESC', NOVALNET_VENDOR_ID_DESC);
define('MODULE_PAYMENT_NOVALNET_IDEAL_AUTH_CODE_TITLE', NOVALNET_AUTH_CODE_TITLE);
define('MODULE_PAYMENT_NOVALNET_IDEAL_AUTH_CODE_DESC', NOVALNET_AUTH_CODE_DESC);
define('MODULE_PAYMENT_NOVALNET_IDEAL_PRODUCT_ID_TITLE', NOVALNET_PRODUCT_ID_TITLE);
define('MODULE_PAYMENT_NOVALNET_IDEAL_PRODUCT_ID_DESC', NOVALNET_PRODUCT_ID_DESC);
define('MODULE_PAYMENT_NOVALNET_IDEAL_TARIFF_ID_TITLE', NOVALNET_TARIFF_ID_TITLE);
define('MODULE_PAYMENT_NOVALNET_IDEAL_TARIFF_ID_DESC', NOVALNET_TARIFF_ID_DESC);
define('MODULE_PAYMENT_NOVALNET_IDEAL_INFO_TITLE', NOVALNET_INFO_TITLE);
define('MODULE_PAYMENT_NOVALNET_IDEAL_INFO_DESC', NOVALNET_INFO_DESC);
define('MODULE_PAYMENT_NOVALNET_IDEAL_COMPLETE_ORDER_STATUS_ID_TITLE', NOVALNET_COMPLETE_ORDER_STATUS_ID_TITLE);
define('MODULE_PAYMENT_NOVALNET_IDEAL_COMPLETE_ORDER_STATUS_ID_DESC', NOVALNET_ORDER_STATUS_ID_DESC);
define('MODULE_PAYMENT_NOVALNET_IDEAL_SORT_ORDER_TITLE', NOVALNET_SORT_ORDER_TITLE);
define('MODULE_PAYMENT_NOVALNET_IDEAL_SORT_ORDER_DESC', NOVALNET_SORT_ORDER_DESC);
define('MODULE_PAYMENT_NOVALNET_IDEAL_ZONE_TITLE', NOVALNET_ZONE_TITLE);
define('MODULE_PAYMENT_NOVALNET_IDEAL_ZONE_DESC', NOVALNET_ZONE_DESC);
define('MODULE_PAYMENT_NOVALNET_IDEAL_ALLOWED_TITLE', NOVALNET_ALLOWED_TITLE);
define('MODULE_PAYMENT_NOVALNET_IDEAL_ALLOWED_DESC', NOVALNET_ALLOWED_DESC);
define('MODULE_PAYMENT_NOVALNET_IDEAL_TEXT_JS_NN_MISSING', NOVALNET_TEXT_JS_NN_MISSING);
define('MODULE_PAYMENT_NOVALNET_IDEAL_TEXT_ERROR', NOVALNET_ACCOUNT_TEXT_ERROR);
define('MODULE_PAYMENT_NOVALNET_IDEAL_TEXT_CUST_INFORM', NOVALNET_TEXT_CUST_INFORM);
define('MODULE_PAYMENT_NOVALNET_IDEAL_TEST_MODE', NOVALNET_TEST_MODE);
define('MODULE_PAYMENT_NOVALNET_IDEAL_IN_TEST_MODE', NOVALNET_IN_TEST_MODE);
define('MODULE_PAYMENT_NOVALNET_IDEAL_NOT_CONFIGURED', NOVALNET_NOT_CONFIGURED);
define('MODULE_PAYMENT_NOVALNET_IDEAL_TEST_MODE_TITLE', NOVALNET_TEST_MODE_TITLE);
define('MODULE_PAYMENT_NOVALNET_IDEAL_TEST_MODE_DESC', NOVALNET_TEST_MODE_DESC);
define('MODULE_PAYMENT_NOVALNET_IDEAL_TEXT_HASH_ERROR', NOVALNET_TEXT_HASH_ERROR);
define('MODULE_PAYMENT_NOVALNET_IDEAL_PASSWORD_TITLE', NOVALNET_PASSWORD_TITLE);
define('MODULE_PAYMENT_NOVALNET_IDEAL_PASSWORD_DESC', NOVALNET_PASSWORD_DESC);
define('MODULE_PAYMENT_NOVALNET_IDEAL_PROXY_TITLE', NOVALNET_PROXY_TITLE);
define('MODULE_PAYMENT_NOVALNET_IDEAL_PROXY_DESC', NOVALNET_PROXY_DESC);
define('MODULE_PAYMENT_NOVALNET_IDEAL_REFERENCE1_TITLE', NOVALNET_REFERENCE1_TITLE);
define('MODULE_PAYMENT_NOVALNET_IDEAL_REFERENCE1_DESC', NOVALNET_REFERENCE1_DESC);
define('MODULE_PAYMENT_NOVALNET_IDEAL_REFERENCE2_TITLE', NOVALNET_REFERENCE2_TITLE);
define('MODULE_PAYMENT_NOVALNET_IDEAL_REFERENCE2_DESC', NOVALNET_REFERENCE2_DESC);
define('MODULE_PAYMENT_NOVALNET_IDEAL_REFERRER_ID_TITLE', NOVALNET_REFERRER_ID_TITLE);
define('MODULE_PAYMENT_NOVALNET_IDEAL_REFERRER_ID_DESC', NOVALNET_REFERRER_ID_DESC);
define('MODULE_PAYMENT_NOVALNET_IDEAL_TEST_ORDER_MESSAGE', NOVALNET_TEST_ORDER_MESSAGE);
define('MODULE_PAYMENT_NOVALNET_IDEAL_TID_MESSAGE', NOVALNET_TID_MESSAGE);
define('MODULE_PAYMENT_NOVALNET_IDEAL_TEXT_REFERRER_ID_ERROR', NOVALNET_REFERRER_ID_ERROR);
define('MODULE_PAYMENT_NOVALNET_IDEAL_NOVALNET_LOGO_ACTIVE_MODE_TITLE', NOVALNET_LOGO_TITLE);
define('MODULE_PAYMENT_NOVALNET_IDEAL_NOVALNET_LOGO_ACTIVE_MODE_DESC', NOVALNET_LOGO_DESC);
define('MODULE_PAYMENT_NOVALNET_IDEAL_PAYMENT_LOGO_ACTIVE_MODE_TITLE', NOVALNET_PAYMENT_LOGO_TITLE);
define('MODULE_PAYMENT_NOVALNET_IDEAL_PAYMENT_LOGO_ACTIVE_MODE_DESC', NOVALNET_PAYMENT_LOGO_DESC);
