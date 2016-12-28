<?php

class Catalin_SEO_Model_Observer {
	
	public function deleteCatFromFilter($observer) {
		$parts = $observer->getCollection()->getProductCountSelect()->getPart(Zend_Db_Select::FROM);
		$from = array();
		foreach ($parts as $key => $part) {
			if (stripos($key, "cat_index") === false) {
				$from[$key] = $part;
			}
		}
		$observer->getCollection()->getProductCountSelect()->setPart(Zend_Db_Select::FROM, $from);		
	}
	
}