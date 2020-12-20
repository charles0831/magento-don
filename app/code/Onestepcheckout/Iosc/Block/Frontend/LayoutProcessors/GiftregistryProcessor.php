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

class GiftregistryProcessor implements \Magento\Checkout\Block\Checkout\LayoutProcessorInterface
{

    /**
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Onestepcheckout\Iosc\Helper\Data $helper
     */
    public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, \Magento\Customer\Model\Session $customerSession, \Onestepcheckout\Iosc\Helper\Data $helper)
    {
        $this->scopeConfig = $scopeConfig;
        $this->customerSession = $customerSession;
        $this->helper = $helper;
    }

    /**
     *
     * {@inheritdoc}
     */
    public function process($jsLayout)
    {
        if ($this->helper->isEnabled()) {

            $isLoggedIn = $this->customerSession->getId();
            if (! $isLoggedIn) {
                if (isset($jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['gift-registry-address-provider'])) {
                    unset($jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['gift-registry-address-provider']);
                    unset($jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['address-list']['config']['deps']);
                    unset($jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['address-list']['rendererTemplates']['gift-registry']);
                }
            }
        }

        return $jsLayout;
    }
}
