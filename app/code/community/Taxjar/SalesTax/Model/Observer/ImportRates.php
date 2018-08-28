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

class Taxjar_SalesTax_Model_Observer_ImportRates
{
    protected $_apiKey;
    protected $_client;
    protected $_storeZip;
    protected $_storeRegion;
    protected $_customerTaxClasses;
    protected $_productTaxClasses;
    protected $_newRates = array();
    protected $_newShippingRates = array();

    public function execute($ignoredObserverOrCronParameter = null)
    {
        $isEnabled = Mage::getStoreConfig('tax/taxjar/backup');
        $this->_apiKey = trim(Mage::getStoreConfig('tax/taxjar/apikey'));

        if ($isEnabled && $this->_apiKey) {
            $this->_client = Mage::getSingleton('taxjar/client');
            $this->_storeZip = $this->_client->getStoreZip();
            $this->_storeRegion = Mage::getModel('directory/region')
                ->load($this->_client->getStoreRegionId());

            $this->_customerTaxClasses = explode(',', Mage::getStoreConfig('tax/taxjar/customer_tax_classes'));
            $this->_productTaxClasses = explode(',', Mage::getStoreConfig('tax/taxjar/product_tax_classes'));
            $this->_importRates();
        } else {
            $states = unserialize(Mage::getStoreConfig('tax/taxjar/states'));

            if (!empty($states)) {
                $this->_purgeRates();
            }

            $this->_setLastUpdateDate(null);
            $this->_unsetBackupRates();
            Mage::getSingleton('core/session')->addNotice('Backup tax rates imported by TaxJar have been removed.');
        }

        // Clear the cache to avoid UI elements not loading
        Mage::app()->getCacheInstance()->flush();
    }

    /**
     * Import tax rates from TaxJar
     *
     * @param void
     * @return void
     */
    private function _importRates()
    {
        $isDebugMode = Mage::getStoreConfig('tax/taxjar/debug');

        if ($isDebugMode) {
            Mage::getSingleton('core/session')->addNotice('Debug mode enabled. Backup tax rates have not been altered.');
            return;
        }

        if ($this->_storeRegion && $this->_storeRegion->getCountryId() != 'US') {
            $this->_unsetBackupRates();
            Mage::throwException('Please check that your region is a valid US region in Shipping Settings > Origin. Currently we only support backup rates for US regions.');
        }

        if (!count($this->_productTaxClasses) || !count($this->_customerTaxClasses)) {
            $this->_unsetBackupRates();
            Mage::throwException('Please select at least one product tax class and one customer tax class to import backup rates from TaxJar.');
        }

        if ($this->_storeZip && preg_match('/^(\d{5}-\d{4}|\d{5})$/', $this->_storeZip)) {
            $ratesJson = $this->_getRatesJson();
        } else {
            $this->_unsetBackupRates();
            Mage::throwException('Please check that your zip code is a valid US zip code in Shipping Settings > Origin. Currently we only support backup rates for US regions.');
        }

        // Purge existing TaxJar rates and remove from rules
        $this->_purgeRates();

        if (file_put_contents($this->_getTempRatesFileName(), serialize($ratesJson)) !== false) {
            // This process can take awhile
            @set_time_limit(0);
            @ignore_user_abort(true);

            $filename = $this->_getTempRatesFileName();
            $ratesJson = unserialize(file_get_contents($filename));

            // Create new TaxJar rates and rules
            $this->_createRates($ratesJson);
            $this->_createRules();
            $this->_setLastUpdateDate(date('m-d-Y'));

            @unlink($filename);

            Mage::getSingleton('core/session')->addSuccess('TaxJar has added new rates to your database. Thanks for using TaxJar!');
            Mage::dispatchEvent('taxjar_salestax_import_rates_after');
        } else {
            $this->_unsetBackupRates();
            Mage::throwException('Could not write to your Magento temp directory. Please check permissions for ' . Mage::getBaseDir('tmp') . '.');
        }
    }

    /**
     * Create new tax rates
     *
     * @param void
     * @return void
     */
    private function _createRates($ratesJson)
    {
        $rate = Mage::getModel('taxjar/import_rate');

        foreach ($ratesJson['rates'] as $rateJson) {
            $rateIdWithShippingId = $rate->create($rateJson);

            if ($rateIdWithShippingId[0]) {
                $this->_newRates[] = $rateIdWithShippingId[0];
            }

            if ($rateIdWithShippingId[1]) {
                $this->_newShippingRates[] = $rateIdWithShippingId[1];
            }
        }
    }

    /**
     * Create or update existing tax rules with new rates
     *
     * @param void
     * @return void
     */
    private function _createRules()
    {
        $rule = Mage::getModel('taxjar/import_rule');
        $productTaxClasses = $this->_productTaxClasses;
        $shippingClass = Mage::getStoreConfig('tax/classes/shipping_tax_class');
        $backupShipping = in_array($shippingClass, $productTaxClasses);

        if ($backupShipping) {
            $productTaxClasses = array_diff($productTaxClasses, array($shippingClass));
        }

        $rule->create('TaxJar Backup Rates', $this->_customerTaxClasses, $productTaxClasses, 1, $this->_newRates);

        if ($backupShipping) {
            $rule->create('TaxJar Backup Rates (Shipping)', $this->_customerTaxClasses, array($shippingClass), 2, $this->_newShippingRates);
        }
    }

    /**
     * Purge existing rule calculations and rates
     *
     * @param void
     * @return void
     */
    private function _purgeRates()
    {
        $rates = Mage::getModel('taxjar/import_rate')->getExistingRates()->load();

        foreach ($rates as $rate) {
            $calculations = Mage::getModel('taxjar/import_rate')->getCalculationsByRateId($rate->getId())->load();

            try {
                foreach ($calculations as $calculation) {
                    $calculation->delete();
                }
            } catch (Exception $e) {
                Mage::getSingleton('core/session')->addError('There was an error deleting from Magento model tax/calculation');
            }

            try {
                $rate->delete();
            } catch (Exception $e) {
                Mage::getSingleton('core/session')->addError('There was an error deleting from Magento model tax/calculation_rate');
            }
        }
    }

    /**
     * Get TaxJar backup rates
     *
     * @param void
     * @return string
     */
    private function _getRatesJson()
    {
        $ratesJson = $this->_client->getResource($this->_apiKey, 'rates', array(
            '403' => Mage::helper('taxjar')->__('Your last backup rate sync from TaxJar was too recent. Please wait at least 5 minutes and try again.')
        ));
        return $ratesJson;
    }

    /**
     * Get the temp rates filename
     *
     * @param void
     * @return string
     */
    private function _getTempRatesFileName()
    {
        return Mage::getBaseDir('tmp') . DS . 'tj_tmp.dat';
    }

    /**
     * Set the last updated date
     *
     * @param string $date
     * @return void
     */
    private function _setLastUpdateDate($date)
    {
        Mage::getConfig()->saveConfig('tax/taxjar/last_update', $date);
    }

    /**
     * Unset the backup rates configuration option
     *
     * @param void
     * @return void
     */
    private function _unsetBackupRates()
    {
        Mage::getConfig()->saveConfig('tax/taxjar/backup', 0);
    }
}
