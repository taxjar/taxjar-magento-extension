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
 * TaxJar HTTP Client
 *
 * @author Taxjar (support@taxjar.com)
 */
class Taxjar_SalesTax_Model_Client
{
    protected $_version = 'v2';
    protected $_storeZip;
    protected $_storeRegionCode;
    protected $_storeRegionId;

    /**
     * Use website specific shipping origin address if possible,
     * fallback to global value otherwise.
     */
    public function __construct()
    {
        $websiteForShippingOrigin = Mage::getStoreConfig('tax/taxjar/shipping_origin_website');
        if ($websiteForShippingOrigin) {
            $this->_storeZip = trim(
                Mage::app()
                    ->getWebsite($websiteForShippingOrigin)
                    ->getConfig('shipping/origin/postcode')
            );
            $this->_storeRegionId = Mage::app()
                ->getWebsite($websiteForShippingOrigin)
                ->getConfig('shipping/origin/region_id');

        } else {
            $this->_storeZip = trim(Mage::getStoreConfig('shipping/origin/postcode'));
            $this->_storeRegionId = Mage::getStoreConfig('shipping/origin/region_id');
        }

        $this->_storeRegionCode = Mage::getModel('directory/region')->load($this->_storeRegionId)->getCode();
    }

    /**
     * @return integer
     */
    public function getStoreRegionId()
    {
        return $this->_storeRegionId;
    }

    /**
     * @return string
     */
    public function getStoreRegionCode()
    {
        return $this->_storeRegionCode;
    }

    /**
     * @return integer
     */
    public function getStoreZip()
    {
        return $this->_storeZip;
    }

    /**
     * Perform a GET request
     *
     * @param string $apiKey
     * @param string $url
     * @param array $errors
     * @return array
     */
    public function getResource($apiKey, $resource, $errors = array())
    {
        $client = $this->_getClient($apiKey, $this->_getApiUrl($resource));
        return $this->_getRequest($client, $errors);
    }

    /**
     * Perform a POST request
     *
     * @param string $apiKey
     * @param string $resource
     * @param array $data
     * @param array $errors
     * @return array
     */
    public function postResource($apiKey, $resource, $data, $errors = array())
    {
        $client = $this->_getClient($apiKey, $this->_getApiUrl($resource), Zend_Http_Client::POST);
        $client->setRawData(json_encode($data), 'application/json');
        return $this->_getRequest($client, $errors);
    }

    /**
     * Perform a PUT request
     *
     * @param string $apiKey
     * @param string $resource
     * @param array $data
     * @param array $errors
     * @return array
     */
    public function putResource($apiKey, $resource, $resourceId, $data, $errors = array())
    {
        $resourceUrl = $this->_getApiUrl($resource) . '/' . $resourceId;
        $client = $this->_getClient($apiKey, $resourceUrl, Zend_Http_Client::PUT);
        $client->setRawData(json_encode($data), 'application/json');
        return $this->_getRequest($client, $errors);
    }

    /**
     * Perform a DELETE request
     *
     * @param string $apiKey
     * @param string $resource
     * @param array $errors
     * @return array
     */
    public function deleteResource($apiKey, $resource, $resourceId, $errors = array())
    {
        $resourceUrl = $this->_getApiUrl($resource) . '/' . $resourceId;
        $client = $this->_getClient($apiKey, $resourceUrl, Zend_Http_Client::DELETE);
        return $this->_getRequest($client, $errors);
    }

    /**
     * Get HTTP Client
     *
     * @param string $apiKey
     * @param string $url
     * @return Zend_Http_Client $response
     */
    private function _getClient($apiKey, $url, $method = Zend_Http_Client::GET)
    {
        $client = new Zend_Http_Client($url, array('timeout' => 30));
        $client->setMethod($method);
        $client->setHeaders('Authorization', 'Bearer ' . $apiKey);

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

        if ($errors[$statusCode]) {
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
