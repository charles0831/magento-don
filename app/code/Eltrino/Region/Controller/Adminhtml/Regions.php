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
namespace Eltrino\Region\Controller\Adminhtml;

use Magento\Backend\App\AbstractAction;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Directory\Model\Country as DirectoryCountry;
use Magento\Directory\Model\Region as DirectoryRegion;
use Magento\Directory\Model\RegionFactory as DirectoryRegionFactory;
use Magento\Directory\Model\ResourceModel\Region\Collection as RegionCollection;
use Magento\Framework\View\Result\PageFactory;

abstract class Regions extends AbstractAction
{

    /**
     * @var RegionCollection
     */
    protected $regionCollection;

    /**
     * Core registry
     *
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @var DirectoryCountry
     */
    protected $directoryCountry;

    /**
     * @var DirectoryRegion
     */
    protected $directoryRegion;

    /**
     * @var DirectoryRegionFactory
     */
    protected $directoryRegionFactory;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * Initialize Layout and set breadcrumbs
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    protected function createPage()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->initLayout();
        $resultPage->setActiveMenu('Eltrino_Region::region_regions')
            ->addBreadcrumb(__('Region Manager'), __('Region Manager'));
        return $resultPage;
    }

    /**
     * Get Country Model
     * @return DirectoryCountry
     */
    protected function getCountryModel()
    {
        return $this->directoryCountry;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        // TODO 
        return $this->_authorization->isAllowed('Eltrino_Region::manage_tax');
    }
}
