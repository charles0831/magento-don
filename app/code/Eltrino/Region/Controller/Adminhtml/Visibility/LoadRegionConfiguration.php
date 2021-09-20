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
namespace Eltrino\Region\Controller\Adminhtml\Visibility;

use Eltrino\Region\Helper\DisabledRegionHelper;
use Magento\Backend\App\AbstractAction;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;

/**
 * Class LoadRegionConfiguration
 * @package Eltrino\Region\Controller\Adminhtml\Visibility
 */
class LoadRegionConfiguration extends AbstractAction
{
    /**
     * @var DisabledRegionHelper
     */
    protected $disabledRegionHelper;

    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param DisabledRegionHelper $disabledRegionHelper
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        DisabledRegionHelper $disabledRegionHelper
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->disabledRegionHelper = $disabledRegionHelper;
        parent::__construct($context);

    }

    /**
     * Execute
     */
    public function execute()
    {
        $countryCode = $this->getRequest()->getParam('countryCode');
        $regions = $this->disabledRegionHelper->getRegionWithDisabledRegionOptions($countryCode);
        array_shift($regions);
        $commonSettings = $this->disabledRegionHelper->getCommonSettingsList($countryCode);

        $this->getResponse()->setHeader('Content-Type', 'application/json', true);
        $this->getResponse()->setBody(
            \Zend_Json::encode(['regions' => $regions, 'commonsettings' => $commonSettings])
        );
    }
}
