<?php
/**
 * Remove addresses with forbidden to show countries or regions
 * in multishipping checkout
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
 * @copyright   Copyright (c) 2019 Eltrino LLC. (http://eltrino.com)
 * @license     http://eltrino.com/license-eula.txt  Eltrino LLC EULA
 */

namespace Eltrino\Region\Plugin\Multishipping\Block\Checkout\Address;

use Eltrino\Region\Helper\DisabledRegionHelper;
use Magento\Multishipping\Block\Checkout\Address\Select;

/**
 * Class SelectPlugin
 *
 * @package Eltrino\Region\Plugin\Multishipping\Block\Checkout\Address
 */
class SelectPlugin
{
    /**
     * @var DisabledRegionHelper
     */
    protected $disabledRegionHelper;

    /**
     * Plugin constructor.
     *
     * @param DisabledRegionHelper $disabledRegionHelper
     */
    public function __construct(
        DisabledRegionHelper $disabledRegionHelper
    ) {
        $this->disabledRegionHelper = $disabledRegionHelper;
    }

    /**
     * @param Select $subject
     * @param $result
     * @return array
     */
    public function afterGetAddress(Select $subject, $result)
    {
        if (!is_array($result)) {
            return $result;
        }
        foreach ($result as $key => $address) {
            if ($this->disabledRegionHelper->isAddressForbidden($address)) {
                unset($result[$key]);
            }
        }
        return $result;
    }
}