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

use Magento\Backend\Block\Template\Context;
use Magento\Directory\Model\ResourceModel\Country\Collection as CountryCollection;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class CountryHelper
 * @package Eltrino\Region\Helper
 */
class CountryHelper
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var DisabledRegionHelper
     */
    private $disabledRegionHelper;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var CountryCollection
     */
    private $countryCollection;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * CountryHelper constructor.
     * @param DisabledRegionHelper $disabledRegionHelper
     * @param Context $context
     * @param CountryCollection $countryCollection
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        DisabledRegionHelper $disabledRegionHelper,
        Context $context,
        CountryCollection $countryCollection,
        ResourceConnection $resourceConnection,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->disabledRegionHelper = $disabledRegionHelper;
        $this->context = $context;
        $this->countryCollection = $countryCollection;
        $this->resourceConnection = $resourceConnection;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get countries
     *
     * @return array
     */
    public function getCountries()
    {
        $connection = $this->resourceConnection->getConnection();

        $allowedConfigCountries = $this->getAllCountriesAllowedInConfig();

        $select = $connection->select()->from(
            ['d' => $this->resourceConnection->getTableName('directory_country_region')],
            ['d.country_id']
        )
            ->group('d.country_id')
            ->where('d.country_id IN (?)', $allowedConfigCountries);

        $allowCountries = $connection->fetchCol($select);

        $result = $this->countryCollection->addFieldToFilter("country_id", ['in' => $allowCountries])
            ->toOptionArray(DisabledRegionHelper::EMPTY_SELECT_LABEL);

        return $result;
    }

    /**
     * Get all allowed countries in config
     *
     * @return array
     */
    public function getAllCountriesAllowedInConfig()
    {
        $allowConfigCountries = [];

        foreach ($this->context->getStoreManager()->getStores() as $store) {
            $allowConfigCountries = array_merge($allowConfigCountries, $this->getStoreAllowedCountries($store));
        }
        return $allowConfigCountries;
    }

    /**
     * Get store allowed countries
     *
     * @param $store
     * @return array
     */
    public function getStoreAllowedCountries($store)
    {
        return explode(',',
            (string)$this->scopeConfig->getValue(
                'general/country/allow',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $store
            )
        );
    }
}
