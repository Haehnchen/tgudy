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

class tx_multicolumn_tce_eval {
		
	/**
	 * Returns input value
	 *
	 * @return	mixed		set value or null
	 */
	public function returnFieldJS() {
		return 'return (value ? value : null);';
	}
	
	/**
	 * Checks if input value of advanced layout column is greater than $returnValue
	 *
	 * @return	mixed		max column value
	 */
	public function evaluateFieldValue($inputValue, $is_in, &$set) {
		if($id = t3lib_div::_GP('popViewId')) {
			$conf = tx_multicolumn_div::getTSConfig($id, 'config');
			$maxNumberOfColumns = $conf['advancedLayouts.']['maxNumberOfColumns'];
			
			$returnValue = ($inputValue > $maxNumberOfColumns) ? $maxNumberOfColumns : $inputValue;
		}
			
		return $returnValue ? $returnValue : ($inputValue ? $inputValue : null);
	}	
}
?>