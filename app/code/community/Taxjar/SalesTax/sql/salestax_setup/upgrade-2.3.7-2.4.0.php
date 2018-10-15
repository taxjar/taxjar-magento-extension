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

$installer = $this;
$installer->startSetup();

try {
    $table = $installer->getConnection()
        ->addColumn($installer->getTable('tax/tax_class'), 'tj_salestax_exempt_type', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
            'length'   => 255,
            'nullable' => false,
            'default'  => 'non_exempt',
            'after'    => 'tj_salestax_code',
            'comment'  => 'Sales Tax Exemption Type for Taxjar Sales Tax'
        ));

    $table = $installer->getConnection()
        ->addColumn($installer->getTable('customer/entity'), 'tj_salestax_sync_date', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
            'nullable' => true,
            'comment'  => 'Customer Last Sync Date To Tax Jar'
        ));



    $otherSetup = new Mage_Customer_Model_Resource_Setup('default_setup');

    $otherSetup->startSetup();
    $otherSetup->addAttribute('customer', 'tj_salestax_sync_date', array(
        'type' => 'static',
        'visible'=>false,
        'default' => false
    ));



} catch (Exception $e) {
    Mage::logException($e);
}

$installer->endSetup();
$otherSetup->endSetup();
