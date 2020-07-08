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
 * SmartCalcs Model
 * Performs sales tax calculations at checkout
 */
class Taxjar_SalesTax_Model_Smartcalcs
{
    protected $_response;
    protected $_logger;

    public function __construct($params = array())
    {
        $this->_logger = Mage::getModel('taxjar/logger')->setFilename('calculations.log');
        $this->initTaxForOrder($params['address']);
    }

    /**
     * Tax calculation for order
     *
     * @param  Mage_Sales_Model_Quote_Address $address
     * @return void
     */
    public function initTaxForOrder($address)
    {
        $storeId = $address->getQuote()->getStore()->getId();
        $apiKey = preg_replace('/\s+/', '', Mage::getStoreConfig('tax/taxjar/apikey'));

        if (!$apiKey) {
            return;
        }

        if (!$address->getPostcode()) {
            return;
        }

        if (!$this->_hasNexus($address->getRegionCode(), $address->getCountry())) {
            return;
        }

        if (!count($address->getAllNonNominalItems())) {
            return;
        }

        if ($this->_isCustomerExempt($address)) {
            return;
        }

        $fromAddress = array(
            'from_country' => Mage::getStoreConfig('shipping/origin/country_id', $storeId),
            'from_zip' => Mage::getStoreConfig('shipping/origin/postcode', $storeId),
            'from_state' => Mage::getModel('directory/region')->load(Mage::getStoreConfig('shipping/origin/region_id', $storeId))->getCode(),
            'from_city' => Mage::getStoreConfig('shipping/origin/city', $storeId),
            'from_street' => Mage::getStoreConfig('shipping/origin/street_line1', $storeId),
        );

        $toAddress = array(
            'to_country' => $address->getCountry(),
            'to_zip' => $address->getPostcode(),
            'to_state' => $address->getRegionCode(),
            'to_city' => $address->getCity(),
            'to_street' => $address->getStreet(1)
        );

        $shipping = (float) $address->getShippingAmount();
        $shippingDiscount = (float) $address->getShippingDiscountAmount();

        $order = array_merge($fromAddress, $toAddress, array(
            'shipping' => $shipping - abs($shippingDiscount),
            'line_items' => $this->_getLineItems($address),
            'nexus_addresses' => $this->_getNexusAddresses(),
            'plugin' => 'magento'
        ));

        if ($this->_orderChanged($order)) {
            $client = new Zend_Http_Client('https://api.taxjar.com/v2/magento/taxes');
            $client->setConfig(array(
                'useragent' => Mage::helper('taxjar')->getUserAgent(),
                'referer' => Mage::getBaseUrl()
            ));
            $client->setHeaders(array(
                'Authorization' => 'Bearer ' . $apiKey,
                'Referer' => Mage::getBaseUrl()
            ));
            $client->setRawData(json_encode($order), 'application/json');

            $this->_logger->log('Calculating sales tax: ' . json_encode($order), 'post');

            $this->_setSessionData('order', json_encode($order));

            try {
                $response = $client->request('POST');
                $this->_response = $response;
                $this->_setSessionData('response', $response);

                if (200 == $response->getStatus()) {
                    $this->_logger->log('Successful API response: ' . $response->getBody(), 'success');
                } else {
                    $errorResponse = json_decode($response->getBody());
                    $this->_logger->log($errorResponse->status . ' ' . $errorResponse->error . ' - ' . $errorResponse->detail, 'error');
                }

            } catch (Zend_Http_Client_Exception $e) {
                // Catch API timeouts and network issues
                $this->_logger->log('API timeout or network issue between your store and TaxJar, please try again later.', 'error');
                $this->_response = null;
                $this->_unsetSessionData('response');
            }
        } else {
            $sessionResponse = $this->_getSessionData('response');

            if (isset($sessionResponse)) {
                $this->_response = $sessionResponse;
            }
        }

        return $this;
    }

    /**
     * Get the SmartCalcs API response
     *
     * @return array
     */
    public function getResponse()
    {
        if ($this->_response) {
            return array(
                'body' => json_decode($this->_response->getBody(), true),
                'status' => $this->_response->getStatus(),
            );
        } else {
            return array(
                'status' => 204,
            );
        }
    }

    /**
     * Get a specific line item breakdown from a SmartCalcs API response
     * Also builds a combined rate based on returned sales tax rates
     *
     * @return array
     */
    public function getResponseLineItem($id)
    {
        if ($this->_response) {
            $responseBody = json_decode($this->_response->getBody(), true);

            if (isset($responseBody['tax']['breakdown']['line_items'])) {
                $lineItems = $responseBody['tax']['breakdown']['line_items'];
                $matchedKey = array_search($id, Mage::helper('taxjar')->array_column($lineItems, 'id'));

                if (isset($lineItems[$matchedKey]) && $matchedKey !== false) {
                    return $lineItems[$matchedKey];
                }
            }
        }
    }

    /**
     * Verify if nexus is triggered for location
     *
     * @param  string $regionCode
     * @param  string $country
     * @return bool
     */
    private function _hasNexus($regionCode, $country)
    {
        $nexusCollection = Mage::getModel('taxjar/tax_nexus')->getCollection();

        if ($country == 'US') {
            $nexusInRegion = $nexusCollection->addFieldToFilter('region_code', $regionCode);

            if ($nexusInRegion->getSize()) {
                return true;
            }
        } else {
            $nexusInCountry = $nexusCollection->addFieldToFilter('country_id', $country);

            if ($nexusInCountry->getSize()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verify if customer is exempt from sales tax
     *
     * @param  object $address
     * @return bool
     */
    private function _isCustomerExempt($address)
    {
        $customerTaxClass = Mage::getModel('tax/class')->load($address->getQuote()->getCustomerTaxClassId());
        $customerTaxCode = $customerTaxClass->getTjSalestaxCode();

        if ($customerTaxCode == '99999') {
            return true;
        }

        return false;
    }

    /**
     * Get order line items
     *
     * @param  array $address
     * @return array
     */
    private function _getLineItems($address)
    {
        $lineItems = array();
        $items = $address->getAllNonNominalItems();

        if (count($items) > 0) {
            $parentQuantities = array();

            foreach ($items as $itemIndex => $item) {
                $id = $item->getId() ? $item->getId() : $itemIndex;
                $parentId = $item->getParentItemId();
                $quantity = $item->getQty();
                $unitPrice = (float) $item->getPrice();
                $discount = (float) $item->getDiscountAmount();
                $taxCode = '';

                if ($item->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
                    $parentQuantities[$id] = $quantity;

                    if ($item->getProduct()->getPriceType() == Mage_Bundle_Model_Product_Price::PRICE_TYPE_DYNAMIC) {
                        continue;
                    }
                }

                if (isset($parentQuantities[$parentId])) {
                    $quantity *= $parentQuantities[$parentId];
                }

                if ($item->getProduct()->getTaxClassId()) {
                    $taxClass = Mage::getModel('tax/class')->load($item->getProduct()->getTaxClassId());
                    $taxCode = $taxClass->getTjSalestaxCode();
                }

                if (Mage::getEdition() == 'Enterprise') {
                    if ($item->getProductType() == Enterprise_GiftCard_Model_Catalog_Product_Type_Giftcard::TYPE_GIFTCARD) {
                        $giftTaxClassId = Mage::getStoreConfig('tax/classes/wrapping_tax_class');
                        $giftTaxClass = Mage::getModel('tax/class')->load($giftTaxClassId);
                        $giftTaxClassCode = $giftTaxClass->getTjSalestaxCode();

                        if ($giftTaxClassCode) {
                            $taxCode = $giftTaxClassCode;
                        } else {
                            $taxCode = '14111803A0001';
                        }
                    }
                }

                if ($unitPrice) {
                    array_push($lineItems, array(
                        'id' => $id,
                        'quantity' => $quantity,
                        'product_tax_code' => $taxCode,
                        'unit_price' => $unitPrice,
                        'discount' => $discount,
                    ));
                }
            }
        }

        return $lineItems;
    }

    /**
     * Get nexus addresses for `nexus_addresses` param
     *
     * @return array
     */
    private function _getNexusAddresses()
    {
        $nexusAddresses = Mage::getModel('taxjar/tax_nexus')->getCollection();
        $addresses = array();

        foreach($nexusAddresses as $nexusAddress) {
            $addresses[] = array(
                'id' => $nexusAddress->getId(),
                'country' => $nexusAddress->getCountryId(),
                'zip' => $nexusAddress->getPostcode(),
                'state' => $nexusAddress->getRegionCode(),
                'city' => $nexusAddress->getCity(),
                'street' => $nexusAddress->getStreet()
            );
        }

        return $addresses;
    }

    /**
     * Verify if the order changed compared to session
     *
     * @param  array $currentOrder
     * @return bool
     */
    private function _orderChanged($currentOrder)
    {
        $sessionOrder = json_decode($this->_getSessionData('order'), true);

        if ($sessionOrder) {
            return $currentOrder != $sessionOrder;
        } else {
            return true;
        }
    }

    /**
     * Get prefixed session data from checkout/session
     *
     * @param  string $key
     * @return object
     */
    private function _getSessionData($key)
    {
        return Mage::getModel('checkout/session')->getData('taxjar_salestax_' . $key);
    }

    /**
     * Set prefixed session data in checkout/session
     *
     * @param  string $key
     * @param  string $val
     * @return object
     */
    private function _setSessionData($key, $val)
    {
        return Mage::getModel('checkout/session')->setData('taxjar_salestax_' . $key, $val);
    }

    /**
     * Unset prefixed session data in checkout/session
     *
     * @param  string $key
     * @return object
     */
    private function _unsetSessionData($key)
    {
        return Mage::getModel('checkout/session')->unsetData('taxjar_salestax_' . $key);
    }
}
