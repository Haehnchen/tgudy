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
require_once(PATH_tx_multicolumn_pi_base);

class tx_multicolumn_pi_sitemap  extends tx_multicolumn_pi_base {
	public $prefixId      = 'tx_multicolumn_pi_sitemap';        // Same as class name
	public $scriptRelPath = 'pi_sitemap/class.tx_multicolumn_pi_sitemap.php';    // Path to this script relative to the extension dir.
	public $extKey        = 'multicolumn';    // The extension key.
	public $pi_checkCHash = true;
	
	/**
	 * Current cObj data
	 *
	 * @var		array
	 */	
	protected $currentCobjData;
	
	/**
	 * The main method of the PlugIn
	 *
	 * @param    string        $content: The PlugIn content
	 * @param    array        $conf: The PlugIn configuration
	 * @return    The content that is displayed on the website
	 */
	public function main($content,$conf)    {
		$this->init($content, $conf);
		
		$uid = intval($this->cObj->stdWrap($this->conf['multicolumnContainerUid'], $this->conf['multicolumnContainerUid.']));
		if(!empty($uid)) {
			$elements = tx_multicolumn_db::getContentElementsFromContainer(null, null, $uid);
			$listData = array (
				'sitemapItem' =>  $this->renderListItems('sitemapItem',$elements)
			);
			return $this->renderItem('sitemapList', $listData);
		}
	}
	
	
	/**
	 * Initalizes the plugin.
	 *
	 * @param	String		$content: Content sent to plugin
	 * @param	String[]	$conf: Typoscript configuration array
	 */
	protected function init($content, $conf) {
		$this->content = $content;
		$this->conf = $conf;
		$this->currentCobjData = $this->cObj->data;
	}
}

if (defined('TYPO3_MODE') && isset($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/multicolumn/pi_sitemap/class.tx_multicolumn_pi_sitemap.php']))    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/multicolumn/pi_sitemap/class.tx_multicolumn_pi_sitemap.php']);
}
?>