<?php

namespace Meetanshi\PayshipRestriction\Plugin;

use Magento\Payment\Model\MethodList as PaymentMethodList;
use Meetanshi\PayshipRestriction\Helper\Data;

/**
 * Class MethodList
 * @package Meetanshi\PayshipRestriction\Plugin
 */
class MethodList
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * MethodList constructor.
     * @param Data $helper
     */
    public function __construct(Data $helper)
    {
        $this->helper = $helper;
    }

    /**
     * @param PaymentMethodList $subject
     * @param $availableMethods
     * @return mixed
     */
    public function afterGetAvailableMethods(PaymentMethodList $subject, $availableMethods)
    {
        foreach($availableMethods as $key => $method) {
            if (!$this->helper->canUseMethod($method->getCode(), 'payment')){
                unset($availableMethods[$key]);
            }
        }

        return $availableMethods;
    }
}
