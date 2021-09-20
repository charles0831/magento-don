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

use Eltrino\Region\Controller\Adminhtml\Visibility;
use Eltrino\Region\Model\DisabledRegionFactory;
use Eltrino\Region\Model\ResourceModel\DisabledRegion as DisabledRegionResource;
use Eltrino\Region\Helper\CountryHelper;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\PageFactory;
use Magento\Directory\Model\Country as DirectoryCountry;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Locale\ListsInterface;
use Magento\Framework\Registry;

/**
 * Class Edit
 * @package Eltrino\Region\Controller\Adminhtml\Visibility
 */
class Edit extends Visibility
{
    /**
     * @var CountryHelper
     */
    private $countryHelper;

    /**
     * Edit constructor.
     * @param ListsInterface $localeLists
     * @param Context $context
     * @param Registry $coreRegistry
     * @param PageFactory $resultPageFactory
     * @param DirectoryCountry $directoryCountry
     * @param DisabledRegionResource $disabledRegionResource
     * @param TypeListInterface $cacheTypeList
     * @param DisabledRegionFactory $disabledRegionFactory
     * @param CountryHelper $countryHelper
     */
    public function __construct(
        ListsInterface $localeLists,
        Context $context,
        Registry $coreRegistry,
        PageFactory $resultPageFactory,
        DirectoryCountry $directoryCountry,
        DisabledRegionResource $disabledRegionResource,
        TypeListInterface $cacheTypeList,
        DisabledRegionFactory $disabledRegionFactory,
        CountryHelper $countryHelper
    ) {
        $this->countryHelper = $countryHelper;
        parent::__construct($localeLists, $context, $coreRegistry, $resultPageFactory, $directoryCountry,
            $disabledRegionResource, $cacheTypeList, $disabledRegionFactory);
    }

    /**
     * Execute
     */
    public function execute()
    {
        $resultPage = $this->createPage();
        $resultRedirect = $this->resultRedirectFactory->create();
        $countryId = $this->getRequest()->getParam('country_id');
        $countries = $this->countryHelper->getCountries();

        if (!isset($countryId) && $countries) {
            $country = array_shift($countries);
            $countryId = $country['value'];
        }

        /** @var \Magento\Directory\Model\Country $model */
        $model = $this->getCountryModel();

        if ($countryId) {
            //        Using in block later
            $this->_coreRegistry->register(
                'disabledRegionsCountryId',
                $countryId
            );

            $model->load($countryId);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This country no exists.'));
                $resultRedirect->setPath('adminhtml/*/');
                return $resultRedirect;
            }
        }

        $countryName = $model->getName();

        $resultPage->getConfig()->getTitle()->prepend(__(($countryName) ? $countryName : 'New Region Configuration'));
        return $resultPage;
    }
}
