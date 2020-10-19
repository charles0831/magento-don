<?php
/**
 * Class CleanCommand
 * This command allow clean mapping log data after users deleted any migrated items in M2
 */

class CleanCommand extends CConsoleCommand
{
    public function actionIndex($step = -1)
    {
        echo "Processing...";

        $tablePrefix = Yii::app()->db->tablePrefix;
        $steps = [
            2 => [
                "DELETE FROM {$tablePrefix}ub_migrate_map_step_2 WHERE entity_name = 'core_website' AND m2_id NOT IN (SELECT website_id FROM {$tablePrefix}store_website);",
                "DELETE FROM {$tablePrefix}ub_migrate_map_step_2 WHERE entity_name = 'core_store_group' AND m2_id NOT IN (SELECT group_id FROM {$tablePrefix}store_group);",
                "DELETE FROM {$tablePrefix}ub_migrate_map_step_2 WHERE entity_name = 'core_store' AND m2_id NOT IN (SELECT store_id FROM {$tablePrefix}store);"
            ],
            3 => [
                "DELETE FROM {$tablePrefix}ub_migrate_map_step_3 WHERE entity_name = 'eav_attribute_group' AND m2_id NOT IN (SELECT attribute_group_id FROM {$tablePrefix}eav_attribute_group);",
                "DELETE FROM {$tablePrefix}ub_migrate_map_step_3_attribute WHERE entity_name = 'eav_attribute' AND m2_id NOT IN (SELECT attribute_id FROM {$tablePrefix}eav_attribute);",
                "DELETE FROM {$tablePrefix}ub_migrate_map_step_3_attribute_option WHERE entity_name = 'eav_attribute_option' AND m2_id NOT IN (SELECT option_id FROM {$tablePrefix}eav_attribute_option);"
            ],
            4 => [
                "DELETE FROM {$tablePrefix}ub_migrate_map_step_4 WHERE entity_name = 'catalog_category_entity' AND m2_id NOT IN (SELECT entity_id FROM {$tablePrefix}catalog_category_entity);"
            ],
            5 => [
                "DELETE FROM {$tablePrefix}ub_migrate_map_step_5 WHERE entity_name = 'catalog_product_entity' AND m2_id NOT IN (SELECT entity_id FROM {$tablePrefix}catalog_product_entity);",
                "DELETE FROM {$tablePrefix}ub_migrate_map_step_5_product_option WHERE entity_name = 'catalog_product_option' AND m2_id NOT IN (SELECT option_id FROM {$tablePrefix}catalog_product_option);",
                "DELETE FROM {$tablePrefix}ub_migrate_map_step_5_product_option WHERE entity_name = 'catalog_product_option_type_value' AND m2_id NOT IN (SELECT option_type_id FROM {$tablePrefix}catalog_product_option_type_value);",
                "DELETE FROM {$tablePrefix}ub_migrate_map_step_5_product_option WHERE entity_name = 'catalog_product_option_type_price' AND m2_id NOT IN (SELECT option_type_price_id FROM {$tablePrefix}catalog_product_option_type_price);",
                "DELETE FROM {$tablePrefix}ub_migrate_map_step_5_product_option WHERE entity_name = 'catalog_product_bundle_option' AND m2_id NOT IN (SELECT option_id FROM {$tablePrefix}catalog_product_bundle_option);",
                "DELETE FROM {$tablePrefix}ub_migrate_map_step_5_product_option WHERE entity_name = 'catalog_product_bundle_selection' AND m2_id NOT IN (SELECT selection_id FROM {$tablePrefix}catalog_product_bundle_selection);"
            ],
            6 => [
                "DELETE FROM {$tablePrefix}ub_migrate_map_step_6 WHERE entity_name = 'customer_group' AND m2_id NOT IN (SELECT customer_group_id FROM {$tablePrefix}customer_group);",
                "DELETE FROM {$tablePrefix}ub_migrate_map_step_6 WHERE entity_name = 'customer_entity' AND m2_id NOT IN (SELECT entity_id FROM {$tablePrefix}customer_entity);",
                "DELETE FROM {$tablePrefix}ub_migrate_map_step_6_customer_address WHERE entity_name = 'customer_address_entity' AND m2_id NOT IN (SELECT entity_id FROM {$tablePrefix}customer_address_entity);"
            ],
            7 => [
                "DELETE FROM {$tablePrefix}ub_migrate_map_step_7 WHERE entity_name = 'salesrule' AND m2_id NOT IN (SELECT rule_id FROM {$tablePrefix}salesrule);",
                "DELETE FROM {$tablePrefix}ub_migrate_map_step_7 WHERE entity_name = 'salesrule_coupon' AND m2_id NOT IN (SELECT coupon_id FROM {$tablePrefix}salesrule_coupon);",
                "DELETE FROM {$tablePrefix}ub_migrate_map_step_7_order_item WHERE entity_name = 'sales_flat_order_item' AND m2_id NOT IN (SELECT item_id FROM {$tablePrefix}sales_order_item);",
                "DELETE FROM {$tablePrefix}ub_migrate_map_step_7_order_address WHERE entity_name = 'sales_flat_order_address' AND m2_id NOT IN (SELECT entity_id FROM {$tablePrefix}sales_order_address);",
                "DELETE FROM {$tablePrefix}ub_migrate_map_step_7_quote WHERE (entity_name = 'sales_flat_quote' OR entity_name = 'sales_flat_quote_active') AND m2_id NOT IN (SELECT entity_id FROM {$tablePrefix}quote);",
                "DELETE FROM {$tablePrefix}ub_migrate_map_step_7_quote_item WHERE entity_name = 'sales_flat_quote_item' AND m2_id NOT IN (SELECT item_id FROM {$tablePrefix}quote_item);",
                "DELETE FROM {$tablePrefix}ub_migrate_map_step_7_quote_address WHERE entity_name = 'sales_flat_quote_address' AND m2_id NOT IN (SELECT address_id FROM {$tablePrefix}quote_address);"
            ],
            8 => [
                "DELETE FROM {$tablePrefix}ub_migrate_map_step_8_review WHERE entity_name = 'review' AND m2_id NOT IN (SELECT review_id FROM {$tablePrefix}review);",
                "DELETE FROM {$tablePrefix}ub_migrate_map_step_8_subscriber WHERE entity_name = 'newsletter_subscriber' AND m2_id NOT IN (SELECT subscriber_id FROM {$tablePrefix}newsletter_subscriber);",
                "DELETE FROM {$tablePrefix}ub_migrate_map_step_8 WHERE entity_name = 'tax_class' AND m2_id NOT IN (SELECT class_id FROM {$tablePrefix}tax_class);",
                "DELETE FROM {$tablePrefix}ub_migrate_map_step_8 WHERE entity_name = 'tax_calculation_rule' AND m2_id NOT IN (SELECT tax_calculation_rule_id FROM {$tablePrefix}tax_calculation_rule);",
                "DELETE FROM {$tablePrefix}ub_migrate_map_step_8 WHERE entity_name = 'tax_calculation_rate' AND m2_id NOT IN (SELECT tax_calculation_rate_id FROM {$tablePrefix}tax_calculation_rate);",
                "DELETE FROM {$tablePrefix}ub_migrate_map_step_8_cms WHERE entity_name = 'cms_block' AND m2_id NOT IN (SELECT block_id From {$tablePrefix}cms_block);",
                "DELETE FROM {$tablePrefix}ub_migrate_map_step_8_cms WHERE entity_name = 'cms_page' AND m2_id NOT IN (SELECT page_id From {$tablePrefix}cms_page);"
            ]
        ];

        if ($step > 0) {
            $queries = $steps[$step];
            foreach ( $queries as $query) {
                Yii::app()->db->createCommand($query)->query();
                echo ".";
            }
        } else {
            foreach ($steps as $step => $queries) {
                foreach ( $queries as $query) {
                    Yii::app()->db->createCommand($query)->query();
                    echo ".";
                }
            }
        }

        Yii::app()->cache->flush();

        echo "Done.\n";
    }
}