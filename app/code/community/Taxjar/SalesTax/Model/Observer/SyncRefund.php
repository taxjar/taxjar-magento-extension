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

class Taxjar_SalesTax_Model_Observer_SyncRefund
{
    /**
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function execute(Varien_Event_Observer $observer)
    {
        if (!Mage::registry('taxjar_sync_refund')) {
            Mage::register('taxjar_sync_refund', true);
        } else {
            return $this;
        }

        /** @var Mage_Sales_Model_Order_Creditmemo $creditmemo */
        $creditmemo = $observer->getCreditmemo();
        $order = $creditmemo->getOrder();
        $orderTransaction = Mage::getModel('taxjar/transaction_order');

        if ($orderTransaction->isSyncable($order)) {
            try {
                $refundTransaction = Mage::getModel('taxjar/transaction_refund');
                $refundTransaction->build($order, $creditmemo);
                $refundTransaction->push();

                if ($observer->getData('order_id')) {
                    Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('taxjar')->__('Credit memo successfully synced to TaxJar.'));
                }
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        } else {
            if ($observer->getData('order_id')) {  // update
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('taxjar')->__('This credit memo was not synced to TaxJar.'));
            }
        }

        return $this;
    }
}
