<?php
/**
 * OneStepCheckout
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to One Step Checkout AS software license.
 *
 * License is available through the world-wide-web at this URL:
 * https://www.onestepcheckout.com/LICENSE.txt
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to mail@onestepcheckout.com so we can send you a copy immediately.
 *
 * @category   onestepcheckout
 * @package    onestepcheckout_iosc
 * @copyright  Copyright (c) 2017 OneStepCheckout  (https://www.onestepcheckout.com/)
 * @license    https://www.onestepcheckout.com/LICENSE.txt
 */
namespace Onestepcheckout\Iosc\Plugin;

class EmailNotification
{

    /**
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Onestepcheckout\Iosc\Helper\Data $helper
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Onestepcheckout\Iosc\Helper\Data $helper
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->checkoutSession = $checkoutSession;
        $this->helper = $helper;
    }

     /**
      * {@inheritDoc}
      */
    public function beforeNewAccount(
        $parent,
        \Magento\Customer\Api\Data\CustomerInterface $customer,
        $type = \Magento\Customer\Model\EmailNotificationInterface::NEW_ACCOUNT_EMAIL_REGISTERED,
        $backUrl = '',
        $storeId = 0,
        $sendemailStoreId = null
    ) {
        if ($this->helper->isEnabled()) {
            $autoRegister = $this->scopeConfig
                ->getValue(
                    'onestepcheckout_iosc/registration/autoregister',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
            if ((int)$autoRegister === 1 &&
                $this->checkoutSession->getQuote()->getIoscRegistered() == "2"
            ) {
                $type = \Magento\Customer\Model\EmailNotificationInterface::NEW_ACCOUNT_EMAIL_REGISTERED_NO_PASSWORD;
            }
        }

        return [$customer, $type, $backUrl, $storeId, $sendemailStoreId];
    }

    /**
     * {@inheritDoc}
     */
    public function aroundNewAccount(
        \Magento\Customer\Model\EmailNotification $subject,
        \Closure $proceed,
        \Magento\Customer\Api\Data\CustomerInterface $customer,
        $type = \Magento\Customer\Model\EmailNotificationInterface::NEW_ACCOUNT_EMAIL_REGISTERED,
        $backUrl = '',
        $storeId = 0,
        $sendemailStoreId = null
    ) {
        if ($this->helper->isEnabled()) {
            $skipEmail = $this->scopeConfig
            ->getValue('onestepcheckout_iosc/registration/skipemail', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            if ((int)$skipEmail === 1) {
                return;
            }
        }

        return  $proceed($customer, $type, $backUrl, $storeId, $sendemailStoreId);
    }
}
