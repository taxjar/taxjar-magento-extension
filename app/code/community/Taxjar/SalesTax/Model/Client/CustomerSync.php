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

/**
 * Customer Sync to Taxjar Customer Endpoint
 * Contains all the logic necessary for creating the package to be sent to Taxjar customer api
 * Whenever a customer is updated or a tax class or customer class is changed and it needs a tax change in taxjar this endpoint
 * is hit
 */
class Taxjar_SalesTax_Model_Client_CustomerSync
{
    protected $_customer;
    protected $_logger;
    protected $_processedCustomers = [];
    /**
     * @param $customer Mage_Customer_Model_Customer
     */
    public function __construct()
    {
        $this->_logger = Mage::getModel('taxjar/logger')->setFilename('customers.log');
    }

    public function syncUpdates($customer)
    {
        // Determine if taxjar touching is necessary
        $this->_customer = $customer;
        $customer = Mage::getModel('customer/customer')->load($this->_customer->getId());
        // if no sync date or sync date is older than the current time run resync
        if((!$customer->getTjSalestaxSyncDate() or $customer->getTjSalestaxSyncDate() < time()) && !in_array($customer->getId(), $this->_processedCustomers)) {
            if (!$customer->getTjSalestaxSyncDate()) {
                $requestType = 'post';
                $url = 'https://api.taxjar.com/v2/customers';
            } else {
                $requestType = 'put';
                $url = 'https://api.taxjar.com/v2/customers/' . $customer->getId();
            }

            $taxClassId = $customer->getTaxClassId();
            $taxClass = Mage::getModel('tax/class')->load($taxClassId);

            $request = array(
                'customer_id'       => $customer->getId(),
                'exemption_type'    => $taxClass->getTjSalestaxExemptType(),
                'name'              => $customer->getName(),
            );

            $addressToUse = $customer->getPrimaryShippingAddress();

            if($addressToUse){
                $request['exempt_regions'] = array(
                    array(
                        'country'       => $addressToUse->getCountry(),
                        'state'         => $addressToUse->getRegionCode()
                    ));
                $request['country']     = $addressToUse->getCountry();
                $request['state']       = $addressToUse->getRegionCode();
                $request['zip']         = $addressToUse->getPostcode();
                $request['city']        = $addressToUse->getCity();
                $request['street']      = $addressToUse->getStreet1();
            } else {
                // Hardcoding due to endpoint requirements
                $request['exempt_regions'] = array(
                    array(
                        'country'       => 'US',
                        'state'         => 'NY'
                    ));
            }

            // Prepare for api call
            $apiKey = preg_replace('/\s+/', '', Mage::getStoreConfig('tax/taxjar/apikey'));
            $client = new Zend_Http_Client($url);
            $client->setHeaders('Authorization', 'Bearer ' . $apiKey);
            $client->setRawData(json_encode($request), 'application/json');

            $this->_logger->log('Creating/Updating Customer: ' . json_encode($request), $requestType);

            try {
                $response = $client->request(strtoupper($requestType));

                if (200 <= $response->getStatus() && 300 > $response->getStatus()) {
                    $this->_logger->log('Successful API response: ' . $response->getBody(), 'success');
                    // Since we are successful we want to set the last sync time to now and save the customer
                    $this->_processedCustomers[] = $customer->getId();
                    $customer->setTjSalestaxSyncDate(time())->save();
                    return true;
                } else {
                    $errorResponse = json_decode($response->getBody());
                    $this->_logger->log($errorResponse->status . ' ' . $errorResponse->error . ' - ' . $errorResponse->detail, 'error');
                    return false;
                }

            } catch (Zend_Http_Client_Exception $e) {
                // Catch API timeouts and network issues
                $this->_logger->log('API timeout or network issue between your store and TaxJar, please try again later.', 'error');
            }

        }

    }

    public function syncDelete()
    {
        $customer = $this->_customer;
        $requestType = 'delete';
        $url = 'https://api.taxjar.com/v2/customers/' . $customer->getId();

        // Prepare for api call
        $apiKey = preg_replace('/\s+/', '', Mage::getStoreConfig('tax/taxjar/apikey'));
        $client = new Zend_Http_Client($url);
        $client->setHeaders('Authorization', 'Bearer ' . $apiKey);

        $this->_logger->log('Deleting Customer: ' . $customer->getId(), $requestType);

        try {
            $response = $client->request(strtoupper($requestType));

            if (200 <= $response->getStatus() && 300 > $response->getStatus()) {
                $this->_logger->log('Successful API response: ' . $response->getBody(), 'success');
                // Since we are successful we want to set the last sync time to now and save the customer
            } else {
                $errorResponse = json_decode($response->getBody());
                $this->_logger->log($errorResponse->status . ' ' . $errorResponse->error . ' - ' . $errorResponse->detail, 'error');
            }

        } catch (Zend_Http_Client_Exception $e) {
            // Catch API timeouts and network issues
            $this->_logger->log('API timeout or network issue between your store and TaxJar, please try again later.', 'error');
        }
    }
}
