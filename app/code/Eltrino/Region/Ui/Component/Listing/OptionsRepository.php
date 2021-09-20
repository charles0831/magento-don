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
namespace Eltrino\Region\Ui\Component\Listing;

use Magento\Directory\Model\ResourceModel\Country\Collection as CountryCollection;

/**
 * Class OptionsRepository
 * @package Eltrino\Region\Ui\Component\Listing
 */
class OptionsRepository
{
    /** @var [] */
    protected $options;

    /**
     * @var CountryCollection
     */
    protected $countryCollection;

    /**
     * @param CountryCollection $countryCollection
     */
    public function __construct(
        CountryCollection $countryCollection
    ) {
        $this->countryCollection = $countryCollection;
    }

    /**
     * @return array
     */
    public function getList()
    {
        if (!$this->options) {
            $this->options[] = $this->getCountryNameOptions();
        }
        return $this->options;
    }

    protected function getCountryNameOptions()
    {
        return [
            'component_name' => 'country_name',
            'options'=> $this->countryCollection->toOptionArray()
        ];
    }
}
