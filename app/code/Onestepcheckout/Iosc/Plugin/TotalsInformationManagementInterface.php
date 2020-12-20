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

class TotalsInformationManagementInterface
{

    /**
     *
     * @param \Onestepcheckout\Iosc\Helper\Data $helper
     * @param \Onestepcheckout\Iosc\Model\Output\ShippingMethod $outputShippingMethod
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepository
     * @param \Magento\Quote\Model\Quote\ShippingAssignment\ShippingAssignmentPersister $shippingAssignmentPersister
     */
    public function __construct(
        \Onestepcheckout\Iosc\Helper\Data $helper,
        \Onestepcheckout\Iosc\Model\Output\ShippingMethod $outputShippingMethod,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Magento\Quote\Model\Quote\ShippingAssignment\ShippingAssignmentPersister $shippingAssignmentPersister
    ) {

        $this->helper = $helper;
        $this->outputShippingMethod = $outputShippingMethod;
        $this->cartRepository = $cartRepository;
        $this->shippingAssignmentPersister = $shippingAssignmentPersister;
    }

    /**
     *
     * @param $subject
     * @param $result
     * @param $cartId
     * @param \Magento\Checkout\Api\Data\TotalsInformationInterface $addressInformation
     */
    public function afterCalculate(
        $subject,
        $result,
        $cartId,
        \Magento\Checkout\Api\Data\TotalsInformationInterface $addressInformation
    ) {

        if (!$this->helper->isEnabled()) {
            return $result;
        }
        $quote = $this->cartRepository->get($cartId);
        $quote = $this
                    ->outputShippingMethod
                    ->prepareShippingAssignment(
                        $quote,
                        $quote->getShippingAddress(),
                        $addressInformation->getShippingCarrierCode() . '_' . $addressInformation->getShippingMethodCode()
                    );
        $shippingAssignments = $quote->getExtensionAttributes()->getShippingAssignments();
        $this->shippingAssignmentPersister->save($quote, current($shippingAssignments));

        return $result;
    }
}
