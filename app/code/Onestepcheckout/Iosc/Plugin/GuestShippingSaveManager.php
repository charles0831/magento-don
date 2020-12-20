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

class GuestShippingSaveManager
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
    public function processPayload($content, $address)
    {
        if ($content) {
            $payload = $this->dataManager->deserializeJsonPost($content);
            if (isset($payload['paymentMethod'])) {
                unset($payload['paymentMethod']);
            }
            if (isset($payload['shippingMethod'])) {
                unset($payload['shippingMethod']);
            }
            $payload = $this->dataManager->process($payload);
        }
        if (!empty($address)) {
            $mockedData = $this->getMockedData($this->getMockManager(), $address);
            $address = $this->addMockedData($address, $mockedData);
            return $address;
        }
    }

    /**
     * Update \Magento\Quote\Api\Data\AddressInterface with mocked data
     *
     * @param \Magento\Quote\Api\Data\AddressInterface $billingAddress
     * @param array $mockedData
     */
    public function addMockedData(\Magento\Quote\Api\Data\AddressInterface $address, array $mockedData)
    {
        $address->addData($mockedData);
        return $address;
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
        \Magento\Quote\Api\Data\AddressInterface $address
    ) {
        return $mockManager->getMockedAddress($address);
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
     *
     * @param unknown $parent
     * @param unknown $cartId
     * @param \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
     */
    public function beforesaveAddressInformation(
        $parent,
        $cartId,
        \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
    ) {

        if (!$this->helper->isEnabled()) {
            return;
        }
        $content = $this->getRequest()->getContent();
        $address = $this->processPayload($content, $addressInformation->getShippingAddress());
        $addressInformation->getShippingAddress()->addData($address->getData());
        $address = $this->processPayload($content, $addressInformation->getBillingAddress());
        $addressInformation->getBillingAddress()->addData($address->getData());
    }
}
