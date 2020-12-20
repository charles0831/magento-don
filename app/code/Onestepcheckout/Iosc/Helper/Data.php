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
namespace Onestepcheckout\Iosc\Helper;

use \Zend\Serializer\Serializer;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    const CONFIG_PATH = 'onestepcheckout_iosc';
    const FIELD_ID = 'field_id';

    /**
     *
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->scopeConfig = $context->getScopeConfig();
        parent::__construct($context);
    }

    /**
     * Get the array of default address and customer attributes
     *
     * @param string $path
     * @param array $unset
     * @param array $serialized
     * @return array
     */
    public function getConfig(array $unset = [], array $serialized = [], $return = '')
    {
        $result = $this->scopeConfig->getValue(self::CONFIG_PATH, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        foreach ($unset as $key) {
            unset($result[$key]);
        }

        foreach ($serialized as $key) {
            if (! empty($result[$key]) && is_array($result[$key])) {
                if (isset($result[$key][$key])) {
                    $value = $result[$key][$key];
                    if (! empty($value) && is_string($value)) {
                        $result[$key] = $this->getSerialized($value);
                    }
                }
            }
        }

        if (! empty($return)) {
            $result = $result[$return];
        }

        return $result;
    }

    /**
     * Check if OneStepCheckout is enabled in store config
     *
     * @return boolean;
     */
    public function isEnabled()
    {
        $enabled = false;

        $enabled = $this->scopeConfig->getValue(
            self::CONFIG_PATH . '/general/enable',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        return $enabled;
    }

    /**
     * Return unserialized string from serialized config string
     *
     * @param string $serializedValue
     * @return []
     */
    public function getSerialized($serializedValue)
    {
        $return = [];

        $config = $this->unserialize($serializedValue);

        if (is_array($config)) {
            foreach ($config as $v) {
                $return[$v[self::FIELD_ID]] = $v;
            }
        }

        return $return;
    }

    /**
     *
     * @param string $serializedValue
     * @return []
     */
    public function unserialize($serializedValue)
    {

        $failed = false;
        $response = false;
        try {
            $response = Serializer::unserialize($serializedValue, 'json');
        } catch (\Exception $e) {
            $failed = true;
        }

        if ($failed) {
            try {
                $response = Serializer::unserialize($serializedValue, 'phpserialize');
            } catch (\Exception $e) {
                $response = false;
            }
        }
        return $response;
    }
}
