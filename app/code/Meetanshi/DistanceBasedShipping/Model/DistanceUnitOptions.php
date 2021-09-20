<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Meetanshi\DistanceBasedShipping\Model;

use Magento\Framework\Option\ArrayInterface;

class DistanceUnitOptions implements ArrayInterface
{
    const DistanceUnitKilometers = 'kilometers';
    const DistanceUnitMeters = 'meters';
    const DistanceUnitMiles = 'miles';
    const DistanceUnitYards = 'yards';

    public function toOptionArray()
    {
        return [
            ['value' => self::DistanceUnitKilometers, 'label' => __('Kilometres')],
            ['value' => self::DistanceUnitMeters, 'label' => __('Meters')],
            ['value' => self::DistanceUnitMiles, 'label' => __('Miles')],
            ['value' => self::DistanceUnitYards, 'label' => __('Yards')]
        ];
    }

    public function toArray()
    {
        return [
            self::DistanceUnitKilometers => __('Kilometres'),
            self::DistanceUnitMeters => __('Meters'),
            self::DistanceUnitMiles => __('Miles'),
            self::DistanceUnitYards => __('Yards')
        ];
    }
}
