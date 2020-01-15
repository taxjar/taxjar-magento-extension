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

/**
 * TaxJar Admin Router
 * Connect and disconnect TaxJar accounts
 */
class Taxjar_SalesTax_Adminhtml_TaxjarController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Connect to TaxJar
     */
    public function connectAction()
    {
        $apiKey = (string) $this->getRequest()->getParam('api_key');
        $apiEmail = (string) $this->getRequest()->getParam('api_email');

        if ($apiKey && $apiEmail) {
            Mage::getConfig()->saveConfig('tax/taxjar/apikey', $apiKey);
            Mage::getConfig()->saveConfig('tax/taxjar/email', $apiEmail);
            Mage::getConfig()->saveConfig('tax/taxjar/connected', 1);
            Mage::getConfig()->reinit();
            Mage::getSingleton('core/session')->addSuccess(Mage::helper('taxjar')->__('TaxJar account for %s is now connected.', $apiEmail));
            Mage::dispatchEvent('taxjar_salestax_import_categories');
        } else {
            Mage::getSingleton('core/session')->addError(Mage::helper('taxjar')->__('Could not connect your TaxJar account. Please make sure you have a valid API token and try again.'));
        }

        $this->_redirect('adminhtml/system_config/edit/section/tax');
    }

    /**
     * Disconnect from TaxJar
     */
    public function disconnectAction()
    {
        Mage::getConfig()->saveConfig('tax/taxjar/apikey', '');
        Mage::getConfig()->saveConfig('tax/taxjar/email', '');
        Mage::getConfig()->saveConfig('tax/taxjar/connected', 0);
        Mage::getConfig()->saveConfig('tax/taxjar/enabled', 0);
        Mage::getConfig()->saveConfig('tax/taxjar/backup', 0);
        Mage::getConfig()->saveConfig('tax/taxjar/transactions', 0);
        Mage::getConfig()->reinit();

        $this->_purgeNexusAddresses();
        $this->_purgeProductTaxCategories();

        Mage::getSingleton('core/session')->addSuccess(Mage::helper('taxjar')->__('Your TaxJar account has been disconnected.'));
        Mage::dispatchEvent('taxjar_salestax_import_rates');

        $this->_redirect('adminhtml/system_config/edit/section/tax');
    }

    /**
     * Sync backup rates from TaxJar
     */
    public function sync_ratesAction()
    {
        try {
            Mage::dispatchEvent('taxjar_salestax_import_categories');
            Mage::dispatchEvent('taxjar_salestax_import_data');
            Mage::dispatchEvent('taxjar_salestax_import_rates');
        } catch (Exception $e) {
            Mage::getSingleton('core/session')->addError($e->getMessage());
        }
    }

    /**
     * Purge nexus addresses on disconnect
     */
    private function _purgeNexusAddresses()
    {
        $nexusAddresses = Mage::getModel('taxjar/tax_nexus')->getCollection();
        foreach($nexusAddresses as $nexusAddress) {
            $nexusAddress->delete();
        }
    }

    /**
     * Purge product tax categories on disconnect
     */
    private function _purgeProductTaxCategories()
    {
        $productTaxCategories = Mage::getModel('taxjar/tax_category')->getCollection();

        foreach($productTaxCategories as $productTaxCategory) {
            $productTaxCategory->delete();
        }
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('system/config/tax');
    }
}
