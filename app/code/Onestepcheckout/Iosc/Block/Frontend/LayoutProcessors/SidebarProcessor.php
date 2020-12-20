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

class SidebarProcessor implements \Magento\Checkout\Block\Checkout\LayoutProcessorInterface
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

        if ($this->helper->isEnabled() && isset($jsLayout['components']['checkout']['children']['sidebar']['children'])) {

            $configKey = 'iosc-summary';
            $lastComponent = end($jsLayout['components']['checkout']['children']['sidebar']['children'])['component'];
            $componentCount = count($jsLayout['components']['checkout']['children']['sidebar']['children']);
            $component = [
                'component' => 'Onestepcheckout_Iosc/js/totals',
                'displayArea' => 'sidebar',
                'config' => [
                    'template' => 'Onestepcheckout_Iosc/summary',
                    'iosc_cnf' => [
                        'lastComponent' => $lastComponent,
                        'componentCount' => $componentCount
                    ]
                ]
            ];
            $sidebar = $jsLayout['components']['checkout']['children']['sidebar'];
            unset($jsLayout['components']['checkout']['children']['sidebar']);
            $jsLayout['components']['checkout']['children'][$configKey] = $component;
            $jsLayout['components']['checkout']['children']['sidebar'] = $sidebar;
        }

        return $jsLayout;
    }
}
