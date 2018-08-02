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

class Taxjar_SalesTax_Model_Observer_ClassTaxChanged
{
    public function execute(Varien_Event_Observer $observer)
    {
        $isExempt = Mage::app()->getRequest()->getParam('tj_salestax_code');
        if('99999' != $isExempt)
        {
            Mage::app()->getRequest()->setPost('tj_salestax_exempt_type', Taxjar_SalesTax_Helper_Data::DEFAULT_EXEMPTION_TYPE);
        }
    }
}
