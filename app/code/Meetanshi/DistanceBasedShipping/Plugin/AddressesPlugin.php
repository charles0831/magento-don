<?php

namespace Meetanshi\DistanceBasedShipping\Plugin;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Multishipping\Controller\Checkout\Addresses;
use Meetanshi\DistanceBasedShipping\Block\Shipping;
use Magento\Framework\Message\ManagerInterface as MessageManager;

class AddressesPlugin
{
    private $shipping;
    private $messageManager;
    private $resultRedirectFactory;
    private $checkoutSession;

    public function __construct(
        Shipping $shipping,
        RedirectFactory $resultRedirectFactory,
        MessageManager $messageManager,
        CheckoutSession $checkoutSession
    ) {
        $this->shipping = $shipping;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->messageManager = $messageManager;
        $this->checkoutSession = $checkoutSession;
    }

    public function aroundExecute(Addresses $index, callable $proceed)
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
