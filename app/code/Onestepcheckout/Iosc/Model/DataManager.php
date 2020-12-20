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
namespace Onestepcheckout\Iosc\Model;

class DataManager
{

    /**
     *
     * @param \Magento\Framework\Webapi\Rest\Request\DeserializerFactory $deserializerFactory
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Onestepcheckout\Iosc\Helper\Data $helper
     * @param array $outputs
     * @param array $inputs
     */
    public function __construct(
        \Magento\Framework\Webapi\Rest\Request\DeserializerFactory $deserializerFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Onestepcheckout\Iosc\Helper\Data $helper,
        array $outputs = [],
        array $inputs = []
    ) {

        $this->deserializerFactory = $deserializerFactory;
        $this->checkoutSession = $checkoutSession;
        $this->url = $urlBuilder;
        $this->inputs = $inputs;
        $this->outputs = $outputs;
        $this->helper = $helper;
    }

    /**
     * Process posted data and return expected output
     *
     * @param array $payload
     * @return array
     */
    public function process(array $payload)
    {

        if (!$this->helper->isEnabled()) {
            return [];
        }
        $quote =  $this->checkoutSession->getQuote();

        if ($quote->getId()) {
            $outputs = [
                'success' => true,
                'error' => false,
                'data' => $this->processPayload($this->outputs, $payload) ,
                'message' => '',
                'redirect'=> ''
            ];
        } else {
            $redirect =  $this->url->getUrl('checkout/cart');
            $outputs = [
                'success' => false,
                'error' => true,
                'data' => [],
                'message' => __('Your cart seems to be empty! Reload the page if you are not redirected automatically'),
                'redirect'=> $redirect
            ];
        }

        return $outputs;
    }

    /**
     * delegate payload processing to registered objects
     *
     * @param array $inputs
     * @param array $payload
     * @return array
     */
    public function processPayload(array $objects, array $payload)
    {
        $return = [];
        if (!empty($objects) && !empty($payload)) {
            foreach ($objects as $object) {
                if (!empty($return[$object->getOutputKey()])) {
                    $return[$object->getOutputKey()] = array_merge(
                        $object->processPayload($payload),
                        $return[$object->getOutputKey()]
                    );
                } else {
                    $return[$object->getOutputKey()] = $object->processPayload($payload);
                }

            }
        }
        return $return;
    }

    /**
     *
     * @param string $content
     */
    public function deserializeJsonPost($content = null)
    {

        $deserializer = $this->deserializerFactory->get('application/json');
        $payload = (array)$deserializer->deserialize((string)$content);

        return $payload;
    }
}
