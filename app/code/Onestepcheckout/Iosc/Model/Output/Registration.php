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
namespace Onestepcheckout\Iosc\Model\Output;

class Registration implements OutputManagementInterface
{

    public function getOutputKey()
    {
        return 'registration';
    }

    /**
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Onestepcheckout\Iosc\Helper\Data $helper
     * @param \Magento\Framework\Escaper $escaper
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Onestepcheckout\Iosc\Model\Extend\AccountManagement $accountManagement
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Onestepcheckout\Iosc\Helper\Data $helper,
        \Magento\Framework\Escaper $escaper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Onestepcheckout\Iosc\Model\Extend\AccountManagement $accountManagement
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
        $this->escaper = $escaper;
        $this->checkoutSession = $checkoutSession;
        $this->accountManagement = $accountManagement;
    }

    /**
     * {@inheritDoc}
     * @see \Onestepcheckout\Iosc\Model\Input\InputManagement::processPayload()
     */
    public function processPayload($input)
    {

        $data = [];

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->checkoutSession->getQuote();
        if ($quote->getId()) {
            $input = (isset($input[$this->getOutputKey()])) ? $input[$this->getOutputKey()] : false;
            $failure = false;
            $quote->setIoscRegistered(0);
            if (!empty($input['iosc-register-pwd']) &&
                !empty($input['iosc-register-pwd-confirm']) &&
                $input['iosc-register-pwd'] ==
                $input['iosc-register-pwd-confirm']
            ) {
                $pwdCandidate = trim($input['iosc-register-pwd']);
                try {
                    $this->accountManagement->checkPasswordStrength($pwdCandidate);
                } catch (\Exception $e) {
                    $failure = true;
                }

                if (!$failure) {
                    $pwdCandidateHash = $this->accountManagement->getPasswordHash($pwdCandidate);
                    $quote->setPasswordHash($pwdCandidateHash);
                    $quote->setIoscRegistered(1);
                } else {
                    $quote->setPasswordHash('');
                    $quote->setIoscRegistered(0);
                }
            } else {
                $quote->setPasswordHash('');
            }
            $quote->save();
        }
        return $data;
    }
}
