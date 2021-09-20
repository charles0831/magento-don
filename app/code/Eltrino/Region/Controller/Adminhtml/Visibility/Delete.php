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
use Magento\Framework\Validator\Exception;

/**
 * Class Delete
 * @package Eltrino\Region\Controller\Adminhtml\Visibility
 */
class Delete extends Visibility
{
    /**
     * Delete step action
     *
     * @return \Magento\Backend\App\Action
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $countryId = $this->getRequest()->getParam('country_id');
        try {
            $this->disabledRegionResource->deleteDisabledRegionsByCountry($countryId);
            $this->messageManager->addSuccess(
                __('Region visibility setting for %1 was deleted.',
                    (string)$this->localeLists->getCountryTranslation($countryId)
                )
            );
            $this->cacheTypeList->invalidate(\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER);
            $resultRedirect->setPath('*/visibility');
            return $resultRedirect;

        } catch (Exception $e) {
            $messages = $e->getMessages();
            $this->messageManager->addMessages($messages);
            $resultRedirect->setPath('*/edit', ['country_id' => $countryId]);
            return $resultRedirect;
        }
    }
}
