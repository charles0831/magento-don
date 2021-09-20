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
namespace Eltrino\Region\Ui\Component\Filters\Type;

use Magento\Ui\Component\Filters\Type\Search as MageSearch;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Ui\Component\Filters\FilterModifier;
use Magento\Directory\Model\ResourceModel\Country\Collection as CountryCollection;

/**
 * Class Search
 * @package Eltrino\Region\Ui\Component\Filters\Type
 */
class Search extends MageSearch
{
    /**
     * @var CountryCollection
     */
    protected $countryCollection;

    /**
     * @param CountryCollection $countryCollection
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param FilterBuilder $filterBuilder
     * @param FilterModifier $filterModifier
     * @param array $components
     * @param array $data
     */
    public function __construct(
        CountryCollection $countryCollection,
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        FilterBuilder $filterBuilder,
        FilterModifier $filterModifier,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $filterBuilder, $filterModifier, $components, $data);
        $this->countryCollection = $countryCollection;
    }

    /**
     * Transfer filters to dataProvider
     *
     * @return void
     */
    protected function applyFilter()
    {
        $value = $this->getContext()->getRequestParam('search');

        if ($value) {
            $filter = $this->filterBuilder->setConditionType('in')
                ->setField($this->getName())
                ->setValue($this->getCountryCodesByKeyword(strtolower($value)))
                ->create();

            $this->getContext()->getDataProvider()->addFilter($filter);
        }
    }

    /**
     * Get country codes after search by keyword.
     */
    protected function getCountryCodesByKeyword($value)
    {
        $codes = [];
        foreach ($this->countryCollection->getItems() as $country) {
            if (strpos(strtolower($country->getName()), $value) !== false) {
                $codes[] = $country->getId();
            }
        }
        return $codes;

    }
}
