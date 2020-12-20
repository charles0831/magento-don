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
namespace Onestepcheckout\Iosc\Controller\Onepage;

class Update extends \Magento\Framework\App\Action\Action
{

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Onestepcheckout\Iosc\Model\DataManager $dataManager
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Onestepcheckout\Iosc\Model\DataManager $dataManager
    ) {
            $this->resultJsonFactory = $resultJsonFactory;
            $this->dataManager = $dataManager;
            $this->url = $context->getUrl();
            parent::__construct($context);
    }

    /**
     * Checkout page
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /**
         * get raw json payload and parse
         */
        $payload = [];
        $data = [];
        $content = $this->getRequest()->getContent();

        $response = $this->resultJsonFactory->create();

        if ($content) {
            try {
                $payload = $this->dataManager->deserializeJsonPost($content);
                $data = $this->dataManager->process($payload);

                if ($data['error']) {
                    $response->setHttpResponseCode(\Magento\Framework\Webapi\Exception::HTTP_BAD_REQUEST);
                }
            } catch (\Exception $e) {
                $response->setHttpResponseCode(\Magento\Framework\Webapi\Exception::HTTP_BAD_REQUEST);
                $error = $e->getMessage();
                $data = [
                    'success' => false,
                    'error' => true,
                    'data' => $data,
                    'message' => $error
                ];
            }
        }

        $response->setData($data);

        return $response;
    }
}
