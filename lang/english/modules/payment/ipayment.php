<?php

define('MODULE_PAYMENT_IPAYMENT_ALLOWED_DESC', 'Enter zones <b>separately</b> in which this module should be allowed. (e.g. AT,DE (leave empty for all zones))');
define('MODULE_PAYMENT_IPAYMENT_ALLOWED_TITLE', 'Allowed zones');
define('MODULE_PAYMENT_IPAYMENT_ACCOUNT_ID_DESC', 'Your merchant ID');
define('MODULE_PAYMENT_IPAYMENT_ACCOUNT_ID_TITLE', 'merchant ID');
define('MODULE_PAYMENT_IPAYMENT_APPLICATION_ID_DESC', 'Your merchant license');
define('MODULE_PAYMENT_IPAYMENT_APPLICATION_ID_TITLE', 'merchant license');
define('MODULE_PAYMENT_IPAYMENT_MISSING', 'MISSING');
define('MODULE_PAYMENT_IPAYMENT_OK', 'OK');
define('MODULE_PAYMENT_IPAYMENT_ORDER_STATUS_ID_DESC', 'Use this status for orders made through this module');
define('MODULE_PAYMENT_IPAYMENT_ORDER_STATUS_ID_TITLE', 'Order status');
define('MODULE_PAYMENT_IPAYMENT_SANDBOX_DESC', 'Activate sandbox mode');
define('MODULE_PAYMENT_IPAYMENT_SANDBOX_TITLE', 'Sandbox mode');
define('MODULE_PAYMENT_IPAYMENT_SORT_ORDER_DESC', 'Order of display, smallest number first');
define('MODULE_PAYMENT_IPAYMENT_SORT_ORDER_TITLE', 'Order of display');
define('MODULE_PAYMENT_IPAYMENT_STATUS_DESC', 'Do you want to accept orders through ipayment?');
define('MODULE_PAYMENT_IPAYMENT_STATUS_TITLE', 'Activate ipayment module');
define('MODULE_PAYMENT_IPAYMENT_SYSTEM_REQUIREMENTS', 'System requirements');
define('MODULE_PAYMENT_IPAYMENT_TEXT_DESCRIPTION', '1&1 ipayment');
define('MODULE_PAYMENT_IPAYMENT_TEXT_DESCRIPTION_LINK', '<a target="_new" style="text-decoration: underline;" href="http://www.ipayment.de/">ipayment</a>');
define('MODULE_PAYMENT_IPAYMENT_TEXT_INFO', 'Info text');
define('MODULE_PAYMENT_IPAYMENT_TEXT_TITLE', '1&1 ipayment');
define('MODULE_PAYMENT_IPAYMENT_TMPORDER_STATUS_ID_DESC', 'Use this status for temporary orders (until checkout completed)');
define('MODULE_PAYMENT_IPAYMENT_TMPORDER_STATUS_ID_TITLE', 'Temporary order status');
define('MODULE_PAYMENT_IPAYMENT_ZONE_DESC', 'If a zone is selected, payment method is only available for this zone');
define('MODULE_PAYMENT_IPAYMENT_ZONE_TITLE', 'Payment zone');
define('MODULE_PAYMENT_IPAYMENT_APPLICATION_PASSWORD_TITLE', 'Application password');
define('MODULE_PAYMENT_IPAYMENT_APPLICATION_PASSWORD_DESC', 'Your application password');
define('MODULE_PAYMENT_IPAYMENT_ADMINACTION_PASSWORD_TITLE', 'Admin action password');
define('MODULE_PAYMENT_IPAYMENT_ADMINACTION_PASSWORD_DESC', 'Your administrative action password');
define('MODULE_PAYMENT_IPAYMENT_SECURITY_KEY_TITLE', 'Security Key');
define('MODULE_PAYMENT_IPAYMENT_SECURITY_KEY_DESC', 'Your Security Key');
define('MODULE_PAYMENT_IPAYMENT_PAYMENT_MODE_TITLE', 'Payment model');
define('MODULE_PAYMENT_IPAYMENT_PAYMENT_MODE_DESC', 'Mode "normal": Redirection to ipayment, Mode "silent": Data is entered on shop site');
define('MODULE_PAYMENT_IPAYMENT_AUTH_MODE_TITLE', 'Transaction mode');
define('MODULE_PAYMENT_IPAYMENT_AUTH_MODE_DESC', 'Mode "auth": immediate payment, Mode "preauth": delayed payment (requires manual capture)');
define('MODULE_PAYMENT_IPAYMENT_ERRORORDER_STATUS_ID_DESC', 'Use this status in case of an error');
define('MODULE_PAYMENT_IPAYMENT_ERRORORDER_STATUS_ID_TITLE', 'Error status');

define('GM_CFG_AUTH', 'auth');
define('GM_CFG_PREAUTH', 'preauth');
define('GM_CFG_NORMAL', 'normal');
define('GM_CFG_SILENT', 'silent');