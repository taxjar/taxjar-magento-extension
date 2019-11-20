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

/** @var Mage_Core_Model_Resource_Setup $installer */
$installer = $this;
$installer->startSetup();
$connection = $installer->getConnection();

try {
    $table = $installer->getConnection()
        ->newTable($installer->getTable('taxjar/tax_category'))
        ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'identity' => true,
            'unsigned' => true,
            'nullable' => false,
            'primary' => true
        ), 'ID')
        ->addColumn('product_tax_code', Varien_Db_Ddl_Table::TYPE_TEXT, 32, array(
            'unsigned' => true,
        ), 'Product Tax Code')
        ->addColumn('name', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(), 'Name')
        ->addColumn('description', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(), 'Description')
        ->addColumn('plus_only', Varien_Db_Ddl_Table::TYPE_BOOLEAN, null, array(), 'Plus Only')
        ->addIndex($installer->getIdxName('taxjar/tax_category', array('product_tax_code')), 'product_tax_code')
        ->setComment('TaxJar Product Tax Codes');

    $installer->getConnection()->createTable($table);

} catch (Exception $e) {
    Mage::logException($e);
}

$installer->endSetup();
