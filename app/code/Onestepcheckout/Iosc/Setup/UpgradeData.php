<?php
/**
 * OneStepCheckout
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to One Step Checkout AS software license.
 *
 * License is available through the world-wide-web at this URL:
 * https://www.onestepcheckout.com/LICENSE.txt
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to mail@onestepcheckout.com so we can send you a copy immediately.
 *
 * @category   onestepcheckout
 * @package    onestepcheckout_iosc
 * @copyright  Copyright (c) 2017 OneStepCheckout  (https://www.onestepcheckout.com/)
 * @license    https://www.onestepcheckout.com/LICENSE.txt
 */
namespace Onestepcheckout\Iosc\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\App\ProductMetadata;

/**
 * Upgrade Data script
 * @codeCoverageIgnore
 */
class UpgradeData implements UpgradeDataInterface
{

    private $objectManager;

    /**
     *
     * @param ProductMetadata $productMetadata
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        ProductMetadata $productMetadata
    ) {
        $this->objectManager = $objectManager;
        $this->productMetadata = $productMetadata;
    }

    /**
     * {@inheritdoc}
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($this->productMetadata->getVersion(), '2.2.0', '>=')) {
            $this->convertSerializedDataToJson($setup);
        }
        $setup->endSetup();
    }

    /**
     * convert from serialized to JSON. Stored value may not be
     * serialized, so validate data format before executing update.
     *
     * @param ModuleDataSetupInterface $setup
     * @return void
     */
    private function convertSerializedDataToJson(ModuleDataSetupInterface $setup)
    {
        $configFields = [
            'onestepcheckout_iosc/shippingfields/shippingfields',
            'onestepcheckout_iosc/billingfields/billingfields'
        ];
        $select = $setup->getConnection()
            ->select()
            ->from(
                $setup->getTable('core_config_data'),
                ['config_id', 'value']
            )
            ->where('path IN (?)', $configFields);

        $rows = $setup->getConnection()->fetchAssoc($select);
        $serializedRows = array_filter($rows, function ($row) {
            return $this->isSerialized($row['value']);
        });

        /*
         * using objectmanager directly cause those classes are not present in < 2.2.* and
         * otherwise we would need to release separate package for separate magento versions
         * thats a overhead we don't need for our customers
         */
        $fieldDataConverter = $this->objectManager->create(\Magento\Framework\DB\FieldDataConverterFactory::class);
        $fieldDataConverter = $fieldDataConverter->create(\Magento\Framework\DB\DataConverter\SerializedToJson::class);
        $queryModifier = $this->objectManager->create(\Magento\Framework\DB\Select\QueryModifierFactory::class);
        $queryModifier = $queryModifier->create(
            'in',
            [
                'values' => [
                    'config_id' => array_keys($serializedRows)
                ]
            ]
        );

        $fieldDataConverter->convert(
            $setup->getConnection(),
            $setup->getTable('core_config_data'),
            'config_id',
            'value',
            $queryModifier
        );
    }

    /**
     * Check if value is a serialized string
     *
     * @param string $value
     * @return boolean
     */
    private function isSerialized($value)
    {
        return (boolean) preg_match('/^((s|i|d|b|a|O|C):|N;)/', $value);
    }
}
