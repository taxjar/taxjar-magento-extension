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

class Taxjar_SalesTax_Model_Tax_Nexus extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        $this->_init('taxjar/tax_nexus');
    }

    /**
     * Create or update nexus address in TaxJar
     *
     * @return void
     */
    public function sync()
    {
        $client = Mage::getSingleton('taxjar/client');
        $apiKey = trim(Mage::getStoreConfig('tax/taxjar/apikey'));

        $data = array(
            'street' => $this->getStreet(),
            'city' => $this->getCity(),
            'state' => $this->getRegionCode(),
            'zip' => $this->getPostcode(),
            'country' => $this->getCountryId()
        );

        $responseErrors = array(
            '400' => Mage::helper('taxjar')->__('Your nexus address contains invalid data. Please verify the address in order to sync with TaxJar.'),
            '409' => Mage::helper('taxjar')->__('A nexus address already exists for this state/region. TaxJar currently supports one address per region.'),
            '422' => Mage::helper('taxjar')->__('Your nexus address is missing one or more required fields. Please verify the address in order to sync with TaxJar.'),
            '500' => Mage::helper('taxjar')->__('Something went wrong while syncing your address with TaxJar. Please verify the address and contact support@taxjar.com if the problem persists.')
        );

        if ($this->getId()) {
            $client->putResource('nexus', $this->getApiId(), $data, $responseErrors);
        } else {
            $savedAddress = $client->postResource('nexus', $data, $responseErrors);
            $this->setApiId($savedAddress['id']);
            $this->save();
        }
    }

    /**
     * Delete nexus address in TaxJar
     *
     * @return void
     */
    public function syncDelete()
    {
        $client = Mage::getSingleton('taxjar/client');
        $apiKey = trim(Mage::getStoreConfig('tax/taxjar/apikey'));

        $responseErrors = array(
            '409' => Mage::helper('taxjar')->__('A nexus address with this ID could not be found in TaxJar.'),
            '500' => Mage::helper('taxjar')->__('Something went wrong while deleting your address in TaxJar. Please contact support@taxjar.com if the problem persists.')
        );

        if ($this->getId()) {
            $client->deleteResource('nexus', $this->getApiId(), $responseErrors);
        }
    }

    /**
     * Sync nexus addresses from TaxJar -> Magento
     *
     * @return void
     */
    public function syncCollection()
    {
        $client = Mage::getSingleton('taxjar/client');
        $apiKey = trim(Mage::getStoreConfig('tax/taxjar/apikey'));
        $nexusJson = $client->getResource('nexus');

        if ($nexusJson['addresses']) {
            $addresses = $nexusJson['addresses'];

            foreach($addresses as $address) {
                if (!isset($address['country']) || empty($address['country'])) {
                    continue;
                }

                if (($address['country'] == 'US' || $address['country'] == 'CA') && (!isset($address['state']) || empty($address['state']))) {
                    continue;
                }

                $addressRegion = Mage::getModel('directory/region')->loadByCode($address['state'], $address['country']);
                $addressCountry = Mage::getModel('directory/country')->loadByCode($address['country']);
                $addressCollection = Mage::getModel('taxjar/tax_nexus')->getCollection();

                // Find existing address by region if US, otherwise country
                if ($address['country'] == 'US') {
                    $existingAddress = $addressCollection->addFieldToFilter('region_id', $addressRegion->getId())->getFirstItem();
                } else {
                    $existingAddress = $addressCollection->addFieldToFilter('country_id', $addressCountry->getId())->getFirstItem();
                }

                if ($existingAddress->getId()) {
                    $existingAddress->addData(array(
                        'api_id'     => $address['id'],
                        'street'     => $address['street'],
                        'city'       => $address['city'],
                        'postcode'   => $address['zip'],
                        'updated_at' => now()
                    ));
                    $existingAddress->save();
                } else {
                    $newAddress = Mage::getModel('taxjar/tax_nexus');
                    $newAddress->setData(array(
                        'api_id'      => $address['id'],
                        'street'      => $address['street'],
                        'city'        => $address['city'],
                        'country_id'  => $addressCountry->getId(),
                        'region'      => $addressRegion->getName(),
                        'region_id'   => $addressRegion->getId(),
                        'region_code' => $addressRegion->getCode(),
                        'postcode'    => $address['zip'],
                        'created_at'  => now(),
                        'updated_at'  => now()
                    ));
                    $newAddress->save();
                }
            }
        }
    }

    /**
     * Validate nexus address
     *
     * @return array
     */
    public function validate()
    {
        $errors = array();
        $nexusModel = Mage::getModel('taxjar/tax_nexus');

        if (!Zend_Validate::is($this->getCountryId(), 'NotEmpty')) {
            $errors[] = Mage::helper('taxjar')->__('Country can\'t be empty');
        }

        if (($this->getCountryId() == 'US' || $this->getCountryId() == 'CA') &&
            !Zend_Validate::is($this->getRegionId(), 'NotEmpty')) {
            $errors[] = Mage::helper('taxjar')->__('State can\'t be empty if country is US/Canada');
        }

        if (!$this->getId()) {
            $countryAddresses = $nexusModel->getCollection()->addFieldToFilter('country_id', $this->getCountryId());

            if ($countryAddresses->getSize() && $this->getCountryId() != 'US' && $this->getCountryId() != 'CA') {
                $errors[] = Mage::helper('taxjar')->__('Only one address per country (outside of US/CA) is currently supported.');
            }
        }

        if (empty($errors)) {
            return true;
        }

        return $errors;
    }
}
