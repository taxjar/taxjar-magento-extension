<?php
/**
 * Taxjar_SalesTax
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   Taxjar
 * @package    Taxjar_SalesTax
 * @copyright  Copyright (c) 2016 TaxJar. TaxJar is a trademark of TPS Unlimited, Inc. (http://www.taxjar.com)
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Taxjar_SalesTax_Model_Observer_ImportCategories
{
    protected $_apiKey;
    protected $_client;

    public function execute(Varien_Event_Observer $observer)
    {
        $this->_apiKey = trim(Mage::getStoreConfig('tax/taxjar/apikey'));

        if ($this->_apiKey) {
            $this->_client = Mage::getSingleton('taxjar/client');
            $this->_importCategories();
        }
    }

    /**
     * Get TaxJar product categories
     *
     * @param void
     * @return string
     */
    private function _getCategoryJson()
    {
        $categoryJson = $this->_client->getResource($this->_apiKey, 'categories');
        return $categoryJson['categories'];
    }

    /**
     * Import TaxJar product categories
     *
     * @param void
     * @return string
     */
    private function _importCategories()
    {
        $categoryJson = $this->_getCategoryJson();
        Mage::getConfig()->saveConfig('tax/taxjar/categories', json_encode($categoryJson));
        Mage::getConfig()->reinit();
    }
}
