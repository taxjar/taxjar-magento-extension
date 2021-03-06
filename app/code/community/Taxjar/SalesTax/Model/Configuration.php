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
 * Configuration Model
 * Set default config values in Magento
 */
class Taxjar_SalesTax_Model_Configuration
{
    /**
     * Sets shipping taxability in Magento
     *
     * @param string $configJson
     * @return void
     */
    public function setShippingTaxability($configJson)
    {
        $taxClass = 0;

        if ($configJson['freight_taxable']) {
            $taxClass = 4;
        }

        $this->_setConfig('tax/classes/shipping_tax_class', $taxClass);
    }

    /**
     * Sets tax basis in Magento
     *
     * @param string $configJson
     * @return void
     */
    public function setTaxBasis($configJson)
    {
        $basis = 'shipping';

        if ($configJson['tax_source'] === 'origin') {
            $basis = 'origin';
        }

        $this->_setConfig('tax/calculation/based_on', $basis);
    }

    /**
     * Set display settings for tax in Magento
     *
     * @param void
     * @return void
     */
    public function setDisplaySettings()
    {
        $settings = array(
            'tax/display/type',
            'tax/display/shipping',
            'tax/cart_display/price',
            'tax/cart_display/subtotal',
            'tax/cart_display/shipping'
        );

        foreach ($settings as $setting) {
            $this->_setConfig($setting, 1);
        }
    }

    /**
     * Store config
     *
     * @param string $path
     * @param string $value
     * @return void
     */
    private function _setConfig($path, $value)
    {
        Mage::getConfig()->saveConfig($path, $value, 'default', 0);
    }
}
