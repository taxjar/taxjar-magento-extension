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
                    'values' => Mage::getModel('taxjar/categories')->toOptionArray()
                )
            );
        }

        if ($connected && $this->getClassType() == 'CUSTOMER') {
            $exemptCheck = $fieldset->addField(
                'tj_salestax_code', 'select', array(
                    'name'  => 'tj_salestax_code',
                    'label' => Mage::helper('taxjar')->__('TaxJar Exempt'),
                    'note' => Mage::helper('taxjar')->__('Fully exempts customer groups associated with this tax class from sales tax calculations through SmartCalcs. This setting does not apply to product exemptions or backup rates.'),
                    'value' => $currentClass->getTjSalestaxCode(),
                    'values' => array(
                        '99999' => 'Yes',
                        '' => 'No'
                    )
                )
            );

            $exemptType = $fieldset->addField(
                'tj_salestax_exempt_type', 'select', array(
                    'name'  => 'tj_salestax_exempt_type',
                    'label' => Mage::helper('taxjar')->__('TaxJar Exemption Type'),
                    'note' => Mage::helper('taxjar')->__('If exempt from TaxJar, select an exemption type for this customer tax class.'),
                    'value' => $currentClass->getTjSalestaxExemptType(),
                    'values' => array(
                        'wholesale' => 'Wholesale',
                        'government' => 'Government',
                        'other' => 'Other'
                    )
                )
            );

            $this->setChild('form_after', $this->getLayout()->createBlock('adminhtml/widget_form_element_dependence')
                ->addFieldMap($exemptCheck->getHtmlId(), $exemptCheck->getName())
                ->addFieldMap($exemptType->getHtmlId(), $exemptType->getName())
                ->addFieldDependence(
                    $exemptType->getName(),
                    $exemptCheck->getName(),
                    '99999'
                )
            );
        }

        return $this;
    }
}
