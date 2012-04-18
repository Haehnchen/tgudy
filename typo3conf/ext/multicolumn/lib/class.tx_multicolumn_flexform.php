<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 snowflake productions GmbH
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

class tx_multicolumn_flexform {
	/**
	 * Flexform configuration
	 *
	 * @var array
	 */
	protected $flex = array();
	
	public function __construct ($flexformString = null) {
		if($flexformString) $this->flex = t3lib_div::xml2array($flexformString);
	}
	
	/**
	 * Returns the value of flexform setting
	 *
	 * @param	string		Name of sheet
	 * @param	key		Name of flexform key
	 *
	 * @return	mixed		Flex value (typical a string)
	 *
	 * */
	public function getFlexValue ($sheet, $key) {
		if(is_array($this->flex['data'])) return $this->flex['data'][$sheet]['lDEF'][$key]['vDEF'];
	}
	
	
	/**
	 * Returns the flexform array
	 *
	 * @param	string		Key of column if none whole array is returned
	 *
	 * @return	array		Flexform array
	 *
	 * */	
	public function getFlexArray($key = null) {
		$flexform = array();

		if(is_array($this->flex['data'])) {

			if($key && $this->flex['data'][$key]['lDEF']) {
				foreach($this->flex['data'][$key]['lDEF'] as $flexKey=>$value) {
					if($value['vDEF']) $flexform[$flexKey] = $value['vDEF'];	
				}
			} else {
				$flexform = $this->flex['data'];
			}
			
		}

		return $flexform;
	}
	
	/**
	 * Generates the icons for the flexform selector layout
	 *
	 * @param	array		Array with current record and empty items arra
	 * @param	object		t3lib_TCEforms object
	 *
	 * @return	array		Generated items array
	 *
	 * */	
	public function addFieldsToFlexForm(&$params, t3lib_TCEforms $pObj) {
		$type = $params['config']['txMulitcolumnField'];
		$pid = ($params['row']['pid'] < 0 && is_array($pObj->cachedTSconfig)) ? tx_multicolumn_div::getBePidFromCachedTsConfig() : $params['row']['pid'];
		$tsConfig = tx_multicolumn_div::getTSConfig($pid, null);

		switch ($type) {
			case 'preSetLayout':
				if(is_array($tsConfig['layoutPreset.'])) {					
						// enable only specific effects
					if(!empty($tsConfig['config.']['layoutPreset.']['enableLayouts'])) {
						$this->filterItems($tsConfig['layoutPreset.'], $tsConfig['config.']['layoutPreset.']['enableLayouts']);
					}
					
						// add effectBox to the end
					if(!empty($tsConfig['layoutPreset.']['effectBox.'])) {
						$effectBox = $tsConfig['layoutPreset.']['effectBox.'];
							// add effect box to the end
						unset($tsConfig['layoutPreset.']['effectBox.']);
						$tsConfig['layoutPreset.']['effectBox.'] = $effectBox;
					}
					$this->buildItems($tsConfig['layoutPreset.'], $params);
				}
				break;
			case 'effect':
				if(is_array($tsConfig['effectBox.'])) {
					
						// enable only specific effects
					if(!empty($tsConfig['config.']['effectBox.']['enableEffects'])) {
						$this->filterItems($tsConfig['effectBox.'], $tsConfig['config.']['effectBox.']['enableEffects']);
					}
					
					$this->buildItems($tsConfig['effectBox.'], $params);
				}
				break;
		}
	}
	
	protected function buildItems(array $config, &$params) {
		foreach($config as $key=>$item) {
			$params['items'][] = array (
				$GLOBALS['LANG']->sL($item['label']),
				$key,
					//replace absolute with relative path
				str_replace(PATH_site, '../', t3lib_div::getFileAbsFileName($item['icon']))
			);
		}		
	}
	
	/**
	 * Filter out items from an array
	 *
	 * @param	array		array
	 * @param	object		comma seperated list
	 *
	 * */		
	protected function filterItems(array &$items, $filterList) {
		foreach($items as $itemKey => $item) {
			if(!t3lib_div::inList($filterList, str_replace('.', null, $itemKey))) {
				unset($items[$itemKey]);
			}
		}
	}
}
?>