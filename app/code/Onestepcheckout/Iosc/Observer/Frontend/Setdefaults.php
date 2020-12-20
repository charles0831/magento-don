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
namespace Onestepcheckout\Iosc\Observer\Frontend;

class Setdefaults implements \Magento\Framework\Event\ObserverInterface
{

    public $scopeConfig = null;

    /**
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Onestepcheckout\Iosc\Model\MockManager $mockManager
     * @param \Magento\Quote\Model\Cart\ShippingMethodConverter $converter
     * @param \Magento\Framework\Webapi\ServiceOutputProcessor $serviceOutputProcessor
     * @param \Magento\Quote\Api\PaymentMethodManagementInterface $paymentMethodManagement
     * @param \Magento\Framework\Filesystem\DirectoryList $dir
     * @param \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\DataObject\Copy $objectCopyService
     * @param \Magento\Payment\Api\PaymentMethodListInterface $paymentMethodListInterface
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Quote\Model\Quote\TotalsCollector $totalsCollector
     * @param \Onestepcheckout\Iosc\Helper\Data $helper
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Onestepcheckout\Iosc\Model\MockManager $mockManager,
        \Magento\Quote\Model\Cart\ShippingMethodConverter $converter,
        \Magento\Framework\Webapi\ServiceOutputProcessor $serviceOutputProcessor,
        \Magento\Quote\Api\PaymentMethodManagementInterface $paymentMethodManagement,
        \Magento\Framework\Filesystem\DirectoryList $dir,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\DataObject\Copy $objectCopyService,
        \Magento\Payment\Api\PaymentMethodListInterface $paymentMethodListInterface,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Quote\Model\Quote\TotalsCollector $totalsCollector,
        \Onestepcheckout\Iosc\Helper\Data $helper
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->mockManager = $mockManager;
        $this->converter = $converter;
        $this->serviceOutputProcessor = $serviceOutputProcessor;
        $this->paymentMethodManagement = $paymentMethodManagement;
        $this->dir = $dir;
        $this->remoteAddress = $remoteAddress;
        $this->regionFactory = $regionFactory;
        $this->customerSession = $customerSession;
        $this->objectCopyService =  $objectCopyService;
        $this->paymentMethodListInterface = $paymentMethodListInterface;
        $this->log = $logger;
        $this->totalsCollector = $totalsCollector;
        $this->helper = $helper;
    }

    /**
     * Add default data to address objects
     *
     * @param Observer $observer
     * @event sales_quote_collect_totals_before
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->helper->isEnabled()) {
            return;
        }

        $this->callDefaults($observer);
    }

    /**
     * Call defaults method
     *
     * @param Varien_Event_Observer $observer
     */
    private function callDefaults(\Magento\Framework\Event\Observer $observer)
    {
        $this->setAddressDefaults($observer);
        $this->setShippingDefaults($observer);
        $this->setPaymentDefaults($observer);
    }

    /**
     * Add default address data to address objects
     *
     * @param Observer $observer
     * @event sales_quote_collect_totals_before
     *
     * @return void
     */
    private function setAddressDefaults(\Magento\Framework\Event\Observer $observer)
    {
        /** @var Quote $quote */
        $quote = $observer->getEvent()->getQuote();

        /**
         * and if the object of quote is not present or related objects have any data
         * we will return as data is either set by customer or other parts of code
         */
        if (! is_object($quote)) {
            return;
        }

        $billingAddress = $quote->getBillingAddress();
        $shippingAddress = $quote->getShippingAddress();
        if (!$billingAddress->getQuoteId() || !$shippingAddress->getQuoteId()) {
            if (!empty($quote->getCustomer()) && !empty($quote->getCustomer()->getAddresses())) {
                $customer = $this->customerSession->getCustomer();

                if (!$billingAddress->getQuoteId()) {
                    $this->copyAddressData($customer->getPrimaryBillingAddress(), $billingAddress);
                }

                if (!$shippingAddress->getQuoteId()) {
                    $this->copyAddressData($customer->getPrimaryShippingAddress(), $shippingAddress);
                }
            } else { // guest or without addresses

                /**
                 * get the defaults from store config
                 */
                $shippingConfig = $this->helper->getConfig([], [
                    'shippingfields'
                ], 'shippingfields');
                $billingConfig = $this->helper->getConfig([], [
                    'billingfields'
                ], 'billingfields');

                if (!$shippingAddress->getQuoteId()) {
                    $currentShipping = [];
                    $scopeStore = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
                    if ($this->scopeConfig->getValue('onestepcheckout_iosc/geoip/enabled', $scopeStore)) {
                        $currentShipping = $this->getGeoIp2($quote);
                    }

                    if (empty($currentShipping)) {
                        $currentShipping = $this->_hasDataSet($shippingAddress, $shippingConfig);
                    }

                    if (!empty($currentShipping)) {
                        $shippingAddress->addData($currentShipping);
                    }
                }

                if (!$billingAddress->getQuoteId()) {
                    $currentBilling = $this->_hasDataSet($billingAddress, $billingConfig);
                    if (!empty($currentBilling)) {
                        $billingAddress->addData($currentBilling);
                    }
                }
            }
        }
    }

    /**
     *
     * @param array $source
     * @param $target
     * @return $target
     */
    private function copyAddressData($source, $target)
    {
        $this->objectCopyService->copyFieldsetToTarget(
            'customer_address',
            'to_quote_address',
            $source,
            $target
        );

        return $target;
    }
    /**
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void|\Onestepcheckout\Iosc\Observer\Frontend\Setdefaults
     */
    public function setShippingDefaults(\Magento\Framework\Event\Observer $observer)
    {

        /** @var Quote $quote */
        $quote = $observer->getEvent()->getQuote();

        /**
         * and if the object of quote is not present or related objects have any data
         * we will return as data is either set by customer or other parts of code
         */
        if (! is_object($quote)) {
            return;
        }
        $quote->setIoscAutoshippingMethod("");

        $scopeStore = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $newCode = $this->scopeConfig
            ->getValue('onestepcheckout_iosc/shipping/default', $scopeStore);
        $freeAvailable = $this->scopeConfig
            ->getValue('onestepcheckout_iosc/shipping/freeifavailable', $scopeStore);
        $defaultIfOne = $this->scopeConfig
            ->getValue('onestepcheckout_iosc/shipping/defaultifone', $scopeStore);

        if (empty($newCode) && !$freeAvailable && !$defaultIfOne) {
            return;
        }

        $oldCode = $quote->getShippingAddress()->getShippingMethod();

        $ratesData = $this
            ->getShippingRates($quote, $this->converter, $this->serviceOutputProcessor);
        $codes = [];

        foreach ($ratesData as $rates) {
            $codes[] = $rates['carrier_code'] . '_' . $rates['method_code'];
        }

        if (empty($codes)) {
            return;
        }
        $tablerate = array_search('tablerate_', $codes);
        if (isset($tablerate) && $tablerate && $tablerate !== null) {
            $codes[$tablerate] = 'tablerate_bestway';
        }

        $codeCount = (int)count($codes);
        $freeshippingCode = 'freeshipping_freeshipping';
        $isFreeShipping = in_array($freeshippingCode, $codes);
        if ($isFreeShipping && $newCode != $freeshippingCode && $freeAvailable) {
            $newCode = $freeshippingCode;
        } else {
            if ($codeCount === 1 && $defaultIfOne) {
                $newCode = current($codes);
            }
        }

        if ($quote->getIoscAutoshipping() && $codeCount !== 1) {
            return;
        }

        if (! empty($codes) && (empty($oldCode) || ! in_array($oldCode, $codes))) {
            if (in_array($newCode, $codes)) {
                $quote->getShippingAddress()->setShippingMethod($newCode);
                $quote->setIoscAutoshippingMethod($newCode);
            }
        }
    }

    /**
     * Get available shipping rates
     *
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Model\Cart\ShippingMethodConverter $converter
     * @param \Magento\Framework\Webapi\ServiceOutputProcessor $serviceOutputProcessor
     * @return array
     */
    private function getShippingRates(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Model\Cart\ShippingMethodConverter $converter,
        \Magento\Framework\Webapi\ServiceOutputProcessor $serviceOutputProcessor
    ) {
        $rates = [];
        $shippingAddress = $quote->getShippingAddress();

        $shippingAddress->setCollectShippingRates(true);
        $this->totalsCollector->collectAddressTotals($quote, $shippingAddress);

        $shippingRates = $shippingAddress->getGroupedAllShippingRates();

        foreach ($shippingRates as $carrierRates) {
            foreach ($carrierRates as $rate) {
                $rates[] = $converter->modelToDataObject($rate, $quote->getQuoteCurrencyCode());
            }
        }
        $rates = $serviceOutputProcessor
            ->convertValue($rates, '\Magento\Quote\Api\Data\ShippingMethodInterface[]');

        return $rates;
    }

    /**
     * Set default payment method for the user
     *
     * @param Varien_Event_Observer $observer
     * @return Onestepcheckout_OneStepCheckout_Model_Observers_PresetDefaults
     */
    private function setPaymentDefaults(\Magento\Framework\Event\Observer $observer)
    {
        /** @var Quote $quote */
        $quote = $observer->getEvent()->getQuote();

        $scopeStore = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $newCode = $this->scopeConfig->getValue('onestepcheckout_iosc/payments/methods', $scopeStore);
        if (empty($newCode) || !is_object($quote) || !$quote->getGrandTotal()) {
            return;
        }
        $oldCode = $quote->getPayment()->getMethod();
        if ($oldCode == $newCode) {
            return;
        }

        $storeId = $quote->getStoreId();
        $codes = $this->getPaymentMethodCodes($this->paymentMethodListInterface->getActiveList($storeId));
        if (empty($codes)) {
            return;
        }

        $codeCount = (int)count($codes);
        if ($codeCount === 1 && current($codes) !='free') {
            $newCode = current($codes);
        }
        if (!empty($codes) && (empty($oldCode) || !in_array($oldCode, $codes))) {
            if (in_array($newCode, $codes)) {
                if ($quote->isVirtual()) {
                    $quote->getBillingAddress()->setPaymentMethod($newCode);
                } else {
                    $quote->getShippingAddress()->setPaymentMethod($newCode);
                }

                try {
                    $quote->getPayment()->setQuote($quote)
                        ->setMethod($newCode)->getMethodInstance();
                } catch (\Exception $e) {
                }
            }
        }
    }

    /**
     *
     * @param ip
     * @param quote
     */
    private function getGeoIp2()
    {
        $data = [];

        $scopeStore = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $database = $this->dir
        ->getRoot() .
            '/' .
            str_replace(
                '../',
                '/',
                $this->scopeConfig->getValue('onestepcheckout_iosc/geoip/dbpath', $scopeStore)
            );

        try {
            $reader = new  \GeoIp2\Database\Reader($database);
            if (is_object($reader) && method_exists($reader, 'city')) {
                $record = $reader->city($this->remoteAddress->getRemoteAddress());

                if (! empty($record->country->isoCode)) {
                    $data['country_id'] = $record->country->isoCode;
                }

                if (! empty($record->mostSpecificSubdivision->isoCode)) {
                    $region = $this->regionFactory->create();
                    $data['region_id'] = $region
                        ->loadByName(
                            $record->mostSpecificSubdivision->names['en'],
                            $record->country->isoCode
                        )->getRegionId();
                }

                if (! empty($record->city)) {
                    $data['city'] = utf8_encode($record->city->name);
                }

                if (! empty($record->postal->code)) {
                    $data['postcode'] = $record->postal->code;
                }
            } else {
                $this->log
                    ->system('GeoIp2 database %s is not installed properly or is
                            inaccessible or region information for ip: %s not found');
            }
        } catch (\Exception $e) {
            $this->log->critical($e);
            return $data;
        }

        return $data;
    }

    /**
     *
     * @param array $methods
     * @return array []
     */
    private function getPaymentMethodCodes($methods)
    {
        $codes = [];
        foreach ($methods as $method) {
            $codes[] = $method->getCode();
        }
        return $codes;
    }

    /**
     * Check if object has values or default values set
     *
     * @param Magento\Quote\Model\Quote\Address $address
     * @return array();
     */
    private function _hasDataSet($address, array $defaults = [])
    {
        $data = [];
        $address = $address->getData();

        foreach ($defaults as $key => $value) {
            if ($value['default_value'] != '') {
                /**
                 * special cases for region and streets are needed as those are handled differently in data structure
                 */
                if ($key == 'region' && is_numeric($value['default_value'])) {
                    $data['region_id'] = $value['default_value'];
                } else {
                    $data[$key] = $value['default_value'];
                }
            }
        }

        return $data;
    }
}
