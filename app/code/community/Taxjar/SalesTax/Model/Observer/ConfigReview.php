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
 * @copyright  Copyright (c) 2019 TaxJar. TaxJar is a trademark of TPS Unlimited, Inc. (http://www.taxjar.com)
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Taxjar_SalesTax_Model_Observer_ConfigReview
{
    public function execute(Varien_Event_Observer $observer)
    {
        $configSection = Mage::app()->getRequest()->getParam('section');

        if ($configSection == 'tax') {
            $enabled = Mage::getStoreConfig('tax/taxjar/enabled');

            if ($enabled) {
                $this->_reviewNexusAddresses();
            }
        }

        return $this;
    }

    protected function _reviewNexusAddresses()
    {
        $nexusAddresses = Mage::getModel('taxjar/tax_nexus')->getCollection();

        if (!$nexusAddresses->getSize()) {
            Mage::getSingleton('core/session')->addError(Mage::helper('taxjar')->__('You have no nexus addresses loaded in Magento. Go to Sales > Tax > Nexus Addresses to sync from your TaxJar account or add a new address.'));
        }
    }
}
