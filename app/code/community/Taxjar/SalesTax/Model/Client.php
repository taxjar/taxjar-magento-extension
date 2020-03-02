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
 * TaxJar HTTP Client
 *
 * @author Taxjar (support@taxjar.com)
 */
class Taxjar_SalesTax_Model_Client
{
    protected $_version = 'v2';
    protected $_apiKey;
    protected $_storeZip;
    protected $_storeRegionCode;

    public function __construct()
    {
        $this->_apiKey = trim(Mage::getStoreConfig('tax/taxjar/apikey'));
        $this->_storeZip = trim(Mage::getStoreConfig('shipping/origin/postcode'));
        $this->_storeRegionCode = Mage::getModel('directory/region')->load(Mage::getStoreConfig('shipping/origin/region_id'))->getCode();
    }

    /**
     * Perform a GET request
     *
     * @param string $url
     * @param array $errors
     * @return array
     */
    public function getResource($resource, $errors = array())
    {
        $client = $this->_getClient($this->_getApiUrl($resource));
        return $this->_getRequest($client, $errors);
    }

    /**
     * Perform a POST request
     *
     * @param string $resource
     * @param array $data
     * @param array $errors
     * @return array
     */
    public function postResource($resource, $data, $errors = array())
    {
        $client = $this->_getClient($this->_getApiUrl($resource), Zend_Http_Client::POST);
        $client->setRawData(json_encode($data), 'application/json');
        return $this->_getRequest($client, $errors);
    }

    /**
     * Perform a PUT request
     *
     * @param string $resource
     * @param array $data
     * @param array $errors
     * @return array
     */
    public function putResource($resource, $resourceId, $data, $errors = array())
    {
        $resourceUrl = $this->_getApiUrl($resource) . '/' . $resourceId;
        $client = $this->_getClient($resourceUrl, Zend_Http_Client::PUT);
        $client->setRawData(json_encode($data), 'application/json');
        return $this->_getRequest($client, $errors);
    }

    /**
     * Perform a DELETE request
     *
     * @param string $resource
     * @param string $resourceId
     * @param array $body
     * @param array $errors
     * @return array
     */
    public function deleteResource($resource, $resourceId, $body = array(), $errors = array())
    {
        $resourceUrl = $this->_getApiUrl($resource) . '/' . $resourceId;
        $client = $this->_getClient($resourceUrl, Zend_Http_Client::DELETE);

        if (!empty($body)) {
            $client->setRawData(json_encode($body));
        }

        return $this->_getRequest($client, $errors);
    }

    /**
     * Get HTTP Client
     *
     * @param string $url
     * @return Zend_Http_Client $response
     */
    private function _getClient($url, $method = Zend_Http_Client::GET)
    {
        $client = new Zend_Http_Client($url, array('timeout' => 30));
        $client->setMethod($method);
        $client->setConfig(array(
            'useragent' => Mage::helper('taxjar')->getUserAgent(),
            'referer' => Mage::getBaseUrl()
        ));
        $client->setHeaders(array(
            'Authorization' => 'Bearer ' . $this->_apiKey,
            'Referer' => Mage::getBaseUrl()
        ));

        return $client;
    }

    /**
     * Get HTTP request
     *
     * @param Zend_Http_Client $client
     * @param array $errors
     * @return array
     */
    private function _getRequest($client, $errors = array())
    {
        try {
            $response = $client->request();

            if ($response->isSuccessful()) {
                $json = $response->getBody();
                return json_decode($json, true);
            } else {
                $this->_handleError($errors, $response->getStatus());
            }
        } catch (Zend_Http_Client_Exception $e) {
            Mage::throwException(Mage::helper('taxjar')->__('Could not connect to TaxJar.'));
        }
    }

    /**
     * Get SmartCalcs API URL
     *
     * @param string $type
     * @return string
     */
    private function _getApiUrl($resource)
    {
        $apiUrl = 'https://api.taxjar.com/' . $this->_version;

        switch($resource) {
            case 'config':
                $apiUrl .= '/plugins/magento/configuration/' . $this->_storeRegionCode;
                break;
            case 'rates':
                $apiUrl .= '/plugins/magento/rates/' . $this->_storeRegionCode . '/' . $this->_storeZip;
                break;
            case 'categories':
                $apiUrl .= '/categories';
                break;
            case 'nexus':
                $apiUrl .= '/nexus/addresses';
                break;
            case 'orders':
                $apiUrl .= '/transactions/orders';
                break;
            case 'refunds':
                $apiUrl .= '/transactions/refunds';
                break;
            case 'deregister':
                $apiUrl .= '/plugins/magento/deregister';
                break;
        }

        return $apiUrl;
    }

    /**
     * Handle API errors and throw exception
     *
     * @param array $customErrors
     * @param string $statusCode
     * @return string
     */
    private function _handleError($customErrors, $statusCode)
    {
        $errors = $this->_defaultErrors() + $customErrors;

        if (isset($errors[$statusCode])) {
            Mage::throwException($errors[$statusCode]);
        } else {
            Mage::throwException($errors['default']);
        }
    }

    /**
     * Return default API errors
     *
     * @return array
     */
    private function _defaultErrors()
    {
        return array(
            '401' => Mage::helper('taxjar')->__('Your TaxJar API token is invalid. Please review your TaxJar account at https://app.taxjar.com.'),
            'default' => Mage::helper('taxjar')->__('Could not connect to TaxJar.')
        );
    }
}
