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

class tx_multicolumn_emconfhelper {
	/**
	 * Checks if cms layout is xclassed
	 *
	 * @param	array				$params: Field information to be rendered
	 * @param	t3lib_tsStyleConfig	$pObj: The calling parent object.
	 * @return	string				Messages as HTML if something needs to be reported
	 */
	public function checkCompatibility(array $params, t3lib_tsStyleConfig $pObj) {
		$GLOBALS['LANG']->includeLLFile('EXT:multicolumn/locallang.xml');
			
			// check templavoila
		if(t3lib_extMgm::isLoaded('templavoila')) {
			$content .= $this->renderFlashMessage($GLOBALS['LANG']->getLL('emconfhelper.templavoila.title'), $GLOBALS['LANG']->getLL('emconfhelper.templavoila.message'), t3lib_FlashMessage::INFO);
		}
			// check XCLASS
		$XCLASS = $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/cms/layout/class.tx_cms_layout.php'];
			
			// get extkey if XCLASS exists
		if($XCLASS) $extKey = $this->getExtKeyByXCLASS($XCLASS);
		if($XCLASS && $extKey && !$this->checkIfDrawItemHookExists($XCLASS)) {
			$content .=  $this->renderDrawItemHookErrorMessage($XCLASS, $extKey);
		} else {
			$content .= $this->renderFlashMessage($GLOBALS['LANG']->getLL('emconfhelper.ok.title'), $GLOBALS['LANG']->getLL('emconfhelper.ok.message'), t3lib_FlashMessage::OK);
		}
		
		return $content;
	}
	
	/**
	 * Checks if cms layout XCLASS has implemented tx_cms_layout_tt_content_drawItemHook to process
	 * mulitcolumn elements
	 *
	 * @param	string		absolute path to XCLASS file
	 * @return	boolean		true if tx_cms_layout_tt_content_drawItemHook exists
	 */	
	protected function checkIfDrawItemHookExists ($XCLASS) {
		$drawItemHookExists = true;
		
		$fileContents = t3lib_div::getURL($XCLASS);
			// check if tt_content_drawItem( method exists?
		if(strpos($fileContents, 'tt_content_drawItem(')) {
				// check if tx_cms_layout_tt_content_drawItemHook is implemented
			if(!strpos($fileContents, 'tx_cms_layout_tt_content_drawItemHook')) $drawItemHookExists = false;
		}

		return $drawItemHookExists;
	}
	
	/**
	 * Renders flash message
	 *
	 * @return	string		flash message content
	 */	
	protected function renderFlashMessage($title, $message, $type = t3lib_FlashMessage::WARNING) {
		$flashMessage = t3lib_div::makeInstance('t3lib_FlashMessage', $message, $title, $type);
		return $flashMessage->render();
	}
	
	protected function renderDrawItemHookErrorMessage($XCLASS, $extKey) {
		$XCLASSwarning = str_replace(PATH_site, null, $XCLASS);

		$title = $GLOBALS['LANG']->getLL('emconfhelper.xclass.title') . ' ' . $extKey . ' XCLASS:<br />' . $XCLASSwarning;
		$uninstallLink = $this->buildUninstallLink($extKey); 
		$message = $extKey . ' ' . $GLOBALS['LANG']->getLL('emconfhelper.xclass.message') . ' ' . $uninstallLink . '.';
		return $this->renderFlashMessage($title, $message);
	}
	
	/**
	 * Builds uninstall link for XCLASS extension
	 *
	 * @return	string		flash message content
	 */	
	protected function buildUninstallLink($extKey) {
		$image = '<img src="uninstall.gif" width="16" height="16" align="top" alt="" />';
		return '<a title="Remove ' . $extKey . '" href="'.htmlspecialchars('index.php?CMD[showExt]='.$extKey.'&CMD[remove]=1').'">' . $image . ' ' . $extKey . '</a>';
	}
	
	/**
	 * Filters out ext key from XCLASS string
	 *
	 * @return	string		extension key from xclass
	 */	
	protected function getExtKeyByXCLASS($XCLASS) {
		$splitedByExtName = preg_split('/ext\//', $XCLASS);
		$extKeyArray = preg_split('/\//',$splitedByExtName[1], 2);
		return $extKeyArray[0];
	}
}
?>