<?php

include_once('BaseController.php');

/**
 * @todo: Catalog Products migration
 *
 * Class Step5Controller
 */
class Step5Controller extends BaseController
{
    protected $stepIndex = 5;
    protected $strDeltaProductIds = null;

    /**
     * @todo: Setting
     */
    public function actionSetting()
    {
        //get step object
        $step = UBMigrate::model()->find("id = {$this->stepIndex}");
        $result = UBMigrate::checkStep($step->sorder);
        if ($result['allowed']) {
            //get current setting data
            $settingData = $step->getSettingData();
            //get selected attribute sets
            $selectedAttributeSetIds = UBMigrate::getSetting(3, 'attribute_set_ids');

            //get selected category ids
            $selectedCategoryIds = UBMigrate::getSetting(4, 'category_ids');
            $isSelectAllCategories = UBMigrate::getSetting(4, 'select_all_category');

            //product types
            $productTypes = array('simple', 'configurable', 'grouped', 'virtual', 'bundle', 'downloadable');
            if (Yii::app()->request->isPostRequest) {
                //check required settings
                if ($selectedAttributeSetIds && ($isSelectAllCategories || $selectedCategoryIds)) {
                    //get selected data ids
                    $selectedProductTypes = Yii::app()->request->getParam('product_types', array());
                    $selectedProductTypes = array_unique($selectedProductTypes);
                    $keepOriginalId = Yii::app()->request->getParam('keep_original_id', 0);
                    if ($selectedProductTypes) {
                        //make setting data to save
                        $newSettingData = [
                            'product_types' => $selectedProductTypes,
                            'select_all_product' => (sizeof($selectedProductTypes) == sizeof($productTypes)) ? 1 : 0,
                            'keep_original_id' => $keepOriginalId,
                            'migrated_product_types' => (isset($settingData['migrated_product_types'])) 
                            ? array_unique($settingData['migrated_product_types']) 
                            : []
                        ];
                        $step->setting_data = base64_encode(serialize($newSettingData));
                        $step->status = UBMigrate::STATUS_SETTING;
                        //save settings data
                        if ($step->update()) {
                            //alert message
                            Yii::app()->user->setFlash('success', "Your settings have been saved successfully");
                            //get next step index
                            $stepIndex = ($this->stepIndex < UBMigrate::MAX_STEP_INDEX) ? ++$this->stepIndex : 1;
                            //go to next step
                            $this->redirect(UBMigrate::getSettingUrl($stepIndex));
                        }
                    } else {
                        Yii::app()->user->setFlash('note', Yii::t(
                            'frontend',
                            'You must select at least one Product type to migrate or you can skip this step.'
                        ));
                    }
                } else {
                    if (!sizeof($selectedAttributeSetIds)) {
                        Yii::app()->user->setFlash('note', Yii::t(
                            'frontend',
                            'Reminder! You have to complete all settings in the step #3 (Attributes) first'
                        ));
                    } else if (!$isSelectAllCategories && !sizeof($selectedCategoryIds)) {
                        Yii::app()->user->setFlash('note', Yii::t(
                            'frontend',
                            'Reminder! You have to complete all settings in the step #4 (Categories) first'
                        ));
                    }
                }
            }
            $assignData = array(
                'step' => $step,
                'productTypes' => $productTypes,
                'settingData' => $settingData
            );
            $this->render("setting", $assignData);
        } else {
            Yii::app()->user->setFlash('note', Yii::t(
                'frontend',
                "Reminder! You need to finish settings in the step #%s", array("%s" => ($result['back_step_index']))
            ));
            $this->redirect($result['back_step_url']);
        }
    }

    /**
     * @todo: Run Migrate data
     */
    public function actionRun()
    {
        //get current step object
        $step = UBMigrate::model()->find("id = {$this->stepIndex}");
        $rs = [
            'step_status_text' => $step->getStepStatusText(),
            'step_index' => $this->stepIndex,
            'status' => 'fail',
            'message' => '',
            'errors' => '',
            'offset' => 0
        ];

        //check can run migrate data
        $check = $step->canRun();
        if ($check['allowed']) {
            //get mapping websites
            $mappingWebsites = UBMigrate::getMappingData('core_website', 2);
            //get mapping stores
            $mappingStores = UBMigrate::getMappingData('core_store', 2);
            //get mapping attributes
            $mappingAttributes = UBMigrate::getMappingData('eav_attribute', '3_attribute');
            //get setting data
            $settingData = $step->getSettingData();
            $selectedProductTypes = (isset($settingData['product_types'])) ? $settingData['product_types'] : [];
            //check has keep original Ids
            $keepOriginalId = (isset($settingData['keep_original_id'])) ? $settingData['keep_original_id'] : 0;

            //some variables for paging
            $max0 = $offset0 = $max1 = $offset1 = $max2 = $offset2 = $max3 = $offset3 = $max4 = $offset4 = 0;
            $max5 = $offset5 = $max6 = $offset6 = $max7 = $offset7 = $max7 = $offset7 = $max8 = $offset8 = 0;
            $lastRecordOffset0 = $lastRecordOffset1 = $lastRecordOffset2 = $lastRecordOffset3 = $lastRecordOffset4 = 0;
            $lastRecordOffset5 = $lastRecordOffset6 = $lastRecordOffset7 = $lastRecordOffset8 = 0;
            try {
                //start migrate data by settings
                if ($selectedProductTypes) {
                    /**
                     * Table: catalog_product_entity
                     */
                    //make condition to get data
                    $strSelectedProductTypeIds = "'" . implode("','", $selectedProductTypes) . "'";
                    $condition = "type_id IN ({$strSelectedProductTypeIds})";
                    $lastRecordOffset0 = Mage1CatalogProductEntity::model()->count($condition);

                    //condition to select all products which belong to the selected front-end websites only
                    /*
                    $strMigratedWebsiteIds = implode(',', array_keys($mappingWebsites));
                    $condition .= " AND entity_id IN (
                    SELECT product_id 
                    FROM catalog_product_website 
                    WHERE website_id IN ({$strMigratedWebsiteIds}
                    ))";
                    */
                   
                    //get max total
                    $max0 = Mage1CatalogProductEntity::model()->count($condition);
                    $offset0 = UBMigrate::getCurrentOffset(5, Mage1CatalogProductEntity::model()->tableName());
                    $orderBy = "entity_id ASC";
                    /**
                     * We are only migrate for the data records which has changed
                     * or for the data records are newly added after the first migration finished.
                     */
                    if ($this->runMode == UBMigrate::RUN_MODE_DELTA) {
                        $lastMigrationTime = UBMigrate::getLastMigrationTime($this->stepIndex, 'catalog_product_entity');
                        $condition .= " AND updated_at >= '{$lastMigrationTime}'";
                        //get all product IDs need to delta
                        if (!$this->strDeltaProductIds) {
                            $this->strDeltaProductIds = $this->_getAllDeltaProductIds($condition);
                        }
                    }
                    //get data by limit and offset
                    $products = UBMigrate::getListObjects(
                        'Mage1CatalogProductEntity',
                        $condition,
                        $offset0,
                        $this->limit,
                        $orderBy
                    );
                    if ($products) {
                        //migrate product and related data
                        $this->_migrateCatalogProducts($products, $mappingWebsites, $mappingStores, $keepOriginalId);
                    }

                    if ($offset0 == 0) {
                        //log for first entry
                        Yii::log("[Start][{$this->runMode}] step #{$this->stepIndex}",'info', 'ub_data_migration');
                        //update status of this step to migrating
                        $step->updateStatus(UBMigrate::STATUS_MIGRATING);
                    }

                    if ($offset0 >= $max0) { //if has migrated all products
                        /**
                         * Start: Migrate other data objects related with a product
                         *
                         * Table: catalog_product_link_type:
                         * 1 - relation - Related Products
                         * 2 - bundle - Bundle products
                         * 3 - super - Grouped Products
                         * 4 - up_sell - Up Sell Products
                         * 5 - cross_sell - Cross Sell Products
                         * Note: Tables "catalog_product_link_type, catalog_product_link_attribute" were not changed.
                         */
                        /**
                         * Table: catalog_product_link
                         */
                        /**
                         * Because some cases the link_type_id can changed.
                         * So we mapping again link type ids in M1 to migrate
                         */
                        $linkTypeIds = array(
                            UBMigrate::getMage1ProductLinkTypeId('relation'),
                            UBMigrate::getMage1ProductLinkTypeId('up_sell'),
                            UBMigrate::getMage1ProductLinkTypeId('cross_sell')
                        );
                        if (in_array('grouped', $selectedProductTypes)) {
                            $linkTypeIds[] = UBMigrate::getMage1ProductLinkTypeId('super');
                        }
                        if (in_array('bundle', $selectedProductTypes)) {
                            $linkTypeIds[] = UBMigrate::getMage1ProductLinkTypeId('bundle');
                        }
                        $strLinkTypeIds = implode(',', array_filter($linkTypeIds));

                        //build condition
                        $condition = "link_type_id IN ({$strLinkTypeIds})";

                        $lastRecordOffset1 = Mage1CatalogProductLink::model()->count($condition);
                        if ($this->strDeltaProductIds) {
                            $condition .= " AND (product_id IN ({$this->strDeltaProductIds})";
                            $condition .= " OR linked_product_id IN ({$this->strDeltaProductIds}))";
                        }

                        //get max total
                        $max1 = Mage1CatalogProductLink::model()->count($condition);
                        $offset1 = UBMigrate::getCurrentOffset(5, Mage1CatalogProductLink::model()->tableName());
                        //get data by limit and offset
                        $productLinks = UBMigrate::getListObjects(
                            'Mage1CatalogProductLink',
                            $condition,
                            $offset1,
                            $this->limit,
                            "link_id ASC"
                        );
                        if ($productLinks) {
                            $this->_migrateCatalogProductLinks($productLinks, $keepOriginalId);
                        }
                        //End: Cross sell, Up sell, Related & Grouped Products

                        //Start: configurable products
                        $canRun = ($offset0 >= $max0 && $offset1 >= $max1) ? 1 : 0;
                        if (in_array('configurable', $selectedProductTypes) && $canRun) {
                            /**
                             * Table: catalog_product_super_link
                             */
                            $condition = '';
                            $lastRecordOffset2 = Mage1CatalogProductSuperLink::model()->count($condition);
                            if ($this->strDeltaProductIds) {
                                $condition = "(product_id IN ({$this->strDeltaProductIds})";
                                $condition .= " OR parent_id IN ({$this->strDeltaProductIds}))";
                            }
                            //get max total
                            $max2 = Mage1CatalogProductSuperLink::model()->count($condition);
                            $offset2 = UBMigrate::getCurrentOffset(5, Mage1CatalogProductSuperLink::model()->tableName());
                            //get data by limit and offset
                            $productSuperLinks = UBMigrate::getListObjects(
                                'Mage1CatalogProductSuperLink',
                                $condition,
                                $offset2,
                                $this->limit,
                                "link_id ASC"
                            );
                            if ($productSuperLinks) {
                                //migrate product super links
                                $this->_migrateCatalogProductSuperLinks($productSuperLinks, $keepOriginalId);
                            }

                            /**
                             * Table: catalog_product_super_attribute
                             */
                            if ($offset2 >= $max2) {
                                $condition = '';
                                $lastRecordOffset3 = Mage1CatalogProductSuperAttribute::model()->count($condition);
                                if ($this->strDeltaProductIds) {
                                    $condition = "product_id IN ({$this->strDeltaProductIds})";
                                }
                                //get max total
                                $max3 = Mage1CatalogProductSuperAttribute::model()->count($condition);
                                $offset3 = UBMigrate::getCurrentOffset(
                                    5,
                                    Mage1CatalogProductSuperAttribute::model()->tableName()
                                );
                                //get data by limit and offset
                                $productSuperAttributes = UBMigrate::getListObjects(
                                    'Mage1CatalogProductSuperAttribute',
                                    $condition,
                                    $offset3,
                                    $this->limit,
                                    "product_super_attribute_id ASC"
                                );
                                if ($productSuperAttributes) {
                                    //migrate catalog product super attributes
                                    $this->_migrateCatalogProductSuperAttributes(
                                        $productSuperAttributes,
                                        $mappingWebsites,
                                        $mappingStores,
                                        $mappingAttributes,
                                        $keepOriginalId
                                    );
                                }
                            }
                        }
                        //End: Configurable products

                        //Start: migrate Bundle products
                        $canRun = ($offset0 >= $max0 && $offset1 >= $max1 && $offset2 >= $max2 && $offset3 >= $max3) 
                        ? 1 
                        : 0;
                        if (in_array('bundle', $selectedProductTypes) && $canRun) {
                            /**
                             * Table: catalog_product_bundle_option
                             */
                            $condition = '';
                            $lastRecordOffset5 = Mage1CatalogProductBundleOption::model()->count($condition);
                            if ($this->strDeltaProductIds) {
                                $condition = "parent_id IN ({$this->strDeltaProductIds})";
                            }
                            $max5 = Mage1CatalogProductBundleOption::model()->count($condition);
                            $offset5 = UBMigrate::getCurrentOffset(5, Mage1CatalogProductBundleOption::model()->tableName());
                            //get data by limit and offset
                            $productBundleOptions = UBMigrate::getListObjects(
                                'Mage1CatalogProductBundleOption',
                                $condition,
                                $offset5,
                                $this->limit,
                                "option_id ASC"
                            );
                            if ($productBundleOptions) {
                                //migrate product bundle options
                                $this->_migrateCatalogProductBundleOptions(
                                    $productBundleOptions,
                                    $mappingWebsites,
                                    $mappingStores,
                                    $keepOriginalId
                                );
                            }
                        }
                        //End: migrate Bundle products

                        //Start: migrate Downloadable products
                        $canRun = ($offset0 >= $max0 && $offset1 >= $max1 && $offset2 >= $max2 && $offset3 >= $max3 
                            && $offset5 >= $max5)
                            ? 1
                            : 0;
                        if (in_array('downloadable', $selectedProductTypes) && $canRun) {
                            /**
                             * Table: downloadable_link
                             */
                            $condition = '';
                            $lastRecordOffset6 = Mage1DownloadableLink::model()->count($condition);
                            if ($this->strDeltaProductIds) {
                                $condition = "product_id IN ({$this->strDeltaProductIds})";
                            }
                            $max6 = Mage1DownloadableLink::model()->count($condition);
                            $offset6 = UBMigrate::getCurrentOffset(5, Mage1DownloadableLink::model()->tableName());
                            //get data by limit and offset
                            $downloadableLinks = UBMigrate::getListObjects(
                                'Mage1DownloadableLink',
                                $condition,
                                $offset6,
                                $this->limit,
                                "link_id ASC"
                            );
                            if ($downloadableLinks) {
                                //migrate download links
                                $this->_migrateCatalogProductDownloadableLinks(
                                    $downloadableLinks,
                                    $mappingWebsites,
                                    $mappingStores,
                                    $keepOriginalId
                                );
                            }

                            /**
                             * Table: downloadable_sample
                             */
                            if ($offset6 >= $max6) {
                                $condition = '';
                                $lastRecordOffset7 = Mage1DownloadableSample::model()->count($condition);
                                if ($this->strDeltaProductIds) {
                                    $condition = "product_id IN ({$this->strDeltaProductIds})";
                                }
                                $max7 = Mage1DownloadableSample::model()->count($condition);
                                $offset7 = UBMigrate::getCurrentOffset(5, Mage1DownloadableSample::model()->tableName());
                                //get data by limit and offset
                                $downloadSamples = UBMigrate::getListObjects(
                                    'Mage1DownloadableSample',
                                    $condition,
                                    $offset7,
                                    $this->limit,
                                    "sample_id ASC"
                                );
                                if ($downloadSamples) {
                                    //migrate download samples
                                    $this->_migrateCatalogProductDownloadableSamples(
                                        $downloadSamples,
                                        $mappingStores,
                                        $keepOriginalId
                                    );
                                }
                            }
                        }
                        //End: migrate Downloadable products

                        /**
                         * Table: catalog_product_relation
                         */
                        $canRun = ($offset0 >= $max0 && $offset1 >= $max1 && $offset2 >= $max2 && $offset3 >= $max3
                            && $offset5 >= $max5 && $offset6 >= $max6 && $offset7 >= $max7)
                            ? 1
                            : 0;
                        if ((in_array('grouped', $selectedProductTypes)
                            || in_array('bundle', $selectedProductTypes)
                            || in_array('configurable', $selectedProductTypes)) && $canRun) {
                            $condition = '';
                            $lastRecordOffset8 = Mage1CatalogProductRelation::model()->count($condition);
                            if ($this->strDeltaProductIds) {
                                $condition = "parent_id IN ({$this->strDeltaProductIds})";
                                $condition .= " OR child_id IN ({$this->strDeltaProductIds})";
                            }
                            $max8 = Mage1CatalogProductRelation::model()->count($condition);
                            $offset8 = UBMigrate::getCurrentOffset(5, Mage1CatalogProductRelation::model()->tableName());
                            //get data by limit and offset
                            $productRelations = UBMigrate::getListObjects(
                                'Mage1CatalogProductRelation',
                                $condition,
                                $offset8,
                                $this->limit
                            );
                            if ($productRelations) {
                                //migrate catalog product relation
                                $this->_migrateCatalogProductRelations($productRelations, $keepOriginalId);
                            }
                        }
                        //End: migrate other data objects related a product
                    }
                }

                //make result to respond
                if ($this->errors) {
                    //update step status
                    $step->updateStatus(UBMigrate::STATUS_ERROR);
                    $rs['step_status_text'] = $step->getStepStatusText();

                    $strErrors = implode('<br/>', $this->errors);
                    $rs['errors'] = $strErrors;
                    Yii::log($rs['errors'], 'error', 'ub_data_migration');
                } else {
                    //if all selected data migrated
                    if ($offset0 >= $max0 AND $offset1 >= $max1 AND $offset2 >= $max2 AND $offset3 >= $max3 AND $offset4 >= $max4
                        AND $offset5 >= $max5 AND $offset6 >= $max6 AND $offset7 >= $max7 AND $offset8 >= $max8) {
                        //update status of this step to finished
                        if ($step->updateStatus(UBMigrate::STATUS_FINISHED)) {
                            //update migrated product types
                            UBMigrate::updateSetting(5, 'migrated_product_types', $selectedProductTypes);

                            //update offset to latest record
                            UBMigrate::updateCurrentOffset(
                                Mage1CatalogProductEntity::model()->tableName(),
                                $lastRecordOffset0,
                                $this->stepIndex,
                                true
                            );
                            UBMigrate::updateCurrentOffset(
                                Mage1CatalogProductLink::model()->tableName(),
                                $lastRecordOffset1,
                                $this->stepIndex,true
                            );
                            UBMigrate::updateCurrentOffset(
                                Mage1CatalogProductSuperLink::model()->tableName(),
                                $lastRecordOffset2,
                                $this->stepIndex,
                                true
                            );
                            UBMigrate::updateCurrentOffset(
                                Mage1CatalogProductSuperAttribute::model()->tableName(),
                                $lastRecordOffset3,
                                $this->stepIndex,
                                true
                            );
                            UBMigrate::updateCurrentOffset(
                                Mage1CatalogProductBundleOption::model()->tableName(),
                                $lastRecordOffset5,
                                $this->stepIndex,
                                true
                            );
                            UBMigrate::updateCurrentOffset(
                                Mage1DownloadableLink::model()->tableName(),
                                $lastRecordOffset6,
                                $this->stepIndex,
                                true
                            );
                            UBMigrate::updateCurrentOffset(
                                Mage1DownloadableSample::model()->tableName(),
                                $lastRecordOffset7,
                                $this->stepIndex,
                                true
                            );
                            UBMigrate::updateCurrentOffset(
                                Mage1CatalogProductRelation::model()->tableName(),
                                $lastRecordOffset8,
                                $this->stepIndex,
                                true
                            );

                            //update result to respond
                            $rs['status'] = 'done';
                            $rs['percent_done'] = UBMigrate::getPercentByStatus(UBMigrate::STATUS_FINISHED, [1]);
                            $rs['step_status_text'] = $step->getStepStatusText();
                            $rs['message'] = Yii::t(
                                'frontend',
                                'Step #%s migration completed successfully',
                                array('%s' => $this->stepIndex)
                            );
                            Yii::log($rs['message']."\n", 'info', 'ub_data_migration');
                        }
                    } else {
                        //update current offset for next run
                        if ($max0) {
                            $max = $max0;
                            $offset = $offset0 + $this->limit;
                            $entityName = Mage1CatalogProductEntity::model()->tableName();
                        }
                        if ($max1) {
                            $max = $max1;
                            $offset = $offset1 + $this->limit;
                            $entityName = Mage1CatalogProductLink::model()->tableName();
                        }
                        if ($max2) {
                            $max = $max2;
                            $offset = $offset2 + $this->limit;
                            $entityName = Mage1CatalogProductSuperLink::model()->tableName();
                        }
                        if ($max3) {
                            $max = $max3;
                            $offset = $offset3 + $this->limit;
                            $entityName = Mage1CatalogProductSuperAttribute::model()->tableName();
                        }
                        if ($max5) {
                            $max = $max5;
                            $offset = $offset5 + $this->limit;
                            $entityName = Mage1CatalogProductBundleOption::model()->tableName();
                        }
                        if ($max6) {
                            $max = $max6;
                            $offset = $offset6 + $this->limit;
                            $entityName = Mage1DownloadableLink::model()->tableName();
                        }
                        if ($max7) {
                            $max = $max7;
                            $offset = $offset7 + $this->limit;
                            $entityName = Mage1DownloadableSample::model()->tableName();
                        }
                        if ($max8) {
                            $max = $max8;
                            $offset = $offset8 + $this->limit;
                            $entityName = Mage1CatalogProductRelation::model()->tableName();
                        }

                        //update current offset for current migrating data object
                        UBMigrate::updateCurrentOffset($entityName, $offset, $this->stepIndex);

                        //update result to respond
                        $rs['status'] = 'ok';
                        $rs['percent_up'] = UBMigrate::getPercentUp(8, $max, $this->limit);

                        //build message
                        $breakLine = ($this->isCLI) ? "\n" : "";
                        $msg = ($offset1 == 0)
                            ? '[Processing]['. $this->runMode .'] Step #%s migration completed with'
                            : '[Processing]['.$this->runMode.'] Step #%s migration completed with';
                        $data['%s'] = $this->stepIndex;
                        if (isset($products) AND $products) {
                            $msg .= ' %s1 Products;';
                            $data['%s1'] = sizeof($products);
                        }
                        if (isset($productLinks) AND $productLinks) {
                            $msg .= ' %s2 Product Links;';
                            $data['%s2'] = sizeof($productLinks);
                        }
                        if (isset($productSuperLinks) AND $productSuperLinks) {
                            $msg .= ' %s3 Product Super Links;';
                            $data['%s3'] = sizeof($productSuperLinks);
                        }
                        if (isset($productSuperAttributes) AND $productSuperAttributes) {
                            $msg .= ' %s4 Product Super Attributes;';
                            $data['%s4'] = sizeof($productSuperAttributes);
                        }
                        if (isset($productRelations) AND $productRelations) {
                            $msg .= ' %s5 Product Relations;';
                            $data['%s5'] = sizeof($productRelations);
                        }
                        if (isset($productBundleOptions) AND $productBundleOptions) {
                            $msg .= ' %s6 Product Bundle Options;';
                            $data['%s6'] = sizeof($productBundleOptions);
                        }
                        if (isset($downloadableLinks) AND $downloadableLinks) {
                            $msg .= ' %s7 Product Downloadable Links;';
                            $data['%s7'] = sizeof($downloadableLinks);
                        }
                        if (isset($downloadSamples) AND $downloadSamples) {
                            $msg .= ' %s8 Product Downloadable Samples';
                            $data['%s8'] = sizeof($downloadSamples);
                        }
                        $rs['message'] = Yii::t('frontend', $breakLine.$msg, $data);
                        Yii::log($rs['message'], 'info', 'ub_data_migration');
                    }
                }

            } catch (Exception $e) {
                //update step status
                $step->updateStatus(UBMigrate::STATUS_ERROR);
                $rs['step_status_text'] = $step->getStepStatusText();

                $rs['errors'] = $e->getMessage();
                Yii::log($rs['errors'], 'error', 'ub_data_migration');
            }
        } else {
            if ($step->status == UBMigrate::STATUS_PENDING) {
                $rs['notice'] = Yii::t(
                    'frontend',
                    "Step #%s has no settings yet. Navigate back to the UI dashboard to check the setting for step #%s again",
                    array('%s' => $this->stepIndex)
                );
            } elseif ($step->status == UBMigrate::STATUS_SKIPPING) {
                $rs['status'] = 'done';
                $rs['notice'] = Yii::t('frontend', "You marked step #%s as skipped.", array('%s' => $this->stepIndex));
            } else {
                if (isset($check['required_finished_step_index'])) {
                    $rs['notice'] = Yii::t(
                        'frontend',
                        "Reminder! Before migrating data in the step #%s1, you have to complete migration in the step #%s2",
                        array('%s1' => $step->sorder, '%s2' => $check['required_finished_step_index'])
                    );
                }
            }
        }

        //respond result
        if ($this->isCLI) {
            return $rs;
        } else {
            echo json_encode($rs);
            Yii::app()->end();
        }
    }

    private function _migrateCatalogProducts($products, $mappingWebsites, $mappingStores, $keepOriginalId)
    {
        //get mapping attribute sets
        $mappingAttributeSets = UBMigrate::getMappingData('eav_attribute_set', 3);
        //get mapping attributes
        $mappingAttributes = UBMigrate::getMappingData('eav_attribute', '3_attribute');
        //migrate products
        foreach ($products as $product) {
            $productId2 = UBMigrate::getM2EntityId(5, 'catalog_product_entity', $product->entity_id);
            $canReset = UBMigrate::RESET_YES;
            if (is_null($productId2)) {
                //make sure SKU is unique as reuired in M2
                $sku2 = (!empty($product->sku)) ? $product->sku : "SKU-{$product->entity_id}";
                $found = Mage2CatalogProductEntity::model()->find("sku = '" . addslashes($sku2) . "'");
                if ($found) {
                    $sku2 =  "{$product->sku}-{$product->entity_id}";
                }
                //add new
                $product2 = new Mage2CatalogProductEntity();
                $excluded = ['sku', 'entity_id', 'attribute_set_id'];
                foreach ($product2->attributes as $key => $value) {
                    if (isset($product->$key) && !in_array($key, $excluded)) {
                        $product2->$key = $product->$key;
                    }
                }
                $product2->sku = $sku2;
                $product2->entity_id = ($keepOriginalId) ? $product->entity_id : null;
                $product2->attribute_set_id = isset($mappingAttributeSets[$product->attribute_set_id]) 
                ? $mappingAttributeSets[$product->attribute_set_id] 
                : 0;
            } else {
                //update
                $product2 = Mage2CatalogProductEntity::model()->find("entity_id = {$productId2}");
                $product2->sku = $product->sku;
                $product2->has_options = $product->has_options;
                $product2->required_options = $product->required_options;
                $product2->updated_at = $product->updated_at;
                $product2->attribute_set_id = isset($mappingAttributeSets[$product->attribute_set_id]) 
                ? $mappingAttributeSets[$product->attribute_set_id] 
                : 0;
            }
            //save/update
            if ($product2->created_at === '0000-00-00 00:00:00' || empty($product2->created_at)) {
                $product2->created_at = date("Y-m-d H:i:s");
            }
            if ($product2->updated_at === '0000-00-00 00:00:00' || empty($product2->updated_at)) {
                $product2->updated_at = date("Y-m-d H:i:s");
            }
            if (!$product2->save()) {
                $this->errors[] = get_class($product2) . ": " . UBMigrate::getStringErrors($product2->getErrors());
            } else {
                if (is_null($productId2)) {
                    //save to map table
                    UBMigrate::log([
                        'entity_name' => $product->tableName(),
                        'm1_id' => $product->entity_id,
                        'm2_id' => $product2->entity_id,
                        'm2_model_class' => get_class($product2),
                        'm2_key_field' => 'entity_id',
                        'can_reset' => $canReset,
                        'step_index' => $this->stepIndex
                    ]);
                }
                $this->_traceInfo();
            }
            //start migrate related data with a product
            if ($product2->entity_id) {
                //migrate product EAV data
                $this->_migrateCatalogProductEAV(
                    $product->entity_id,
                    $product2->entity_id,
                    $mappingStores,
                    $mappingAttributes,
                    $product->type_id,
                    $keepOriginalId
                );
                //migrate product gallery
                $this->_migrateCatalogProductGallery(
                    $product->entity_id,
                    $product2->entity_id,
                    $mappingStores,
                    $mappingAttributes,
                    $keepOriginalId
                );
                //migrate product options
                $this->_migrateCatalogProductOptions(
                    $product->entity_id,
                    $product2->entity_id,
                    $mappingStores,
                    $keepOriginalId
                );
                //migrate product stock item
                $this->_migrateCatalogProductStockItem(
                    $product->entity_id,
                    $product2->entity_id,
                    $product2->sku,
                    $keepOriginalId
                );
                //migrate product URLs rewrite
                $this->_migrateCatalogProductUrlReWrite(
                    $product->entity_id,
                    $product2->entity_id,
                    $mappingStores,
                    $keepOriginalId
                );
                //migrate product website relation
                $this->_migrateCatalogProductWebsite($product->entity_id, $product2->entity_id, $mappingWebsites);
                //migrate product category relation
                $this->_migrateCatalogCategoryProduct($product->entity_id, $product2->entity_id);
            }
        }// end foreach products

        return true;
    }

    private function _migrateCatalogProductEAV(
        $entityId,
        $entityId2,
        $mappingStores,
        $mappingAttributes,
        $productTypeId,
        $keepOriginalId
    ) {
        /*
         * Get list attributes which we have to reset value on it to default values
        */
        $entityTypeId = UBMigrate::getM1EntityTypeIdByCode(UBMigrate::PRODUCT_TYPE_CODE);
        $resetAttributes = array(
            UBMigrate::getMage1AttributeId('custom_design', $entityTypeId) => '',
            UBMigrate::getMage1AttributeId('custom_design_from', $entityTypeId) => null,
            UBMigrate::getMage1AttributeId('custom_design_to', $entityTypeId) => null,
            UBMigrate::getMage1AttributeId('page_layout', $entityTypeId) => '',
            UBMigrate::getMage1AttributeId('custom_layout_update', $entityTypeId) => null,
        );
        $resetAttributeIds = array_keys($resetAttributes);

        /**
         * Because some system product attribute has change the backend_type value
         * Example:
         * + Attribute with code: media_gallery has change backend_type from `varchar` => `static`
         * So we will check to ignore values of these attributes
         */
        $mediaGalleryAttrId1 = UBMigrate::getMage1AttributeId('media_gallery', $entityTypeId);
        $ignoreAttributeIds = array (
            $mediaGalleryAttrId1
        );

        /**
         * M2 hasn't support pricing on parent product in a configurable product,
         * all prices calculates on pricing of associated products.
         * Therefore, we will exclude related value at this step
         */
        if ($productTypeId == 'configurable') {
            $priceAttrId1 = UBMigrate::getMage1AttributeId('price', $entityTypeId);
            $specialPriceAttrId1 = UBMigrate::getMage1AttributeId('special_price', $entityTypeId);
            $specialPriceFromAttrId1 = UBMigrate::getMage1AttributeId('special_from_date', $entityTypeId);
            $specialPriceToAttrId1 = UBMigrate::getMage1AttributeId('special_to_date', $entityTypeId);
            $ignoreAttributeIds = array_merge($ignoreAttributeIds, array (
                $priceAttrId1,
                $specialPriceAttrId1,
                $specialPriceFromAttrId1,
                $specialPriceToAttrId1
            ));
        }

        //make string migrated store ids
        $strMigratedStoreIds = implode(',', array_keys($mappingStores));

        $eavTables = [
            'catalog_product_entity_int',
            'catalog_product_entity_text',
            'catalog_product_entity_varchar',
            'catalog_product_entity_datetime',
            'catalog_product_entity_decimal'
        ];
        foreach ($eavTables as $table) {
            $className = str_replace(' ', '', ucwords(str_replace('_', ' ', $table)));
            $className1 = "Mage1{$className}";
            $className2 = "Mage2{$className}";
            $models = $className1::model()->findAll(
                "entity_id = {$entityId} AND store_id IN ({$strMigratedStoreIds})"
            );
            if ($models) {
                foreach ($models as $model) {
                    if (!in_array($model->attribute_id, $ignoreAttributeIds)) {
                        $storeId2 = isset($mappingStores[$model->store_id])
                            ? $mappingStores[$model->store_id]
                            : 0;
                        $attributeId2 = isset($mappingAttributes[$model->attribute_id])
                            ? $mappingAttributes[$model->attribute_id]
                            : null;
                        if ($attributeId2) {
                            $model2 = $className2::model()->find(
                                "entity_id = {$entityId2} AND attribute_id = {$attributeId2} AND store_id = {$storeId2}"
                            );
                            if (!$model2) { //add new
                                $model2 = new $className2();
                                $model2->value_id = null;
                                $model2->attribute_id = $attributeId2;
                                $model2->store_id = $storeId2;
                                $model2->entity_id = $entityId2;
                            }
                            //Reset value for needed attributes
                            if (isset($resetAttributes[$model->attribute_id])) {
                                $model2->value = $resetAttributes[$model->attribute_id];
                            } else {
                                $model2->value = $model->value;
                                //Load M1 Attribute by attribute ID
                                $attribute1 = UBMigrate::getMage1AttributeById($model->attribute_id);
                                /**
                                 * Because IDs (option_id) in `eav_attribute_option` table were changed after migrated to M2.
                                 * Thus, we need to checking and mapping here
                                 */
                                if (in_array($attribute1->frontend_input, array('select', 'multiselect'))) {
                                    $count = Mage1AttributeOption::model()->count(
                                        "attribute_id = {$model->attribute_id}"
                                    );
                                    if ($count AND $model2->value) {
                                        if ($attribute1->frontend_input == 'multiselect') {
                                            $ids = preg_split('/,\s*/', $model2->value);
                                            foreach ($ids as $key => $id) {
                                                $ids[$key] = UBMigrate::getM2EntityId(
                                                    '3_attribute_option',
                                                    'eav_attribute_option',
                                                    $id
                                                );
                                            }
                                            $model2->value = implode(',', $ids);
                                        } else {
                                            $model2->value = UBMigrate::getM2EntityId(
                                                '3_attribute_option',
                                                'eav_attribute_option',
                                                $model2->value
                                            );
                                        }
                                    }
                                }
                                //Checking for other special cases
                                if ($className2 == 'Mage2CatalogProductEntityDecimal') {
                                    //Uncomment following code lines to using for bad data cases only
                                    /*if (strlen($model2->value) > 20) {
                                        $model2->value = substr(trim($model2->value), 0, 20);
                                    }*/
                                } else if ($className2 == 'Mage2CatalogProductEntityInt') {
                                    /**
                                     * we will check and migrate related product tax classes in here
                                     */
                                    if ($attribute1->attribute_code === 'tax_class_id') {
                                        //migrate product tax class
                                        $this->_migrateProductTaxClass($model->value, $model2);
                                    }
                                } else if ($className2 == 'Mage2CatalogProductEntityVarchar') {
                                    if ($attribute1->attribute_code === 'url_path' 
                                        && preg_match("/.html/i", $model2->value)) {
                                        $model2->value = str_replace('.html', '', $model2->value);
                                    }
                                    //Uncomment following code lines to using for bad data cases only
                                    /*if (strlen(trim($model2->value)) > 255) {
                                        $model2->value = substr(trim($model2->value), 0, 255);
                                    }*/
                                }
                            }
                            //save/update
                            if (!$model2->save()) {
                                $this->errors[] = "{$className2}: " . UBMigrate::getStringErrors($model2->getErrors());
                            } else {
                                $this->_traceInfo();
                            }
                        }
                    } else {
                        /**
                         * Cases with ignored attributeIds:
                         * media_gallery,
                         * price, sepcial_price,
                         * sepcial_price_from_date, sepcial_price_to_date
                         */
                        if ($productTypeId === 'configurable') {
                            /**
                             * Proceed to convert: price, special price, special_from_date, special_to_date
                             * of parent product in a Configurable product and to all associated simple products
                             */
                            $storeId2 = isset($mappingStores[$model->store_id])
                                ? $mappingStores[$model->store_id]
                                : 0;
                            $attributeId2 = isset($mappingAttributes[$model->attribute_id])
                                ? $mappingAttributes[$model->attribute_id]
                                : null;
                            if ($attributeId2 && $model->attribute_id != $mediaGalleryAttrId1) {
                                //Get all associated simple product IDs of current configurable product
                                $associatedProductIds1 = $this->_getAssociatedProductIds1($entityId);
                                $associatedSuperAttrs1 = $this->_getAssociatedSuperAttrs1($entityId);
                                if ($associatedProductIds1) {
                                    foreach ($associatedProductIds1 as $associatedProductId1) {
                                        $associatedProductId2 = (!$keepOriginalId) 
                                        ? UBMigrate::getM2EntityId(5, 'catalog_product_entity', $associatedProductId1)
                                        : $associatedProductId1;
                                        $condition = "entity_id = {$associatedProductId2} AND attribute_id = {$attributeId2}";
                                        $condition .= " AND store_id = {$storeId2}";
                                        $eavModel2 = $className2::model()->find($condition);
                                        if (!$eavModel2) {
                                            $eavModel2 = new $className2();
                                            $eavModel2->value_id = null;
                                            $eavModel2->entity_id = $associatedProductId2;
                                            $eavModel2->attribute_id = $attributeId2;
                                            $eavModel2->store_id = $storeId2;
                                        }
                                        //assign value of parent product for children product
                                        if (!empty($model->value)) {
                                            $eavModel2->value = $model->value;
                                            /**
                                             * Check has variation setting of current associated simple product in M1
                                             * we will convert to apply for the related simple product in M2
                                             */
                                            if (in_array($model->attribute_id, array($priceAttrId1, $specialPriceAttrId1))) {
                                                foreach ($associatedSuperAttrs1 as $associatedSuperAttr1) {
                                                    $intQuery = "attribute_id = {$associatedSuperAttr1->attribute_id}";
                                                    $intQuery .= " AND entity_id = {$associatedProductId1} AND store_id = {$model->store_id}";
                                                    $relatedIntModel1 = Mage1CatalogProductEntityInt::model()->find($intQuery);
                                                    if ($relatedIntModel1) {
                                                        if ($relatedIntModel1->value) {
                                                            $valueIndex = $relatedIntModel1->value; //option_id
                                                            $superAttrId = $associatedSuperAttr1->product_super_attribute_id;
                                                            $varQuery = "product_super_attribute_id = {$superAttrId}";
                                                            $varQuery .= " AND value_index = {$valueIndex}";
                                                            $relatedVariation = Mage1CatalogProductSuperAttributePricing::model()->find(
                                                                $varQuery
                                                            );
                                                            if ($relatedVariation) {
                                                                $variationPrice = (float) $relatedVariation->pricing_value;
                                                                $basePrice = $eavModel2->value;
                                                                if ($relatedVariation->is_percent) {
                                                                    $newPrice = $basePrice + (($variationPrice * $basePrice) / 100);
                                                                } else { //fixed type
                                                                    $newPrice = $basePrice + $variationPrice;
                                                                }
                                                                $eavModel2->value = $newPrice;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                            //save
                                            if (!$eavModel2->save()) {
                                                $this->errors[] = "{$className2}: " . UBMigrate::getStringErrors($eavModel2->getErrors());
                                            } else {
                                                $this->_traceInfo();
                                            }
                                        } else {
                                            //unset for special prices and related specia prices data
                                            if (in_array($model->attribute_id, array(
                                                $specialPriceAttrId1, 
                                                $specialPriceFromAttrId1, 
                                                $specialPriceToAttrId1
                                            ))) {
                                                $className2::model()->deleteAll($condition);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return true;
    }

    private function _migrateProductTaxClass($taxClassId1, &$model2)
    {
        $taxClass1 = Mage1TaxClass::model()->findByPk($taxClassId1);
        if ($taxClass1) {
            $m2Id = UBMigrate::getM2EntityId(5, 'tax_class', $taxClass1->class_id);
            $canReset = UBMigrate::RESET_YES;
            if (is_null($m2Id)) {
                $taxClass2 = Mage2TaxClass::model()->find(
                    "class_name = '".addslashes($taxClass1->class_name)."' AND class_type = '{$taxClass1->class_type}'"
                );
                if (!$taxClass2) {
                    $taxClass2 = new Mage2TaxClass();
                    $taxClass2->class_name = $taxClass1->class_name;
                    $taxClass2->class_type = $taxClass1->class_type;
                } else {
                    $canReset = UBMigrate::RESET_NO;
                }
            } else {
                $taxClass2 = Mage2TaxClass::model()->find("class_id = {$m2Id}");
                $taxClass2->class_name = $taxClass1->class_name;
                $taxClass2->class_type = $taxClass1->class_type;
            }
            //save/update
            if ($taxClass2->save()) {
                if (is_null($m2Id)) {
                    //save to map table
                    UBMigrate::log([
                        'entity_name' => $taxClass1->tableName(),
                        'm1_id' => $taxClass1->class_id,
                        'm2_id' => $taxClass2->class_id,
                        'm2_model_class' => get_class($taxClass2),
                        'm2_key_field' => 'class_id',
                        'can_reset' => $canReset,
                        'step_index' => $this->stepIndex
                    ]);
                }
                $this->_traceInfo();
                //update new product tax class_id
                $model2->value = $taxClass2->class_id;
            } else {
                $this->errors[] = get_class($taxClass2) . ": " . UBMigrate::getStringErrors($taxClass2->getErrors());
            }
        }

        return true;
    }

    private function _migrateCatalogProductGallery(
        $entityId,
        $entityId2,
        $mappingStores,
        $mappingAttributes,
        $keepOriginalId
    ) {
        /**
         * Table: catalog_product_entity_gallery
         */
        //get migrated store ids
        $strMigratedStoreIds = implode(',', array_keys($mappingStores));
        $galleryCon = "entity_id = {$entityId} AND store_id IN ({$strMigratedStoreIds})";
        $models = Mage1CatalogProductEntityGallery::model()->findAll($galleryCon);
        if ($models) {
            foreach ($models as $model) {
                $storeId2 = isset($mappingStores[$model->store_id]) 
                ? $mappingStores[$model->store_id] 
                : 0;
                $attributeId2 = isset($mappingAttributes[$model->attribute_id]) 
                ? $mappingAttributes[$model->attribute_id] 
                : 0;
                if ($attributeId2) {
                    $model2 = Mage2CatalogProductEntityGallery::model()->find(
                        "entity_id = {$entityId2} AND attribute_id = {$attributeId2} AND store_id = {$storeId2}"
                    );
                    if (!$model2) { //add new
                        $model2 = new Mage2CatalogProductEntityGallery();
                        $model2->value_id = null;
                        $model2->attribute_id = $attributeId2;
                        $model2->store_id = $storeId2;
                        $model2->entity_id = $entityId2;
                    }
                    $model2->position = $model->position;
                    $model2->value = $model->value;
                    //save/update
                    if (!$model2->save()) {
                        $this->errors[] = get_class($model2) . ": " . UBMigrate::getStringErrors($model2->getErrors());
                    } else {
                        $this->_traceInfo();
                    }
                }
            }
        }

        /**
         * Table: catalog_product_entity_media_gallery
         */
        $models = Mage1CatalogProductEntityMediaGallery::model()->findAll("entity_id = {$entityId}");
        if ($models) {
            foreach ($models as $model) {
                $attributeId2 = isset($mappingAttributes[$model->attribute_id]) 
                ? $mappingAttributes[$model->attribute_id] 
                : 0;
                $condition = "attribute_id = {$attributeId2} AND value = '".addslashes($model->value)."'";
                $model2 = Mage2CatalogProductEntityMediaGallery::model()->find($condition);
                if (!$model2) { //add new
                    $model2 = new Mage2CatalogProductEntityMediaGallery();
                    $model2->value_id = null;
                    $model2->attribute_id = $attributeId2;
                    $model2->media_type = 'image'; //default value
                    $model2->disabled = 0; //this is new field in Magento 2, Default value is 0
                }
                $model2->value = $model->value;
                //save/update
                if (!$model2->save()) {
                    $this->errors[] = get_class($model2) . ": " . UBMigrate::getStringErrors($model2->getErrors());
                } else {
                    $this->_traceInfo();
                }
                if ($model2->value_id) {
                    /**
                     * Table:catalog_product_entity_media_gallery_value
                     */
                    $galleryValues = Mage1CatalogProductEntityMediaGalleryValue::model()->findAll(
                        "value_id = {$model->value_id}"
                    );
                    if ($galleryValues) {
                        foreach ( $galleryValues as $galleryValue) {
                            $storeViewId2 = isset($mappingStores[$galleryValue->store_id]) 
                            ? $mappingStores[$galleryValue->store_id] 
                            : 0;
                            $con = "value_id = {$model2->value_id} AND store_id = {$storeViewId2}";
                            $con .= " AND label = '" . addslashes($galleryValue->label) . "'";
                            $con .= " AND position = {$galleryValue->position}";
                            $galleryValue2 = Mage2CatalogProductEntityMediaGalleryValue::model()->find($con);
                            if (!$galleryValue2) { //add new
                                $galleryValue2 = new Mage2CatalogProductEntityMediaGalleryValue();
                                $galleryValue2->value_id = $model2->value_id;
                                $galleryValue2->store_id = $storeViewId2;
                                $galleryValue2->entity_id = $entityId2;
                            }
                            $galleryValue2->label = $galleryValue->label;
                            $galleryValue2->position = $galleryValue->position;
                            $galleryValue2->disabled = $galleryValue->disabled;
                            //save/update
                            if (!$galleryValue2->save()) {
                                $this->errors[] = get_class($galleryValue2) . ": " . UBMigrate::getStringErrors($galleryValue2->getErrors());
                            } else {
                                $this->_traceInfo();
                            }
                        }
                    }
                    /**
                     * Table: catalog_product_entity_media_gallery_value_to_entity
                     * this table is new in Magento 2
                     */
                    $galleryValueToEntity2 = Mage2CatalogProductEntityMediaGalleryValueToEntity::model()->find(
                        "value_id = {$model2->value_id} AND entity_id = {$entityId2}"
                    );
                    if (!$galleryValueToEntity2) { //add new
                        $galleryValueToEntity2 = new Mage2CatalogProductEntityMediaGalleryValueToEntity();
                        $galleryValueToEntity2->value_id = $model2->value_id;
                        $galleryValueToEntity2->entity_id = $entityId2;
                        if (!$galleryValueToEntity2->save()) {
                            $this->errors[] = get_class($galleryValueToEntity2) . ": " . UBMigrate::getStringErrors($galleryValueToEntity2->getErrors());
                        } else {
                            $this->_traceInfo();
                        }
                    }
                }
            }
        }

        return true;
    }

    private function _migrateCatalogProductOptions($entityId, $entityId2, $mappingStores, $keepOriginalId)
    {
        /**
         * Table: catalog_product_option
         */
        $productOptions = Mage1CatalogProductOption::model()->findAll("product_id = {$entityId}");
        if ($productOptions) {
            foreach ($productOptions as $productOption) {
                $optionId2 = UBMigrate::getM2EntityId(
                    '5_product_option',
                    'catalog_product_option',
                    $productOption->option_id
                );
                if (is_null($optionId2)) {
                    //add new
                    $productOption2 = new Mage2CatalogProductOption();
                    foreach ($productOption2->attributes as $key => $value) {
                        if (isset($productOption->$key)) {
                            $productOption2->$key = $productOption->$key;
                        }
                    }
                    $productOption2->option_id = ($keepOriginalId) ? $productOption->option_id : null;
                    //because product id was changed
                    $productOption2->product_id = $entityId2;
                } else {
                    //update
                    $productOption2 = Mage2CatalogProductOption::model()->find("option_id = {$optionId2}");
                    foreach ($productOption2->attributes as $key => $value) {
                        if (isset($productOption->$key) AND (!in_array($key, array('option_id', 'product_id')))) {
                            $productOption2->$key = $productOption->$key;
                        }
                    }
                }
                //convert swatch type to dropdown type
                if ($productOption2->type == 'swatch') {
                    $productOption2->type = 'drop_down';
                }
                //save/update
                if (!$productOption2->save()) {
                    $this->errors[] = get_class($productOption2) . ": " . UBMigrate::getStringErrors($productOption2->getErrors());
                } else {
                    if (is_null($optionId2)) {
                        //save to map table
                        UBMigrate::log([
                            'entity_name' => $productOption->tableName(),
                            'm1_id' => $productOption->option_id,
                            'm2_id' => $productOption2->option_id,
                            'm2_model_class' => get_class($productOption2),
                            'm2_key_field' => 'option_id',
                            'can_reset' => UBMigrate::RESET_YES,
                            'step_index' => "5ProductOption"
                        ]);
                    }
                    $this->_traceInfo();
                }
                //migrate related data
                if ($productOption2->option_id) {
                    //migrate option type value
                    $this->_migrateCatalogProductOptionTypeValue(
                        $productOption->option_id,
                        $productOption2->option_id,
                        $mappingStores,
                        $keepOriginalId
                    );
                    /**
                     * Tables: catalog_product_option_price and catalog_product_option_title
                     * We have to migrate by migrated stores
                     */
                    $migratedStoreIds = array_keys($mappingStores);
                    foreach ($migratedStoreIds as $storeId) {
                        //migrate catalog product option price
                        $this->_migrateCatalogProductOptionPrice(
                            $productOption->option_id,
                            $productOption2->option_id,
                            $storeId,
                            $mappingStores[$storeId],
                            $keepOriginalId
                        );
                        //migrate catalog product option title
                        $this->_migrateCatalogProductOptionTitle(
                            $productOption->option_id,
                            $productOption2->option_id,
                            $storeId,
                            $mappingStores[$storeId],
                            $keepOriginalId
                        );
                    }
                }
            }
        }

        return true;
    }

    private function _migrateCatalogProductOptionPrice($optionId1, $optionId2, $storeId, $storeId2, $keepOriginalId)
    {
        /**
         * Table: catalog_product_option_price
         */
        $optionPrice = Mage1CatalogProductOptionPrice::model()->find(
            "option_id = {$optionId1} AND store_id = {$storeId}"
        );
        if ($optionPrice) {
            $optionPrice2 = Mage2CatalogProductOptionPrice::model()->find(
                "option_id = {$optionId2} AND store_id = {$storeId2}"
            );
            if (!$optionPrice2) {
                $optionPrice2 = new Mage2CatalogProductOptionPrice();
                $optionPrice2->option_price_id = null;
                $optionPrice2->option_id = $optionId2;
                $optionPrice2->store_id = $storeId2;
            }
            $optionPrice2->price = $optionPrice->price;
            $optionPrice2->price_type = $optionPrice->price_type;
            //save/update
            if (!$optionPrice2->save()) {
                $this->errors[] = get_class($optionPrice2) . ": " . UBMigrate::getStringErrors($optionPrice2->getErrors());
            } else {
                $this->_traceInfo();
            }
        }

        return true;
    }

    private function _migrateCatalogProductOptionTitle($optionId1, $optionId2, $storeId, $storeId2, $keepOriginalId)
    {
        /**
         * Table: catalog_product_option_title
         */
        $optionTitle = Mage1CatalogProductOptionTitle::model()->find(
            "option_id = {$optionId1} AND store_id = {$storeId}"
        );
        if ($optionTitle) {
            $optionTitle2 = Mage2CatalogProductOptionTitle::model()->find(
                "option_id = {$optionId2} AND store_id = {$storeId2}"
            );
            if (!$optionTitle2) {
                $optionTitle2 = new Mage2CatalogProductOptionTitle();
                $optionTitle2->option_title_id = null;
                $optionTitle2->option_id = $optionId2;
                $optionTitle2->store_id = $storeId2;
            }
            $optionTitle2->title = $optionTitle->title;
            //save/update
            if (!$optionTitle2->save()) {
                $this->errors[] = get_class($optionTitle2) . ": " . UBMigrate::getStringErrors($optionTitle2->getErrors());
            } else {
                $this->_traceInfo();
            }
        }

        return true;
    }

    private function _migrateCatalogProductOptionTypeValue($optionId1, $optionId2, $mappingStores, $keepOriginalId)
    {
        /**
         * Table: catalog_product_option_type_value
         */
        $optionTypeValues = Mage1CatalogProductOptionTypeValue::model()->findAll("option_id = {$optionId1}");
        if ($optionTypeValues) {
            foreach ($optionTypeValues as $optionTypeValue) {
                $m2Id = UBMigrate::getM2EntityId(
                    '5_product_option',
                    'catalog_product_option_type_value',
                    $optionTypeValue->option_type_id
                );
                if (is_null($m2Id)) {
                    $optionTypeValue2 = new Mage2CatalogProductOptionTypeValue();
                    $optionTypeValue2->option_type_id = null;
                    //because option_id was changed
                    $optionTypeValue2->option_id = $optionId2;
                } else {
                    $optionTypeValue2 = Mage2CatalogProductOptionTypeValue::model()->find("option_type_id = {$m2Id}");
                }
                $optionTypeValue2->sku = $optionTypeValue->sku;
                $optionTypeValue2->sort_order = $optionTypeValue->sort_order;
                //save/update
                if (!$optionTypeValue2->save()) {
                    $this->errors[] = get_class($optionTypeValue2) . ": " . UBMigrate::getStringErrors($optionTypeValue2->getErrors());
                } else {
                    if (is_null($m2Id)) {
                        //save to map table
                        UBMigrate::log([
                            'entity_name' => $optionTypeValue->tableName(),
                            'm1_id' => $optionTypeValue->option_type_id,
                            'm2_id' => $optionTypeValue2->option_type_id,
                            'm2_model_class' => get_class($optionTypeValue2),
                            'm2_key_field' => 'option_type_id',
                            'can_reset' => UBMigrate::RESET_YES,
                            'step_index' => "5ProductOption"
                        ]);
                    }
                    $this->_traceInfo();
                }
                if ($optionTypeValue2->option_type_id) {
                    $migratedStoreIds = array_keys($mappingStores);
                    foreach ($migratedStoreIds as $storeId) {
                        $storeId2 = isset($mappingStores[$storeId]) ? $mappingStores[$storeId] : 0;
                        //migrate catalog product option type title
                        $this->_migrateCatalogProductOptionTypeTitle(
                            $optionTypeValue->option_type_id,
                            $optionTypeValue2->option_type_id,
                            $storeId,
                            $storeId2,
                            $keepOriginalId
                        );
                        //migrate catalog product option type price
                        $this->_migrateCatalogProductOptionTypePrice(
                            $optionTypeValue->option_type_id,
                            $optionTypeValue2->option_type_id,
                            $storeId,
                            $storeId2,
                            $keepOriginalId
                        );
                    }
                }
            }
        }

        return true;
    }

    private function _migrateCatalogProductOptionTypePrice(
        $optionTypeId1,
        $optionTypeId2,
        $storeId,
        $storeId2,
        $keepOriginalId
    ) {
        /**
         * Table: catalog_product_option_type_price
         */
        $condition = "option_type_id = {$optionTypeId1} AND store_id = {$storeId}";
        $optionTypePrice = Mage1CatalogProductOptionTypePrice::model()->find($condition);
        if ($optionTypePrice) {
            $m2Id = UBMigrate::getM2EntityId(
                '5_product_option',
                'catalog_product_option_type_price',
                $optionTypePrice->option_type_price_id
            );
            if (is_null($m2Id)) {
                $optionTypePrice2 = new Mage2CatalogProductOptionTypePrice();
                foreach ($optionTypePrice2->attributes as $key => $value) {
                    if (isset($optionTypePrice->$key)) {
                        $optionTypePrice2->$key = $optionTypePrice->$key;
                    }
                }
                $optionTypePrice2->option_type_price_id = null;
                //because ids was changed
                $optionTypePrice2->option_type_id = $optionTypeId2;
                $optionTypePrice2->store_id = $storeId2;
            } else {
                $optionTypePrice2 = Mage2CatalogProductOptionTypePrice::model()->find("option_type_price_id = {$m2Id}");
                $optionTypePrice2->price = $optionTypePrice->price;
                $optionTypePrice2->price_type = $optionTypePrice->price_type;
            }
            //save/update
            if (!$optionTypePrice2->save()) {
                $this->errors[] = get_class($optionTypePrice2) . ": " . UBMigrate::getStringErrors($optionTypePrice2->getErrors());
            } else {
                if (is_null($m2Id)) {
                    //save to map table
                    UBMigrate::log([
                        'entity_name' => $optionTypePrice->tableName(),
                        'm1_id' => $optionTypePrice->option_type_price_id,
                        'm2_id' => $optionTypePrice2->option_type_price_id,
                        'm2_model_class' => get_class($optionTypePrice2),
                        'm2_key_field' => 'option_type_price_id',
                        'can_reset' => UBMigrate::RESET_YES,
                        'step_index' => "5ProductOption"
                    ]);
                }
                $this->_traceInfo();
            }
        }

        return true;
    }

    private function _migrateCatalogProductOptionTypeTitle(
        $optionTypeId1,
        $optionTypeId2,
        $storeId,
        $storeId2,
        $keepOriginalId
    ) {
        /**
         * Table: catalog_product_option_type_title
         */
        $condition = "option_type_id = {$optionTypeId1} AND store_id = {$storeId}";
        $optionTypeTitle = Mage1CatalogProductOptionTypeTitle::model()->find($condition);
        if ($optionTypeTitle) {
            $optionTypeTitle2 = Mage2CatalogProductOptionTypeTitle::model()->find(
                "option_type_id = {$optionTypeId2} AND store_id = {$storeId2}"
            );
            if (!$optionTypeTitle2) {
                $optionTypeTitle2 = new Mage2CatalogProductOptionTypeTitle();
                $optionTypeTitle2->option_type_title_id = null;
                $optionTypeTitle2->option_type_id = $optionTypeId2;
                $optionTypeTitle2->store_id = $storeId2;
            }
            $optionTypeTitle2->title = $optionTypeTitle->title;
            //save/update
            if (!$optionTypeTitle2->save()) {
                $this->errors[] = get_class($optionTypeTitle2) . ": " . UBMigrate::getStringErrors($optionTypeTitle2->getErrors());
            } else {
                $this->_traceInfo();
            }
        }

        return true;
    }

    private function _migrateCatalogProductStockItem($entityId, $entityId2, $sku, $keepOriginalId)
    {
        /**
         * Table: cataloginventory_stock_item
         */
        $stockItems = Mage1StockItem::model()->findAll("product_id = {$entityId}");
        $websiteId = 0; //default value is 0
        if ($stockItems) {
            foreach ($stockItems as $stockItem) {
                $uniqueCon = "product_id = {$entityId2} AND stock_id = {$stockItem->stock_id}";
                $stockItem2 = Mage2StockItem::model()->find($uniqueCon);
                if (!$stockItem2) {
                    //add new
                    $stockItem2 = new Mage2StockItem();
                    foreach ($stockItem2->attributes as $key => $value) {
                        if (isset($stockItem->$key)) {
                            $stockItem2->$key = $stockItem->$key;
                            if (in_array($key, array('notify_stock_qty', 'qty', 'max_sale_qty')) 
                                AND $stockItem2->$key 
                                AND strlen(trim($stockItem2->$key)) > 12) {
                                $stockItem2->$key = substr(trim($stockItem2->$key), 0, 12);
                            }
                        }
                    }
                    $stockItem2->item_id = null;
                    $stockItem2->product_id = $entityId2;
                    //this field is new in Magento 2
                    $stockItem2->website_id = $websiteId;
                    if ($stockItem2->low_stock_date === '0000-00-00 00:00:00' || empty($stockItem2->low_stock_date)) {
                        $stockItem2->low_stock_date = date("Y-m-d H:i:s");
                    }
                } else {
                    //update
                    foreach ($stockItem2->attributes as $key => $value) {
                        if (isset($stockItem->$key) AND !in_array($key, array('item_id','product_id','stock_id'))) {
                            $stockItem2->$key = $stockItem->$key;
                            if (in_array($key, array('notify_stock_qty', 'qty', 'max_sale_qty')) 
                                AND $stockItem2->$key 
                                AND strlen(trim($stockItem2->$key)) > 12) {
                                $stockItem2->$key = substr($stockItem2->$key, 0, 12);
                            }
                        }
                    }
                }
                //save/update
                if (!$stockItem2->save()) {
                    $this->errors[] = get_class($stockItem2) . ": " . UBMigrate::getStringErrors($stockItem2->getErrors());
                } else {
                    $this->_traceInfo();
                    /**
                     * Because the attribute code 'quantity_and_stock_status' is new added in Magento
                     * So, we will update value of that for each product in table catalog_product_entity_int
                     */
                    $entityTypeId = UBMigrate::getM2EntityTypeIdByCode(UBMigrate::PRODUCT_TYPE_CODE);
                    $attribute2 = UBMigrate::getMage2Attribute('quantity_and_stock_status', $entityTypeId);
                    $storeId2 = 0; //default value
                    $model2 = Mage2CatalogProductEntityInt::model()->find(
                        "entity_id = {$entityId2} AND attribute_id = {$attribute2->attribute_id} AND store_id = {$storeId2}"
                    );
                    if (!$model2) {
                        $model2 = new Mage2CatalogProductEntityInt();
                        $model2->attribute_id = $attribute2->attribute_id;
                        $model2->store_id = $storeId2;
                        $model2->entity_id = $entityId2;
                    }
                    $model2->value = $stockItem2->is_in_stock;
                    //save/update
                    if (!$model2->save()) {
                        $this->errors[] = get_class($model2) . ": " . UBMigrate::getStringErrors($model2->getErrors());
                    } else {
                        $this->_traceInfo();
                    }

                    /**
                     * Handle for data in table inventory_source_item. This is a new data table from M2.3.1
                     */
                    $msiEnabled = 1; //1- enabled MSI module, 0 - disabled MSI module
                    if ($msiEnabled) {
                        $condition = "source_code = 'default' AND sku = '" . addslashes($sku) . "'";
                        $model2 = Mage2InventorySourceItem::model()->find($condition);
                        if (!$model2) {
                            $model2 = new Mage2InventorySourceItem();
                            $model2->source_code = 'default';
                            $model2->sku = $sku;
                        }
                        $model2->quantity = $stockItem2->qty;
                        $model2->status = $stockItem2->is_in_stock;
                        //save/update
                        if (!$model2->save()) {
                            $this->errors[] = get_class($model2) . ": " . UBMigrate::getStringErrors($model2->getErrors());
                        } else {
                            $this->_traceInfo();
                        }
                    }
                }
            }
        }

        return true;
    }

    private function _migrateCatalogProductUrlRewrite($entityId, $entityId2, $mappingStores, $keepOriginalId)
    {
        /**
         * Table: url_rewrite
         */
        $strMigratedStoreIds = implode(',', array_keys($mappingStores));
        $condition = "product_id = {$entityId} AND store_id IN ({$strMigratedStoreIds})";
        $urls = Mage1UrlRewrite::model()->findAll($condition);
        if ($urls) {
            foreach ($urls as $url) {
                $storeId2 = isset($mappingStores[$url->store_id]) ? $mappingStores[$url->store_id] : null;
                if (!is_null($storeId2)) {
                    $url2 = Mage2UrlRewrite::model()->find("request_path = '{$url->request_path}' AND store_id = {$storeId2}");
                    if (!$url2) {
                        //add new
                        $url2 = new Mage2UrlRewrite();
                        $url2->entity_type = 'product';
                        $url2->entity_id = $entityId2;
                        $url2->store_id = $storeId2;
                        $url2->is_autogenerated = $url->is_system;
                        $url2->target_path = $url->target_path;
                        $url2->metadata = null;
                        if (!is_null($url->category_id)) {
                            $categoryId2 = UBMigrate::getM2EntityId(4, 'catalog_category_entity', $url->category_id);
                            if (!is_null($categoryId2)) {
                                //$url2->metadata = serialize(array('category_id' => $categoryId2));
                                $url2->metadata = json_encode(array('category_id' => $categoryId2));
                            }
                        }
                        //because product id was changed, we have to update new product id for target_path has format: catalog/product/view/id/...
                        if (preg_match('/catalog\/product\/view/i', $url2->target_path)) {
                            if (isset($categoryId2) AND !is_null($categoryId2)) {
                                $url2->target_path = "catalog/product/view/id/{$entityId2}/category/{$categoryId2}";
                            } else {
                                $url2->target_path = "catalog/product/view/id/{$entityId2}";
                            }
                        }
                    }
                    //update values
                    $url2->request_path = $url->request_path;
                    if (!is_null($url->category_id)) {
                        $categoryId2 = UBMigrate::getM2EntityId(4, 'catalog_category_entity', $url->category_id);
                        if (!is_null($categoryId2)) {
                            $url2->metadata = json_encode(array('category_id' => $categoryId2));
                        }
                    }
                    if ($url->options == 'RP') { //Permanent (301)
                        $url2->redirect_type = 301;
                    } elseif ($url->options == 'R') { // Temporary (302)
                        $url2->redirect_type = 302;
                    } else { //No redirect
                        $url2->redirect_type = 0;
                    }
                    $url2->description = $url->description;
                    //save/update
                    if ($url2->save()) {
                        $this->_traceInfo();
                    } else {
                        $this->errors[] = get_class($url2) . ": " . UBMigrate::getStringErrors($url2->getErrors());
                    }
                    //catalog_url_rewrite_product_category => this table is new in Magento 2
                    if ($url2->url_rewrite_id AND isset($categoryId2) AND !is_null($categoryId2)) {
                        $catalogUrl2 = Mage2CatalogUrlRewriteProductCategory::model()->find(
                            "url_rewrite_id = {$url2->url_rewrite_id}"
                        );
                        if (!$catalogUrl2) {
                            $catalogUrl2 = new Mage2CatalogUrlRewriteProductCategory();
                            $catalogUrl2->url_rewrite_id = $url2->url_rewrite_id;
                            $catalogUrl2->category_id = $categoryId2;
                            $catalogUrl2->product_id = $url2->entity_id;
                            if (!$catalogUrl2->save()) {
                                $this->errors[] = get_class($catalogUrl2) . ": " . UBMigrate::getStringErrors($catalogUrl2->getErrors());
                            } else {
                                $this->_traceInfo();
                            }
                        }
                    }
                }
            }
        }

        return true;
    }

    private function _migrateCatalogProductWebsite($productId1, $productId2, $mappingWebsites)
    {
        /**
         * Table: catalog_product_website
         */
        $strMigratedWebsiteIds = implode(',', array_keys($mappingWebsites));
        $condition = "product_id = {$productId1} AND website_id IN ({$strMigratedWebsiteIds})";
        $models = Mage1CatalogProductWebsite::model()->findAll($condition);
        if ($models) {
            foreach ($models as $model) {
                $websiteId2 = isset($mappingWebsites[$model->website_id]) ? $mappingWebsites[$model->website_id] : null;
                if (!is_null($websiteId2)) {
                    $model2 = Mage2CatalogProductWebsite::model()->find(
                        "product_id = {$productId2} AND website_id = {$websiteId2}"
                    );
                    if (!$model2) {
                        $model2 = new Mage2CatalogProductWebsite();
                        $model2->product_id = $productId2;
                        $model2->website_id = $websiteId2;
                        if (!$model2->save()) {
                            $this->errors[] = get_class($model2) . ": " . UBMigrate::getStringErrors($model2->getErrors());
                        } else {
                            $this->_traceInfo();
                        }
                    }
                }
            }
        }

        return true;
    }

    private function _migrateCatalogCategoryProduct($productId1, $productId2)
    {
        /**
         * Table: catalog_category_product
         */
        $models = Mage1CatalogCategoryProduct::model()->findAll("product_id = {$productId1}");
        if ($models) {
            foreach ($models as $model) {
                $categoryId2 = UBMigrate::getM2EntityId(4, 'catalog_category_entity', $model->category_id);
                if (!is_null($categoryId2)) {
                    $model2 = Mage2CatalogCategoryProduct::model()->find(
                        "product_id = {$productId2} AND category_id = {$categoryId2}"
                    );
                    if (!$model2) {
                        $model2 = new Mage2CatalogCategoryProduct();
                        $model2->category_id = $categoryId2;
                        $model2->product_id = $productId2;
                    }
                    $model2->position = $model->position;
                    if (!$model2->save()) {
                        $this->errors[] = get_class($model2) . ": " . UBMigrate::getStringErrors($model2->getErrors());
                    } else {
                        $this->_traceInfo();
                    }
                }
            }
        }

        return true;
    }

    private function _migrateCatalogProductLinks($models, $keepOriginalId)
    {
        /**
         * Table: catalog_product_link
         */
        foreach ($models as $model) {
            $productId2 = (!$keepOriginalId) 
            ? UBMigrate::getM2EntityId(5, 'catalog_product_entity', $model->product_id) 
            : $model->product_id;
            $linkedProductId2 = (!$keepOriginalId) 
            ? UBMigrate::getM2EntityId(5, 'catalog_product_entity', $model->linked_product_id) 
            : $model->linked_product_id;
            $linkTypeId2 = UBMigrate::getMage2ProductLinkTypeId($model->link_type_id);
            if ($productId2 && $linkedProductId2 && $linkTypeId2) {
                $condition = "link_type_id = {$linkTypeId2} AND product_id = {$productId2}";
                $condition .= " AND linked_product_id = {$linkedProductId2}";
                $model2 = Mage2CatalogProductLink::model()->find($condition);
                if (!$model2) { //add new
                    $model2 = new Mage2CatalogProductLink();
                    $model2->link_id = null;
                    $model2->product_id = $productId2;
                    $model2->linked_product_id = $linkedProductId2;
                    $model2->link_type_id = $linkTypeId2;
                    //save
                    if (!$model2->save()) {
                        $this->errors[] = get_class($model2) . ": " . UBMigrate::getStringErrors($model2->getErrors());
                    } else {
                        $this->_traceInfo();
                    }
                }
                //migrate related data
                if ($model2->link_id) {
                    //migrate product links eav data
                    $this->_migrateCatalogProductLinksEAV($model->link_id, $model2->link_id, $keepOriginalId);
                }
            }
        }

        return true;
    }

    private function _migrateCatalogProductLinksEAV($linkId1, $linkId2, $keepOriginalId)
    {
        $eavTables = [
            'catalog_product_link_attribute_decimal',
            'catalog_product_link_attribute_int',
            'catalog_product_link_attribute_varchar'
        ];
        foreach ($eavTables as $table) {
            $className = str_replace(' ', '', ucwords(str_replace('_', ' ', $table)));
            $className1 = "Mage1{$className}";
            $className2 = "Mage2{$className}";
            $items = $className1::model()->findAll("link_id = {$linkId1}");
            if ($items) {
                foreach ($items as $item) {
                    $productLinkAttributeId2 = UBMigrate::getMage2ProductLinkAttrId($item->product_link_attribute_id);
                    if ($productLinkAttributeId2) {
                        $condition = "product_link_attribute_id = {$productLinkAttributeId2} AND link_id = {$linkId2}";
                        $item2 = $className2::model()->find($condition);
                        if (!$item2) { //add new
                            $item2 = new $className2();
                            $item2->value_id = null;
                            $item2->product_link_attribute_id = $productLinkAttributeId2;
                            $item2->link_id = $linkId2;
                        }
                        //update value
                        $item2->value = $item->value;
                        //save/update
                        if (!$item2->save()) {
                            $this->errors[] = get_class($item2) . ": " . UBMigrate::getStringErrors($item2->getErrors());
                        } else {
                            $this->_traceInfo();
                        }
                    }
                }
            }
        }

        return true;
    }

    private function _migrateCatalogProductSuperLinks($models, $keepOriginalId)
    {
        /**
         * Table: catalog_product_super_link
         */
        foreach ($models as $model) {
            $productId2 = (!$keepOriginalId) 
            ? UBMigrate::getM2EntityId(5, 'catalog_product_entity', $model->product_id) 
            : $model->product_id;
            $parentId2 = (!$keepOriginalId) 
            ? UBMigrate::getM2EntityId(5, 'catalog_product_entity', $model->parent_id) 
            : $model->parent_id;
            if ($productId2 && $parentId2) {
                $condition = "product_id = {$productId2} AND parent_id = {$parentId2}";
                $model2 = Mage2CatalogProductSuperLink::model()->find($condition);
                if (!$model2) { //add new
                    $model2 = new Mage2CatalogProductSuperLink();
                    $model2->link_id = null;
                    $model2->product_id = $productId2;
                    $model2->parent_id = $parentId2;
                    //save
                    if (!$model2->save()) {
                        $this->errors[] = get_class($model2) . ": " . UBMigrate::getStringErrors($model2->getErrors());
                    } else {
                        $this->_traceInfo();
                    }
                }
            }
        }

        return true;
    }

    private function _migrateCatalogProductSuperAttributes(
        $models,
        $mappingWebsites,
        $mappingStores,
        $mappingAttributes,
        $keepOriginalId
    ) {
        /**
         * Table: catalog_product_super_attribute
         */
        foreach ($models as $model) {
            $attributeId2 = isset($mappingAttributes[$model->attribute_id]) 
            ? $mappingAttributes[$model->attribute_id] 
            : 0;
            $productId2 = (!$keepOriginalId)
                ? UBMigrate::getM2EntityId(5, 'catalog_product_entity', $model->product_id)
                : $model->product_id;

            if ($attributeId2 AND !is_null($productId2)) {
                $model2 = Mage2CatalogProductSuperAttribute::model()->find(
                    "product_id = {$productId2} AND attribute_id = {$attributeId2}"
                );
                if (!$model2) { //add new
                    $model2 = new Mage2CatalogProductSuperAttribute();
                    $model2->product_super_attribute_id = null;
                    $model2->product_id = $productId2;
                    $model2->attribute_id = $attributeId2;
                }
                $model2->position = $model->position;
                if (!$model2->save()) {
                    $this->errors[] = get_class($model2) . ": " . UBMigrate::getStringErrors($model2->getErrors());
                } else {
                    $this->_traceInfo();
                }
                //migrate related data
                if ($model2->product_super_attribute_id) {
                    /**
                     * catalog_product_super_attribute_label
                     */
                    $strMigratedStoreIds = implode(',', array_keys($mappingStores));
                    $condition = "product_super_attribute_id = {$model->product_super_attribute_id}";
                    $condition .= " AND store_id IN ({$strMigratedStoreIds})";
                    $superAttributeLabels = Mage1CatalogProductSuperAttributeLabel::model()->findAll($condition);
                    if ($superAttributeLabels) {
                        foreach ($superAttributeLabels as $superAttributeLabel) {
                            $storeId2 = isset($mappingStores[$superAttributeLabel->store_id]) 
                            ? $mappingStores[$superAttributeLabel->store_id] 
                            : 0;
                            $condition = "product_super_attribute_id = {$model2->product_super_attribute_id} AND store_id = {$storeId2}";
                            $superAttributeLabel2 = Mage2CatalogProductSuperAttributeLabel::model()->find($condition);
                            if (!$superAttributeLabel2) { //add new
                                $superAttributeLabel2 = new Mage2CatalogProductSuperAttributeLabel();
                                $superAttributeLabel2->value_id = null;
                                $superAttributeLabel2->product_super_attribute_id = $model2->product_super_attribute_id;
                                $superAttributeLabel2->store_id = $storeId2;
                            }
                            $superAttributeLabel2->use_default = $superAttributeLabel->use_default;
                            $superAttributeLabel2->value = $superAttributeLabel->value;
                            //save/update
                            if (!$superAttributeLabel2->save()) {
                                $this->errors[] = get_class($superAttributeLabel2) . ": " . UBMigrate::getStringErrors($superAttributeLabel2->getErrors());
                            } else {
                                $this->_traceInfo();
                            }
                        }
                    }
                }
            }
        }

        return true;
    }

    private function _migrateCatalogProductRelations($models, $keepOriginalId)
    {
        /**
         * Table: catalog_product_relation
         */
        foreach ($models as $model) {
            $parentId2 = (!$keepOriginalId) 
            ? UBMigrate::getM2EntityId(5, 'catalog_product_entity', $model->parent_id) 
            : $model->parent_id;
            $childId2 = (!$keepOriginalId) 
            ? UBMigrate::getM2EntityId(5, 'catalog_product_entity', $model->child_id) 
            : $model->child_id;
            if (!is_null($parentId2) AND !is_null($childId2)) {
                $model2 = Mage2CatalogProductRelation::model()->find(
                    "parent_id = {$parentId2} AND child_id = {$childId2}"
                );
                if (!$model2) {
                    $model2 = new Mage2CatalogProductRelation();
                    $model2->parent_id = $parentId2;
                    $model2->child_id = $childId2;
                    if (!$model2->save()) {
                        $this->errors[] = get_class($model2) . ": " . UBMigrate::getStringErrors($model2->getErrors());
                    } else {
                        $this->_traceInfo();
                    }
                }
            }
        }

        return true;
    }

    private function _migrateCatalogProductBundleOptions(
        $models,
        $mappingWebsites,
        $mappingStores,
        $keepOriginalId
    ) {
        /**
         * Table: catalog_product_bundle_option
         */
        foreach ($models as $model) {
            $optionId2 = UBMigrate::getM2EntityId(
                '5_product_option',
                'catalog_product_bundle_option',
                $model->option_id
            );
            $parentId2 = (!$keepOriginalId) 
            ? UBMigrate::getM2EntityId(5, 'catalog_product_entity', $model->parent_id) 
            : $model->parent_id;
            $canReset = UBMigrate::RESET_YES;
            if (is_null($optionId2)) {
                $model2 = new Mage2CatalogProductBundleOption();
                $model2->option_id = ($keepOriginalId) ? $model->option_id : null;
                $model2->parent_id = $parentId2;
                $model2->required = $model->required;
                $model2->position = $model->position;
                $model2->type = $model->type;
            } else { //update
                $model2 = Mage2CatalogProductBundleOption::model()->find("option_id = {$optionId2}");
                $model2->required = $model->required;
                $model2->position = $model->position;
                $model2->type = $model->type;
            }
            //save/update
            if (!$model2->save()) {
                $this->errors[] = get_class($model2) . ": " . UBMigrate::getStringErrors($model2->getErrors());
            } else {
                if (is_null($optionId2)) {
                    //save to map table
                    UBMigrate::log([
                        'entity_name' => $model->tableName(),
                        'm1_id' => $model->option_id,
                        'm2_id' => $model2->option_id,
                        'm2_model_class' => get_class($model2),
                        'm2_key_field' => 'option_id',
                        'can_reset' => $canReset,
                        'step_index' => "5ProductOption"
                    ]);
                }
                $this->_traceInfo();
            }

            //migrate related data
            if ($model2->option_id) {
                //get string migrated store ids
                $strMigratedStoreIds = implode(',', array_keys($mappingStores));
                /**
                 * Table: catalog_product_bundle_option_value
                 */
                $condition = "option_id = {$model->option_id} AND store_id IN ({$strMigratedStoreIds})";
                $bundleOptionValues = Mage1CatalogProductBundleOptionValue::model()->findAll($condition);
                if ($bundleOptionValues) {
                    foreach ($bundleOptionValues as $bundleOptionValue) {
                        $storeId2 = isset($mappingStores[$bundleOptionValue->store_id]) 
                        ? $mappingStores[$bundleOptionValue->store_id] 
                        : null;
                        if (!is_null($storeId2)) {
                            $condition = "option_id = {$model2->option_id} AND store_id = {$storeId2}";
                            $bundleOptionValue2 = Mage2CatalogProductBundleOptionValue::model()->find($condition);
                            if (!$bundleOptionValue2) { //add new
                                $bundleOptionValue2 = new Mage2CatalogProductBundleOptionValue();
                                $bundleOptionValue2->value_id = ($keepOriginalId) ? $bundleOptionValue->value_id : null;
                                $bundleOptionValue2->option_id = $model2->option_id;
                                $bundleOptionValue2->store_id = $storeId2;
                                //this field is new added from Magento ver.2.2.0
                                $bundleOptionValue2->parent_product_id = $parentId2;
                            }
                            $bundleOptionValue2->title = $bundleOptionValue->title;
                            //save/update
                            if (!$bundleOptionValue2->save()) {
                                $this->errors[] = get_class($bundleOptionValue2) . ": " . UBMigrate::getStringErrors($bundleOptionValue2->getErrors());
                            } else {
                                $this->_traceInfo();
                            }
                        }
                    }
                }

                /**
                 * Table: catalog_product_bundle_selection
                 */
                $condition = "option_id = {$model->option_id}";
                $bundleSelections = Mage1CatalogProductBundleSelection::model()->findAll($condition);
                if ($bundleSelections) {
                    foreach ($bundleSelections as $bundleSelection) {
                        $parentProductId2 = (!$keepOriginalId)
                            ? UBMigrate::getM2EntityId(5, 'catalog_product_entity', $bundleSelection->parent_product_id)
                            : $bundleSelection->parent_product_id;
                        $productId2 = (!$keepOriginalId)
                            ? UBMigrate::getM2EntityId(5, 'catalog_product_entity', $bundleSelection->product_id)
                            : $bundleSelection->product_id;
                        if (!is_null($parentProductId2) AND !is_null($productId2)) {
                            $m2Id = UBMigrate::getM2EntityId(
                                '5_product_option',
                                'catalog_product_bundle_selection',
                                $bundleSelection->selection_id
                            );
                            $canReset = UBMigrate::RESET_YES;
                            if (is_null($m2Id)) {
                                $bundleSelection2 = new Mage2CatalogProductBundleSelection();
                                $bundleSelection2->selection_id = ($keepOriginalId) 
                                ? $bundleSelection->selection_id 
                                : null;
                                $bundleSelection2->option_id = $model2->option_id;
                                $bundleSelection2->parent_product_id = $parentProductId2;
                                $bundleSelection2->product_id = $productId2;
                                $bundleSelection2->position = $bundleSelection->position;
                                $bundleSelection2->is_default = $bundleSelection->is_default;
                                $bundleSelection2->selection_price_type = $bundleSelection->selection_price_type;
                                $bundleSelection2->selection_price_value = $bundleSelection->selection_price_value;
                                $bundleSelection2->selection_qty = $bundleSelection->selection_qty;
                                $bundleSelection2->selection_can_change_qty = $bundleSelection->selection_can_change_qty;
                            } else { //update
                                $bundleSelection2 = Mage2CatalogProductBundleSelection::model()->find("selection_id = {$m2Id}");
                                $bundleSelection2->position = $bundleSelection->position;
                                $bundleSelection2->is_default = $bundleSelection->is_default;
                                $bundleSelection2->selection_price_type = $bundleSelection->selection_price_type;
                                $bundleSelection2->selection_price_value = $bundleSelection->selection_price_value;
                                $bundleSelection2->selection_qty = $bundleSelection->selection_qty;
                                $bundleSelection2->selection_can_change_qty = $bundleSelection->selection_can_change_qty;
                            }
                            //save/update
                            if (!$bundleSelection2->save()) {
                                $this->errors[] = get_class($bundleSelection2) . ": " . UBMigrate::getStringErrors($bundleSelection2->getErrors());
                            } else {
                                if (is_null($m2Id)) {
                                    //save to map table
                                    UBMigrate::log([
                                        'entity_name' => $bundleSelection->tableName(),
                                        'm1_id' => $bundleSelection->selection_id,
                                        'm2_id' => $bundleSelection2->selection_id,
                                        'm2_model_class' => get_class($bundleSelection2),
                                        'm2_key_field' => 'selection_id',
                                        'can_reset' => $canReset,
                                        'step_index' => "5ProductOption"
                                    ]);
                                }
                                $this->_traceInfo();
                            }

                            //migrate child data
                            if ($bundleSelection2->selection_id) {
                                /**
                                 * Table: catalog_product_bundle_selection_price
                                 */
                                $strMigratedWebsiteIds = implode(',', array_keys($mappingWebsites));
                                $condition = "selection_id = {$bundleSelection->selection_id} AND website_id IN ({$strMigratedWebsiteIds})";
                                $selectionPrices = Mage1CatalogProductBundleSelectionPrice::model()->findAll($condition);
                                if ($selectionPrices) {
                                    foreach ($selectionPrices as $selectionPrice) {
                                        $websiteId2 = isset($mappingWebsites[$selectionPrice->website_id]) 
                                        ? $mappingWebsites[$selectionPrice->website_id] 
                                        : null;
                                        if (!is_null($websiteId2)) {
                                            $selectionPrice2 = Mage2CatalogProductBundleSelectionPrice::model()->find(
                                                "selection_id = {$bundleSelection2->selection_id} AND website_id = {$websiteId2}"
                                            );
                                            if (!$selectionPrice2) {
                                                $selectionPrice2 = new Mage2CatalogProductBundleSelectionPrice();
                                                $selectionPrice2->selection_id = $bundleSelection2->selection_id;
                                                $selectionPrice2->website_id = $websiteId2;
                                                $selectionPrice2->parent_product_id = $parentProductId2;
                                            }
                                            $selectionPrice2->selection_price_type = $selectionPrice->selection_price_type;
                                            $selectionPrice2->selection_price_value = $selectionPrice->selection_price_value;

                                            if (!$selectionPrice2->save()) {
                                                $this->errors[] = get_class($selectionPrice2) . ": " . UBMigrate::getStringErrors($selectionPrice2->getErrors());
                                            } else {
                                                $this->_traceInfo();
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return true;
    }

    private function _migrateCatalogProductDownloadableLinks(
        $downloadableLinks,
        $mappingWebsites,
        $mappingStores,
        $keepOriginalId
    ) {
        /**
         * Table: downloadable_link
         */
        foreach ($downloadableLinks as $model) {
            $linkId2 = UBMigrate::getM2EntityId('5_product_download', 'downloadable_link', $model->link_id);
            $canReset = UBMigrate::RESET_YES;
            if (is_null($linkId2)) {
                $productId2 = (!$keepOriginalId)
                    ? UBMigrate::getM2EntityId(5, 'catalog_product_entity', $model->product_id)
                    : $model->product_id;
                $model2 = new Mage2DownloadableLink();
                foreach ($model2->attributes as $key => $value) {
                    if (isset($model->$key)) {
                        $model2->$key = $model->$key;
                    }
                }
                $model2->link_id = null;
                $model2->product_id = $productId2;
            } else {
                //update
                $model2 = Mage2DownloadableLink::model()->find("link_id = {$linkId2}");
                foreach ($model2->attributes as $key => $value) {
                    if (isset($model->$key) AND !in_array($key, array('link_id', 'product_id'))) {
                        $model2->$key = $model->$key;
                    }
                }
            }
            //save/update
            if (!$model2->save()) {
                $this->errors[] = get_class($model2) . ": " . UBMigrate::getStringErrors($model2->getErrors());
            } else {
                if (is_null($linkId2)) {
                    //save to map table
                    UBMigrate::log([
                        'entity_name' => $model->tableName(),
                        'm1_id' => $model->link_id,
                        'm2_id' => $model2->link_id,
                        'm2_model_class' => get_class($model2),
                        'm2_key_field' => 'link_id',
                        'can_reset' => $canReset,
                        'step_index' => "5ProductDownload"
                    ]);
                }
                $this->_traceInfo();
            }
            //migrate related data
            if ($model2->link_id) {
                /**
                 * Table: downloadable_link_price
                 */
                $strMigratedWebsiteIds = implode(',', array_keys($mappingWebsites));
                $linkPrices = Mage1DownloadableLinkPrice::model()->findAll(
                    "link_id = {$model->link_id} AND website_id IN ({$strMigratedWebsiteIds})"
                );
                if ($linkPrices) {
                    foreach ($linkPrices as $linkPrice) {
                        $websiteId2 = isset($mappingWebsites[$linkPrice->website_id]) 
                        ? $mappingWebsites[$linkPrice->website_id] 
                        : 0;
                        $linkPrice2 = Mage2DownloadableLinkPrice::model()->find(
                            "link_id = {$model2->link_id} AND website_id = {$websiteId2}"
                        );
                        if (!$linkPrice2) { //add new
                            $linkPrice2 = new Mage2DownloadableLinkPrice();
                            $linkPrice2->price_id = ($keepOriginalId) ? $linkPrice->price_id : null;
                            $linkPrice2->link_id = $model2->link_id;
                            $linkPrice2->website_id = $websiteId2;
                        }
                        $linkPrice2->price = $linkPrice->price;
                        if (!$linkPrice2->save()) {
                            $this->errors[] = get_class($linkPrice2) . ": " . UBMigrate::getStringErrors($linkPrice2->getErrors());
                        } else {
                            $this->_traceInfo();
                        }
                    }
                }
                /**
                 * Table: downloadable_link_title
                 */
                $strMigratedStoreIds = implode(',', array_keys($mappingStores));
                $linkTitles = Mage1DownloadableLinkTitle::model()->findAll(
                    "link_id = {$model->link_id} AND store_id IN ({$strMigratedStoreIds})"
                );
                if ($linkTitles) {
                    foreach ($linkTitles as $linkTitle) {
                        $storeId2 = isset($mappingStores[$linkTitle->store_id]) ? $mappingStores[$linkTitle->store_id] : 0;
                        $linkTitle2 = Mage2DownloadableLinkTitle::model()->find(
                            "link_id = {$model2->link_id} AND store_id = {$storeId2}"
                        );
                        if (!$linkTitle2) { //add new
                            $linkTitle2 = new Mage2DownloadableLinkTitle();
                            $linkTitle2->title_id = ($keepOriginalId) ? $linkTitle->title_id : null;
                            $linkTitle2->link_id = $model2->link_id;
                            $linkTitle2->store_id = $storeId2;
                        }
                        $linkTitle2->title = $linkTitle->title;
                        //save
                        if (!$linkTitle2->save()) {
                            $this->errors[] = get_class($linkTitle2) . ": " . UBMigrate::getStringErrors($linkTitle2->getErrors());
                        } else {
                            $this->_traceInfo();
                        }
                    }
                }
            }
        }

        return true;
    }

    private function _migrateCatalogProductDownloadableSamples($downloadSamples, $mappingStores, $keepOriginalId)
    {
        /**
         * Table: downloadable_sample
         */
        foreach ($downloadSamples as $model) {
            $productId2 = (!$keepOriginalId) 
            ? UBMigrate::getM2EntityId(5, 'catalog_product_entity', $model->product_id) 
            : $model->product_id;
            if (!is_null($productId2)) {
                $sampleFile = addslashes($model->sample_file);
                $model2 = Mage2DownloadableSample::model()->find(
                    "product_id = {$productId2} AND sample_file = '{$sampleFile}'"
                );
                if (!$model2) {
                    //add new
                    $model2 = new Mage2DownloadableSample();
                    foreach ($model2->attributes as $key => $value) {
                        if (isset($model->$key)) {
                            $model2->$key = $model->$key;
                        }
                    }
                    $model2->sample_id = ($keepOriginalId) ? $model->sample_id : null;
                    $model2->product_id = $productId2;
                } else {
                    //update
                    $model2->sample_url = $model->sample_url;
                    $model2->sample_file = $model->sample_file;
                    $model2->sample_type = $model->sample_type;
                    $model2->sort_order = $model->sort_order;
                }
                //save/update
                if (!$model2->save()) {
                    $this->errors[] = get_class($model2) . ": " . UBMigrate::getStringErrors($model2->getErrors());
                } else {
                    $this->_traceInfo();
                }
                //migrate related data
                if ($model2->sample_id) {
                    /**
                     * Table: downloadable_sample_title
                     */
                    $strMigratedStoreIds = implode(',', array_keys($mappingStores));
                    $condition = "sample_id = {$model->sample_id} AND store_id IN ({$strMigratedStoreIds})";
                    $sampleTitles = Mage1DownloadableSampleTitle::model()->findAll($condition);
                    if ($sampleTitles) {
                        foreach ($sampleTitles as $sampleTitle) {
                            $storeId2 = isset($mappingStores[$sampleTitle->store_id]) 
                            ? $mappingStores[$sampleTitle->store_id] 
                            : 0;
                            $sampleTitle2 = Mage2DownloadableSampleTitle::model()->find(
                                "sample_id = {$model2->sample_id} AND store_id = {$storeId2}"
                            );
                            if (!$sampleTitle2) {
                                //add new
                                $sampleTitle2 = new Mage2DownloadableSampleTitle();
                                $sampleTitle2->title_id = ($keepOriginalId) ? $sampleTitle->title_id : null;
                                $sampleTitle2->sample_id = $model2->sample_id;
                                $sampleTitle2->store_id = $storeId2;
                            }
                            $sampleTitle2->title = $sampleTitle->title;
                            if (!$sampleTitle2->save()) {
                                $this->errors[] = get_class($sampleTitle2) . ": " . UBMigrate::getStringErrors($sampleTitle2->getErrors());
                            } else {
                                $this->_traceInfo();
                            }
                        }
                    }
                }
            }
        }

        return true;
    }

    private function _getAssociatedProductIds1($parentId1) {
        $rs = [];
        if ($parentId1) {
            //get all child product simple product IDs in M1
            $criteria = new CDbCriteria();
            $criteria->condition = "parent_id = {$parentId1}";
            $criteria->order = "link_id ASC";
            $productSuperLinks = Mage1CatalogProductSuperLink::model()->findAll($criteria);
            if ($productSuperLinks) {
                foreach ($productSuperLinks as $productSuperLink) {
                    if ($productSuperLink->product_id > 0) {
                        $rs[] = $productSuperLink->product_id;
                    }
                }
            }
        }

        return $rs;
    }

    private function _getAssociatedSuperAttrs1($parentId1) {
        $rs = [];
        if ($parentId1) {
            $criteria = new CDbCriteria();
            $criteria->condition = "product_id = {$parentId1}";
            $criteria->order = "product_super_attribute_id ASC";
            $models = Mage1CatalogProductSuperAttribute::model()->findAll($criteria);
            $rs = $models;
        }

        return $rs;
    }

    private function _getAllDeltaProductIds($deltaCondition) {
        $strIds = "";
        $products = UBMigrate::getListObjects('Mage1CatalogProductEntity', $deltaCondition);
        if ($products) {
            $ids = [];
            foreach ($products as $product) {
                $ids[] = $product->entity_id;
            }
            $strIds = implode(",", $ids);
        }

        return $strIds;
    }

    private function _traceInfo()
    {
        if ($this->isCLI) {
            echo ".";
        }
    }
}
