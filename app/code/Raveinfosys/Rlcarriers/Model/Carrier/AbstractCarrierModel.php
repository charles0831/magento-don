<?php

namespace Raveinfosys\Rlcarriers\Model\Carrier;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Config;
use Magento\Shipping\Model\Rate\ResultFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\Method;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Framework\Encryption\EncryptorInterface;
use Raveinfosys\Rlcarriers\Helper\Data;
use Psr\Log\LoggerInterface;

abstract class AbstractCarrierModel extends AbstractCarrier implements CarrierInterface
{

    /**
     * Carrier's code
     *
     * @var string
     */
    protected $_code = '';

    /**
     * Whether this carrier has fixed rates calculation
     *
     * @var bool
     */
    protected $_isFixed = true;

    /**
     * @var ResultFactory
     */
    protected $_rateResultFactory;

    /**
     * @var MethodFactory
     */
    protected $_rateMethodFactory;

    /**
     * @var ErrorFactory
     */
    protected $_rateErrorFactory;

    /*
     * @RateRequest $request
     */
    protected $_request = null;

    /**
     * Config values
     */
    protected $_config = [];

    /**
     * Config Helper
     */
    protected $_dataHelper = [];

    /**
     * $LogFilePath
     */
    protected $_logFilePath = "/var/log/rlcarriers.log";

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ErrorFactory $rateErrorFactory
     * @param LoggerInterface $logger
     * @param ResultFactory $rateResultFactory
     * @param MethodFactory $rateMethodFactory
     * @param array $data
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        ResultFactory $rateResultFactory,
        MethodFactory $rateMethodFactory,
        Data $dataHelper,
        array $data = []
    ) {
        $this->_rateResultFactory = $rateResultFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        $this->_rateErrorFactory = $rateErrorFactory;
        $this->_logger = $logger;
        $this->_dataHelper = $dataHelper;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    abstract protected function collectFreightRate();

    /**
     * Generates list of allowed carrier`s shipping methods
     * Displays on cart price rules page
     *
     * @return array
     * @api
     */
    public function getAllowedMethods()
    {
        return [$this->getCarrierCode() => __($this->getConfigData('name'))];
    }

    /**
     * Collect and get rates for storefront
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @param RateRequest $request
     * @return DataObject|bool|null
     * @api
     */
    public function collectRates(RateRequest $request)
    {
        /**
         * Make sure that Shipping method is enabled
         */
        if (!$this->isActive()) {
            return false;
        }

        $this->_request = $request;

        if (!$this->isPackageWeightAllowed()) {
            return false;
        }

        /** @var \Magento\Shipping\Model\Rate\Result $result */
        $result = $this->_rateResultFactory->create();


        $rates = $this->collectFreightRate();
        if ($rates === false) {
            $error = $this->_rateErrorFactory->create();
            $error->setCarrier('rlcarriers');
            $error->setCarrierTitle($this->getConfigData('title'));
            $error->setErrorMessage($this->getConfigData('title'));
            $result->append($error);
        } else {
            foreach ($rates as $rate) {
                $shippingCost = $this->checkMinFreighCharge($rate['rate']);
                $finalShippingCost = $this->calculateHandlingFee($shippingCost);
                $method = $this->_rateMethodFactory->create();
                $method->setCarrier('rlcarriers');
                $method->setCarrierTitle($this->getConfigData('title'));
                $method->setMethod($rate['code']);
                $method->setMethodTitle($rate['method']);
                $method->setCost($finalShippingCost);
                $method->setPrice($finalShippingCost);
                $result->append($method);
            }
        }

        return $result;
    }

    public function calculateHandlingFee($_rate)
    {
        if ($this->getConfigData('handlingfee_type') == 'F') {
            $_rate = $_rate + $this->getConfigData('handlingfee');
        } elseif ($this->getConfigData('handlingfee_type') == 'P') {
            $_handlingFee = $this->getConfigData('handlingfee');
            $_rate = $_rate + ($_rate * $_handlingFee / 100);
        }
        return $_rate;
    }

    public function isPackageWeightAllowed()
    {
        $packageWeight = $this->_request->getPackageWeight();
        $min = $this->getConfigData('min_weight');
        $max = $this->getConfigData('max_weight');
        if ($packageWeight < 0 || ($min && $min > $packageWeight) || ($max && $max < $packageWeight)) {
            return false;
        }
        return true;
    }

    public function checkMinFreighCharge($price)
    {
        $minFerightCharge = $this->getConfigData('min_freight');
        if ($minFerightCharge && $minFerightCharge > $price) {
            $price = $minFerightCharge;
        }
        return $price;
    }

    public function getItems()
    {
        $items = [];
        foreach ($this->_request->getAllItems() as $item) {
            if ($item->getProduct()->isVirtual() || $item->getParentItem()) {
                continue;
            }
            if ($item->getHasChildren()) {
                foreach ($item->getChildren() as $child) {
                    if ($child->getProduct()->isVirtual()) {
                        continue;
                    }
                    $items[] = $child;
                }
            } else {
                $items[] = $item;
            }
        }
        return $items;
    }

    public function debugRlcData($data)
    {
        if (!$this->getConfigData('debug')) {
            return;
        }
        $writer = new \Zend\Log\Writer\Stream(BP . $this->_logFilePath);
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info(var_export($data, true));
    }
}
