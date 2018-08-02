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

class Taxjar_SalesTax_Model_Observer_TaxClassChanged
{
    public function preDispatch(Varien_Event_Observer $observer)
    {
        if ('CUSTOMER' == Mage::app()->getRequest()->getParam('class_type')) {
            $isExempt = Mage::app()->getRequest()->getParam('tj_salestax_code');
            if ('99999' != $isExempt) {
                Mage::app()->getRequest()->setPost('tj_salestax_exempt_type', Taxjar_SalesTax_Helper_Data::DEFAULT_EXEMPTION_TYPE);
            }
            Mage::register('original_tax_class', Mage::getModel('tax/class')->load(Mage::app()->getRequest()->getParam('class_id'))->getData());
        }
    }

    public function postDispatch(Varien_Event_Observer $observer)
    {
        if ('CUSTOMER' == Mage::app()->getRequest()->getParam('class_type')) {
            $classId = Mage::app()->getRequest()->getParam('class_id');
            $taxClass = Mage::getModel('tax/class')->load($classId);
            $originalData = Mage::registry('original_tax_class');
            Mage::unregister('original_tax_class'); // Remove from register just in case

            // On change we need to loop through all the affected customers and sync/resync them
            // First verify that it's a change we care about
            $customers = Mage::getModel('customer/customer')->getCollection()->addFieldToFilter('group_id', $taxClass->getId())->addAttributeToSelect('*');

            if ('' == $taxClass->getTjSalestaxCode() && '' == $originalData['tj_salestax_code']) {
                // * No -> No -- Don't sync
                return;
            } else if (('99999' == $taxClass->getTjSalestaxCode() && '99999' == $originalData['tj_salestax_code']) && ($taxClass->getTjSalestaxExemptType() == $originalData['tj_salestax_exempt_type'])) {
                // * Yes -> Yes -- Sync as exemption type
                return;
            }

            // This process can take awhile
            @set_time_limit(0);
            @ignore_user_abort(true);
            foreach ($customers as $customer) {
                Mage::getModel('taxjar/client_customerSync', $customer)->syncUpdates();
            }
        }
    }

}
