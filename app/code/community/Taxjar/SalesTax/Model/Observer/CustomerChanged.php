<?php
/**
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

class Taxjar_SalesTax_Model_Observer_CustomerChanged
{
    protected $_logger;
    protected $_response;

    public function __construct($params = array())
    {
        $this->_logger = Mage::getModel('taxjar/logger')->setFilename('customers.log');
    }


    public function afterSave(Varien_Event_Observer $observer)
    {
        $customer = $observer->getCustomer();
        Mage::getSingleton('taxjar/client_customerSync')->syncUpdates($customer);
    }

    public function afterDelete(Varien_Event_Observer $observer)
    {
        $customer = $observer->getCustomer();
        Mage::getSingleton('taxjar/client_customerSync')->syncDelete($customer);
    }
}
