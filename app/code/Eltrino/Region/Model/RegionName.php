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
namespace Eltrino\Region\Model;

use Magento\Framework\Model\AbstractModel;
use Eltrino\Region\Model\Resource\Entity as EntityResource;

/**
 * Model for configuration step
 *
 * @category   Eltrino
 * @package    Eltrino_Region
 */
class RegionName extends AbstractModel
{
    /**
     * Class Constructor
     */
    protected function _construct()
    {
        $this->_init('Eltrino\Region\Model\ResourceModel\RegionName');
    }
}
