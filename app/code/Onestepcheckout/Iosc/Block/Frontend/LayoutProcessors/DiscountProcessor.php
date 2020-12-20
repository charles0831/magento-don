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

class DiscountProcessor implements \Magento\Checkout\Block\Checkout\LayoutProcessorInterface
{
    /**
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Onestepcheckout\Iosc\Helper\Data $helper
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Onestepcheckout\Iosc\Helper\Data $helper
    ) {

        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
    }

    /**
     * {@inheritdoc}
     */
    public function process($jsLayout)
    {

        if ($this->helper->isEnabled()) {
            $enabled = $this->scopeConfig->getValue(
                'onestepcheckout_iosc/discount/enable',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );

            if ($enabled && isset($jsLayout['components']['checkout']['children']
                ['steps']['children']['billing-step']['children']
                ['payment']['children']['afterMethods']['children']['discount'])
            ) {
                $discount = $jsLayout['components']['checkout']['children']
                ['steps']['children']['billing-step']['children']
                ['payment']['children']['afterMethods']['children']['discount'];

                unset($jsLayout['components']['checkout']['children']
                    ['steps']['children']['billing-step']['children']
                    ['payment']['children']['afterMethods']['children']['discount']);

                if (isset($jsLayout['components']['checkout']['children']['sidebar']['children']['discount'])) {
                    $totalsDiscount = $jsLayout['components']['checkout']['children']
                    ['sidebar']['children']['discount'];
                    $discount = array_merge($discount, $totalsDiscount);
                }
                $jsLayout['components']['checkout']['children']['sidebar']['children']['discount'] = $discount;
            }

        }

        return $jsLayout;
    }
}
