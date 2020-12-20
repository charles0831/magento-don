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
namespace Onestepcheckout\Iosc\Block\Frontend\LayoutProcessors;

class AuthProcessor implements \Magento\Checkout\Block\Checkout\LayoutProcessorInterface
{
    /**
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Onestepcheckout\Iosc\Helper\Data $helper
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Onestepcheckout\Iosc\Helper\Data $helper,
        \Magento\Customer\Model\Session $customerSession
    ) {

        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
        $this->customerSession = $customerSession;
    }

    /**
     * {@inheritdoc}
     */
    public function process($jsLayout)
    {

        if ($this->helper->isEnabled()) {
            $enabled = $this->scopeConfig->getValue(
                'onestepcheckout_iosc/registration/showlogin',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            if (!$enabled) {
                unset($jsLayout['components']['checkout']['children']['authentication']);
            }
            $enabled = $this->scopeConfig->getValue(
                'onestepcheckout_iosc/registration/optionalpwd',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );

            $isLoggedIn = $this->customerSession->getId();

            if (!$isLoggedIn && $enabled && isset($jsLayout['components']['checkout']['children']['sidebar']['children']['registration-fields'])) {
                $fields = $jsLayout['components']['checkout']['children']['sidebar']['children']['registration-fields'];
                $registration = $jsLayout['components']['checkout']['children']['iosc']['children']['registration'];
                $regFields = array_merge($fields, $registration);
                $jsLayout['components']['checkout']['children']['sidebar']['children']['registration-fields'] = $regFields;
                unset($jsLayout['components']['checkout']['children']['iosc']['children']['registration']);
            } elseif (isset($jsLayout['components']['checkout']['children']['sidebar']['children']['registration-fields'])) {
                unset($jsLayout['components']['checkout']['children']['sidebar']['children']['registration-fields']);
            }
        }

        return $jsLayout;
    }
}
