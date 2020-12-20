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

use Magento\Framework\Exception\CouldNotSaveException;

class SaveManager
{

    /**
     *
     * @param \Onestepcheckout\Iosc\Model\DataManager $dataManager
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Onestepcheckout\Iosc\Model\MockManager $mockManager
     * @param \Onestepcheckout\Iosc\Helper\Data $helper
     */
    public function __construct(
        \Onestepcheckout\Iosc\Model\DataManager $dataManager,
        \Magento\Framework\App\Request\Http $request,
        \Onestepcheckout\Iosc\Model\MockManager $mockManager,
        \Onestepcheckout\Iosc\Helper\Data $helper
    ) {

        $this->dataManager = $dataManager;
        $this->request = $request;
        $this->mockManager = $mockManager;
        $this->helper = $helper;
    }

    /**
     * Process posted data
     *
     * @param unknown $content
     * @param unknown $billingAddress
     */
    public function processPayload($content, $billingAddress)
    {
        if ($content) {
            $payload = $this->dataManager->deserializeJsonPost($content);
            unset($payload['paymentMethod']);
            unset($payload['shippingMethod']);
            $payload = $this->dataManager->process($payload);
        }

        if (!empty($billingAddress)) {
            $mockedData = $this->getMockedData($this->getMockManager(), $billingAddress);

            $errors = $this->getMockManager()->validateMockedData($mockedData);
            if (!empty($errors)) {
                throw new CouldNotSaveException(__(implode(', ', $errors)), null);
            }

            $this->addMockedData($billingAddress, $mockedData);
        }
    }

    /**
     * Update \Magento\Quote\Api\Data\AddressInterface with mocked data
     *
     * @param \Magento\Quote\Api\Data\AddressInterface $billingAddress
     * @param array $mockedData
     */
    public function addMockedData(\Magento\Quote\Api\Data\AddressInterface $billingAddress, array $mockedData)
    {
        $billingAddress->addData($mockedData);
    }

    /**
     * Get mocked data
     *
     * @param \Onestepcheckout\Iosc\Model\MockManager $mockManager
     * @param \Magento\Quote\Api\Data\AddressInterface $billingAddress
     * @return array
     */
    public function getMockedData(
        \Onestepcheckout\Iosc\Model\MockManager $mockManager,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress
    ) {
        return $mockManager->getMockedAddress($billingAddress);
    }

    /**
     * Get \Onestepcheckout\Iosc\Model\MockManager
     */
    public function getMockManager()
    {
        return $this->mockManager;
    }

    /**
     * Get \Magento\Framework\App\Request\Http
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Before plugin to save all posted data before order placement
     *
     * @param unknown $parent
     * @param unknown $cartId
     * @param \Magento\Quote\Api\Data\PaymentInterface $paymentMethod
     * @param \Magento\Quote\Api\Data\AddressInterface $billingAddress
     */
    public function beforeSavePaymentInformationAndPlaceOrder(
        $parent,
        $cartId,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    ) {

        if (!$this->helper->isEnabled()) {
            return;
        }
        try {
            $content = $this->getRequest()->getContent();
            $this->processPayload($content, $billingAddress);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(
                __('Please verify your billing address on following fields: ' . $e->getMessage()),
                $e
            );
        }
    }
}
