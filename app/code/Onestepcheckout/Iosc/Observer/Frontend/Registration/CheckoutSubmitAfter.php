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
namespace Onestepcheckout\Iosc\Observer\Frontend\Registration;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;

class CheckoutSubmitAfter implements \Magento\Framework\Event\ObserverInterface
{

    public $scopeConfig;
    public $helper;

    /**
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\DataObject\Copy $objectCopyService
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptorInterface
     * @param \Magento\Framework\Math\Random $random
     * @param \Magento\Framework\Api\SimpleDataObjectConverter $simpleDataObjectConverter
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface
     * @param \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerFactory
     * @param \Magento\Customer\Api\Data\AddressInterfaceFactory $addressFactory
     * @param \Magento\Customer\Api\Data\RegionInterfaceFactory $regionFactory
     * @param \Magento\Customer\Api\AccountManagementInterface $accountManagementInterface
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Onestepcheckout\Iosc\Helper\Data $helper
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\DataObject\Copy $objectCopyService,
        \Magento\Framework\Encryption\EncryptorInterface $encryptorInterface,
        \Magento\Framework\Math\Random $random,
        \Magento\Framework\Api\SimpleDataObjectConverter $simpleDataObjectConverter,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerFactory,
        \Magento\Customer\Api\Data\AddressInterfaceFactory $addressFactory,
        \Magento\Customer\Api\Data\RegionInterfaceFactory $regionFactory,
        \Magento\Customer\Api\AccountManagementInterface $accountManagementInterface,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Psr\Log\LoggerInterface $logger,
        \Onestepcheckout\Iosc\Helper\Data $helper
    ) {

        $this->scopeConfig = $scopeConfig;

        $this->objectCopyService = $objectCopyService;
        $this->encryptorInterface = $encryptorInterface;
        $this->random = $random;
        $this->simpleDataObjectConverter = $simpleDataObjectConverter;

        $this->customerSession = $customerSession;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->customerFactory = $customerFactory;
        $this->addressFactory = $addressFactory;
        $this->regionFactory = $regionFactory;
        $this->accountManagementInterface = $accountManagementInterface;

        $this->storeManager = $storeManager;
        $this->logger = $logger;
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
        $order = $observer->getEvent()->getOrder();
        if ($order->getStoreId() == "0") {
            return false;
        }

        $customer = false;
        $scopeStore = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $autoPlace  = $this->scopeConfig
            ->getValue('onestepcheckout_iosc/registration/autoplace', $scopeStore);
        $autoRegister = $this->scopeConfig
            ->getValue('onestepcheckout_iosc/registration/autoregister', $scopeStore);
        $optionalRegister = $this->scopeConfig
            ->getValue('onestepcheckout_iosc/registration/optionalpwd', $scopeStore);

        $email = $quote->getCustomerEmail();
        $isLoggedIn = $this->customerSession->getId();
        $isEmailAvailable  = (int)$this->accountManagementInterface
            ->isEmailAvailable($quote->getCustomerEmail());
        $tryAccountCreation = false;
        if (!$isLoggedIn &&
            (
                $autoRegister ||
                ($optionalRegister && !empty($quote->getPasswordHash()))
            ) &&
            $isEmailAvailable) {
            $customer = $this->customerFactory
                        ->create(['data' => $this->prepcustomerData($quote)]);

            $customer
                ->setWebsiteId($this->storeManager->getWebsite()->getId())
                ->setStoreId($this->storeManager->getStore()->getId());

            $quote->getShippingAddress()->setEmail($email);
            if ($optionalRegister && !empty($quote->getPasswordHash())) {
                $pwordH = $quote->getPasswordHash();
                $autoPlace = true;
                $quote->setIoscRegistered(1);
            } else {
                $pword  = $this->random->getRandomString(8);
                $pwordH = $this->encryptorInterface->getHash($pword, true);
                $quote->setIoscRegistered(2);
            }

            try {
                $customerA = $this->accountManagementInterface
                                ->createAccountWithPasswordHash($customer, $pwordH);
                $tryAccountCreation = true;
                if ($optionalRegister && !empty($quote->getPasswordHash())) {
                    $quote->setPasswordHash("");
                }
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }

            if ($tryAccountCreation) {
                $customer = $customerA;
            }

            $isEmailAvailable = 0;
        }
        if (!$isLoggedIn && $autoPlace && !$isEmailAvailable) {
            if (!is_object($customer)) {
                $customer = $this->customerRepositoryInterface
                    ->get($email, $this->storeManager->getWebsite()->getId());

            }

            if (!empty($order) && is_object($order) && is_object($customer)) {
                $this->addUserToOrder($quote, $order, $customer);
                $order->save();
                $quote->save();
            }
        }
    }

    /**
     *
     * @param unknown $quote
     * @param unknown $order
     * @param unknown $customer
     */
    private function addUserToOrder(
        $quote,
        $order,
        $customer
    ) {
        $orderCustomerAttr = array_keys($order->getData());
        foreach ($orderCustomerAttr as $k) {
            if (strpos($k, 'customer_') !== false) {
                $k = str_replace('customer_', '', $k);

                $convertedKey = $this->simpleDataObjectConverter
                                    ->snakeCaseToUpperCamelCase($k);
                $setMethodName = 'setCustomer' . $convertedKey;
                $getMethodName = 'get' . $convertedKey;

                if (method_exists($customer, $getMethodName)) {
                    $v = call_user_func([
                        $customer,
                        $getMethodName
                    ]);

                    if ($v) {
                        call_user_func([
                            $order,
                            $setMethodName
                        ], $v);

                        call_user_func([
                            $quote,
                            $setMethodName
                        ], $v);
                    }
                }
            }
        }
    }

    /**
     *
     * @param object $salesObj
     * @return \Magento\Customer\Api\Data\CustomerInterfaceFactory object
     */
    private function prepcustomerData($salesObj)
    {

        $customerData = $this->objectCopyService->copyFieldsetToTarget(
            'order_address',
            'to_customer',
            $salesObj->getBillingAddress(),
            []
        );

        if ($salesObj->getCustomerGender() && !isset($customerData['gender'])) {
            $customerData['gender'] = $salesObj->getCustomerGender();
        }

        if ($salesObj->getCustomerDob() && !isset($customerData['dob'])) {
            $customerData['dob'] = $salesObj->getCustomerDob();
        }

        if (!$salesObj->getIsVirtual()) {
            $addresses = [$salesObj->getBillingAddress(), $salesObj->getShippingAddress()];
        } else {
            $addresses = [$salesObj->getBillingAddress()];
        }

        foreach ($addresses as $address) {
            $addressData = $this->objectCopyService->copyFieldsetToTarget(
                'order_address',
                'to_customer_address',
                $address,
                []
            );
            /** @var \Magento\Customer\Api\Data\AddressInterface $customerAddress */
            $customerAddress = $this->addressFactory->create(['data' => $addressData]);
            switch ($address->getAddressType()) {
                case \Magento\Quote\Model\Quote\Address::ADDRESS_TYPE_BILLING:
                    $customerAddress->setIsDefaultBilling(true);
                    break;
                case \Magento\Quote\Model\Quote\Address::ADDRESS_TYPE_SHIPPING:
                    $customerAddress->setIsDefaultShipping(true);
                    break;
            }

            if (is_string($address->getRegion())) {
                /** @var \Magento\Customer\Api\Data\RegionInterface $region */
                $region = $this->regionFactory->create();
                $region->setRegion($address->getRegion());
                $region->setRegionCode($address->getRegionCode());
                $region->setRegionId($address->getRegionId());
                $customerAddress->setRegion($region);
            }
            $customerData['addresses'][] = $customerAddress;
        }

        return $customerData;
    }
}
