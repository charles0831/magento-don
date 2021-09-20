<?php

namespace Meetanshi\DistanceBasedShipping\Setup;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    public function install(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        try {
            $installer = $setup;
            $installer->startSetup();
            if (!$installer->tableExists('mt_distance_shipping_warehouse')) {
                $table = $installer->getConnection()
                    ->newTable($installer->getTable('mt_distance_shipping_warehouse'))
                    ->addColumn(
                        'id',
                        Table::TYPE_INTEGER,
                        null,
                        [
                            'identity' => true,
                            'nullable' => false,
                            'primary' => true,
                            'unsigned' => true,
                        ],
                        'ID'
                    )->addColumn(
                        'longitude',
                        Table::TYPE_TEXT,
                        20,
                        ['nullable' => false],
                        'Longitude'
                    )->addColumn(
                        'latitude',
                        Table::TYPE_TEXT,
                        20,
                        ['nullable' => false],
                        'Latitude'
                    )->addColumn(
                        'street',
                        Table::TYPE_TEXT,
                        255,
                        ['nullable' => false],
                        'Street'
                    )->addColumn(
                        'city',
                        Table::TYPE_TEXT,
                        20,
                        ['nullable' => false],
                        'Street'
                    )->addColumn(
                        'state',
                        Table::TYPE_TEXT,
                        20,
                        ['nullable' => false],
                        'Street'
                    )->addColumn(
                        'country',
                        Table::TYPE_TEXT,
                        20,
                        ['nullable' => false],
                        'Street'
                    )->addColumn(
                        'zipcode',
                        Table::TYPE_TEXT,
                        10,
                        ['nullable' => false],
                        'Street'
                    )->addColumn(
                        'status',
                        Table::TYPE_BOOLEAN,
                        null,
                        ['nullable' => false],
                        'Status'
                    )->addColumn(
                        'created_at',
                        Table::TYPE_TIMESTAMP,
                        null,
                        ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
                        'Creation Time'
                    )->addColumn(
                        'updated_at',
                        Table::TYPE_TIMESTAMP,
                        null,
                        ['nullable' => false, 'default' => Table::TIMESTAMP_INIT_UPDATE],
                        'Update Time'
                    );
                $installer->getConnection()->createTable($table);

                $installer->getConnection()->addIndex(
                    $installer->getTable('mt_distance_shipping_warehouse'),
                    $setup->getIdxName(
                        $installer->getTable('mt_distance_shipping_warehouse'),
                        ['longitude', 'latitude','street','city','state','country','zipcode'],
                        AdapterInterface::INDEX_TYPE_FULLTEXT
                    ),
                    ['longitude', 'latitude','street','city','state','country','zipcode'],
                    AdapterInterface::INDEX_TYPE_FULLTEXT
                );
            }
            $installer->getConnection()->addColumn(
                $installer->getTable('quote'),
                'pickup_from_id',
                [
                    'type' => Table::TYPE_INTEGER,
                    'length' => 10,
                    'nullable' => true,
                    'unsigned' => true,
                    'comment' => 'Pickup From Id'
                ]
            );
            $installer->endSetup();
        } catch (\Exception $ex) {
            ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->info($ex->getMessage());
        }
    }
}
