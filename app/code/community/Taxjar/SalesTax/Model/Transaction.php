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
 * Transaction Model
 * Sync transactions with TaxJar
 */
class Taxjar_SalesTax_Model_Transaction
{
    protected $client;
    protected $logger;

    public function __construct()
    {
        $this->client = Mage::getSingleton('taxjar/client');
        $this->logger = Mage::getSingleton('taxjar/logger')->setFilename('transactions.log');
    }

    /**
     * Check if a transaction is synced
     *
     * @param string $syncDate
     * @return array
     */
    protected function isSynced($syncDate)
    {
        if (empty($syncDate) || $syncDate == '0000-00-00 00:00:00') {
            return false;
        }
        return true;
    }

    /**
     * Build `from` address for SmartCalcs request
     *
     * @param int $storeId
     * @return array
     */
    protected function buildFromAddress($storeId)
    {
        $fromCountry = Mage::getStoreConfig('shipping/origin/country_id', $storeId);
        $fromPostcode = Mage::getStoreConfig('shipping/origin/postcode', $storeId);
        $fromState = Mage::getModel('directory/region')->load(Mage::getStoreConfig('shipping/origin/region_id', $storeId))->getCode();
        $fromCity = Mage::getStoreConfig('shipping/origin/city', $storeId);
        $fromStreet = Mage::getStoreConfig('shipping/origin/street_line1', $storeId) . Mage::getStoreConfig('shipping/origin/street_line2', $storeId);

        return array(
            'from_country' => $fromCountry,
            'from_zip' => $fromPostcode,
            'from_state' => $fromState,
            'from_city' => $fromCity,
            'from_street' => $fromStreet
        );
    }

    /**
     * Build `to` address for SmartCalcs request
     *
     * @param $order
     * @return array
     */
    protected function buildToAddress($order) {
        if ($order->getIsVirtual()) {
            $address = $order->getBillingAddress();
        } else {
            $address = $order->getShippingAddress();
        }

        $toAddress = array(
            'to_country' => $address->getCountryId(),
            'to_zip' => $address->getPostcode(),
            'to_state' => $address->getRegionCode(),
            'to_city' => $address->getCity(),
            'to_street' => $address->getData('street')
        );

        return $toAddress;
    }

    /**
     * Build line items for SmartCalcs request
     *
     * @param $order
     * @param array $items
     * @param string $type
     * @return array
     */
    protected function buildLineItems($order, $items, $type = 'order') {
        $lineItems = array();
        $parentDiscounts = $this->getParentAmounts('discount', $items, $type);
        $parentTaxes = $this->getParentAmounts('tax', $items, $type);

        foreach ($items as $item) {
            $itemType = $item->getProductType();
            $parentItem = $item->getParentItem();

            if (($itemType == 'simple' || $itemType == 'virtual') && $item->getParentItemId()) {
                if (!empty($parentItem) && $parentItem->getProductType() == 'bundle') {
                    if ($parentItem->getProduct()->getPriceType() == Mage_Bundle_Model_Product_Price::PRICE_TYPE_FIXED) {
                        continue;  // Skip children of fixed price bundles
                    }
                } else {
                    continue;  // Skip children of configurable products
                }
            }

            if ($itemType == 'bundle' && $item->getProduct()->getPriceType() != Mage_Bundle_Model_Product_Price::PRICE_TYPE_FIXED) {
                continue;  // Skip dynamic bundle parent item
            }

            if (method_exists($item, 'getOrderItem') && $item->getOrderItem()->getParentItemId()) {
                continue;
            }

            $itemId = $item->getOrderItemId() ? $item->getOrderItemId() : $item->getItemId();
            $product = Mage::getModel('catalog/product')->load($item->getProductId());
            $discount = (float) $item->getDiscountAmount();
            $tax = (float) $item->getTaxAmount();
            $taxCode = '';

            if (isset($parentDiscounts[$itemId]) && $parentDiscounts[$itemId] > 0) {
                $discount = $parentDiscounts[$itemId] ?: $discount;
            }

            if (isset($parentTaxes[$itemId]) && $parentTaxes[$itemId] > 0) {
                $tax = $parentTaxes[$itemId] ?: $tax;
            }

            if ($product->getTaxClassId()) {
                $taxClass = Mage::getModel('tax/class')->load($product->getTaxClassId());
                $taxCode = $taxClass->getTjSalestaxCode();
            }

            $lineItem = array(
                'id' => $itemId,
                'quantity' => (int) $item->getQtyOrdered(),
                'product_identifier' => $item->getSku(),
                'description' => $item->getName(),
                'product_tax_code' => $taxCode,
                'unit_price' => (float) $item->getPrice(),
                'discount' => $discount,
                'sales_tax' => $tax
            );

            if ($type == 'refund' && method_exists($item, 'getOrderItem')) {
                $orderItem = $item->getOrderItem();
                $lineItem['quantity'] = (int) $orderItem->getQtyRefunded();
                $lineItem['unit_price'] = $orderItem->getAmountRefunded() / $lineItem['quantity'];
            }

            $lineItems['line_items'][] = $lineItem;
        }

        return $lineItems;
    }

    /**
     * Get parent amounts (discounts, tax, etc) for bundle products
     *
     * @param string $attr
     * @param array $items
     * @param string $type
     * @return array
     */
    protected function getParentAmounts($attr, $items, $type = 'order') {
        $parentAmounts = array();

        foreach ($items as $item) {
            $parentItemId = null;

            if ($item->getParentItemId()) {
                $parentItemId = $item->getParentItemId();
            }

            if (method_exists($item, 'getOrderItem') && $item->getOrderItem()->getParentItemId()) {
                $parentItemId = $item->getOrderItem()->getParentItemId();
            }

            if ($parentItemId) {
                switch ($attr) {
                    case 'discount':
                        $amount = (float) (($type == 'order') ? $item->getDiscountAmount() : $item->getDiscountRefunded());
                        break;
                    case 'tax':
                        $amount = (float) (($type == 'order') ? $item->getTaxAmount() : $item->getTaxRefunded());
                        break;
                }

                if (isset($parentAmounts[$parentItemId])) {
                    $parentAmounts[$parentItemId] += $amount;
                } else {
                    $parentAmounts[$parentItemId] = $amount;
                }
            }
        }

        return $parentAmounts;
    }

    /**
     * Return custom errors for transaction endpoints
     *
     * @return array
     */
    protected function transactionErrors()
    {
        return array(
            '400' => Mage::helper('taxjar')->__('Bad Request – Your request format is bad.'),
            '403' => Mage::helper('taxjar')->__('Forbidden – The resource requested is not authorized for use.'),
            '404' => Mage::helper('taxjar')->__('Not Found – The specified resource could not be found.'),
            '405' => Mage::helper('taxjar')->__('Method Not Allowed – You tried to access a resource with an invalid method.'),
            '406' => Mage::helper('taxjar')->__('Not Acceptable – Your request is not acceptable.'),
            '410' => Mage::helper('taxjar')->__('Gone – The resource requested has been removed from our servers.'),
            '422' => Mage::helper('taxjar')->__('Unprocessable Entity – Your request could not be processed.'),
            '429' => Mage::helper('taxjar')->__('Too Many Requests – You’re requesting too many resources! Slow down!'),
            '500' => Mage::helper('taxjar')->__('Internal Server Error – We had a problem with our server. Try again later.'),
            '503' => Mage::helper('taxjar')->__('Service Unavailable – We’re temporarily offline for maintenance. Try again later.')
        );
    }

    /**
     * Get the default provider parameter
     *
     * @param Mage_Sales_Model_Order $order
     * @return mixed|string
     */
    protected function getProvider($order) {
        $provider = Mage::getStoreConfig('tax/taxjar/provider');

        if (is_null($provider)) {
            $provider = 'magento';
            /** @var Mage_Core_Model_Resource $connection */
            $connection = Mage::getSingleton('core/resource');
            $orderTable = $connection->getTableName('sales/order');
            $creditMemoTable = $connection->getTableName('sales/creditmemo');

            if ($connection->getConnection('core_read')->tableColumnExists($orderTable, 'tj_salestax_sync_date') == true) {
                $orders = Mage::getModel('sales/order')->getCollection();
                $orders->addFieldToFilter('tj_salestax_sync_date', array('notnull' => true));

                if (count($orders) > 0) {
                    $provider = 'api';
                }
            }

            if ($connection->getConnection('core_read')->tableColumnExists($creditMemoTable, 'tj_salestax_sync_date') == true) {
                $creditMemos = Mage::getModel('sales/order_creditmemo')->getCollection();
                $creditMemos->addFieldToFilter('tj_salestax_sync_date', array('notnull' => true));

                if (count($creditMemos) > 0) {
                    $provider = 'api';
                }
            }

            Mage::getConfig()->saveConfig('tax/taxjar/provider', $provider);
            Mage::app()->cleanCache();
        }

        if (class_exists('Ess_M2ePro_Model_Order')) {
            try {
                /** @var Ess_M2ePro_Model_Order $m2eOrder */
                $m2eOrder = Mage::getModel('M2ePro/Order')->load($order->getId(), 'magento_order_id');

                if (in_array($m2eOrder->getComponentMode(), array('amazon', 'ebay', 'walmart'))) {
                    $provider = $m2eOrder->getComponentMode();
                }
            } catch (Ess_M2ePro_Model_Exception $e) {
                // noop: M2e order does not exist or component mode can't be loaded
            }
        }

        return $provider;
    }
}
