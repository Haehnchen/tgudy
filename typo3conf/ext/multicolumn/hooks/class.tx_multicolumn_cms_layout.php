<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 snowflake productions GmbH
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
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
class tx_multicolumn_cms_layout {
	
	/**
	 * Expands the delete warning with "(This multicolumn container has X content elements(s)...)
	 * before you delete a records
	 */
	public function addDeleteWarning ($params, $pObj) {
		if(!$params[0] == 'tt_content') return;
		$LL = tx_multicolumn_div::includeBeLocalLang();
		$multicolumnUid = false;
		
			// adjust delete warning	
		if($params['2']['CType'] == 'multicolumn') {
			$numberOfContentElements = tx_multicolumn_db::getNumberOfContentElementsFromContainer($params['2']['uid']);

				// no children found? return!
			if(!$numberOfContentElements) {
				$this->restoreOrginalDeleteWarning($LL);
				return;
			}
			
			$llGlobal = &$GLOBALS['LOCAL_LANG'];

				// add multicolumn delete warning
			foreach($LL as $llKey => $ll) {
				$deleteWarningOrginal = isset($llGlobal[$llKey]['deleteWarningOrginal']) ? $llGlobal[$llKey]['deleteWarningOrginal'] : $llGlobal[$llKey]['deleteWarning'];
				
				$cmsLayoutDeleteWarning = $ll['cms_layout.deleteWarning'];
				if(is_array($cmsLayoutDeleteWarning)) {
					$cmsLayoutDeleteWarning = $cmsLayoutDeleteWarning[0]['target'];
				}
				$deleteWarningMulticolumn = str_replace('%s', $numberOfContentElements, $cmsLayoutDeleteWarning);
				$deleteWarning = isset($llGlobal[$llKey]['deleteWarningOrginal']) ? $llGlobal[$llKey]['deleteWarningOrginal'] : $llGlobal[$llKey]['deleteWarning'];

				if(is_array($llGlobal[$llKey]['deleteWarning'])) {
					foreach($llGlobal[$llKey]['deleteWarning'] as &$llValue) {
						$llValue = $deleteWarning[0];
					}
				} else {
					$llGlobal[$llKey]['deleteWarningOrginal'] = isset($llGlobal[$llKey]['deleteWarningOrginal']) ? $llGlobal[$llKey]['deleteWarningOrginal'] : $llGlobal[$llKey]['deleteWarning'];	
				}
				
				if(is_array($llGlobal[$llKey]['deleteWarning'])) {
					foreach($llGlobal[$llKey]['deleteWarning'] as &$llValue) {
						$llValue['source'] = $deleteWarningOrginal[0]['source'] . chr(10) . $deleteWarningMulticolumn;
						$llValue['target'] = $deleteWarningOrginal[0]['target'] . chr(10) . $deleteWarningMulticolumn;
					}
				} else {
					$llGlobal[$llKey]['deleteWarning'] = $deleteWarningOrginal . chr(10) . $deleteWarningMulticolumn;
				}
			}

			// restore orginal deleteWarning
		} else if(isset($llGlobal['default']['deleteWarningOrginal'])) {
			$this->restoreOrginalDeleteWarning($LL);
		}
		
		unset($llGlobal);
	}
	
	protected function restoreOrginalDeleteWarning (array $LL) {
		foreach($LL as $llKey => $ll) {
			if($GLOBALS['LOCAL_LANG'][$llKey]['deleteWarningOrginal']) $GLOBALS['LOCAL_LANG'][$llKey]['deleteWarning'] = $GLOBALS['LOCAL_LANG'][$llKey]['deleteWarningOrginal'];
		}	
	}
}
?>