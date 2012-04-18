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

class tx_multicolumn_tcemain {
	/**
	 * @var		t3lib_TCEmain
	 */	
	protected $pObj;
	
	/**
	 * Copy children of a localized multicolumn container
	 *
	 * @param	string		$status: (reference) Status of the current operation, 'new' or 'update
	 * @param	string		$table: (refrence) The table currently processing data for
	 * @param	string		$id: (reference) The record uid currently processing data for, [integer] or [string] (like 'NEW...')
	 * @param	array		$fieldArray: (reference) The field array of a record
	 */	
	public function processDatamap_afterDatabaseOperations($status, $table, $id, &$fieldArray, t3lib_TCEmain $pObj) {
		$GPvar = t3lib_div::_GP('cmd');

			// element gets localized
		$localizeToSysLanguageUid = intval($GPvar['tt_content'][$fieldArray['t3_origuid']]['localize']);
		if($status == 'new' && $fieldArray['CType'] == 'multicolumn' && !empty($localizeToSysLanguageUid)) {
			$this->pObj = clone $pObj;

				//get new uid
			$multiColCeUid = $this->pObj->substNEWwithIDs[$id];
			
				//has container children?
			$parentUid = !empty($fieldArray['l18n_parent']) ? $fieldArray['l18n_parent'] : key($GPvar['tt_content']);
			if(!empty($parentUid)) {
				$containerHasChildren = tx_multicolumn_db::containerHasChildren($parentUid);
	
				if($multiColCeUid && $containerHasChildren) {
					$this->localizeMulticolumnChildren($containerHasChildren, $multiColCeUid, $localizeToSysLanguageUid);
				}
			}
			
				//reset rempat stack record for multicolumn item (prevents double call of processDatamap_afterDatabaseOperations)
			unset($pObj->remapStackRecords['tt_content'][$id]);
		}
	}
	
	/**
	 * Localize multicolumn children
	 *
	 * @param	array		content elements to be localize
	 * @param	integer		multicolumn_parentid
	*/	
	protected function localizeMulticolumnChildren(array $elementsToBeLocalized, $multicolumnParentId, $sysLanguageUid) {
		foreach($elementsToBeLocalized as $element) {
				//create localization
			$newUid = $this->pObj->localize('tt_content', $element['uid'], $sysLanguageUid);
			if($newUid) {
				$fields_values = array (
					'tx_multicolumn_parentid' => $multicolumnParentId
				);

				tx_multicolumn_db::updateContentElement($newUid, $fields_values);
				
					// if is element a multicolumn element ? localize children too (recursive)
				if($element['CType'] == 'multicolumn') {
					$containerChildrenChildren = tx_multicolumn_db::containerHasChildren($element['uid']);
					if(!empty($containerChildrenChildren)) {
						$this->localizeMulticolumnChildren($containerChildrenChildren, $newUid, $sysLanguageUid);
					}
				}
			}
		}
	}

	/**
	 * - Copy children of a multicolumn container
	 * - Delete children of a multicolumn container
	 * - Check if a seedy releation to a multicolumn container exits
	 * - Check if pasteinto multicolumn container is requested
	 *
	 * @param	string		$command: (reference) Status of the current operation, 'new' or 'update
	 * @param	string		$table: (refrence) The table currently processing data for
	 * @param	string		$id: (reference) The record uid currently processing data for, [integer] or [string] (like 'NEW...')
	 * @param	integer		$currentContentelementId: on copy this is the uid of selected element
	 * @param	object 		t3lib_TCEmain
	 */		
	public function processCmdmap_postProcess ($command, $table, $id, $currentContentelementId, t3lib_TCEmain $pObj) {
		if($table == 'tt_content'){
			$this->pObj = $pObj;

				// if pasteinto multicolumn container is requested?
			if($this->getMulticolumnGetAction() == 'pasteInto') {
				$moveOrCopy = $this->pObj->copyMappingArray['tt_content'][$id] ? 'copy' : 'move';
				$updateId = ($moveOrCopy == 'copy') ? $this->pObj->copyMappingArray['tt_content'][$id] : $id;

				$this->pasteIntoMulticolumnContainer($moveOrCopy, $updateId, $id);
			} else {
				$containerChildren = tx_multicolumn_db::containerHasChildren($id);
	
					// copy children of a multicolumn container too
				if($command == 'copy' && $containerChildren) {
						// the only way from here without db request to get the destinationPid?
					$destinationPid = key($this->pObj->cachedTSconfig);
					$sysLanguageUid = tx_multicolumn_db::getContentElement($this->pObj->copyMappingArray['tt_content'][$id], 'sys_language_uid');

					$this->copyMulticolumnContainer($id, $containerChildren, $destinationPid, $sysLanguageUid['sys_language_uid']);
					
					// check if content element has a seedy relation to multicolumncontainer?
				} else if($command == 'copy' && $newUid = intval($this->pObj->copyMappingArray['tt_content'][$id])) {
					$row = t3lib_BEfunc::getRecordWSOL('tt_content', $newUid);
						
						// if copy after
					if($pObj->cmdmap['tt_content'][$id]['copy'] < 0) {
						$elemetBeforeUid = abs($pObj->cmdmap['tt_content'][$id]['copy']);
						$elemetBeforeData = tx_multicolumn_db::getContentElement($elemetBeforeUid, 'uid,tx_multicolumn_parentid,colPos');
					}
	
					if($row['tx_multicolumn_parentid'] || $elemetBeforeData['tx_multicolumn_parentid']) {
						$updateRecordFields = array (
							'tx_multicolumn_parentid' => $elemetBeforeData['tx_multicolumn_parentid'] ? $elemetBeforeData['tx_multicolumn_parentid'] : 0,
							'colPos' => $elemetBeforeData['colPos'] ? $elemetBeforeData['colPos'] : 0
						);
						tx_multicolumn_db::updateContentElement($newUid, $updateRecordFields);	
					}
				}
					// delete children too
				if ($command == 'delete' && $containerChildren) {
					$this->deleteMulticolumnContainer($containerChildren);
				}				
			}
		}
	}
	
	/**
	 * Paste an element into multicolumn container
	 *
	 * @param	string		$action: copy or move
	 * @param	integer		$updateId: content element to update
	 * @param	integer		$orginalId: orginal id of content element (copy from)
	 */	
	protected function pasteIntoMulticolumnContainer ($action, $updateId, $orginalId = null) {
		$multicolumnId = intval(t3lib_div::_GET('tx_multicolumn_parentid'));
			// stop if someone is trying to cut the multicolumn container inside the container
		if($multicolumnId == $updateId) return;
		
		$updateRecordFields = array (
			'colPos' => intval(t3lib_div::_GET('colPos')),
			'tx_multicolumn_parentid' => $multicolumnId
		);

		tx_multicolumn_db::updateContentElement($updateId, $updateRecordFields);
			
		$containerChildren = tx_multicolumn_db::containerHasChildren($orginalId);
			// copy children too
		if($containerChildren) {
			$pid = $this->pObj->pageCache ? key($this->pObj->pageCache) : key($this->pObj->cachedTSconfig);

				// copy or move
			($action == 'copy') ? $this->copyMulticolumnContainer($updateId, $containerChildren, $pid) : $this->moveContainerChildren($containerChildren, $pid);
			
		}
	}
	
	/**
	 * Delete multicolumn container with children elements (recursive)
	 *
	 * @param	array		$containerChildren: Content elements of multicolumn container
	 * @param	integer		$pid: Target pid of page
	 */	
	protected function deleteMulticolumnContainer(array $containerChildren) {
		foreach($containerChildren as $child) {
			$this->pObj->deleteRecord('tt_content', $child['uid']);
				
				// if is element a multicolumn element ? delete children too (recursive)
			if($child['CType'] == 'multicolumn') {
				$containerChildrenChildren = tx_multicolumn_db::containerHasChildren($child['uid']);
				if($containerChildrenChildren) {
					$this->deleteMulticolumnContainer($containerChildrenChildren);
				}
			}
		}		
	}
	
	/**
	 * If an elements get copied outside from a multicontainer inside a multicolumncontainer add multicolumn parent id
	 * to content element
	 *
	 * @param	string		$status: (reference) Status of the current operation, 'new' or 'update
	 * @param	string		$table: (refrence) The table currently processing data for
	 * @param	string		$id: (reference) The record uid currently processing data for, [integer] or [string] (like 'NEW...')
	 * @param	array		$fieldArray: (reference) The field array of a record
	 * @param	object 		t3lib_TCEmain
	 */		
	public function processDatamap_postProcessFieldArray($status, $table, $id, &$fieldArray, t3lib_TCEmain $pObj) {
		if($status == 'new' && $table == 'tt_content' && $fieldArray['CType'] == 'multicolumn') {
			$this->pObj = $pObj;

				// get multicolumn status of element before?
			$fieldArray = $this->checkIfElementGetsCopiedOrMovedInsideOrOutsideAMulticolumnContainer($this->pObj->checkValue_currentRecord['pid'], $fieldArray);
		}
	}
	
	/**
	 * If an elements get moved outside from a multicontainer inside to a multicolumncontainer
	 * add tx_multicolumn_parentid to moved record
	 *
	 * @param	string		$table: The table currently processing data for
	 * @param	integer		$uid: The record uid currently processing
	 * @param	integer		$origDestPid: The uid of the record before current record
	 * @param	array		$moveRec: Record to move
	 * @param	array		$updateFields: Updated fields
	 * @param	object 		t3lib_TCEmain
	 */
	public function moveRecord_afterAnotherElementPostProcess($table, $uid, $destPid, $origDestPid, $moveRec, $updateFields, t3lib_TCEmain $pObj) {
			// check if we must update the move record
		if($table == 'tt_content' && (is_array($this->isMulticolumnContainer($uid)) || tx_multicolumn_db::contentElementHasAMulticolumnParentContainer($uid) || (($origDestPid < 0) && tx_multicolumn_db::contentElementHasAMulticolumnParentContainer(abs($origDestPid))))) {
			if(!$this->getMulticolumnGetAction() == 'pasteInto') {
				$updateRecordFields = array();
				$updateRecordFields = $this->checkIfElementGetsCopiedOrMovedInsideOrOutsideAMulticolumnContainer($origDestPid, $updateRecordFields);

				tx_multicolumn_db::updateContentElement($uid, $updateRecordFields);
					
					// check language
				if($origDestPid < 0) {
					$recordBeforeUid = abs($origDestPid);
					
					$row = tx_multicolumn_db::getContentElement($recordBeforeUid, 'sys_language_uid');
					$sysLanguageUid = $row['sys_language_uid'];

					$containerChildren = tx_multicolumn_db::containerHasChildren($uid);
					if(is_array($containerChildren)) {
						$firstElement = $containerChildren[0];
							// update only if destination has a diffrent langauge
						if(!($firstElement['sys_language_uid'] == $sysLanguageUid)) {
							$this->updateLanguage($containerChildren, $sysLanguageUid);
						}
					}
				}
	
					// update children (only if container is moved to a new page)
				if($moveRec['pid'] != $destPid) {
					$this->checkIfContainerHasChilds($table, $uid, $destPid, $pObj);
				}
			}
		}
	}
	
	/**
	 * If an elements get moved – move child records from multicolumn container too
	 *
	 * @param	string		$table: The table currently processing data for
	 * @param	integer		$uid: The record uid currently processing
	 * @param	integer		$destPid: The page id of the moved record
	 * @param	array		$moveRec: Record to move
	 * @param	array		$updateFields: Updated fields
	 * @param	object 		t3lib_TCEmain
	 */
	public function moveRecord_firstElementPostProcess($table, $uid, $destPid, array $moveRec, array $updateFields, t3lib_TCEmain $pObj) {
		if($table == 'tt_content'  && $this->isMulticolumnContainer($uid))  {
			if(!$this->getMulticolumnGetAction() == 'pasteInto') {
				$this->checkIfContainerHasChilds($table, $uid, $destPid, $pObj);
			}
		}
	}
	
	/**
	 * If an elements get moved – move child records from multicolumn container too
	 *
	 * @param	string		$table: The table currently processing data for
	 * @param	integer		$uid: The record uid currently processing
	 * @param	integer		$destPid: The page id of the moved record
	 * @param	object 		t3lib_TCEmain
	 */	
	protected function checkIfContainerHasChilds($table, $uid, $destPid, t3lib_TCEmain $pObj) {
		$this->pObj = $pObj;
		
		$row = t3lib_BEfunc::getRecordWSOL($table, $uid);
		if($row['CType'] == 'multicolumn') {
			$containerChildren = tx_multicolumn_db::containerHasChildren($row['uid']);
			if($containerChildren) {
				$this->moveContainerChildren($containerChildren, $destPid);
			}
			
			// if element is moved as first element on page ? set multicolumn_parentid and colPos to 0
		} else if ($row['tx_multicolumn_parentid']) {
			$multicolumnContainerExists = tx_multicolumn_db::getContentElement($row['tx_multicolumn_parentid'], 'uid', 'AND pid=' . $row['pid']);
			if(!$multicolumnContainerExists) {
				$updateRecordFields = array (
					'tx_multicolumn_parentid' => 0,
					'colPos' => 0
				);
				tx_multicolumn_db::updateContentElement($row['uid'], $updateRecordFields);	
			}
		}
	}
	
	/**
	 * Move container children (recursive)
	 *
	 * @param	array		$containerChildren: Children of multicolumn container
	 * @param	integer		$destPid: Target pid of page
	 */	
	protected function moveContainerChildren(array $containerChildren, $destPid) {
		foreach($containerChildren as $child) {
			$this->pObj->moveRecord_raw('tt_content', $child['uid'], $destPid);
		}
	}
	
	/**
	 * Updates the language of container children
	 *
	 * @param	array		$containerChildren: Children of multicolumn container
	 * @param	integer		$destPid: Target pid of page
	 */	
	protected function updateLanguage(array $containerChildren, $sysLanguageUid) {
		foreach($containerChildren as $child) {
			$updateRecordFields = array (
				'sys_language_uid' => $sysLanguageUid	
			);
			tx_multicolumn_db::updateContentElement($child['uid'], $updateRecordFields);	
		}		
	}
	
	/**
	 * Set new multicolumn container id for content elements and copies children of multicolumn container (recursive)
	 *
	 * @param	integer		$id new multicolumn element id
	 * @param	array		$elementsToCopy: Content element array with uid, and pid
	 * @param	integer		$pid: Target pid of page
	 */
	protected function copyMulticolumnContainer($id, array $elementsToCopy, $pid, $sysLanguageUid = 0) {
		$overrideValues = array (
			'tx_multicolumn_parentid' => $id,
			'sys_language_uid' => $sysLanguageUid
		);

		foreach($elementsToCopy as $element) {
			$newUid = $this->pObj->copyRecord_raw('tt_content', $element['uid'], $pid, $overrideValues);

				// if is element a multicolumn element ? copy children too (recursive)
			if($element['CType'] == 'multicolumn') {
				$containerChildren = tx_multicolumn_db::containerHasChildren($element['uid']);

				if($containerChildren) {
					$copiedMulticolumncontainer = tx_multicolumn_db::getContentElement($newUid, 'uid,pid');

					$this->copyMulticolumnContainer($newUid, $containerChildren, $copiedMulticolumncontainer['pid'], $sysLanguageUid);
				}
			}
		}
	}
	
	/**
	 * If an elements get copied outside from a multicontainer inside a multicolumncontainer or inverse
	 * add or remove multicolumn parent id to content element
	 *
	 * @param	array		$fieldArray: (reference) The field array of a record
	 * @return	array		$fieldArray: Modified field array
	 */	
	protected function checkIfElementGetsCopiedOrMovedInsideOrOutsideAMulticolumnContainer($pidToCheck, array &$fieldArray) {
		if($pidToCheck < 0) {
			$elementId = abs($pidToCheck);
			$elementBefore = t3lib_BEfunc::getRecord('tt_content', $elementId, 'tx_multicolumn_parentid, colPos');
	
			if($elementBefore['tx_multicolumn_parentid']) {
				$fieldArray['tx_multicolumn_parentid'] = $elementBefore['tx_multicolumn_parentid'];
			} else {
				$fieldArray['tx_multicolumn_parentid'] = 0;
			}
			$fieldArray['colPos'] = $elementBefore['colPos'];
		}
		
		return $fieldArray;
	}
	
	/**
	 * Check uid if is a multicolumn container
	 *
	 * @return	array		uid
	 */
	protected function isMulticolumnContainer($uid) {
		return tx_multicolumn_db::getContainerFromUid($uid, 'uid');
	}
	
	/**
	 * Evaluates specific multicolumn get &tx_multicolumn[action]
	 * currently the action as GET var is used only for paste into clickmenu action
	 *
	 * @return	string		value of action 
	 */
	protected function getMulticolumnGetAction() {
		$gpVars = t3lib_div::_GET('tx_multicolumn');
		return $gpVars['action'];
	}
}
?>