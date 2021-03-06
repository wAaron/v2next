<?php

/* -----------------------------------------------------------------
 * 	$Id: product_mini_images.php 420 2013-06-19 18:04:39Z akausch $
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

$a = new image_manipulation(DIR_FS_CATALOG_ORIGINAL_IMAGES . $products_image_name, PRODUCT_IMAGE_MINI_WIDTH, PRODUCT_IMAGE_MINI_HEIGHT, DIR_FS_CATALOG_MINI_IMAGES . $products_image_name, IMAGE_QUALITY, '');

$array = clear_string(PRODUCT_IMAGE_INFO_SMOTH);
if (PRODUCT_IMAGE_INFO_SMOTH != '') {
    $a->smoth($array[0]);
}

$array = clear_string(PRODUCT_IMAGE_MINI_BEVEL);
if (PRODUCT_IMAGE_MINI_BEVEL != '') {
    $a->bevel($array[0], $array[1], $array[2]);
}

$array = clear_string(PRODUCT_IMAGE_MINI_GREYSCALE);
if (PRODUCT_IMAGE_MINI_GREYSCALE != '') {
    $a->greyscale($array[0], $array[1], $array[2]);
}

$array = clear_string(PRODUCT_IMAGE_MINI_ELLIPSE);
if (PRODUCT_IMAGE_MINI_ELLIPSE != '') {
    $a->ellipse($array[0]);
}

$array = clear_string(PRODUCT_IMAGE_MINI_ROUND_EDGES);
if (PRODUCT_IMAGE_MINI_ROUND_EDGES != '') {
    $a->round_edges($array[0], $array[1], $array[2]);
}

$string = str_replace("'", '', PRODUCT_IMAGE_MINI_MERGE);
$string = str_replace(')', '', $string);
$string = str_replace('(', DIR_FS_CATALOG_IMAGES, $string);
$array = explode(',', $string);
//$array=clear_string();
if (PRODUCT_IMAGE_MINI_MERGE != '') {
    $a->merge($array[0], $array[1], $array[2], $array[3], $array[4]);
}

$array = clear_string(PRODUCT_IMAGE_MINI_FRAME);
if (PRODUCT_IMAGE_MINI_FRAME != '') {
    $a->frame($array[0], $array[1], $array[2], $array[3]);
}

$array = clear_string(PRODUCT_IMAGE_MINI_DROP_SHADDOW);
if (PRODUCT_IMAGE_MINI_DROP_SHADDOW != '') {
    $a->drop_shadow($array[0], $array[1], $array[2]);
}

$array = clear_string(PRODUCT_IMAGE_MINI_MOTION_BLUR);
if (PRODUCT_IMAGE_MINI_MOTION_BLUR != '') {
    $a->motion_blur($array[0], $array[1]);
}

$a->create();
