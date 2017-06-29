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
 * Tax totals calculation model
 */
class Taxjar_SalesTax_Model_Sales_Total_Quote_Tax extends Mage_Tax_Model_Sales_Total_Quote_Tax
{
    /**
     * Collect tax totals for quote address
     *
     * @param   Mage_Sales_Model_Quote_Address $address
     * @return  Mage_Tax_Model_Sales_Total_Quote
     */
    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        $this->_roundingDeltas = array();
        $this->_baseRoundingDeltas = array();
        $this->_hiddenTaxes = array();
        $this->_setAddress($address);
        $this->_setAmount(0);
        $this->_setBaseAmount(0);
        $address->setShippingTaxAmount(0);
        $address->setBaseShippingTaxAmount(0);

        $smartCalcs = $this->_getSmartCalcs($address);
        $smartCalcsResponse = $smartCalcs->getResponse();

        if (isset($smartCalcsResponse['body']['tax']) && $smartCalcsResponse['status'] == 200) {
            $store = $address->getQuote()->getStore();
            $items = $this->_getAddressItems($address);
            $rates = $smartCalcsResponse['body']['tax'];

            if (isset($rates['breakdown']['shipping']['tax_collectable'])) {
                $shippingTaxAmount = $rates['breakdown']['shipping']['tax_collectable'];
            } else {
                $shippingTaxAmount = 0;
            }

            $this->_addAmount($store->convertPrice($shippingTaxAmount));
            $this->_addBaseAmount($shippingTaxAmount);

            $address->setShippingTaxAmount($store->convertPrice($shippingTaxAmount));
            $address->setBaseShippingTaxAmount($shippingTaxAmount);

            if (count($items) > 0) {
                foreach ($items as $item) {
                    $itemTax = $smartCalcs->getResponseLineItem($item->getId());

                    if ($this->_getTaxConfig('weee/enable', $store->getId())) {
                        $fptAmount = $item->getWeeeTaxAppliedAmount() * $item->getQty();
                        $this->_addAmount($store->convertPrice($fptAmount));
                        $this->_addBaseAmount($fptAmount);
                    }

                    if (isset($itemTax)) {
                        $this->_addAmount($store->convertPrice($itemTax['tax_collectable']));
                        $this->_addBaseAmount($itemTax['tax_collectable']);
                        $item->setTaxPercent($itemTax['combined_tax_rate'] * 100);
                        $item->setTaxAmount($store->convertPrice($itemTax['tax_collectable']));
                        $item->setBaseTaxAmount($itemTax['tax_collectable']);
                    }
                }
            }
        } else {
            return parent::collect($address);
        }

        return $this;
    }

    /**
     * Get SmartCalcs model
     *
     * @param   Mage_Sales_Model_Quote_Address $address
     * @return  Taxjar_SalesTax_Model_SmartCalcs
     */
    protected function _getSmartCalcs(Mage_Sales_Model_Quote_Address $address)
    {
        return Mage::getModel(
            'taxjar/smartcalcs',
            array('address' => $address)
        );
    }

    /**
     * Get Magento tax configuration field by store ID
     *
     * @param string $key
     * @param integer $storeId
     * @return string|bool
     */
    protected function _getTaxConfig($key, $storeId)
    {
        return Mage::getStoreConfig('tax/' . $key, $storeId);
    }
}
