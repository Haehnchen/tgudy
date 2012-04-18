<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');
	
	// add CType multicolumn
t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['multicolumn'] = array (
	'showitem' => 'CType;;4;;1-1-1, hidden, header;;3;;2-2-2, linkToTop;;;;3-3-3,--div--;LLL:EXT:multicolumn/locallang_db.xml:tt_content.tx_multicolumn_tab.content, tx_multicolumn_items,--div--;LLL:EXT:multicolumn/locallang_db.xml:tt_content.tx_multicolumn_tab.config,pi_flexform,--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.access, starttime, endtime, fe_group'
);

	// add multicolumn to CType
if(is_array($TCA['tt_content']['columns']['CType']['config']['items'])) {
	$multicolumnAdded = false;
	$firstDivChecked = false;
	$sortedItems = array();
		
	foreach ($TCA['tt_content']['columns']['CType']['config']['items'] as $key => $item) {
		if($item[1] == '--div--' && $firstDivChecked &! $multicolumnAdded) {
			$sortedItems[] = array (
				'LLL:EXT:multicolumn/locallang_db.xml:tx_multicolumn_multicolumn',
				'multicolumn',
				PATH_tx_multicolumn_rel . 'tt_content_multicolumn.gif'
			);
			$multicolumnAdded = true;
		}
	
		$firstDivChecked = true;
		$sortedItems[] = $item;
	}

	$TCA['tt_content']['columns']['CType']['config']['items'] = $sortedItems;
	unset($sortedItems, $firstDivChecked, $multicolumnAdded);
}

$TCA['tt_content']['ctrl']['typeicons']['multicolumn'] = PATH_tx_multicolumn_rel . 'tt_content_multicolumn.gif';

if(tx_multicolumn_div::isTypo3VersionAboveTypo343()) {
	t3lib_spritemanager::addTcaTypeIcon('tt_content', 'multicolumn', PATH_tx_multicolumn_rel . 'tt_content_multicolumn.gif');
}

	// add tx_multicolumn_contentid to tt_content table
$tempColumns = array (
	'tx_multicolumn_parentid' => array (		
		'exclude' => 1,		
		'label' => 'LLL:EXT:multicolumn/locallang_db.xml:tt_content.tx_multicolumn_parentid',		
		'config' => array (
			'type' => 'select',
			'foreign_table' => 'tt_content',    
			'foreign_table_where' => 'AND tt_content.uid=###REC_FIELD_tx_multicolumn_parentid###',
			'itemsProcFunc' => 'tx_multicolumn_tceform->init',
			'multicolumnProc' => 'buildMulticolumnList',
			'items' => array (
				array()	
			),
			'size' => 1,	
			'minitems' => 0,
			'maxitems' => 1,
			'wizards' => array(
				'_PADDING' => 2,
				'_VERTICAL' => 1,
				'edit' => array(
					'type' => 'popup',
					'title' => 'Edit',
					'script' => 'wizard_edit.php',
					'popup_onlyOpenIfSelected' => 1,
					'icon' => 'edit2.gif',
					'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
				)
			)
		)
	)
);

t3lib_extMgm::addTCAcolumns('tt_content', $tempColumns);
	// for 4.5
if(!empty($GLOBALS['TCA']['tt_content']['palettes']['general'])) t3lib_extMgm::addFieldsToPalette('tt_content', 'general', 'tx_multicolumn_parentid', 'before:colPos');
	// compatibility
if(!empty($GLOBALS['TCA']['tt_content']['palettes'][4])) t3lib_extMgm::addFieldsToPalette('tt_content', 4, 'tx_multicolumn_parentid', 'before:colPos');

if(TYPO3_MODE == 'BE') {
		// add itemsProcFunc to colPos for dynamic colPos
	require_once(PATH_tx_multicolumn . 'lib/class.tx_multicolumn_tceform.php');
	
		// add clickmenu expansion	
	$GLOBALS['TBE_MODULES_EXT']['xMOD_alt_clickmenu']['extendCMclasses'][]=array(
		'name' => 'tx_multicolumn_alt_clickmenu',
		'path' => PATH_tx_multicolumn . 'hooks/class.tx_multicolumn_alt_clickmenu.php'
	);
}

$TCA['tt_content']['columns']['colPos']['config']['itemsProcFunctions'] = array (
	'default' => $TCA['tt_content']['columns']['colPos']['config']['itemsProcFunc']
);
$TCA['tt_content']['columns']['colPos']['config']['multicolumnProc'] = 'buildDynamicCols';
	// overwrite default ...
$TCA['tt_content']['columns']['colPos']['config']['itemsProcFunc'] = 'tx_multicolumn_tceform->init';


	// request refresh if multicolumn_parent_id is changed
$TCA['tt_content']['ctrl']['requestUpdate'] .= ',layoutKey,tx_multicolumn_parentid';

	// add typoscript
t3lib_extMgm::addStaticFile($_EXTKEY,'pi1/static/','Multicolumn');
	
	// add configuration flexform
t3lib_extMgm::addPiFlexFormValue('*', 'FILE:EXT:multicolumn/flexform_ds.xml','multicolumn');
?>