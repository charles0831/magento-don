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

/**
 * Class Save
 * @package Eltrino\Region\Controller\Adminhtml\Visibility
 */
class Save extends Visibility
{
    /**
     * Execute
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $countryId = $this->getRequest()->getParam('country_id');
        $data = $this->getRequest()->getPostValue();

        if (!$data || !$data['disabled_regions']) {
            $this->messageManager->addError('Malformed request');
        } elseif ($data['disabled_regions'][0] === 'null') {
            $this->messageManager->addError('You have to choose at least one region.');
        }

        if (!$data['country_id']) {
            $this->messageManager->addError('Country can\'t be empty');
        }

        if ($this->messageManager->hasMessages()) {
            $resultRedirect->setPath('adminhtml/*/');
            return $resultRedirect;
        }

        try {
            $this->disabledRegionResource->deleteDisabledRegionsByCountry($data['country_id']);
            foreach ($data['disabled_regions'] as $region) {
                $disabledRegion = $this->disabledRegionFactory->create();
                $regionData['country_id'] = $countryId;
                $regionData['region_id'] = $region;
                $disabledRegion->setData($regionData);
                $disabledRegion->save();
            }

            $this->messageManager->addSuccess(
                __('You saved the new configuration for %1.',
                    (string)$this->localeLists->getCountryTranslation($countryId)
                )
            );
            $this->cacheTypeList->invalidate(\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER);
            $resultRedirect->setPath('*/visibility');

        } catch (\Exception $e) {
            $message = $e->getMessage();
            $this->messageManager->addError($message);
            $resultRedirect->setPath('*/edit', ['country_id' => $countryId]);
        }
        return $resultRedirect;
    }
}
