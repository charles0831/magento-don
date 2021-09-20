<?php

namespace Meetanshi\PayshipRestriction\Plugin;

use Magento\Payment\Block\Form\Container;
use Meetanshi\PayshipRestriction\Helper\Data;

/**
 * Class Methods
 * @package Meetanshi\PayshipRestriction\Plugin
 */
class Methods
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * Methods constructor.
     * @param Data $helper
     */
    public function __construct(Data $helper)
    {
        $this->helper = $helper;
    }

    /**
     * Retrieve available payment methods
     *
     * @return array
     */

    public function afterGetMethods(Container $subject, $methods)
    {
        foreach($methods as $key => $method) {
            if (!$this->helper->canUseMethod($method->getCode(), 'payment')){
                unset($methods[$key]);
            }
        }

        return $methods;
    }
}
