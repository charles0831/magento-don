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
namespace Onestepcheckout\Iosc\Model\Config\Source;

class Paymentmethods implements \Magento\Framework\Option\ArrayInterface
{

    /**
     *
     * @param \Magento\Payment\Helper\Data $paymentData
     */
    public function __construct(\Magento\Payment\Helper\Data $paymentData)
    {
        $this->_paymentData = $paymentData;
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $methods = [];

        $methods['iosc_default'] = [
            'label' => __('Choose default payment method'),
            'value' => ''
        ];

        foreach ($this->_paymentData->getPaymentMethodList(true, true, true) as $k => $v) {
            $methods[$k] = $this->_addCode($v);
        }

        return $methods;
    }

    /**
     * Replace labels with more meaningful information since labels are not
     * always present nor unique we add [method_code]
     *
     * @param array $data
     * @return array
     */
    public function _addCode(array $data)
    {
        if (isset($data['value']) && is_array($data['value'])) {
            $sub = [];
            foreach ($data['value'] as $k => $v) {
                $sub[$k] = $this->_addCode($v);
            }
            $data['value'] = $sub;
        } else {
            if ($data['label']) {
                $data['label'] = __('%1 [%2]', $data['label'], $data['value']);
            } else {
                $data['label'] = __('No title [%1]', $data['value']);
            }
        }

        return $data;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return $this->_paymentData->getPaymentMethodList(true, false, true);
    }
}
