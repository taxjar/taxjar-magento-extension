<?php
class Taxjar_SalesTax_Model_Client_CustomerSync
{

    protected $_customer;
    protected $_logger;

    /**
     * @param $customer Mage_Customer_Model_Customer
     */
    public function __construct($customer)
    {
        $this->_customer = $customer;
        $this->_logger = Mage::getModel('taxjar/logger')->setFilename('customers.log');
    }

    public function syncUpdates()
    {
        // Determine if taxjar touching is necessary
        $customer = Mage::getModel('customer/customer')->load($this->_customer->getId());
        // if no sync date or sync date is older than the current time run resync
        if((!$customer->getTjSalestaxSyncDate() or $customer->getTjSalestaxSyncDate() < time()) && !$customer->getTjProcessed()) {
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
                    $customer->setTjSalestaxSyncDate(time())->setTjProcessed(true)->save();
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
