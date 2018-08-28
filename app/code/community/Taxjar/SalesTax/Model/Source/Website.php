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
 * @copyright  Copyright (c) 2018 TaxJar. TaxJar is a trademark of TPS Unlimited, Inc. (http://www.taxjar.com)
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

/**
 * Source model for website based shipping origin
 *
 * @author Taxjar (support@taxjar.com)
 */
class Taxjar_SalesTax_Model_Source_Website
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $output = [];
        $output[] = ['label' => '', 'value' => ''];

        $websites = Mage::app()->getWebsites();

        /** @var Mage_Core_Model_Website $website */
        foreach($websites as $website) {
            $output[] = ['value' => $website->getId(), 'label' => $website->getName()];
        }

        return $output;
    }
}
