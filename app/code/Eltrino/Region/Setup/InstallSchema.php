<?php
/**
 * Remove or Change Displayed States and Regions
 *
 * LICENSE
 *
 * This source file is subject to the Eltrino LLC EULA
 * that is bundled with this package in the file LICENSE_EULA.txt.
 * It is also available through the world-wide-web at this URL:
 * http://eltrino.com/license-eula.txt
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@eltrino.com so we can send you a copy immediately.
 *
 * @category    Eltrino
 * @package     Eltrino_Region
 * @copyright   Copyright (c) 2015 Eltrino LLC. (http://eltrino.com)
 * @license     http://eltrino.com/license-eula.txt  Eltrino LLC EULA
 */
namespace Eltrino\Region\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

/**
 * Class InstallSchema
 * @package Eltrino\Region\Setup
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws \Zend_Db_Exception
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $eltrinoDisabledRegion = $setup->getTable('eltrino_region_entity');

        $setup->run("DROP TABLE IF EXISTS {$eltrinoDisabledRegion}");

        $table = $setup->getConnection()->newTable(
            $setup->getTable($eltrinoDisabledRegion)
        )->addColumn(
            'entity_id',
            Table::TYPE_INTEGER,
            null,
            [
                'auto_increment' => true,
                'primary'  => true,
                'unsigned' => true,
                'nullable' => false
            ],
            'Entity Id'
        )->addColumn(
            'country_id',
            Table::TYPE_TEXT,
            2,
            [
                'nullable' => false
            ],
            'Country Id'
        )->addColumn(
            'region_id',
            Table::TYPE_INTEGER,
            null,
            [
                'unsigned' => true,
                'nullable' => false,
            ],
            'Region Id'
        );

        $setup->getConnection()->createTable($table);

        $setup->endSetup();

    }
}
