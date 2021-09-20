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

use Magento\Framework\Event\ObserverInterface;

/**
 * Class Observer
 * @package Eltrino\Region\Model
 */
class Observer implements ObserverInterface{

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;
    protected $_state;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\App\State $state
    ) {
        $this->objectManager = $objectManager;
        $this->_state = $state;
    }
    
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if(!($observer->getCollection() instanceof \Magento\Directory\Model\ResourceModel\Region\Collection)){
            return;
        }
        $regionCollection = $observer->getCollection();
        $forbidden = $this->objectManager->create('\Eltrino\Region\Model\ResourceModel\DisabledRegion\Collection')->getColumnValues('region_id');
        foreach ($regionCollection->getItems() as $key => $region) {
            if (!$region->getRegionId()) {
                continue;
            }
            if(in_array($region->getRegionId(),$forbidden)){
                $regionCollection->removeItemByKey($key);
            }
        }
    }
}