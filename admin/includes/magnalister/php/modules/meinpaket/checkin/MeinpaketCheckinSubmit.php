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
 * $Id: MeinpaketCheckinSubmit.php 3561 2014-03-02 03:03:48Z derpapst $
 *
 * (c) 2011 RedGecko GmbH -- http://www.redgecko.de
 *     Released under the MIT License (Expat)
 * -----------------------------------------------------------------------------
 */

defined('_VALID_XTC') or die('Direct Access to this location is not allowed.');

require_once(DIR_MAGNALISTER_INCLUDES.'lib/classes/CheckinSubmit.php');
require_once(DIR_MAGNALISTER_MODULES.'meinpaket/MeinpaketHelper.php');

class MeinpaketCheckinSubmit extends CheckinSubmit {
	private $checkinDetails = array();
	
	protected $varMatchingCache = array();
	
	public function __construct($settings = array()) {
		global $_MagnaSession;
		/* Setzen der Currency nicht noetig, da Preisberechnungen bereits in 
		   der MeinpaketSummaryView Klasse gemacht wurden.
		 */
		$settings = array_merge(array(
			'language' => getDBConfigValue($settings['marketplace'].'.lang', $_MagnaSession['mpID']),
			'itemsPerBatch' => 100,
			'mlProductsUseLegacy' => (!defined('MAGNA_SECRET_DEV') || !MAGNA_SECRET_DEV),
		), $settings);
		
		parent::__construct($settings);
	}
	
	private function generateMPCategoryPath($id, $from = 'category', $langID, $categories_array = array(), $index = 0, $callCount = 0) {
		$descCol = '';
		if (MagnaDB::gi()->columnExistsInTable('categories_description', TABLE_CATEGORIES_DESCRIPTION)) {
			$descCol = 'categories_description';
		} else {
			$descCol = 'categories_name';
		}
		$trim = " \n\r\0\x0B\xa0\xc2"; # last 2 ones are utf8 &nbsp;
		if ($from == 'product') {
			$categoryIds = MagnaDB::gi()->fetchArray('
				SELECT categories_id AS code
				  FROM '.TABLE_PRODUCTS_TO_CATEGORIES.'
				 WHERE products_id = "'.$id.'"
			', true);
			foreach ($categoryIds as $cId) {
				if ($cId != '0') {
					$category = MagnaDB::gi()->fetchRow('
						SELECT cd.categories_name AS `name`, cd.'.$descCol.' AS `desc`, c.parent_id AS `parent`
						  FROM '.TABLE_CATEGORIES.' c, '.TABLE_CATEGORIES_DESCRIPTION.' cd 
						 WHERE c.categories_id = "'.$cId.'" 
						       AND c.categories_id = cd.categories_id 
						       AND cd.language_id = "'.$langID.'"
						 LIMIT 1
					');
					if (empty($category)) {
						continue;
					}
					$c = array (
						'code' => $cId,
						'name' => trim(html_entity_decode(strip_tags($category['name']), ENT_QUOTES, 'UTF-8'), $trim),
						'desc' => $category['desc'],
						'parent' => $category['parent'],
					);
					if ($c['parent'] == '0') {
						unset($c['parent']);
					}
					if ($c['desc'] == '') {
						$c['desc'] = $c['name'];
					}
					$categories_array[$index][] = $c;
					if (($category['parent'] != '') && ($category['parent'] != '0')) {
						$categories_array = $this->generateMPCategoryPath($category['parent'], 'category', $langID, $categories_array, $index);
					}
				}
				++$index;
			}
		} else if ($from == 'category') {
			$category = MagnaDB::gi()->fetchRow('
				SELECT c.categories_id AS code, cd.categories_name AS `name`, cd.'.$descCol.' AS `desc`, c.parent_id AS `parent`
				  FROM '.TABLE_CATEGORIES.' c, '.TABLE_CATEGORIES_DESCRIPTION.' cd
				 WHERE c.categories_id = "'.$id.'" 
				       AND c.categories_id = cd.categories_id
				       AND cd.language_id = "'.$langID.'"
				 LIMIT 1
			');
			if (empty($category)) {
				return $categories_array;
			}
			$c = array (
				'code' => $category['code'],
				'name' => trim(html_entity_decode(strip_tags($category['name']), ENT_QUOTES, 'UTF-8'), $trim),
				'desc' => $category['desc'],
				'parent' => $category['parent'],
			);
			if ($c['parent'] == '0') {
				unset($c['parent']);
			}
			if ($c['desc'] == '') {
				$c['desc'] = $c['name'];
			}
			$categories_array[$index][] = $c;
			if (($category['parent'] != '') && ($category['parent'] != '0')) {
				$categories_array = $this->generateMPCategoryPath($category['parent'], 'category', $langID, $categories_array, $index, $callCount + 1);
			}
			if ($callCount == 0) {
				$categories_array[$index] = array_reverse($categories_array[$index]);
			}
		}
		
		return $categories_array;
	}
	
	public function makeSelectionFromErrorLog() {}
	
	protected function generateRequestHeader() {
		return array(
			'ACTION' => 'AddItems',
			'MODE' => $this->submitSession['mode']
		);
	}
	
	protected function markAsFailed($sku, $error = '') {
		if (empty($error)) {
			$error = ML_GENERIC_ERROR_UNABLE_TO_LOAD_PREPARE_DATA;
		}
		MagnaDB::gi()->insert(
			TABLE_MAGNA_MEINPAKET_ERRORLOG,
			array (
				'mpID' => $this->mpID,
				'errormessage' => $error,
				'dateadded' => gmdate('Y-m-d H:i:s'),
				'additionaldata' => serialize(array(
					'SKU' => $sku
				))
			)
		);
		$this->badItems[] = $pID;
		unset($this->selection[$pID]);
	}
	
	protected function setUpMLProduct() {
		parent::setUpMLProduct();
		
		// Set a db matching (e.g. 'ManufacturerPartNumber')
		$mfrmd = getDBConfigValue('meinpaket.checkin.manufacturerpartnumber.table', $this->mpID, false);
		if (is_array($mfrmd) && !empty($mfrmd['column']) && !empty($mfrmd['table'])) {
			$pIDAlias = getDBConfigValue('meinpaket.checkin.manufacturerpartnumber.alias', $this->mpID);
			if (empty($pIDAlias)) {
				$pIDAlias = 'products_id';
			}
			MLProduct::gi()->setDbMatching('ManufacturerPartNumber', array (
				'Table'  => $mfrmd['table'],
				'Column' => $mfrmd['column'],
				'Alias'  => $pIDAlias,
			));
		}
		
		// Use multi dimensional variations
		MLProduct::gi()->useMultiDimensionalVariations(true);
		
		// Set Price and Quantity settings
		MLProduct::gi()->setPriceConfig(MeinpaketHelper::loadPriceSettings($this->mpID));
		MLProduct::gi()->setQuantityConfig(MeinpaketHelper::loadQuantitySettings($this->mpID));
	}
	
	protected function loadVariationMatching($variationConfiguration) {
		$key = md5($variationConfiguration['MpIdentifier'].$variationConfiguration['CustomIdentifier']);
		
		if (isset($this->varMatchingCache[$key])) {
			return $this->varMatchingCache[$key];
		}
		
		// Load variation configuration
		$varConfig = @json_decode(MagnaDB::gi()->fetchOne('
			SELECT ShopVariation
			 FROM '.TABLE_MAGNA_MEINPAKET_VARIANTMATCHING.'
			 WHERE MpId = '.$this->mpID.'
			       AND MpIdentifier="'.$variationConfiguration['MpIdentifier'].'"
			       AND CustomIdentifier="'.$variationConfiguration['CustomIdentifier'].'"
		'), true);
		
		if (empty($varConfig)) {
			return false;
		}
		
		$keys = array_keys($varConfig);
		foreach ($keys as $key) {
			$mpname = base64_decode($key);
			$newkey = $varConfig[$key]['Code'];
			$varConfig[$key]['MPName'] = $mpname;
			
			$varConfig[$newkey] = $varConfig[$key];
			unset($varConfig[$key]);
			
			// translate free text matching
			if ($varConfig[$newkey]['Kind'] == 'FreeText') {
				$trans = MagnaDB::gi()->fetchArray('
				    SELECT DISTINCT products_options_values_id AS Id, products_options_values_name As Name
				      FROM '.TABLE_PRODUCTS_OPTIONS_VALUES.'
				     WHERE language_id = "'.$this->settings['language'].'"
				           AND products_options_values_id IN ("'.implode('", "', $varConfig[$newkey]['Values']).'")
				');
				if (!empty($trans)) {
					foreach ($trans as $row) {
						$varConfig[$newkey]['Values'][$row['Id']] = ($varConfig[$newkey]['Values'][$row['Id']] == 'null')
							? 'null'
							: $row['Name'];
					}
				}
			}
		}
		
		arrayEntitiesToUTF8($varConfig);
		
		$this->varMatchingCache[$key] = $varConfig;
		
		return $this->varMatchingCache[$key];
	}
	
	protected function processVariations($propertiesRow, &$data) {
		if (empty($data['submit']['Variations'])) {
			unset($data['submit']['Variations']);
			return true;
		}
		
		if (empty($propertiesRow['VariationConfiguration'])) {
			// means submit no variations
			unset($data['submit']['Variations']);
			return true;
		}
		
		$varConfig = $this->loadVariationMatching($propertiesRow['VariationConfiguration']);
		if (empty($varConfig)) {
			$this->markAsFailed($data['submit']['SKU'], ML_MEINPAKET_ERROR_CHECKIN_VARIATION_CONFIG_EMPTY);
			return false;
		}
		
		/*
[0] => Array
    (
        [VariationId] => 1757
        [VariationModel] => CoolPantsS:LC:Blu
        [MarketplaceId] => ML4641_1.3_2.12
        [MarketplaceSku] => CoolPantsS:LC:Blu
        [Variation] => Array
            (
                [0] => Array
                    (
                        [NameId] => 1
                        [Name] => Größe
                        [ValueId] => 3
                        [Value] => L
                    )

                [1] => Array
                    (
                        [NameId] => 2
                        [Name] => Farbe
                        [ValueId] => 12
                        [Value] => blau
                    )

            )

        [Price] => 109.9
        [Quantity] => 1
        [Status] => 1
        [ShippingTimeId] => 1
        [ShippingTime] => ca. 3-4 Tage
        [EAN] => 
    )
-------------------------------------------
$varConfig :: Array
(
    [2] => Array
        (
            [Code] => 2
            [Kind] => FreeText
            [Values] => Array
                (
                    [10] => 10
                    [11] => 11
                    [12] => 12
                    ...
                    [49] => 49
                )

            [MPName] => Farbe
        )
-------------------------------------------
{
    "Name": "Gr\u00f6\u00dfe",
    "MPName": "Gr\u00f6\u00dfe",
    "Value": "L",
    "MPValue:" "190",
},
-------------------------------------------
*/
		
		foreach ($propertiesRow['VariationConfiguration'] as &$sBaseItem) {
			$sBaseItem = base64_decode($sBaseItem);
		}
		$data['submit']['MPVariationConfiguration'] = $propertiesRow['VariationConfiguration'];
		
		foreach ($data['submit']['Variations'] as $key => &$vItem) {
			foreach ($vItem['Variation'] as &$vSet) {
				if (!isset($varConfig[$vSet['NameId']])) {
					$this->markAsFailed(
						$data['submit']['SKU'], 
						sprintf(
							ML_MEINPAKET_ERROR_CHECKIN_VARIATION_CONFIG_MISSING_NAMEID,
							$vSet['Name'],
							((getDBConfigValue('general.keytype', '0') == 'artNr')
								? $vItem['MarketplaceSku']
								: $vItem['MarketplaceId']
							)
						)
					);
					return false;
				}
				$matching = $varConfig[$vSet['NameId']];
				
				$vSet['MPName'] = $matching['MPName'];
				
				if (!isset($matching['Values'][$vSet['ValueId']]) || ($matching['Values'][$vSet['ValueId']] == 'null')) {
					unset($data['submit']['Variations'][$key]);
					break;
				}
				$vSet['MPValue'] = $matching['Values'][$vSet['ValueId']];
			}
			
			$vItem['SKU'] = (getDBConfigValue('general.keytype', '0') == 'artNr')
				? $vItem['MarketplaceSku']
				: $vItem['MarketplaceId'];
			
			$vItem['ShippingTime'] = getDBConfigValue('meinpaket.checkin.leadtimetoship', $this->mpID, 3);
			
			// remove stuff we do not want.
			foreach (array('ShippingTimeId') as $key) {
				unset($vItem[$key]);
			}
		}
		
		if (empty($data['submit']['Variations'])) {
			$this->markAsFailed($data['submit']['SKU'], ML_MEINPAKET_ERROR_CHECKIN_VARIATION_CONFIG_CANNOT_CALC_VARIATIONS);
			return false;
		}
		
		#echo print_m($varConfig, '$varConfig');
		#echo print_m($data['submit'], 'Submit');
		
		return true;
	}
	
	protected function appendAdditionalDataNew($pID, $product, &$data) {
		$propertiesRow = MagnaDB::gi()->fetchRow(eecho('
			SELECT *
			  FROM ' . TABLE_MAGNA_MEINPAKET_PROPERTIES . '
			 WHERE ' . ((getDBConfigValue('general.keytype', '0') == 'artNr') 
						? 'products_model="' . MagnaDB::gi()->escape($product['ProductsModel']) . '"'
						: 'products_id="' . $pID . '"'
					) . ' 
			       AND mpID = ' . $this->_magnasession['mpID']
		));
		#echo print_m($propertiesRow, '$propertiesRow');
		
		// Will not happen in sumbit cycle but can happen in loadProductByPId.
		if (empty($propertiesRow)) {
			$data['submit'] = array();
			$this->markAsFailed(magnaPID2SKU($pID));
			return;
		}
		$propertiesRow['VariationConfiguration'] = @json_decode($propertiesRow['VariationConfiguration'], true);
		$propertiesRow['ShippingDetails'] = @json_decode($propertiesRow['ShippingDetails'], true);
		
		if ($data['quantity'] < 0) {
			$data['quantity'] = 0;
		}
		
		// remove stuff we do not want.
		foreach (array('ProductId', 'ProductsModel', 'ManufacturerId', 'ShippingTimeId', 'DateAdded', 'LastModified') as $key) {
			unset($product[$key]);
		}
		
		$data['submit'] = $product;
		
		$data['submit']['SKU'] = magnaPID2SKU($pID);
		
		$data['submit']['ItemTitle'] = $product['Title'];
		$data['submit']['Price'] = $data['price'];
		$data['submit']['Quantity'] = $data['quantity'];
		
		if (!empty($data['submit']['EAN'])
			|| !getDBConfigValue(array('meinpaket.checkin.ean', 'submit'), $this->mpID, true)
		) {
			unset($data['submit']['EAN']);
		}
		
		$shortdescField = getDBConfigValue('meinpaket.checkin.shortdesc.field', $this->mpID, '');
		if (!empty($shortdescField) && array_key_exists($shortdescField, $product)) {
			$data['submit']['ShortDescription'] = $product[$shortdescField];
		} else {
			$data['submit']['ShortDescription'] = $product['Description'];
		}
		
		$longdescField = getDBConfigValue('meinpaket.checkin.longdesc.field', $this->mpID, '');
		if (!empty($longdescField) && array_key_exists($longdescField, $product)) {
			$data['submit']['Description'] = $product[$longdescField];
		} else {
			unset($data['submit']['Description']);
		}
		/* Short-Desc ist leer, vielleicht ist die Lang-Desc ja nicht leer. */
		$longDesc = $product['products_description'];
		if (empty($data['submit']['ShortDescription']) && !empty($longDesc)) {
			$data['submit']['ShortDescription'] = $longDesc;
		}
		
		/* Falls Langbeschreibung leer, Kurzbeschreibung ebenfalls fuer Langbeschreibung verwenden. Ansonsten entfernt Meinpaket
		   zu viele HTML-Tags */
		if (!isset($data['submit']['Description']) || empty($data['submit']['Description'])) {
			$data['submit']['Description'] = $data['submit']['ShortDescription'];
		}
		
		$taxMatch = getDBConfigValue('meinpaket.checkin.taxmatching', $this->mpID, array());
		if (is_array($taxMatch) && array_key_exists($product['TaxClass'], $taxMatch)) {
			$data['submit']['ItemTax'] = $taxMatch[$product['TaxClass']];
		} else {
			$data['submit']['ItemTax'] = 'Standard';
		}
		
		$data['submit']['ShippingTime'] = getDBConfigValue('meinpaket.checkin.leadtimetoship', $this->mpID, 3);
		$data['submit']['ShippingDetails'] = $propertiesRow['ShippingDetails'];
		
		$imageWSPath = getDBConfigValue('meinpaket.checkin.imagepath', $this->mpID, SHOP_URL_POPUP_IMAGES);
		$images = array();
		
		if (!empty($product['Images'])) {
			foreach($product['Images'] as $img) {
				$images[] = array('URL' => $imageWSPath.$img);
			}
		}
		$data['submit']['Images'] = $images;
		
		$data['submit']['MarketplaceCategory'] = $propertiesRow['MarketplaceCategory'];
		
		if (getDBConfigValue(array('meinpaket.catmatch.mpshopcats', 'val'), $this->mpID, false)) {
			$cPath = $this->generateMPCategoryPath($pID, 'product', $this->settings['language']);
			if (empty($cPath)) {
				$data['submit']['MarketplaceShopCategory'] = '';
				$data['submit']['MarketplaceShopCategoryStructure'] = array();
			} else {
				$cPath = array_shift($cPath);
				$data['submit']['MarketplaceShopCategory'] = $cPath[count($cPath)-1]['code'];
				$data['submit']['MarketplaceShopCategoryStructure'] = $cPath;
			}
		} else if (!empty($catMatching['StoreCategory'])) {
			$data['submit']['MarketplaceShopCategory'] = $propertiesRow['StoreCategory'];
		}
		
		if (!$this->processVariations($propertiesRow, $data)) {
			$data['submit'] = array();
			//$this->markAsFailed(magnaPID2SKU($pID));
			return;
		}
		
		//echo print_m($product, '$product');
		//echo print_m($propertiesRow, '$propertiesRow');
		
		return;
	}
	
	protected function appendAdditionalDataOld($pID, $product, &$data) {
		if ($data['quantity'] < 0) {
			$data['quantity'] = 0;
		}
		
		$data['submit']['SKU'] = magnaPID2SKU($pID);
		$data['submit']['ItemTitle'] = $product['products_name'];
		$data['submit']['Price'] = $data['price'];
		$data['submit']['Quantity'] = $data['quantity'];
		
		if (defined('MAGNA_FIELD_PRODUCTS_EAN')
			&& !empty($product[MAGNA_FIELD_PRODUCTS_EAN])
			&& getDBConfigValue(array('meinpaket.checkin.ean', 'submit'), $this->mpID, true)
		) {
			$data['submit']['EAN'] = $product[MAGNA_FIELD_PRODUCTS_EAN];
		}

		$shortdescField = getDBConfigValue('meinpaket.checkin.shortdesc.field', $this->mpID, '');
		if (!empty($shortdescField) && array_key_exists($shortdescField, $product)) {
			$data['submit']['ShortDescription']	= $product[$shortdescField];
		} else {
			$data['submit']['ShortDescription']	= $product['products_description'];
		}

		$longdescField = getDBConfigValue('meinpaket.checkin.longdesc.field', $this->mpID, '');
		if (!empty($longdescField) && array_key_exists($longdescField, $product)) {
			$data['submit']['Description'] = $product[$longdescField];
		}
		/* Short-Desc ist leer, vielleicht ist die Lang-Desc ja nicht leer. */
		$longDesc = $product['products_description'];
		if (empty($data['submit']['ShortDescription']) && !empty($longDesc)) {
			$data['submit']['ShortDescription'] = $longDesc;
		}
		
		/* Falls Langbeschreibung leer, Kurzbeschreibung ebenfalls fuer Langbeschreibung verwenden. Ansonsten entfernt Meinpaket
		   zu viele HTML-Tags */
		if (!isset($data['submit']['Description']) || empty($data['submit']['Description'])) {
			$data['submit']['Description'] = $data['submit']['ShortDescription'];
		}

		#VPE
		$showvpe = getDBConfigValue('meinpaket.checkin.showvpe', $this->mpID, false);
		if ((isset($product['products_vpe_name'])) && (0 <> $product['products_vpe_value'])) {
			$formatted_vpe = floatval($product['products_vpe_value']);
			$data['submit']['BasePrice'] = array (	   
				'Unit' => htmlspecialchars(trim($product['products_vpe_name'])),         // $product['products_vpe_name']
				'Value' => $formatted_vpe,                                               // $product['products_vpe_value']
				'showVPE' => $showvpe
			);
		} else {
			$data['submit']['BasePrice'] = array (               # falls in Zukunft meinpaket.de eigene Felder dafür definiert, base price = Grundpreis
				'Unit' => '',                                   // $product['products_vpe_name']
				'Value' => '',                                  // $product['products_vpe_value']
				'showVPE' => $showvpe
			);
		}
		# Ende VPE
		
		# Titel-Template 
		$title_template = getDBConfigValue('meinpaket.checkin.title_template', $this->mpID, '');
		$data['submit']['TitleTemplate'] = strip_tags($title_template);
		# Ende Titel-Template

		$manufacturerName = '';
		if ($product['manufacturers_id'] > 0) {
			$manufacturerName = (string)MagnaDB::gi()->fetchOne(
				'SELECT manufacturers_name FROM '.TABLE_MANUFACTURERS.' WHERE manufacturers_id="'.$product['manufacturers_id'].'"'
			);
		}
		if (empty($manufacturerName)) {
			$manufacturerName = getDBConfigValue(
				'meinpaket.checkin.manufacturerfallback',
				$this->mpID,
				''
			);
		}
		$mfrmd = getDBConfigValue('meinpaket.checkin.manufacturerpartnumber.table', $this->mpID, false);
		if (is_array($mfrmd) && !empty($mfrmd['column']) && !empty($mfrmd['table'])) {
			$pIDAlias = getDBConfigValue('meinpaket.checkin.manufacturerpartnumber.alias', $this->mpID);
			if (empty($pIDAlias)) {
				$pIDAlias = 'products_id';
			}
			$data['submit']['Manufacturer'] = $manufacturerName;
			$data['submit']['ManufacturerPartNumber'] = MagnaDB::gi()->fetchOne('
				SELECT `'.$mfrmd['column'].'` 
				  FROM `'.$mfrmd['table'].'` 
				 WHERE `'.$pIDAlias.'`="'.MagnaDB::gi()->escape($pID).'"
				 LIMIT 1
			');
		}
		
		$taxMatch = getDBConfigValue('meinpaket.checkin.taxmatching', $this->mpID, array());
		if (is_array($taxMatch) && array_key_exists($product['products_tax_class_id'], $taxMatch)) {
			$data['submit']['ItemTax'] = $taxMatch[$product['products_tax_class_id']];
		} else {
			$data['submit']['ItemTax'] = 'Standard';
		}

		$data['submit']['ShippingTime'] = getDBConfigValue('meinpaket.checkin.leadtimetoship', $this->mpID, 3);


		$imageWSPath = getDBConfigValue('meinpaket.checkin.imagepath', $this->mpID, SHOP_URL_POPUP_IMAGES);
		$images = array();
		
		if (!empty($product['products_allimages'])) {
			foreach($product['products_allimages'] as $img) {
				$images[] = array('URL' => $imageWSPath.$img);
			}
		}
		$data['submit']['Images'] = $images;
		
		if (($catMatching = MagnaDB::gi()->fetchRow('
			SELECT mp_category_id, store_category_id 
			  FROM `'.TABLE_MAGNA_MEINPAKET_CATEGORYMATCHING.'`
			 WHERE '.((getDBConfigValue('general.keytype', '0') == 'artNr')
			            ? 'products_model="'.MagnaDB::gi()->escape($product['products_model']).'"'
			            : 'products_id="'.$pID.'"'
			        ).' AND
			       mpID="'.$this->mpID.'"
			 LIMIT 1
		')) === false) {
			$this->markAsFailed(magnaPID2SKU($pID));
			return;
		}
		$data['submit']['MarketplaceCategory'] = $catMatching['mp_category_id'];

		if (getDBConfigValue(array('meinpaket.catmatch.mpshopcats', 'val'), $this->mpID, false)) {
			$cPath = $this->generateMPCategoryPath($pID, 'product', $this->settings['language']);
			$cPath = array_shift($cPath);
			$data['submit']['MarketplaceShopCategory'] = $cPath[count($cPath)-1]['code'];
			$data['submit']['MarketplaceShopCategoryStructure'] = $cPath;
		} else if (!empty($catMatching['store_category_id'])) {
			$data['submit']['MarketplaceShopCategory'] = $catMatching['store_category_id'];
		}

		$variationTheme = array();
		if (defined('MAGNA_FIELD_ATTRIBUTES_EAN') 
			&& MagnaDB::gi()->columnExistsInTable('attributes_stock', TABLE_PRODUCTS_ATTRIBUTES)
		) {
			$variationTheme = MagnaDB::gi()->fetchArray('
			    SELECT po.products_options_name AS VariationTitle,
			           pov.products_options_values_name AS VariationValue,
			           pa.products_attributes_id AS aID,
			           pa.options_values_price AS aPrice,
			           pa.price_prefix AS aPricePrefix,
			           pa.attributes_stock AS Quantity
			      FROM '.TABLE_PRODUCTS_ATTRIBUTES.' pa,
			           '.TABLE_PRODUCTS_OPTIONS.' po, 
			           '.TABLE_PRODUCTS_OPTIONS_VALUES.' pov, 
			           '.TABLE_LANGUAGES.' l
			     WHERE pa.products_id = "'.$pID.'"
			           AND po.language_id = l.languages_id
			           AND po.products_options_id = pa.options_id
			           AND po.products_options_name<>""
			           AND pov.language_id = l.languages_id
			           AND pov.products_options_values_id = pa.options_values_id
			           AND pov.products_options_values_name<>""
			           AND pa.attributes_stock IS NOT NULL
			           AND l.directory = "'.$_SESSION['language'].'"
			');
			arrayEntitiesToUTF8($variationTheme);
			#print_r($variationTheme);
			$quantityType = getDBConfigValue(
				$this->_magnasession['currentPlatform'].'.quantity.type',
				$this->mpID
			);
			$quantityValue = getDBConfigValue(
				$this->_magnasession['currentPlatform'].'.quantity.value',
				$this->mpID,
				0
			);
		
			if (!empty($variationTheme)) {
				foreach ($variationTheme as &$item) {
					$item['SKU'] = magnaAID2SKU($item['aID']);
					unset($item['aID']);
					switch ($quantityType) {
						case 'stock': {
							# Already set.
							break;
						}
						case 'stocksub': {
							$item['Quantity'] = (int)$item['Quantity'] - $quantityValue;
							break;
						}
						default: {
							$item['Quantity'] = $quantityValue;
						}
					}
					if ($item['Quantity'] < 0) {
						$item['Quantity'] = 0;
					}
					$this->simpleprice->setPrice($data['price'])->removeTax($tax);
	
					if ($item['aPricePrefix'] == '+') {
						$this->simpleprice->addLump($item['aPrice']);
					} else {
						$this->simpleprice->subLump($item['aPrice']);
					}
	
					$this->simpleprice->addTax($tax);
					if (getDBConfigValue(
							$this->_magnasession['currentPlatform'].'.price.addkind', 
							$this->mpID
						) == 'percent'
					) {
						$this->simpleprice->addTax((float)getDBConfigValue(
							$this->_magnasession['currentPlatform'].'.price.factor',
							$this->mpID
						));
					} else if (getDBConfigValue(
							$this->_magnasession['currentPlatform'].'.price.addkind',
							$this->mpID
						) == 'addition'
					) {
						$this->simpleprice->addLump((float)getDBConfigValue(
							$this->_magnasession['currentPlatform'].'.price.factor',
							$this->mpID
						));
					}
	
					$item['Price'] = $this->simpleprice->roundPrice()->makeSignalPrice(
							getDBConfigValue($this->_magnasession['currentPlatform'].'.price.signal', $this->mpID, '')
					    )->getPrice();
					unset($item['aPrice']);
					unset($item['aPricePrefix']);
				}
			}
			$data['submit']['Variations'] = $variationTheme;
			#echo print_m($variationTheme);
		}
	}
	
	protected function appendAdditionalData($pID, $product, &$data) {
		if ($this->settings['mlProductsUseLegacy']) {
			$this->appendAdditionalDataOld($pID, $product, $data);
		} else {
			$this->appendAdditionalDataNew($pID, $product, $data);
		}
	}
	
	protected function processSubmitResult($result) {
		if (array_key_exists('ERRORS', $result)
			&& is_array($result['ERRORS'])
			&& !empty($result['ERRORS'])
		) {
			foreach ($result['ERRORS'] as $err) {
				$ad = array ();
				if (isset($err['DETAILS']['SKU'])) {
					$ad['SKU'] = $err['DETAILS']['SKU'];
				}
				$err = array (
					'mpID' => $this->mpID,
					'errormessage' => $err['ERRORMESSAGE'],
					'dateadded' => gmdate('Y-m-d H:i:s'),
					'additionaldata' => serialize($ad),
				);
				MagnaDB::gi()->insert(TABLE_MAGNA_MEINPAKET_ERRORLOG, $err);
			}
		}
		magnaMeinpaketProcessCheckinResult($result, $this->mpID);
	}

	protected function filterSelection() { }

	protected function preSubmit(&$request) {
		MagnaConnector::gi()->setTimeOutInSeconds(600);
	}

	protected function postSubmit() {
		MagnaConnector::gi()->resetTimeOut();
	}

	protected function generateRedirectURL($state) {
		return toURL(array(
			'mp' => $this->realUrl['mp'],
			'mode' => 'listings',
		), true);
	}

}