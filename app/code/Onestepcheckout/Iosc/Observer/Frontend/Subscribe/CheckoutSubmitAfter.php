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
namespace Onestepcheckout\Iosc\Observer\Frontend\Subscribe;

class CheckoutSubmitAfter implements \Magento\Framework\Event\ObserverInterface
{

    public $scopeConfig;
    public $helper;

    /**
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface
     * @param \Magento\Newsletter\Model\Subscriber $subscriber
     * @param \Onestepcheckout\Iosc\Helper\Data $helper
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Magento\Newsletter\Model\Subscriber $subscriber,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Onestepcheckout\Iosc\Helper\Data $helper
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->customerSession = $customerSession;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->subscriber = $subscriber;
        $this->storeManager = $storeManager;
        $this->helper = $helper;
    }

    /**
     * Handle post order registration and order tieing to guest customer
     *
     * @param Observer $observer
     * @event checkout_submit_after
     *
     * @return void
     */
    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
        if (!$this->helper->isEnabled()) {
            return;
        }
        $quote = $observer->getEvent()->getQuote();
        $needToSubscribe = $quote->getIoscSubscribe();
        if (!$needToSubscribe || $needToSubscribe > 1) {
            return;
        }

        $status = false;
        $customer = false;
        $customerId = false;
        $email = $quote->getCustomerEmail();
        $isLoggedIn = $this->customerSession->getId();

        if ($isLoggedIn) {
            $status = $this->subscriber->loadByCustomerId($isLoggedIn);
        } else {
            $status = $this->subscriber->loadByEmail($email);
        }

        if (!$status->getStatus()) {
            try {
                $this->subscriber->subscribe($email);
            } catch (\Exception $e) {
               /*
                * silence , we don't care if this fails
                */
            }

            if ($status->getStatus() == '1') {

                if (! $isLoggedIn) {
                    try {
                        $customer = $this->customerRepositoryInterface
                            ->get(
                                $email,
                                $this->storeManager->getWebsite()
                                ->getId()
                            );
                        $customerId = $customer->getId();
                    } catch (\Exception $e) {
                        /*
                         * silence , we don't care if this fails
                         */
                    }
                } else {
                    $customerId = $this->subscriber->getCustomerId();
                }

                if ($customerId) {
                    $this->subscriber->setCustomerId($customerId)
                    ->save();
                }

                $quote->setIoscSubscribe(2)->save();
            }
        }
    }
}
