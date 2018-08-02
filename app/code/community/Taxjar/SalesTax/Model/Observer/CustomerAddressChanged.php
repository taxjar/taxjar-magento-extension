<?php
class Taxjar_SalesTax_Model_Observer_CustomerAddressChanged
{
    public function afterUpdate(Varien_Event_Observer $observer)
    {
        $customer = $observer->getCustomerAddress()->getCustomer();
        Mage::getModel('taxjar/client_customerSync', $customer)->syncUpdates();
    }
}
