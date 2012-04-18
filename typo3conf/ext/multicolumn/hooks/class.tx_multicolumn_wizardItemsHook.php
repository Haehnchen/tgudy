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

require_once(PATH_typo3 . 'interfaces/interface.cms_newcontentelementwizarditemshook.php');
class tx_multicolumn_wizardItemsHook implements cms_newContentElementWizardsHook {
	/**
	 * modifies WizardItems array
	 *
	 * @param	array					array of Wizard Items
	 * @param	SC_db_new_content_el	parent object New Content element wizard
	 * @return	void
	 */
	public function manipulateWizardItems(&$wizardItems, &$parentObject) {
		if(tx_multicolumn_div::beUserHasRightToSeeMultiColumnContainer()) $this->addMulitcolumnElementToWizardArray($wizardItems);

		$this->mulitColumnParentId = intval(t3lib_div::_GP('tx_multicolumn_parentid'));
			//is mulitcolum parentId set
		if($this->mulitColumnParentId) {
			$this->addMulticolumnParentId($wizardItems);
		}
	}
	
	/* Processing the wizard items array
	 *
	 * @param	array		$wizardItems: The wizard items
	 * @return	array		Modified array with wizard items
	 */
	protected function addMulitcolumnElementToWizardArray(&$wizardItems)	{
		global $LANG;
		$LL = $this->includeLocalLang();

		$multicolumnElement = array(
			'icon'=>t3lib_extMgm::extRelPath('multicolumn').'pi1/ce_wiz.gif',
			'title'=>$LANG->getLLL('pi1_title',$LL),
			'description'=>$LANG->getLLL('pi1_plus_wiz_description',$LL),
			'tt_content_defValues'=> array (
				'CType' => 'multicolumn'
			),
			'params' => '&defVals[tt_content][CType]=multicolumn'
		);
	
		$sortedItems = array();
		
		$position = $this->getWizardItemPosition($wizardItems);
		
		foreach ($wizardItems as $key => &$item) {
			$sortedItems[$key] = $item;
			if($key == $position) $sortedItems['common_multicolumn'] = $multicolumnElement;
		}

		$wizardItems = $sortedItems;
	}
	
	/* Evaluates the position of the multicolumn element in the wizardItem list
	 *
	 * @param	array		$wizardItems: The wizard items
	 * @return	string		The array key to insert
	 */	
	protected function getWizardItemPosition (array $wizardItems) {
		foreach ($wizardItems as $key => &$item) {
			if(!strstr($key ,'common')) return $lastKey;
			$lastKey = $key;
		}
	}

	/**
	 * Reads the [extDir]/locallang.xml and returns the $LOCAL_LANG array found in that file.
	 *
	 * @return	array	The array with language labels
	 */
	protected function includeLocalLang()	{
		$llFile = PATH_tx_multicolumn.'locallang.xml';
		$LOCAL_LANG = t3lib_div::readLLXMLfile($llFile, $GLOBALS['LANG']->lang);

		return $LOCAL_LANG;
	}
	
	/**
	 * add mulitcolumn parentid to wizard params
	 *
	 * @param	array					array of Wizard Items
	 * @return	void
	 */	
	protected function addMulticolumnParentId (array &$wizardItems) {
		foreach ($wizardItems as &$wizardItem) {
			if($wizardItem['params']) {
					//add mulitcolumn parent id
				$wizardItem['params'] .= '&defVals[tt_content][tx_multicolumn_parentid]=' . $this->mulitColumnParentId;
			}
		}
	}
}
?>