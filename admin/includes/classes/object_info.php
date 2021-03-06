<?php

/* -----------------------------------------------------------------
 * 	$Id: object_info.php 420 2013-06-19 18:04:39Z akausch $
 * 	Copyright (c) 2011-2021 commerce:SEO by Webdesign Erfurt
 * 	http://www.commerce-seo.de
 * ------------------------------------------------------------------
 * 	based on:
 * 	(c) 2000-2001 The Exchange Project  (earlier name of osCommerce)
 * 	(c) 2002-2003 osCommerce - www.oscommerce.com
 * 	(c) 2003     nextcommerce - www.nextcommerce.org
 * 	(c) 2005     xt:Commerce - www.xt-commerce.com
 * 	Released under the GNU General Public License
 * --------------------------------------------------------------- */

defined('_VALID_XTC') or die('Direct Access to this location is not allowed.');

class objectInfo {

    // function __construct() {
    // }

    function objectInfo($object_array) {
        reset($object_array);
        while (list($key, $value) = each($object_array)) {
            $this->$key = xtc_db_prepare_input($value);
        }
    }

}
