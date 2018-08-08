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

class Taxjar_SalesTax_Model_Observer_CustomerGroupChanged
{
    protected $originalTaxClass;

    public function beforeSave(Varien_Event_Observer $observer)
    {
        $this->originalTaxClass = $observer->getObject()->getOrigData('tax_class_id');
    }

    public function afterSaveCommit(Varien_Event_Observer $observer)
    {
        if ($this->originalTaxClass != $observer->getObject()->getTaxClassId()) {
            $customerGroup = $observer->getObject();

            // On change we need to loop through all the affected customers and sync/resync them
            // First verify that it's a change we care about

            $customers = Mage::getModel('customer/customer')->getCollection()->addFieldToFilter('group_id', $customerGroup->getId());

            // This process can take awhile
            @set_time_limit(0);
            @ignore_user_abort(true);
            foreach ($customers as $customer) {
                Mage::getModel('taxjar/client_customerSync', $customer)->syncUpdates();
            }
        }
    }

}
