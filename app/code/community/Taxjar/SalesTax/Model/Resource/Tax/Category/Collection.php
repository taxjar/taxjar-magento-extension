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

class Taxjar_SalesTax_Model_Resource_Tax_Category_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    /**
     * Resource initialization
     */
    public function _construct()
    {
        $this->_init('taxjar/tax_category');
    }

    /**
     * Retrieve option array
     *
     * @return array
     */
    public function toOptionArray()
    {
        $labels = array();
        $options = array(
            array(
                'label' => 'Fully Taxable',
                'value' => ''
            )
        );

        foreach ($this as $category) {
            $options[] = array(
                'label' => $category->getName() . ' (' . $category->getProductTaxCode() . ')' .
                    ($category->getPlusOnly() ? ' *(PLUS ONLY)*' : ''),
                'value' => $category->getProductTaxCode()
            );
        }

        foreach($options as $option) {
            $labels[] = $option['label'];
        }

        array_multisort($labels, SORT_ASC, $options);

        return $options;
    }
}
