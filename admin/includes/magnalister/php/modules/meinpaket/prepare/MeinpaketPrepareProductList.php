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
 * $Id$
 *
 * (c) 2010 - 2014 RedGecko GmbH -- http://www.redgecko.de
 *     Released under the MIT License (Expat)
 * -----------------------------------------------------------------------------
 */
require_once(DIR_MAGNALISTER_MODULES . 'meinpaket/classes/MLProductListMeinpaketAbstract.php');

class MeinpaketPrepareProductList extends MLProductListMeinpaketAbstract {

	public function __construct() {
		$this->aListConfig[] = array(
			'head' => array(
				'attributes' => 'class="lowestprice"',
				'content' => 'ML_MEINPAKET_LABEL_CATEGORY',
			),
			'field' => array('meinpaketmpcategory'),
		);
		$this->aListConfig[] = array(
			'head' => array(
				'attributes' => 'class="matched"',
				'content' => 'ML_MEINPAKET_LABEL_PREPARED',
			),
			'field' => array('preparestatusindicator'),
		);
		parent::__construct();
		$this
			->addDependency('MLProductListDependencyMeinpaketPrepareFormAction', array('selectionname' => $this->getSelectionName()))
			->addDependency('MLProductListDependencyStatusFilter')
			->addDependency('MLProductListDependencyMeinpaketPrepareStatusFilter')
		;
	}

	protected function getSelectionName() {
		return 'prepare';
	}

	protected function getPreparedStatusIndicator($aRow) {
		$aData = $this->getPrepareData($aRow);
		if ($aData !== false) {
					if ($aData['MarketplaceCategory'] != '') {
								return html_image(DIR_MAGNALISTER_WS_IMAGES . 'status/green_dot.png', ML_MEINPAKET_LABEL_CATMATCH_PREPARE_COMPLETE, 12, 12);
					} else {
								return html_image(DIR_MAGNALISTER_WS_IMAGES . 'status/red_dot.png', ML_MEINPAKET_LABEL_CATMATCH_PREPARE_INCOMPLETE, 12, 12);
					}
		}
		return html_image(DIR_MAGNALISTER_WS_IMAGES . 'status/grey_dot.png', ML_MEINPAKET_LABEL_CATMATCH_NOT_PREPARED, 12, 12);
	}

}
