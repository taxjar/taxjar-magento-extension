<?php
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
        Mage::getModel('taxjar/client_customerSync', $customer)->syncUpdates();
    }

    public function afterDelete(Varien_Event_Observer $observer)
    {
        $customer = $observer->getCustomer();
        Mage::getModel('taxjar/client_customerSync', $customer)->syncDelete();
    }
}
