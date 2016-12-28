<?php

/**
 * Catalin Ciobanu
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License (MIT)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/MIT
 *
 * @package     Catalin_Seo
 * @copyright   Copyright (c) 2016 Catalin Ciobanu
 * @license     https://opensource.org/licenses/MIT  MIT License (MIT)
 */
class Catalin_SEO_Block_Catalog_Layer_Filter_Category extends Mage_Catalog_Block_Layer_Filter_Category
{

    /**
     * Class constructor
     */
    public function __construct()
    {
        parent::__construct();
		$this->setCacheTags(array('catalin_category_navigation'));
		$this->setCacheLifetime(false);
		if ($this->helper('catalin_seo')->isEnabled()) {
			$this->setTemplate('catalin_seo/catalog/layer/category.phtml');
		}
    }
	


    public function renderCategoriesMenuHtmlItem($item){
    	$isCurrent = ( $this->_current == $item->getId() ? true : false);
    
    	$html[] .= '<li '.($hasActiveChildren ? 'class="parentCat"' : '').' >';
    	if ($isCurrent) {
    		$html[] = '<span class="isActive">'. $this->escapeHtml($item->getLabel()) .'</span>';
    	}
    	else if($item->getCount()>0 && !$isCurrent){
    		$html[] = '<a href="' . $item->getCaturl() .'">'. $this->escapeHtml($item->getLabel()) . '</a>';
    	}
    	else {
    		$html[] = '<span class="inActive">'. $this->escapeHtml($item->getLabel()) . '</span>';
    	}
    
    	// render children if active category tree
    	$htmlChildren = '';
    	foreach ($item->getChildren() as $child) {
    		$htmlChildren .= $this->renderCategoriesMenuHtmlItem($child);
    	}
    	if (!empty($htmlChildren)) {
    		$html[] = '<ul class="subCat">'.$htmlChildren.'</ul>';
    	}
    
    	$html[] = '</li>';
    	return implode("\n", $html);
    }
    
    public function renderCategoriesMenuHtml()
    {
    	$this->_current = $this->getLayer()->getCurrentCategory()->getId();
    	$html = '';
    	foreach($this->getItems() as $item){
    		$html .= $this->renderCategoriesMenuHtmlItem($item);
    	}
    	return $html;
    }
    
    
    protected function _getItemPosition($level)
    {
    	if ($level == 0) {
    		$zeroLevelPosition = isset($this->_itemLevelPositions[$level]) ? $this->_itemLevelPositions[$level] + 1 : 1;
    		$this->_itemLevelPositions = array();
    		$this->_itemLevelPositions[$level] = $zeroLevelPosition;
    	} elseif (isset($this->_itemLevelPositions[$level])) {
    		$this->_itemLevelPositions[$level]++;
    	} else {
    		$this->_itemLevelPositions[$level] = 1;
    	}
    
    	$position = array();
    	for($i = 0; $i <= $level; $i++) {
    		if (isset($this->_itemLevelPositions[$i])) {
    			$position[] = $this->_itemLevelPositions[$i];
    		}
    	}
    	return implode('-', $position);
    }
    
}
