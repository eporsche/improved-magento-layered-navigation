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
class Catalin_SEO_Model_Catalog_Layer_Filter_Category extends Mage_Catalog_Model_Layer_Filter_Category
{

    /**
     * Retrieve a collection of child categories for the provided category
     *
     * @param Mage_Catalog_Model_Category $category
     * @return Varien_Data_Collection_Db
     */
    protected function getChildrenCategories(Mage_Catalog_Model_Category $category)
    {
        $collection = $category->getCollection();
        $collection->addAttributeToSelect('url_key')
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('is_anchor')
            ->addAttributeToFilter('is_active', 1)
            ->addIdFilter($category->getChildren())
            ->setOrder('position', Varien_Db_Select::SQL_ASC)
            ->load();

        return $collection;
    }

    /**
     * Get data array for building category filter items
     *
     * @return array
     */

    /**
     * Get data array for building category filter items
     *
     * @return array
     */
    protected function _getItemsData()
    {
        if (!Mage::helper('catalin_seo')->isEnabled()) {
            return parent::_getItemsData();
        }

        $key = $this->getLayer()->getStateKey() . '_SUBCATEGORIES';
        $data = $this->getLayer()->getAggregator()->getCacheData($key);

        if ($data === null) {
        	$catid = Mage::helper('catalin_seo')->getRootCat() ?  Mage::helper('catalin_seo')->getRootCat() : $this->getCategory()-getId();
        	$currentCategory = Mage::getModel('catalog/category')->load($catid);
            /** @var $currentCategory Mage_Catalog_Model_Category */
            $categories = $this->getChildrenCategories($currentCategory);
            $this->getLayer()->getProductCollection()->addCountToCategories($categories);

            $data = array();
            foreach ($categories as $category) {
                if ($category->getIsActive() ) {
                	if (Mage::helper('catalin_seo')->isCategoryLinksEnabled()) {
                        $urlKey = $category->getUrl();
                    } else {
                        $urlKey = $category->getUrlKey();
                        if (empty($urlKey)) {
                            $urlKey = $category->getId();
                        }
                    }
                    $data[] = array(
                        'label' => Mage::helper('core')->escapeHtml($category->getName()),
                    	'value' => $urlKey,
                        'count' => $category->getProductCount(),
                    	'children' => $this->_getChildrenCat($category),
                    	'level' => $category->getLevel(),
    					'id' => $category->getId(),
                    	'caturl' => Mage::helper('catalin_seo')->getCategoryUrlById($category->getId())
                    );
                }
            }
            $tags = $this->getLayer()->getStateTags();
            $this->getLayer()->getAggregator()->saveCacheData($data, $key, $tags);
        }  
        return $data;
    }

    protected function _getChildrenCat($currentCategory) {
    	//do not add anything to tree if current category is not part of active tree
    	//or current category has no children
    	if (!$this->isCategoryActive($currentCategory) || count($currentCategory->getChildren()) == 0 ){
    		return null;
    	}
    	/** @var $currentCategory Mage_Catalog_Model_Category */
    	$categories = $this->getChildrenCategories($currentCategory);
    	$this->getLayer()->getProductCollection()->addCountToCategories($categories);
    	$data = array();
    	foreach ($categories as $category) {
    		if ($category->getIsActive() ) {
    			$urlKey = $category->getUrlKey();
    			if (empty($urlKey)) {
    				$urlKey = $category->getId();
    			}
    			$data[] = $this->_createItem(
    					Mage::helper('core')->escapeHtml($category->getName()),
    					$urlKey,
    					$category->getProductCount(),
    					$this->getChildrenCat($category),
    					$category->getLevel(),
    					$category->getId(),
    					Mage::helper('catalin_seo')->getCategoryUrlById($category->getId())
    					);
    		}
    	}
    	return $data;
    }
    
    
    public function isCategoryActive($category)
    {
    	return  $this->getLayer()->getCurrentCategory()->getId() ? in_array($category->getId(), $this->getLayer()->getCurrentCategory()->getPathIds()) : false;
    }
    
    
    /**
     * Apply category filter to layer
     *
     * @param   Zend_Controller_Request_Abstract $request
     * @param   Mage_Core_Block_Abstract $filterBlock
     * @return  Mage_Catalog_Model_Layer_Filter_Category
     */
    public function apply(Zend_Controller_Request_Abstract $request, $filterBlock)
    {
        if (!Mage::helper('catalin_seo')->isEnabled()) {
            return parent::apply($request, $filterBlock);
        }

        $filter = $request->getParam($this->getRequestVar());
        if (!$filter) {
            return $this;
        }

        $parts = explode('-', $filter);

        // Load the category filter by url_key
        $this->_appliedCategory = Mage::getModel('catalog/category')
            ->setStoreId(Mage::app()->getStore()->getId())
            ->loadByAttribute('url_key', $parts[0]);

        // Extra check in case it is a category id and not url key
        if (!($this->_appliedCategory instanceof Mage_Catalog_Model_Category)) {
            return parent::apply($request, $filterBlock);
        }

        $this->_categoryId = $this->_appliedCategory->getId();
        Mage::register('current_category_filter', $this->getCategory(), true);

        if ($this->_isValidCategory($this->_appliedCategory)) {
            $this->getLayer()->getProductCollection()
                ->addCategoryFilter($this->_appliedCategory);

            $this->getLayer()->getState()->addFilter(
                $this->_createItem($this->_appliedCategory->getName(), $filter)
            );
        }

        return $this;
    }


    protected function _initItems()
    {
    	$data = $this->_getItemsData();
    	$items=array();
    	foreach ($data as $itemData) {
    		$items[] = $this->_createItem(
    				$itemData['label'],
    				$itemData['value'],
    				$itemData['count'],
    				$itemData['children'],
    				$itemData['level'],
    				$itemData['id'],
    				$itemData['caturl']
    				);
    	}
    
    	$this->_items = $items;
    	return $this;
    }
    
    /**
     * Create filter item object
     *
     * @param   string $label
     * @param   mixed $value
     * @param   int $count
     * @return  Mage_Catalog_Model_Layer_Filter_Item
     */
    protected function _createItem($label, $value, $count=0, $children, $level=0, $id, $caturl)
    {
    	return Mage::getModel('catalog/layer_filter_item')
    	->setFilter($this)
    	->setLabel($label)
    	->setValue($value)
    	->setCount($count)
    	->setChildren($children)
    	->setLevel($level)
    	->setId($id)
    	->setCaturl($caturl);
    }     
    
}
