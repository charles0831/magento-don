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

namespace Eltrino\Region\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Checkout\Block\Checkout\AttributeMerger;
use Magento\Checkout\Block\Onepage;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Eltrino\Region\Model\Plugin;

class PluginTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Plugin
     */
    protected $plugin;

    /**
     * @var \Eltrino\Region\Helper\DisabledRegionHelper
     */
    protected $disabledRegionHelper;

    /**
     * @var AttributeMerger;
     */
    protected $merger;

    /**
     * @var Onepage
     */
    protected $onpage;

    /**
     * @var DirectoryHelper
     */
    protected $directoryHelper;

    public function setUp()
    {
        $this->disabledRegionHelper = $this->getMockBuilder('Eltrino\Region\Helper\DisabledRegionHelper')
            ->disableOriginalConstructor()
            ->setMethods(['getAvailableRegionOptions', 'getAllDisabledRegions'])
            ->getMock();

        $this->merger = $this->getMockBuilder('Magento\Checkout\Block\Checkout\AttributeMerger')
            ->disableOriginalConstructor()
            ->getMock();

        $this->onpage = $this->getMockBuilder('Magento\Checkout\Block\Onepage')
            ->disableOriginalConstructor()
            ->getMock();

        $this->directoryHelper = $this->getMockBuilder('Magento\Directory\Helper\Data')
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);

        $this->plugin = $objectManager->getObject('Eltrino\Region\Model\Plugin', [
            'disabledRegionHelper' => $this->disabledRegionHelper,
        ]);
    }

    public function testBeforeMerge()
    {
        $elements = ['region_id' => ['options' => 'Options']];

        $this->disabledRegionHelper->expects($this->once())
            ->method('getAvailableRegionOptions')
            ->willReturn('DisabledOptions');

        $result = $this->plugin->beforeMerge($this->merger, $elements, '', 'billing', []);

        $this->assertEquals($result[0]['region_id']['options'], 'DisabledOptions');
    }

    public function testAfterGetCheckoutConfig()
    {
        $output = [
            'customerData' => [
                'addresses' => [
                    ['region_id' => 1, 'address' => 'Address 1'],
                    ['region_id' => 2, 'address' => 'Address 2'],
                    ['region_id' => 3, 'address' => 'Address 3'],
                    ['region_id' => 4, 'address' => 'Address 4'],
                ]
            ]
        ];

        $this->disabledRegionHelper->expects($this->once())
            ->method('getAllDisabledRegions')
            ->willReturn([1, 2, 4]);

        $result = $this->plugin->afterGetCheckoutConfig($this->onpage, $output);

        $addresses = &$output['customerData']['addresses'];

        unset($addresses[0], $addresses[1], $addresses[3]);

        $this->assertEquals($result['customerData']['addresses'], $output['customerData']['addresses']);
    }

    public function testAfterGetRegionData()
    {
        $regions = [
            'US' => [
                1 => 'Region 1',
                2 => 'Region 2',
                3 => 'Region 3',
                4 => 'Region 4',
                ],
            'DE' => [
                21 => 'Region 21',
                22 => 'Region 22',
                23 => 'Region 23',
                24 => 'Region 24',
            ],
        ];

        $expectResult = [
            'US' => [
                3 => 'Region 3',
            ],
            'DE' => [
                22 => 'Region 22',
                23 => 'Region 23',
            ],
        ];

        $this->disabledRegionHelper->expects($this->once())
            ->method('getAllDisabledRegions')
            ->willReturn([1, 2, 4, 21, 24]);

        $result = $this->plugin->afterGetRegionData($this->directoryHelper, $regions);
        $this->assertEquals($result, $expectResult);
    }

}

