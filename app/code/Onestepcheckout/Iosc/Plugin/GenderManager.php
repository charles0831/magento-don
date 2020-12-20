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

class GenderManager
{

    /**
     *
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Onestepcheckout\Iosc\Helper\Data $helper
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Onestepcheckout\Iosc\Helper\Data $helper
    ) {

        $this->helper = $helper;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     *  Soft set gender value to customer_gender
     * @param unknown $parent
     * @param unknown $value
     */
    public function afterSetGender(
        $parent,
        $value
    ) {

        if (! $this->helper->isEnabled()) {
            return;
        }

        $isSet = $this->checkoutSession->getQuote()->getCustomer()->getGender();
        if (!$isSet) {
            $value = ($parent->getGender()) ? $parent->getGender() : '' ;
            $this->checkoutSession->getQuote()->setCustomerGender($value);
        }
    }
}
