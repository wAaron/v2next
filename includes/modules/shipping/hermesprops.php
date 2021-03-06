<?php

/**
 * Hermes ProfiPaketService (ProPS)/PrivatPaketService (PriPS)
 */
class hermesprops {
	var $code, $title, $description, $icon, $enabled;
	
	function hermesprops() {
		global $order;

		$this->code = 'hermesprops';
		$this->title = MODULE_SHIPPING_HERMESPROPS_TEXT_TITLE;
		$this->description = MODULE_SHIPPING_HERMESPROPS_TEXT_DESCRIPTION;
		$this->sort_order = MODULE_SHIPPING_HERMESPROPS_SORT_ORDER;
		$this->icon = '';
		$this->tax_class = MODULE_SHIPPING_HERMESPROPS_TAX_CLASS;
		$this->enabled = ((MODULE_SHIPPING_HERMESPROPS_STATUS == 'True') ? true : false);
		$this->icon = DIR_WS_ICONS.'hermes_logo.png';

		if(($this->enabled == true) && ((int)MODULE_SHIPPING_HERMESPROPS_ZONE > 0)) {
			$check_flag = false;
			$check_query = xtc_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_SHIPPING_HERMESPROPS_ZONE . "' and zone_country_id = '" . $order->delivery['country']['id'] . "' order by zone_id");
			while ($check = xtc_db_fetch_array($check_query)) {
				if($check['zone_id'] < 1) {
					$check_flag = true;
					break;
				} elseif ($check['zone_id'] == $order->delivery['zone_id']) {
					$check_flag = true;
					break;
				}
			}

			if ($check_flag == false) {
				$this->enabled = false;
			}
		}
	}
	
	function determinePacketClass($products) {
		require_once DIR_FS_INC.'/xtc_get_prid.inc.php';
		$classes = array('XS' => 0, 'S' => 1, 'M' => 2, 'L' => 3, 'XL' => 4, 'XXL' => 5);
		$fclasses = array_flip($classes);
		$minclass = 0;
		foreach($products as $p) {
			$prid = xtc_get_prid($p['id']);
			$classquery = xtc_db_query("SELECT min_pclass FROM products_hermesoptions WHERE products_id = ". $prid);
			if(xtc_db_num_rows($classquery) == 0) {
				$min_pclass = 'XS';
			}
			else {
				$classrow = xtc_db_fetch_array($classquery);
				$min_pclass = $classrow['min_pclass'];
			}
			if($classes[$min_pclass] > $minclass) {
				$minclass = $classes[$min_pclass];
			}
		}
		return $fclasses[$minclass];
	}

	function quote($method = '') {
		global $order, $total_count;
		
		$packet_class = $this->determinePacketClass($order->products);
		
		$this->quotes = array('id' => $this->code,
													'module' => MODULE_SHIPPING_HERMESPROPS_TEXT_TITLE,
													'methods' => array(array(
																			'id' => $this->code,
																			'title' => MODULE_SHIPPING_HERMESPROPS_TEXT_WAY, // ." Paketklasse $packet_class",
																			'cost' => MODULE_SHIPPING_HERMESPROPS_HANDLING + constant('MODULE_SHIPPING_HERMESPROPS_COST_'.$packet_class))
																	  )
												 );

		if ($this->tax_class > 0) {
			$this->quotes['tax'] = xtc_get_tax_rate($this->tax_class, $order->delivery['country']['id'], $order->delivery['zone_id']);
		}

		if (xtc_not_null($this->icon)) $this->quotes['icon'] = xtc_image($this->icon, $this->title);

		return $this->quotes;
	}
	
	function check() {
		if(!isset($this->_check)) {
			$check_query = xtc_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_SHIPPING_HERMESPROPS_STATUS'");
			$this->_check = xtc_db_num_rows($check_query);
		}
		return $this->_check;
	}

	function install() {
		xtc_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, set_function, date_added) values ('MODULE_SHIPPING_HERMESPROPS_STATUS', 'True', '6', '0', 'xtc_cfg_select_option(array(\'True\', \'False\'), ', now())");
		xtc_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, date_added) values ('MODULE_SHIPPING_HERMESPROPS_ALLOWED', 'DE,BE,DK,EE,FI,FR,GB,IE,IT,LV,LT,LU,MC,NL,AT,PL,PT,SE,SK,SI,ES,CZ,HU', '6', '3', now())");
		xtc_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, date_added) values ('MODULE_SHIPPING_HERMESPROPS_COST_XS', '0.00', '6', '4', now())");
		xtc_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, date_added) values ('MODULE_SHIPPING_HERMESPROPS_COST_S', '0.00', '6', '4', now())");
		xtc_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, date_added) values ('MODULE_SHIPPING_HERMESPROPS_COST_M', '0.00', '6', '4', now())");
		xtc_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, date_added) values ('MODULE_SHIPPING_HERMESPROPS_COST_L', '0.00', '6', '4', now())");
		xtc_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, date_added) values ('MODULE_SHIPPING_HERMESPROPS_COST_XL', '0.00', '6', '4', now())");
		/*xtc_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, date_added) values ('MODULE_SHIPPING_HERMESPROPS_COST_XXL', '0.00', '6', '4', now())");*/
		xtc_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, date_added) values ('MODULE_SHIPPING_HERMESPROPS_HANDLING', '0', '6', '5', now())");
		xtc_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, use_function, set_function, date_added) values ('MODULE_SHIPPING_HERMESPROPS_TAX_CLASS', '0', '6', '6', 'xtc_get_tax_class_title', 'xtc_cfg_pull_down_tax_classes(', now())");
		xtc_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, use_function, set_function, date_added) values ('MODULE_SHIPPING_HERMESPROPS_ZONE', '0', '6', '7', 'xtc_get_zone_class_title', 'xtc_cfg_pull_down_zone_classes(', now())");
		xtc_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, date_added) values ('MODULE_SHIPPING_HERMESPROPS_SORT_ORDER', '0', '6', '8', now())");
		xtc_db_query("CREATE TABLE IF NOT EXISTS products_hermesoptions (
						  products_id int(11) NOT NULL,
						  min_pclass enum('XS','S','M','L','XL','XXL') NOT NULL,
						  PRIMARY KEY (products_id)
						);");
		xtc_db_query("INSERT INTO admin_navigation (id, name, title, subsite, filename, gid, languages_id, nav_set, sort) VALUES (NULL, 'hermes_config', 'Hermes Config', 'modules', 'hermes_config.php', NULL, 1, NULL, 6);");
		xtc_db_query("INSERT INTO admin_navigation (id, name, title, subsite, filename, gid, languages_id, nav_set, sort) VALUES (NULL, 'hermes_config', 'Hermes Konfiguration', 'modules', 'hermes_config.php', NULL, 2, NULL, 6);");
	
		xtc_db_query("INSERT INTO admin_navigation (id, name, title, subsite, filename, gid, languages_id, nav_set, sort) VALUES (NULL, 'hermes_info', 'Hermes Info', 'modules', 'hermes_info.php', NULL, 1, NULL, 6);");
		xtc_db_query("INSERT INTO admin_navigation (id, name, title, subsite, filename, gid, languages_id, nav_set, sort) VALUES (NULL, 'hermes_info', 'Hermes Info', 'modules', 'hermes_info.php', NULL, 2, NULL, 6);");
	
		xtc_db_query("INSERT INTO admin_navigation (id, name, title, subsite, filename, gid, languages_id, nav_set, sort) VALUES (NULL, 'hermes_list', 'Hermes List', 'modules', 'hermes_list.php', NULL, 1, NULL, 6);");
		xtc_db_query("INSERT INTO admin_navigation (id, name, title, subsite, filename, gid, languages_id, nav_set, sort) VALUES (NULL, 'hermes_list', 'Hermes Versandaufträge', 'modules', 'hermes_list.php', NULL, 2, NULL, 6);");
	
		xtc_db_query("INSERT INTO admin_navigation (id, name, title, subsite, filename, gid, languages_id, nav_set, sort) VALUES (NULL, 'hermes_collection', 'Hermes Abhol-Aufträge', 'modules', 'hermes_collection.php', NULL, 1, NULL, 6);");
		xtc_db_query("INSERT INTO admin_navigation (id, name, title, subsite, filename, gid, languages_id, nav_set, sort) VALUES (NULL, 'hermes_collection', 'Hermes Abhol-Aufträge', 'modules', 'hermes_collection.php', NULL, 2, NULL, 6);");
		
		if (column_exists ('admin_access','hermes_config')==false) {
			xtc_db_query("ALTER TABLE admin_access ADD hermes_config INT( 1 ) NOT NULL DEFAULT 0;");
			xtc_db_query("UPDATE admin_access SET hermes_config = '1' WHERE reviews = 1;");
		}		
		if (column_exists ('admin_access','hermes_info')==false) {
			xtc_db_query("ALTER TABLE admin_access ADD hermes_info INT( 1 ) NOT NULL DEFAULT 0;");
			xtc_db_query("UPDATE admin_access SET hermes_info = '1' WHERE reviews = 1;");
		}		
		if (column_exists ('admin_access','hermes_list')==false) {
			xtc_db_query("ALTER TABLE admin_access ADD hermes_list INT( 1 ) NOT NULL DEFAULT 0;");
			xtc_db_query("UPDATE admin_access SET hermes_list = '1' WHERE reviews = 1;");
		}		
		if (column_exists ('admin_access','hermes_collection')==false) {
			xtc_db_query("ALTER TABLE admin_access ADD hermes_collection INT( 1 ) NOT NULL DEFAULT 0;");
			xtc_db_query("UPDATE admin_access SET hermes_collection = '1' WHERE reviews = 1;");
		}			
		if (column_exists ('admin_access','hermes_order')==false) {
			xtc_db_query("ALTER TABLE admin_access ADD hermes_order INT( 1 ) NOT NULL DEFAULT 0;");
			xtc_db_query("UPDATE admin_access SET hermes_order = '1' WHERE reviews = 1;");
		}	
		xtc_db_query("CREATE TABLE orders_hermes (
					  orderno varchar(255) NOT NULL,
					  order_type enum('props','prips') NOT NULL DEFAULT 'props',
					  orders_id int(11) NOT NULL,
					  receiver_firstname varchar(25) DEFAULT NULL,
					  receiver_lastname varchar(25) NOT NULL,
					  receiver_street varchar(27) NOT NULL,
					  receiver_housenumber varchar(5) DEFAULT NULL,
					  receiver_addressadd varchar(25) DEFAULT NULL,
					  receiver_postcode varchar(25) NOT NULL,
					  receiver_city varchar(30) NOT NULL,
					  receiver_district varchar(25) DEFAULT NULL,
					  receiver_countrycode varchar(3) NOT NULL,
					  receiver_email varchar(255) DEFAULT NULL,
					  receiver_telephonenumber varchar(32) DEFAULT NULL,
					  receiver_telephoneprefix varchar(25) DEFAULT NULL,
					  clientreferencenumber varchar(255) DEFAULT NULL,
					  parcelclass varchar(255) DEFAULT NULL,
					  amountcashondeliveryeurocent int(11) NOT NULL,
					  state enum('not_sent','sent','printed') NOT NULL DEFAULT 'not_sent',
					  shipping_id varchar(255) DEFAULT NULL,
					  paket_shop_id varchar(255) DEFAULT NULL,
					  hand_over_mode varchar(255) DEFAULT NULL,
					  collection_desired_date datetime DEFAULT NULL,
					  PRIMARY KEY (orderno),
					  KEY order_id (orders_id)
					);");
	
	}

	function remove() {
		xtc_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
		xtc_db_query("DROP TABLE products_hermesoptions;");
		xtc_db_query("DROP TABLE IF EXISTS orders_hermes;");
		xtc_db_query("delete from admin_navigation where name = 'hermes_config';");
		xtc_db_query("delete from admin_navigation where name = 'hermes_info';");
		xtc_db_query("delete from admin_navigation where name = 'hermes_list';");
		xtc_db_query("delete from admin_navigation where name = 'hermes_collection';");
		xtc_db_query("delete from admin_navigation where name = 'hermes_order';");
	}

	function keys() {
		return array(
			'MODULE_SHIPPING_HERMESPROPS_STATUS',
			'MODULE_SHIPPING_HERMESPROPS_COST_XS',
			'MODULE_SHIPPING_HERMESPROPS_COST_S',
			'MODULE_SHIPPING_HERMESPROPS_COST_M',
			'MODULE_SHIPPING_HERMESPROPS_COST_L',
			'MODULE_SHIPPING_HERMESPROPS_COST_XL',
			'MODULE_SHIPPING_HERMESPROPS_HANDLING',
			'MODULE_SHIPPING_HERMESPROPS_ALLOWED', 'MODULE_SHIPPING_HERMESPROPS_TAX_CLASS', 'MODULE_SHIPPING_HERMESPROPS_ZONE',
			'MODULE_SHIPPING_HERMESPROPS_SORT_ORDER');
	}
}
