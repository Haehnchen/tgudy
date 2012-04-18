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

final class tx_multicolumn_div {
	/**
	 * Start index of colpos
	 **/
	const colPosStart = 10;
	
	/**
	 * Get layout configuration options merged between typoscript and flexform options
	 *
	 * @param	array				$pageUid to get tsConfig (backendOnly)
	 * @param	tx_multicolumn_flexform		$flexform object
	 *
	 * @return	array				layout configuration options
	 */	
	public static function getLayoutConfiguration($pageUid, tx_multicolumn_flexform $flex) {
			//load default config
		$config = self::getDefaultLayoutConfiguration();
		
		$layoutKey = $flex->getFlexValue('preSetLayout', 'layoutKey');
			//remove . from ts string
		if($layoutKey) $config['layoutKey'] = substr($layoutKey, 0, -1);
		
		$tsConfig = self::getTSConfig($pageUid);
		if(isset($tsConfig[$layoutKey]['config.'])) $tsConfig = $tsConfig[$layoutKey]['config.'];

			//merge default config with ts config
		if(is_array($tsConfig)) $config = array_merge($config, $tsConfig);

			//merge with flexconfig
		$flexConfig = $flex->getFlexArray('advancedLayout');
		if(is_array($flexConfig)) $config = array_merge($config, $flexConfig);
		
		return $config;
	}
	
	/**
	 * Get layout configuration options merged between typoscript and flexform options
	 *
	 * @param	array				$pageUid to get tsConfig (backendOnly)
	 * @param	tx_multicolumn_flexform		$flexform object
	 *
	 * @return	array				layout configuration options
	 */	
	public static function getEffectConfiguration($pageUid, tx_multicolumn_flexform $flex) {
		$effect = substr($flex->getFlexValue('effectBox', 'effect'), 0, -1);
		$flexConfig = $flex->getFlexArray('effectBox');
		$tsConfig = self::getTSConfig($pageUid, 'effectBox');

		if(!empty($tsConfig[$effect . '.']['config.'])) {
			$config = $tsConfig[$effect . '.']['config.'];
			$config['effect'] = $effect;
			$tsConfigOptions = (!empty($config['defaultOptions'])) ? $config['defaultOptions'] : null;
				
				// check for options
			if(!empty($flexConfig['effectOptions'])) {
				$addComma = (strpos($flexConfig['effectOptions'], ',') === 0 && $tsConfigOptions) ?  null : ',';
				$config['options']  = $tsConfigOptions . $addComma . $flexConfig['effectOptions'];
			} else {
				$config['options']  = $tsConfigOptions;
			}
			
			$config['options']  = t3lib_div::minifyJavaScript($config['options']);

			unset($flexConfig['effectOptions'], $flexConfig['effect']);
			unset($config['defaultOptions']);

			$config = t3lib_div::array_merge($config, $flexConfig);
			return $config;
		}
	}
	
	/**
	 * Get prset layout configuration from tsconfig
	 *
	 * @param	array				$pageUid to get pageTsConfig
	 *
	 * @return	array				Preset layout configuration
	 */	
	public static function getTSConfig($pageUid, $tsConfigKey = 'layoutPreset') {
		$tsConfig = isset($GLOBALS['TSFE']->cObj) ? $GLOBALS['TSFE']->getPagesTSconfig() : t3lib_BEfunc::getPagesTSconfig($pageUid);

		$tsConfig = empty($tsConfig['tx_multicolumn.'][$tsConfigKey . '.']) ? $tsConfig['tx_multicolumn.'] : $tsConfig['tx_multicolumn.'][$tsConfigKey . '.'];
		return $tsConfig;
	}
	
	/**
	 * Evaluates current page id from backend context
	 *
	 *
	 * @return	integer		current backed page uid
	 */	
	public static function getBePidFromCachedTsConfig() {
		if(is_array($GLOBALS['SOBE']->tceforms->cachedTSconfig)) {
			$tsConfig = array_pop($GLOBALS['SOBE']->tceforms->cachedTSconfig);
			return $tsConfig['_CURRENT_PID'];
		}
	}
	
	/**
	 * If TYPO3 branch is above 4.3
	 *
	 * @return	boolean		true if version is above 4.3
	 */	
	public static function isTypo3VersionAboveTypo343() {
		if(!defined('TX_MULTICOLUMN_TYPO3_4-3')) return true;
	}
	
	/**
	 * If TYPO3 branch is above 4.4
	 *
	 * @return	boolean		true if version is above 4.4
	 */	
	public static function isTypo3VersionAboveTypo344() {
		if(!defined('TX_MULTICOLUMN_TYPO3_4-5_OR_ABOVE')) return true;
	}
	
	/**
	 * Calculates the maximal width  of the column in pixel based on {$styles.content.imgtext.colPos0.maxW}
	 *
	 * @return	integer			max width of column in pixel
	 */		
	public static function calculateMaxColumnWidth($columnWidth, $colPosMaxWidth, $numberOfColumns, $columnPadding = 0) {
		return floor(($colPosMaxWidth / 100) * $columnWidth);
	}
	
	/**
	 * Evaluates the total width of padding in colum
	 *
	 * @param	string		css string link 10px 20px 30px;		
	 * @return	integer		totalwidth of padding
	 */		
	public static function getPaddingTotalWidth($columnPadding) {
		$padding = preg_split('/ /', trim($columnPadding));
			//how many css attributes are set?
		$paddingNum = count($padding);
			
			//calculate total width
		$paddingTotalWidth = ($paddingNum == 2) ? intval($padding[1]) * 2 : (intval($padding[1]) + intval($padding[3]));
		return $paddingTotalWidth;
	}
	
	/**
	 * Returns default Layout configuration options
	 *
	 * @return	array			Layout configuration options
	 */	
	public static function getDefaultLayoutConfiguration() {
		return array (
			'layoutKey' => null,
			'layoutCss' => null,
			'columns' => 2,
			'containerMeasure' => '%',
			'containerWidth' => 100,
			'columnMeasure' => '%',
			'columnWidth' => null,
			'columnMargin' => null,
			'columnPadding' => null,
			'disableImageShrink' => null
		);
	}
	
	/**
	 * Prefix the keys in an array
	 *
	 * @param	Array		$array			The original array
	 * @param	String		$prefix			Prefix string (ex: 'LLL:')
	 * @return	Array		Prefixed array
	 */
	public static function prefixArray(array $array, $prefix) {
		$newArray	= array();

		foreach($array as $key => $value) {
			$newArray[$prefix.$key] = $value;
		}

		return $newArray;
	}
		
	/**
	 * Reads the [extDir]/locallang.xml and returns the $LOCAL_LANG array found in that file.
	 *
	 * @return	array	The array with language labels
	 */
	public static function includeBeLocalLang($llFile = null) {
		$llFile = $llFile ? $llFile : 'locallang.xml';
		return  t3lib_div::readLLXMLfile(PATH_tx_multicolumn . $llFile, $GLOBALS['LANG']->lang);
	}
		
	/**
	 * Checks if backend user has the rights to see multicolumn container
	 *
	 * @return	boolean	true if it has access false if not
	 */	
	public static function beUserHasRightToSeeMultiColumnContainer () {
		$hasAccess = true;
            // is admin?
        if(!empty($GLOBALS['BE_USER']->user['admin'])) return $hasAccess;
		
			// Possibly remove some items from TSconfig
		$TSconfig = t3lib_BEfunc::getPagesTSconfig($GLOBALS['SOBE']->id);
		if(!empty($TSconfig['TCEFORM.']['tt_content.']['CType.']['removeItems'])) {
			$hasAccess = t3lib_div::inList($TSconfig['TCEFORM.']['tt_content.']['CType.']['removeItems'], 'multicolumn') ? false : true;
		}
			// is explicitADmode allow ?
		if($GLOBALS['TYPO3_CONF_VARS']['BE']['explicitADmode'] === 'explicitAllow') {
			$hasAccess = t3lib_div::inList($GLOBALS['BE_USER']->groupData['explicit_allowdeny'], 'tt_content:CType:multicolumn:ALLOW') ? true : false;
		} else {
			$hasAccess = t3lib_div::inList($GLOBALS['BE_USER']->groupData['explicit_allowdeny'], 'tt_content:CType:multicolumn:DENY') ? false : true;
		}
		
		return $hasAccess;
	}
}
?>