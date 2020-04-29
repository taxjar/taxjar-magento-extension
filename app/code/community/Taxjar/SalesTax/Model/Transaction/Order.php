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
 * Order Transaction Model
 * Sync order transactions with TaxJar
 */
class Taxjar_SalesTax_Model_Transaction_Order extends Taxjar_SalesTax_Model_Transaction
{
    protected $originalOrder;
    protected $request;

    /**
     * Build an order transaction
     *
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    public function build($order) {
        $createdAt = new DateTime($order->getCreatedAt());
        $subtotal = (float) $order->getSubtotal();
        $shipping = (float) $order->getShippingAmount();
        $discount = (float) $order->getDiscountAmount();
        $shippingDiscount = (float) $order->getShippingDiscountAmount();
        $salesTax = (float) $order->getTaxAmount();

        $this->originalOrder = $order;

        $newOrder = array(
            'plugin' => 'magento',
            'transaction_id' => $order->getIncrementId(),
            'transaction_date' => $createdAt->format(DateTime::ISO8601),
            'amount' => $subtotal + $shipping - abs($discount),
            'shipping' => $shipping - abs($shippingDiscount),
            'sales_tax' => $salesTax,
            'provider' => $this->getProvider($order)
        );

        $this->request = array_merge(
            $newOrder,
            $this->buildFromAddress($order->getStoreId()),
            $this->buildToAddress($order),
            $this->buildLineItems($order, $order->getAllItems())
        );

        return $this->request;
    }

   /**
     * Push an order transaction to SmartCalcs
     *
     * @param string|null $forceMethod
     * @return void
     */
    public function push($forceMethod = null) {
        $orderUpdatedAt = $this->originalOrder->getUpdatedAt();
        $orderSyncedAt = $this->originalOrder->getTjSalestaxSyncDate();

        if (!$this->isSynced($orderSyncedAt)) {
            $method = 'POST';
        } else {
            if ($orderSyncedAt < $orderUpdatedAt) {
                $method = 'PUT';
            } else {
                $this->logger->log('Order #' . $this->request['transaction_id'] . ' not updated since last sync', 'skip');
                return;
            }
        }

        if ($forceMethod) {
            $method = $forceMethod;
        }

        try {
            $this->logger->log('Pushing order #' . $this->request['transaction_id'] . ': ' . json_encode($this->request), $method);

            if ($method == 'POST') {
                $response = $this->client->postResource('orders', $this->request, $this->transactionErrors());
                $this->logger->log('Order #' . $this->request['transaction_id'] . ' created in TaxJar: ' . json_encode($response), 'api');
                $this->originalOrder->addStatusHistoryComment('Order created in TaxJar with $' . $this->request['sales_tax'] . ' sales tax collected.')->save();
            } else {
                $response = $this->client->putResource('orders', $this->request['transaction_id'], $this->request, $this->transactionErrors());
                $this->logger->log('Order #' . $this->request['transaction_id'] . ' updated in TaxJar: ' . json_encode($response), 'api');
            }

            $this->originalOrder->setTjSalestaxSyncDate(gmdate('Y-m-d H:i:s'))->save();
        } catch (Exception $e) {
            $errorStatusCode = $e->getCode();
            $this->logger->log($errorStatusCode . ' Error: ' . $e->getMessage(), 'error');

            // Retry push for not found records using POST
            if (!$forceMethod && $method == 'PUT' && $errorStatusCode == 404) {
                $this->logger->log('Attempting to create order #' . $this->request['transaction_id'], 'retry');
                return $this->push('POST');
            }

            // Retry push for existing records using PUT
            if (!$forceMethod && $method == 'POST' && $errorStatusCode == 422) {
                $this->logger->log('Attempting to update order #' . $this->request['transaction_id'], 'retry');
                return $this->push('PUT');
            }
        }
    }

    /**
     * Determines if an order can be synced
     *
     * @param $order
     * @return bool
     */
    public function isSyncable($order) {
        $states = array('complete', 'closed');

        if (!in_array($order->getState(), $states)) {
            return false;
        }

        // USD currency orders for reporting only
        if ($order->getOrderCurrencyCode() != 'USD') {
            return false;
        }

        if ($order->getIsVirtual()) {
            $address = $order->getBillingAddress();
        } else {
            $address = $order->getShippingAddress();
        }

        // US orders for reporting only
        if ($address->getCountryId() != 'US') {
            return false;
        }

        return true;
    }
}
