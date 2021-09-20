<?php

namespace Meetanshi\PayshipRestriction\Plugin;

use Meetanshi\PayshipRestriction\Helper\Data;
use Magento\Paypal\Model\AbstractConfig as PaypalConfig;

/**
 * Class MethodActive
 * @package Meetanshi\PayshipRestriction\Plugin
 */
class MethodActive
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * MethodActive constructor.
     * @param Data $helper
     */
    public function __construct(Data $helper)
    {
        $this->helper = $helper;
    }

    /**
     * @param PaypalConfig $subject
     * @param $result
     * @param null $method
     * @return bool
     */
    public function afterIsMethodActive(PaypalConfig $subject, $result, $method = null)
    {
        if (!$this->helper->canUseMethod($method, 'payment')){
            return false;
        }

        return $result;
    }
}
