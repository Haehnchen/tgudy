<?php
require_once(PATH_typo3 . 'sysext/cms/layout/interfaces/interface.tx_cms_layout_tt_content_drawitemhook.php');
class tx_multicolumn_tt_content_drawItem implements tx_cms_layout_tt_content_drawItemHook {
	
	/**
	 * Mulitcolumn content element
	 *
	 * @var		array
	 */
	protected $multiColCe;

	/**
	 * Instance of tx_multicolumn_flexform
	 *
	 * @var		tx_multicolumn_flexform
	 */
	protected $flex;
	
	/**
	 * Reference of tx_cms_layout Object
	 *
	 * @var		tx_cms_layout
	 */
	protected $pObj;
	
	/**
	 * Layout configuration array from ts / flexform
	 *
	 * @var		array
	 */
	protected $layoutConfiguration;
	
	/**
	 * Layout configuration array from ts / flexform with option split
	 *
	 * @var		array
	 */	
	protected $layoutConfigurationSplited;
	
	/**
	 * Reference of tx_cms_layout Object
	 *
	 * @var		t3lib_TStemplate
	 */
	protected $tmpl;
	
	/**
	 * Locallang array
	 *
	 * @var		array
	 */	
	protected $LL;
	
	/**
	 * Is effectbox?
	 *
	 * @var		boolean
	 */	
	protected $isEffectBox;
	
	/**
	 * Preprocesses the preview rendering of a content element.
	 *
	 * @param	tx_cms_layout		$parentObject: Calling parent object
	 * @param	boolean				$drawItem: Whether to draw the item using the default functionalities
	 * @param	string				$headerContent: Header content
	 * @param	string				$itemContent: Item content
	 * @param	array				$row: Record row of tt_content
	 * @return	void
	 */
	public function preProcess(tx_cms_layout &$parentObject, &$drawItem, &$headerContent, &$itemContent, array &$row) {
			// return if not multicolumn
		if($row['CType'] != 'multicolumn') return;

			// add css file
		$GLOBALS['TBE_TEMPLATE']->getPageRenderer()->addCssFile('../../../../typo3conf/ext/multicolumn/res/backend/style.css', 'stylesheet','screen');
		require_once(PATH_t3lib . 'class.t3lib_tstemplate.php');
		require_once(PATH_tx_multicolumn . 'lib/class.tx_multicolumn_flexform.php');
				
		$this->flex = t3lib_div::makeInstance('tx_multicolumn_flexform', $row['pi_flexform']);
		$this->pObj = $parentObject;
		$this->tmpl = t3lib_div::makeInstance('t3lib_TStemplate');
		$this->LL = tx_multicolumn_div::includeBeLocalLang();
		$this->isEffectBox = ($this->flex->getFlexValue ('preSetLayout', 'layoutKey') == 'effectBox.') ? true : false;

		$this->multiColCe = $row;
		$this->multiColUid = intval($row['uid']);
			
		$this->layoutConfiguration = tx_multicolumn_div::getLayoutConfiguration($this->multiColCe['pid'], $this->flex);

		if($this->layoutConfiguration['columns']) {
				// do option split
			$this->layoutConfigurationSplited = $this->tmpl->splitConfArray($this->layoutConfiguration, $this->layoutConfiguration['columns']);
			$itemContent .= $this->buildColumns($this->layoutConfiguration['columns']);
		}
	}
	
	/**
	 * Builds the columns markup
	 *
	 * @param	integer			$numberOfColumns: how many columns to build
	 * @return	string			html content
	 */	
	protected function buildColumns($numberOfColumns) {
			//build columns
		$markup = '</span><table class="multicolumn t3-page-columns"><tr>';
		$columnIndex = 0;
		while ($columnIndex < $numberOfColumns) {
			$multicolumnColPos = tx_multicolumn_div::colPosStart + $columnIndex;
			
			$splitedColumnConf = $this->layoutConfigurationSplited[$columnIndex];
			$columnWidth = $splitedColumnConf['columnWidth'] ? $splitedColumnConf['columnWidth'] : round(100/$numberOfColumns);

				//create header
			$this->buildColumn($columnWidth, $columnIndex, $multicolumnColPos, $markup);
			$columnIndex ++;
		}
		
		$markup .= '</tr>';
		$markup .= '</table>';
			// are there any lost content elements?
		$markup .= $this->buildLostContentElementsRow($multicolumnColPos);
		$markup .= '<span>';
		
		return $markup;
	}
	
	/**
	 * Builds a single column with conten telements
	 *
	 * @param	integer			$columnWidth: width of column
	 * @param	integer			$columnIndex: number of column
	 * @param	integer			$colPos
	 * @return	string			$column markup
	 */	
	protected function buildColumn($columnWidth, $columnIndex, $colPos, &$markup) {
		$markup .= '<td id="column_' . $this->multiColCe['uid'] . '_' . $colPos . '" class="t3-page-column t3-page-column-' . $columnIndex . ' column column' . $columnIndex . '" style="width: ' . $columnWidth . '%"><div class="innerContent">';
		
		$newParams = $this->getNewRecordParams($this->multiColCe['pid'], $colPos, $this->multiColCe['uid'], $this->multiColCe['sys_language_uid']);
		$columnNumber = $columnIndex + 1;
		$columnLabel = $this->isEffectBox ? $GLOBALS['LANG']->getLLL('cms_layout.effectBox', $this->LL) : $GLOBALS['LANG']->getLLL('cms_layout.columnTitle', $this->LL) . ' '.$columnNumber;

		$markup .= $this->pObj->tt_content_drawColHeader($columnLabel, null, $newParams);

		$markup .= $this->buildColumnContentElements($colPos, $this->multiColCe['pid'], $this->multiColCe['uid'], $this->multiColCe['sys_language_uid']);
		
		$markup .= '</div></td>';
	}
	
	/**
	 * Builds the contentElement for the column
	 *
	 * @param	integer			$colPos
	 * @param	integer			$pid page id
	 * @param	integer			$mulitColumnParentId parent id of multicolumn content element
	 * @param	integer			$sysLanguageUid sys language uid
	 */	
	protected function buildColumnContentElements($colPos, $pid, $mulitColumnParentId, $sysLanguageUid) {
		$showHidden = $this->pObj->tt_contentConfig['showHidden'] ? true : false;

		$elements = tx_multicolumn_db::getContentElementsFromContainer($colPos, $pid, $mulitColumnParentId, $sysLanguageUid, $showHidden, null, $this->pObj);
		if($elements) return $this->renderContentElements($elements);
	}
	
	/**
	 * Builds the lost content elements container
	 *
	 * @param	integer			$lastColumnNumber last visible columnNumber
	 * @return	string			$column markup
	 */		
	protected function buildLostContentElementsRow ($lastColumnNumber) {
		$additionalWhere = ' deleted = 0 AND colPos >' . intval($lastColumnNumber) . ' OR (colPos < 10 AND tx_multicolumn_parentid = ' . $this->multiColUid . ')';
                $elements = tx_multicolumn_db::getContentElementsFromContainer(null, null, $this->multiColUid, $this->multiColCe['sys_language_uid'], true, $additionalWhere, $this->pObj);

		if($elements) {
			$markup = '<div class="lostContentElementContainer">';
			
			$flashMessage = t3lib_div::makeInstance('t3lib_FlashMessage', $GLOBALS['LANG']->getLLL('cms_layout.lostElements.message', $this->LL), $GLOBALS['LANG']->getLLL('cms_layout.lostElements.title', $this->LL), t3lib_FlashMessage::WARNING);
			$markup .= $flashMessage->render();
			
			$markup .= $this->renderContentElements($elements, 'lostContentElements', true);
			$markup .= '</div>';
			return $markup;
		}
	}

	/**
	 * Render content elements like class.tx_cms_layout.php
	 *
	 * @param	array		$rowArr records form tt_content table
	 * @param	string		$additionalClasses to append to <ul>
	 * @param	string		$additionalClasses to append to <ul>	
	 */	
	protected function renderContentElements(array $rowArr, $additionalClasses = null, $lostElements = false) {
		$content = '<ul class="contentElements ' . $additionalClasses . '">';
			// used only for TYPO3 > 4.3
		$trailingDiv = tx_multicolumn_div::isTypo3VersionAboveTypo343() ? '</div>' : null;

		$item = 0;
		foreach($rowArr as $rKey => $row)	{
			if (is_array($row) && (int)$row['t3ver_state'] != 2)	{
				$statusHidden = ($this->pObj->isDisabled('tt_content', $row) ? ' t3-page-ce-hidden' : '');
				
				$ceClass = 't3-page-ce' . $statusHidden;
				$content .= '<li id="element_' . $row['tx_multicolumn_parentid'] . '_' . $row['colPos'] . '_' . $row['uid'] . '" class="contentElement item' . $item . '"><div class="'.$ceClass.'">';
				
				$isRTE = $this->pObj->isRTEforField('tt_content', $row, 'bodytext');
				$space = $this->pObj->tt_contentConfig['showInfo'] ? 15 : 5;
				
					// render diffrent header
				if($lostElements) {
						// prevents fail edit icon
					$currentNextThree = $this->pObj->tt_contentData['nextThree'];
					$this->pObj->tt_contentData['nextThree'][$row['uid']] = $row['uid'];
					
					$content .= $this->pObj->tt_content_drawHeader($row, $space, true, true);
						
						// restore next three
					$this->pObj->tt_contentData['nextThree'] = $currentNextThree;
				} else {
					$content .= $this->addMultiColumnParentIdToCeHeader($this->pObj->tt_content_drawHeader($row, $space, $this->pObj->defLangBinding && $lP > 0, true));
				}
					// pre crop bodytext
				if($row['bodytext']) {
					$row['bodytext'] = $this->pObj->strip_tags($row['bodytext'], true);
					$row['bodytext'] = $this->pObj->wordWrapper(t3lib_div::fixed_lgd_cs($row['bodytext'], 50), 25,' ');
				}

				$content.= '<div ' . (isset($row['_ORIG_uid']) ? ' class="ver-element"' :'') . '>' . $this->pObj->tt_content_drawItem($row, $isRTE) . '</div>';
				$content .= '</div>' . $trailingDiv . '</li>';
				$item ++;
			} else { unset($rowArr[$rKey]); }
		}
		
		$content .= '</ul>';
		return $content;
	}
	
	/**
	 * Adds tx_multicolumn_parentid to default db_new_content_el.php? query string
	 *
	 * @param	string		
	 * @return	string		Substituted content
	 */	
	protected function addMultiColumnParentIdToCeHeader($headerContent) {
		return str_replace('db_new_content_el.php?', 'db_new_content_el.php?tx_multicolumn_parentid=' . $this->multiColUid . '&amp;', $headerContent);
	}
	
	
	/**
	 * Generates the url for the insertRecord links. Special value tx_multicolumn is considered here...
	 *
	 * @param	integer		record id
	 * @param	integer		Column position value.
	 * @param	integer		content id, reference where this content element belongs to
	 * @param	integer		System language
	 * @return	string
	 */
	function getNewRecordParams($pid, $colPos, $mulitColumnParentId, $sysLanguageUid=0) {
		$params = '?id=' . $pid;
		$params .= '&colPos=' . $colPos;
		$params .= '&tx_multicolumn_parentid=' . $mulitColumnParentId;
		$params .= '&sys_language_uid=' . $sysLanguageUid;
		$params .= '&uid_pid='.$pid;
		$params .= '&returnUrl=' . rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'));
		
		return "window.location.href='db_new_content_el.php".$params."'";
	}
}
?>