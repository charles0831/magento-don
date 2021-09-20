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

namespace Eltrino\Region\Helper;

use Magento\Customer\Model\Data\Address;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory as DirectoryRegionCollectionFactory;
use Eltrino\Region\Model\ResourceModel\DisabledRegion as DisabledRegionResource;
use Eltrino\Region\Model\ResourceModel\DisabledRegion\Collection as DisabledRegionCollection;

/**
 * Class DisabledRegionHelper
 * @package Eltrino\Region\Helper
 */
class DisabledRegionHelper
{
    /**
     * Path to Common Settings
     */
    const XML_PATH_COMMON_SETTINGS = 'eltrino_region/common_settings/';

    /**
     * Empty label
     */
    const EMPTY_SELECT_LABEL = '--Please Select--';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var DirectoryRegionCollectionFactory
     */
    protected $directoryRegionCollectionFactory;

    /**
     * @var DisabledRegionResource
     */
    protected $disabledRegionResource;

    /**
     * @var DisabledRegionCollection
     */
    protected $disabledRegionCollection;

    /**
     * @var array
     */
    protected $disabledRegionsIds = [];

    /**
     * @param DisabledRegionCollection $disabledRegionCollection
     * @param ScopeConfigInterface $scopeConfig
     * @param DirectoryRegionCollectionFactory $directoryRegionCollectionFactory
     * @param DisabledRegionResource $disabledRegionResource
     */
    public function __construct(
        DisabledRegionCollection $disabledRegionCollection,
        ScopeConfigInterface $scopeConfig,
        DirectoryRegionCollectionFactory $directoryRegionCollectionFactory,
        DisabledRegionResource $disabledRegionResource
    ) {
        $this->disabledRegionCollection = $disabledRegionCollection;
        $this->disabledRegionResource = $disabledRegionResource;
        $this->scopeConfig = $scopeConfig;
        $this->directoryRegionCollectionFactory = $directoryRegionCollectionFactory;
    }

    /**
     * Get Common Settings
     *
     * @param $countryId
     * @return array
     */
    public function getCommonSettingsList($countryId)
    {
        if (empty($optionArray)) {
            $optionArray[] = [
                'value' => '',
                'label' => '--Please Select--'
            ];

            $commonSettingsArr = $this->scopeConfig->getValue(self::XML_PATH_COMMON_SETTINGS . $countryId);

            if ($commonSettingsArr) {
                foreach ($commonSettingsArr as $itemValue) {
                    if (!isset($itemValue['label']) || !isset($itemValue['regions_code'])) {
                        continue;
                    }
                    $regionIds = [];
                    $regionsCollection = $this->directoryRegionCollectionFactory->create()
                        ->addCountryFilter($countryId)
                        ->addRegionCodeFilter(array_keys($itemValue['regions_code']));

                    foreach ($regionsCollection as $region) {
                        $regionIds[] = $region->getId();
                    }
                    $optionArray[] = [
                        'value' => implode(',', $regionIds),
                        'label' => $itemValue['label']
                    ];
                }
            } else {
                $optionArray = [
                    'value' => '-- Not Provided --',
                    'label' => '-- Not Provided --'
                ];
            }
        }

        return $optionArray;
    }

    /**
     * Get Regions
     *
     * @param $countryId
     * @return array
     */
    public function getRegionWithDisabledRegionOptions($countryId)
    {
        $options = $this->directoryRegionCollectionFactory->create()->addCountryFilter($countryId)->toOptionArray();

        $disabledRegions = $this->getDisabledRegionByCountry($countryId);

        foreach ($options as &$region) {
            if (in_array($region['value'], $disabledRegions)) {
                $region['selected'] = true;
            }
        }

        return $options;
    }

    public function getDisabledRegionByCountry($countryId)
    {
        return $this->disabledRegionResource->getDisabledRegionIdsByCountry($countryId);
    }

    /**
     * Get Available Regions
     *
     * @param $stepType
     * @return array
     */
    public function getAvailableRegionOptions()
    {
        if (empty($options)) {
            $options = $this->directoryRegionCollectionFactory->create()->addFieldToFilter(
                'main_table.region_id',
                [
                    'nin' => $this->getAllDisabledRegions()
                ]
            )->toOptionArray();
        }

        return $options;
    }

    /**
     * @return array
     */
    public function getAllDisabledRegions()
    {
        if (empty($this->disabledRegionsIds)) {
            $this->disabledRegionsIds = $this->disabledRegionCollection->getColumnValues('region_id');
        }

        return $this->disabledRegionsIds;
    }

    /**
     * @param Address $address
     * @return bool
     */
    public function isAddressForbidden($address)
    {
        $disabledRegionsIds = $this->getAllDisabledRegions();
        $addressRegionId = $address->getRegionId();

        if (isset($addressRegionId) && in_array($addressRegionId, $disabledRegionsIds)) {
            return true;
        }

        return false;
    }

    /**
     * Get Regions
     *
     * @param $countryId
     * @return array
     */
    public function getRegions($countryId)
    {
        if ($countryId) {
            $regions = $this->getRegionWithDisabledRegionOptions($countryId);
            array_shift($regions);
            return $regions;
        } else {
            return "";
        }
    }

    /**
     * Check is commons setting selected
     *
     * @param $commonSettings
     * @return string
     */
    public function checkCommonSettings($commonSettings, $countryId)
    {
        if (isset($commonSettings[0])) {
            $regions = $this->getDisabledRegionByCountry($countryId);
            foreach ($commonSettings as $commonSetting) {
                if ($commonSetting['value'] != '') {
                    $settings = explode(',', $commonSetting['value']);
                    for ($i = 0; $i < count($settings); $i++) {
                        if (count($settings) == count($regions) && empty(array_diff($settings, $regions))) {
                            return $commonSetting['value'];
                        }
                    }
                }
            }
        }
        return '';
    }
}
