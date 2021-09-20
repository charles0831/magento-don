<?php

namespace Meetanshi\DistanceBasedShipping\Controller\Index;

use Exception;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\ResultFactory;

class Index extends Action
{
    protected $pageFactory;
    protected $objectManager;
    private $checkoutSession;

    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        CheckoutSession $checkoutSession
    ) {
        $this->pageFactory = $pageFactory;
        $this->checkoutSession = $checkoutSession;
        return parent::__construct($context);
    }

    public function execute()
    {
        try {
            $quote = $this->checkoutSession->getQuote();
            $pickupFrom = $this->getRequest()->getParam('pickup_from');
            $quote->setPickupFromId($pickupFrom);
            $quote->save();
            $response = $this->resultFactory->create(ResultFactory::TYPE_RAW);
            $response->setHeader('Content-type', 'text/plain');
            return $response;
        } catch (Exception $ex) {
            \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->info($ex->getMessage());
            $this->messageManager->addErrorMessage(__($ex->getMessage()));
            return false;
        }
    }
}
