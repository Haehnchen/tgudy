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

class tx_multicolumn_db {
	/**
	 * Is TYPO3_BE ?
	 *
	 * @return	boolean		ture if TYPO3_mode is be
	 */
	public static function isBackend() {
		if(TYPO3_MODE == 'BE') return true;
	}
	
	/**
	 * Is the user in a workspace ?
	 *
	 * @return	boolean		ture if the user is an a workspace
	 */
	public static function isWorkspaceActive() {
		if(!empty($GLOBALS['BE_USER']->workspace) || !empty($GLOBALS['TSFE']->sys_page->versioningPreview)) {
			return true;
		}
	}
	
	/**
	 * Get content elements from tt_content table
	 *
	 * @param	integer			$colPos
	 * @param	integer			$pid page id
	 * @param	integer			$mulitColumnParentId parent id of multicolumn content element
	 * @param	integer			$sysLanguageUid sys language uid
	 * @param	booleand		$showHidden show hidden elements
	 * @param	string			$additionalWhere add additional where
	 * @param	object			tx_cms_layout object
	 *
	 * @return	array			Array with database fields
	 */	
	public static function getContentElementsFromContainer($colPos = null, $pid = null, $mulitColumnParentId, $sysLanguageUid = 0, $showHidden = false, $additionalWhere = null, tx_cms_layout &$cmsLayout = null) {
			// is workspace active?
		$isWorkspace = self::isWorkspaceActive();

		$selectFields = '*';
		$fromTable = 'tt_content';

		$whereClause = '1=1';
		if($colPos) $whereClause .= ' AND colPos=' . intval($colPos);
		if($pid && !$isWorkspace) {
			$whereClause .= ' AND pid =' . intval($pid);
		}
		
		$whereClause .= ' AND tx_multicolumn_parentid=' . intval($mulitColumnParentId);
		$whereClause .= ' AND sys_language_uid=' . intval($sysLanguageUid);
		
		if($additionalWhere) {
			$whereClause .=  ' AND ' . $additionalWhere;
		}

			// enable fields
		$whereClause .= self::enableFields($fromTable, $showHidden);
		if($isWorkspace) {
			$whereClause = self::getWorkspaceClause($whereClause);	
		}
		
		$orderBy = 'sorting ASC';
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($selectFields, $fromTable, $whereClause, null, $orderBy);

		if (!$GLOBALS['TYPO3_DB']->sql_error()) {
			if($cmsLayout) {
					//use cms layout object for correct icons
				$output = $cmsLayout->getResult($res,'tt_content', 1);
			} else {
				while ($output[] = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res));
				array_pop($output);
			}

			$GLOBALS['TYPO3_DB']->sql_free_result($res);
		}

		return $output;
	}
	
	/**
	 * Add additional workspace clause if needed
	 *
	 * @param	string			whereclause with enableFields
	 * 
	 * @return	string			whereclause with workspace
	 */	
	public static function getWorkspaceClause($whereClause) {
		$table = 'tt_content';

		if(!empty($GLOBALS['BE_USER']->workspace)) {
			$workspaceId = intval($GLOBALS['BE_USER']->workspace);
			$workspaceClause = ' AND (' . $table . '.t3ver_wsid=' . $workspaceId . ' OR ' . $table . '.t3ver_wsid=0)';

			if(strstr($whereClause, ' AND tt_content.pid > 0')) {
				$whereClause = str_replace(' AND tt_content.pid > 0', $workspaceClause, $whereClause);	
			} else {
				$whereClause = str_replace(' AND tt_content.deleted=0', ' AND tt_content.deleted=0' . $workspaceClause, $whereClause);
			}
		}

		return $whereClause;
	}
	
	/**
	 * Get number of content elements inside a multicolumn container
	 *
	 * @param	integer			$mulitColumnParentId tx_multicolumn_parentid
	 * 
	 * @return	integer			number of columns
	 */
	public static function getNumberOfContentElementsFromContainer($mulitColumnId) {
		$selectFields = 'uid';
		$fromTable = 'tt_content';
		$whereClause = 'tt_content.tx_multicolumn_parentId=' .intval($mulitColumnId) .  self::enableFields($fromTable);

		return count($GLOBALS['TYPO3_DB']->exec_SELECTgetRows($selectFields, $fromTable, $whereClause));	

	}
	
	/**
	 * Get number of columns
	 *
	 * @param	integer			$mulitColumnParentId tx_multicolumn_parentid
	 * 
	 * @return	integer			number of columns
	 */
	public static function getNumberOfColumnsFromContainer($mulitColumnId) {
		$row = self::getContentElement($mulitColumnId);
		if($row['pi_flexform']) {
			require_once(PATH_tx_multicolumn . 'lib/class.tx_multicolumn_flexform.php');
			$flexObj = t3lib_div::makeInstance('tx_multicolumn_flexform', $row['pi_flexform']);
			
			$layoutConfiguration = tx_multicolumn_div::getLayoutConfiguration($row['pid'], $flexObj);
			return intval($layoutConfiguration['columns']);
		}
	}
	
	/**
	 * Get content elemnt
	 *
	 * @param	integer			$uid of container element
	 * @param	string			$additionalWhere additional where
	 * @param	boolean			$useDeleteClause use delete clause
	 * 
	 * @return	array			container data
	 */		
	public static function getContentElement($uid, $selectFields = '*', $additionalWhere = null, $useDeleteClause = true) {
		if(self::isBackend()) {
			return t3lib_befunc::getRecordWSOL('tt_content', $uid, $selectFields, $additionalWhere, $useDeleteClause = true);
		}
		
		$selectFields = $selectFields;
		$fromTable = 'tt_content';
		$whereClause = ' uid=' . intval($uid);
		if($additionalWhere) $whereClause .= ' ' . $additionalWhere;

		$row = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($selectFields, $fromTable, $whereClause, null, $orderBy, null);		
		if($row) return $row[0];
	}
	
	/**
	 * Get multicolumn content elements from page uid
	 *
	 * @param	integer			$pid page id to fetch containers
	 * @param	integer			$sysLanguageUid language
	 * @param	integer			$selectFields
	 * 
	 * @return	array			container data
	 */
	public static function getContainersFromPid($pid, $sysLanguageUid = 0, $selectFields = 'uid,header') {
		$fromTable = 'tt_content';

		$whereClause = ' pid=' . intval($pid) . ' AND CType=\'multicolumn\'';
		$whereClause .= ' AND sys_language_uid = ' . intval($sysLanguageUid);
		$whereClause .= self::enableFields('tt_content');
		$orderBy = 'sorting';

		return $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($selectFields, $fromTable, $whereClause, null, $orderBy, null);			
	}
	
	/**
	 * Get multicolumn content element from uid
	 *
	 * @param	integer			$uid uid of content element
	 * @param	integer			$selectFields
	 * 
	 * @return	mixed			array if container found
	 */
	public static function getContainerFromUid($uid, $selectFields = 'uid,header', $enableFields = false) {
		$fromTable = 'tt_content';

		$whereClause = ' uid=' . intval($uid) . ' AND CType=\'multicolumn\'';
		if($enableFields) $whereClause .= self::enableFields('tt_content');
		
		$container = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($selectFields, $fromTable, $whereClause, null, $orderBy, null);	
		if(!empty($container)) return $container[0];
	}
	
	/**
	 * Checks if content element has an parent multicolumn content element
	 *
	 * @param	integer			$uid ontent element
	 * 
	 * @return	boolean			true if content element has a multicolumn content element as parent
	 */	
	public static function contentElementHasAMulticolumnParentContainer($uid) {
		$fromTable = 'tt_content';
		$selectFields = 'uid';
		$whereClause = ' uid=' . intval($uid) . ' AND tx_multicolumn_parentid != 0';
		$whereClause .= self::enableFields('tt_content');
		
		$container = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($selectFields, $fromTable, $whereClause, null, $orderBy, null);
		if(!empty($container[0]['uid'])) return true;
	}
	
	/**
	 * Updateds a content element
	 *
	 * @param	integer			$uid of element to be updated
	 * @param	array			$fields_values update value
	 * 
	 * @return	resource		mysql resource
	 */	
	public static function updateContentElement($uid, array $fields_values) {
		$table = 'tt_content';
		$where = 'tt_content.uid=' . intval($uid);
		
		$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, $where, $fields_values);
	}
	
	/**
	 * Checks if a multicolumn container has children
	 * 
	 * @param	integer		$containerUid 	multicolumn content element uid
	 * @param	string		$showHidden 	consider hidden elements too
	 *
	 * @return	mixed		array if with uid null if nothing found
	 */	
	public static function containerHasChildren($containerUid, $showHidden = true) {
		$fromTable = 'tt_content';
		$selectFields = 'uid,pid,sys_language_uid,CType';
		$whereClause .= 'tx_multicolumn_parentid =' . intval($containerUid);
		$whereClause .= self::enableFields($fromTable, $showHidden);

		$row = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($selectFields, $fromTable, $whereClause, null, $orderBy, null);
		if($row) return $row;
	}
	
	/**
	 * Get enableFields frontend / backend
	 *
	 * @param	string			$table table name
	 * @param	boolean			$showHidden show hidden records
	 * @param	array			$ignoreFields fields to ignore
	 * 
	 * @return	string			mysql query string
	 */
	protected static function enableFields($table, $showHidden = false, $ignoreFields = array()) {
		$enableFields = is_object($GLOBALS['TSFE']->cObj) ? self::enableFieldsFe($table, $showHidden, $ignoreFields) : self::enableFieldsBe($table, $showHidden, $ignoreFields);
		return $enableFields;
	}
	
	/**
	 * Get enableFields frontend
	 *
	 * @param	string			$table table name
	 * @param	boolean			$showHidden show hidden records
	 * @param	array			$ignoreFields fields to ignore
	 * 
	 * @return	string			mysql query string
	 */
	public static function enableFieldsFe($table, $showHidden = false, $ignoreFields = array()) {
		return $GLOBALS['TSFE']->cObj->enableFields($table, $showHidden, $ignoreFields);
	}
	
	/**
	 * Get enableFields backend
	 *
	 * @param	string			$table table name
	 * @param	boolean			$showHidden show hidden records
	 * @param	array			$ignoreFields fields to ignore
	 * 
	 * @return	string			mysql query string
	 */
	public static function enableFieldsBe($table, $showHidden = false, $ignoreFields = array()) {
		$whereClause = t3lib_BEfunc::BEenableFields($table) . t3lib_BEfunc::deleteClause($table);
		$whereClause .= ' AND ' . $table . '.pid > 0';

			// remove hidden
		if($showHidden) {
			$whereClause = str_replace('AND '.$table . '.hidden=0', null, $whereClause);
		}

		return $whereClause;
	}
}

?>