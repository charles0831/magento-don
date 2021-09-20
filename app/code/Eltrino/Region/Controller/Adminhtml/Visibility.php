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

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Directory\Model\Country as DirectoryCountry;
use Magento\Backend\Model\View\Result\PageFactory as PageFactory;
use Magento\Backend\App\AbstractAction;
use Magento\Framework\Locale\ListsInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Eltrino\Region\Model\ResourceModel\DisabledRegion as DisabledRegionResource;
use Eltrino\Region\Model\DisabledRegionFactory;

/**
 * Class Visibility
 * @package Eltrino\Region\Controller\Adminhtml
 */
abstract class Visibility extends AbstractAction
{
    /**
     * Locale model
     *
     * @var \Magento\Framework\Locale\ListsInterface
     */
    protected $localeLists;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var DisabledRegionResource
     */
    protected $disabledRegionResource;

    /**
     * @var DisabledRegionFactory
     */
    protected $disabledRegionFactory;

    /**
     * @var DirectoryCountry
     */
    protected $directoryCountry;

    /**
     * @var TypeListInterface
     */
    protected $cacheTypeList;

    /**
     * @param ListsInterface $localeLists
     * @param Context $context
     * @param Registry $coreRegistry
     * @param PageFactory $resultPageFactory
     * @param DisabledRegionResource $disabledRegionResource
     * @param DirectoryCountry $directoryCountry
     * @param TypeListInterface $cacheTypeList
     * @param DisabledRegionFactory $disabledRegionFactory
     */
    public function __construct(
        ListsInterface $localeLists,
        Context $context,
        Registry $coreRegistry,
        PageFactory $resultPageFactory,
        DirectoryCountry $directoryCountry,
        DisabledRegionResource $disabledRegionResource,
        TypeListInterface $cacheTypeList,
        DisabledRegionFactory $disabledRegionFactory
    ) {
        parent::__construct($context);
        $this->cacheTypeList = $cacheTypeList;
        $this->localeLists = $localeLists;
        $this->disabledRegionFactory = $disabledRegionFactory;
        $this->disabledRegionResource = $disabledRegionResource;
        $this->directoryCountry = $directoryCountry;
        $this->resultPageFactory = $resultPageFactory;
        $this->_coreRegistry = $coreRegistry;
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
     * Initialize Layout and set breadcrumbs
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    protected function createPage()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->initLayout();
        $resultPage->setActiveMenu('Eltrino_Region::region_visibility')
            ->addBreadcrumb(__('Region Manager'), __('Region Manager'));
        return $resultPage;
    }
}
