<?php

namespace Meetanshi\DistanceBasedShipping\Plugin;

use Magento\Checkout\Controller\Index\Index;
use Magento\Checkout\Model\Session as CheckoutSession;
use Meetanshi\DistanceBasedShipping\Block\Shipping;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use Magento\Framework\Controller\Result\RedirectFactory;

class CheckoutPlugin
{
    private $shipping;
    private $messageManager;
    private $resultRedirectFactory;
    private $checkoutSession;

    public function __construct(
        Shipping $shipping,
        MessageManager $messageManager,
        RedirectFactory $resultRedirectFactory,
        CheckoutSession $checkoutSession
    ) {
        $this->shipping = $shipping;
        $this->messageManager = $messageManager;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->checkoutSession = $checkoutSession;
    }

    public function aroundExecute(Index $index, callable $proceed)
    {
        try {
            $quote=$this->checkoutSession->getQuote();

            if (!($this->shipping->getIsEnable() && count($this->shipping->getAddresses()))) {
                $quote->setPickupFromId(null);
                $quote->save();
                return $proceed();
            }
            if ($quote->getPickupFromId()) {
                return $proceed();
            }
            $this->messageManager->addErrorMessage('Please provied Pickup From address');
            return $this->resultRedirectFactory->create()->setPath('checkout/cart');
        } catch (\Exception $e) {
            \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->info($e->getMessage());
            return $proceed();
        }
    }
}
