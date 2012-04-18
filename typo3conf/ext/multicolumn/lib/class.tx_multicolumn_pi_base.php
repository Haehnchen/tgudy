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
*  */
require_once( PATH_tslib . 'class.tslib_pibase.php' );

class tx_multicolumn_pi_base extends tslib_pibase {
	
	/**
	 * Render an array with data element with $confName
	 *
	 * @param	String		$confName		Path to typoscript to render each element with
	 * @param	Array		$recordsArray	Array which contains elements (array) for typoscript rendering
	 * @param	Array		$appendData		Additinal data
	 * @return	String		All items rendered as a string
	 */
	public function renderListItems($confName, array $recordsArray, array $appendData = array(), $debug = false) {
		$arrayLength= count($recordsArray);
		$rowNr	= 1;
		$index = 0;
		$content = null;
		
		foreach($recordsArray as $data) {
			// first run?
			if($rowNr == 1)
				$data['isFirst'] = $confName.'First listItemFirst';

			// last run
			if($rowNr == $arrayLength)
				$data['isLast'] = $confName.'Last listItemLast';

			// push recordNumber to $data array
			$data['recordNumber'] = $rowNr;
			$data['index'] = $rowNr-1;

			// push arrayLength to $data array
			$data['arrayLength'] = $arrayLength;

			// Add odd or even to the cObjData array.
			$data['oddeven'] = $rowNr % 2 ? $confName.'Odd listItemOdd' : $confName.'Even listItemEven';
			
			// set data
			$data = array_merge($data, $appendData);
			$this->cObj->data = $data;
			$this->cObj->parentRecordNumber = $rowNr;
			
			// set uid for current record 
			$this->cObj->currentRecord = $GLOBALS['TYPO3_CONF_VARS']['SYS']['contentTable'] . ':' . $data['uid'];
			$content .= $this->cObj->cObjGetSingle($this->conf[$confName], $this->conf[$confName.'.']);
	    
			$rowNr ++;
		}
		
		$this->restoreCobjData();
		return $content;
	}
	
	/**
	 * Render an array with trough cObjGetSingle
	 *
	 * @param	String		$confName Path to typoscript to render each element with
	 * @param	Array		$recordsArray	Array which contains elements (array) for typoscript rendering
	 * @return	String		All items rendered as a string
	 */	
	protected function renderItem($confName, array $data) {
		$this->cObj->data = $data;
		$content = $this->cObj->cObjGetSingle($this->conf[$confName], $this->conf[$confName.'.']);
		
		$this->restoreCobjData();
		return $content;
	}
	
	/**
	 * Includes a css or js file
	 *
	 * @param	include files
	 */	
	protected function includeCssJsFiles(array $files) {
		foreach($files as $fileKey=>$file) {
			if(is_array($file)) continue;
			$mediaTypeSplit = strrchr($file, '.');

			$hookRequestParams = array(
				'includeFile' => array(
					$fileKey => $file,
					$fileKey . '.' => $files[$fileKey . '.']  	       
				),
				'mediaType' => str_replace('.', null, $mediaTypeSplit)
			);

			if(!$this->hookRequest('addJsCssFile', $hookRequestParams)) {
				$resolved = $GLOBALS['TSFE']->tmpl->getFileName($file);
				if($resolved) {
					($mediaTypeSplit ==  '.js') ? $GLOBALS['TSFE']->getPageRenderer()->addJsFooterFile($resolved) : $GLOBALS['TSFE']->getPageRenderer()->addCssFile($resolved);
				}
			}			
		}
	}
	
	/**
	 * Displays a flash message
	 *
	 * @param	string		$title flash message title
	 * @param	string		$message flash message message
	 *
	 * @retun	string		html content of flash message
	 */		
	protected function showFlashMessage($title, $message, $type = t3lib_FlashMessage::ERROR) {
			// get relative path
		$relPath = str_replace(t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST'), null, t3lib_div::getIndpEnv('TYPO3_SITE_URL'));
			// add error csss
		$GLOBALS['TSFE']->getPageRenderer()->addCssFile($relPath . 'typo3conf/ext/multicolumn/res/flashmessage.css', 'stylesheet','screen');
		$flashMessage = t3lib_div::makeInstance('t3lib_FlashMessage', $message, $title, $type);
		return $flashMessage->render();
	}
	
	/**
	 * Returns an object reference to the hook object if any
	 *
	 * @param	string		Name of the function you want to call / hook key
	 * @param	array		Request params
	 * @return	integer		Hook objects found
	 */
	protected function hookRequest($functionName, array $hookRequestParams) {
		global $TYPO3_CONF_VARS;
		$hooked = 0;
		
			// Hook: menuConfig_preProcessModMenu
		if (is_array($TYPO3_CONF_VARS['EXTCONF']['multicolumn']['pi1_hooks'][$functionName])) {
			foreach($TYPO3_CONF_VARS['EXTCONF']['multicolumn']['pi1_hooks'][$functionName] as $classRef) {
				$hookObj = t3lib_div::getUserObj($classRef);
				if (method_exists ($hookObj, $functionName)) {
					$hookObj->$functionName($this, $hookRequestParams);
					$hooked ++;
				}
			}
		}

		return $hooked;
	}
	
	/**
	 * Restore orginal cObj data to current cObj
	 */	
	protected function restoreCobjData() {
		$this->cObj->data = $this->currentCobjData;
		$this->cObj->currentRecord = $this->currentCobjRecordString;
		$this->cObj->parentRecordNumber = $this->currentCobjParentRecordNumber;
	}
	
}
?>