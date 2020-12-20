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

class SubscribeProcessor implements \Magento\Checkout\Block\Checkout\LayoutProcessorInterface
{
    /**
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Newsletter\Model\Subscriber $subscriber
     * @param \Onestepcheckout\Iosc\Helper\Data $helper
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Newsletter\Model\Subscriber $subscriber,
        \Onestepcheckout\Iosc\Helper\Data $helper
    ) {

        $this->scopeConfig = $scopeConfig;
        $this->customerSession = $customerSession;
        $this->subscriber = $subscriber;
        $this->helper = $helper;
    }

    /**
     * {@inheritdoc}
     */
    public function process($jsLayout)
    {
        $configKey = 'subscribe';

        if ($this->helper->isEnabled() && isset($jsLayout['components']['checkout']['children']['iosc']['children'][$configKey])) {
            $customConfig = $jsLayout['components']['checkout']['children']['iosc']['children'][$configKey];
            unset($jsLayout['components']['checkout']['children']['iosc']['children'][$configKey]);

            $include = $this->getIsEnabled($configKey);

            if ($include) {
                if (isset($jsLayout['components']['checkout']['children']['sidebar']['children'][$configKey])) {
                    $componentConfig = $jsLayout['components']['checkout']['children']['sidebar']['children'][$configKey];
                    $componentConfig = array_merge($componentConfig, $customConfig);
                    $jsLayout['components']['checkout']['children']['sidebar']['children'][$configKey] = $componentConfig;
                }
            } else {
                if (isset($jsLayout['components']['checkout']['children']['sidebar']['children'][$configKey])) {
                    unset($jsLayout['components']['checkout']['children']['sidebar']['children'][$configKey]);
                }
            }
        }

        return $jsLayout;
    }

    private function getIsEnabled($configKey)
    {

        $include = false;
        $scopeStore = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

        $enabled = $this->scopeConfig->getValue('onestepcheckout_iosc/' . $configKey . '/enable', $scopeStore);

        if ($enabled) {
            $include = true;
        }

        $isLoggedIn = $this->customerSession->getId();
        if ($isLoggedIn) {
            $enabledForReg = $this->scopeConfig->getValue('onestepcheckout_iosc/' . $configKey . '/enableforreg', $scopeStore);
            $hideFromRegandSubscribed = $this->scopeConfig->getValue('onestepcheckout_iosc/' . $configKey . '/hidefromregandsubscribed', $scopeStore);

            if (!$enabledForReg) {
                $include = false;
            }

            if ($enabledForReg && $hideFromRegandSubscribed) {
                $status = $this->subscriber->loadByCustomerId($isLoggedIn);
                if ($status->getStatus()) {
                    $include = false;
                }
            }
        }

        return $include;
    }
}
