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

class Taxjar_SalesTax_Block_Adminhtml_Tax_Class_Edit_Form extends Mage_Adminhtml_Block_Tax_Class_Edit_Form
{
    protected function _prepareForm()
    {
        parent::_prepareForm();

        $connected = Mage::getStoreConfig('tax/taxjar/connected');
        $fieldset = $this->getForm()->getElement('base_fieldset');
        $currentClass = Mage::registry('tax_class');

        if ($connected && $this->getClassType() == 'PRODUCT') {
            $fieldset->addField(
                'tj_salestax_code', 'select', array(
                    'name'  => 'tj_salestax_code',
                    'label' => Mage::helper('taxjar')->__('TaxJar Category'),
                    'value' => $currentClass->getTjSalestaxCode(),
                    'values' => Mage::getModel('taxjar/tax_category')->getCollection()->toOptionArray()
                )
            );
        }

        if ($connected && $this->getClassType() == 'CUSTOMER') {
            $fieldset->addField(
                'tj_salestax_code', 'select', array(
                    'name'  => 'tj_salestax_code',
                    'label' => Mage::helper('taxjar')->__('TaxJar Exempt'),
                    'note' => Mage::helper('taxjar')->__('Fully exempts customer groups associated with this tax class from sales tax calculations through the TaxJar API. This setting does not apply to product exemptions or backup rates.'),
                    'value' => $currentClass->getTjSalestaxCode(),
                    'values' => array(
                        '99999' => 'Yes',
                        '' => 'No'
                    )
                )
            );
        }

        return $this;
    }
}
