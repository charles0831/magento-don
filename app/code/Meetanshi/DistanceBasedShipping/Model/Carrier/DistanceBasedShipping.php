<?php

namespace Meetanshi\DistanceBasedShipping\Model\Carrier;

use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Directory\Model\AllowedCountries;
use Magento\Directory\Model\ResourceModel\Region\Collection as RegionCollection;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\ResultFactory;
use Meetanshi\DistanceBasedShipping\Block\Adminhtml\Form\Field\TypeColumn;
use Meetanshi\DistanceBasedShipping\Helper\Data as Helper;
use Meetanshi\DistanceBasedShipping\Model\ResourceModel\DistanceBasedShipping\CollectionFactory as DBSCollectionFactory;
use Psr\Log\LoggerInterface;

class DistanceBasedShipping extends AbstractCarrier implements CarrierInterface
{
    protected $_code = 'distancebasedshipping';

    protected $_isFixed = true;

    private $rateResultFactory;

    private $rateMethodFactory;

    private $helper;

    private $cart;

    private $dbsCollectionFactory;

    private $regionCollection;

    private $checkoutSession;

    private $allowedCountryModel;

    public function __construct(
        AllowedCountries $allowedCountryModel,
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        ResultFactory $rateResultFactory,
        MethodFactory $rateMethodFactory,
        Helper $helper,
        Cart $cart,
        DBSCollectionFactory $dbsCollectionFactory,
        RegionCollection $regionCollection,
        CheckoutSession $checkoutSession,
        array $data = []
    ) {
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
        $this->rateResultFactory = $rateResultFactory;
        $this->rateMethodFactory = $rateMethodFactory;
        $this->helper = $helper;
        $this->cart = $cart;
        $this->dbsCollectionFactory = $dbsCollectionFactory;
        $this->regionCollection = $regionCollection;
        $this->checkoutSession = $checkoutSession;
        $this->allowedCountryModel = $allowedCountryModel;
        \Magento\Framework\App\ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class)->info(__FILE__.':'.__LINE__.'->'.'called');
    }

    public function collectRates(RateRequest $request)
    {
        \Magento\Framework\App\ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class)->info(__FILE__.':'.__LINE__.'->'.'called');
        $result=$this->checkAvailability($request);
        if ($result == false && $this->getConfigData('showmethod')) {
            $result = $this->rateResultFactory->create();
            $error = $this->_rateErrorFactory->create(
                [
                    'data' => [
                        'carrier' => $this->_code,
                        'carrier_title' => $this->getConfigData('title'),
                        'error_message' => $this->getConfigData('specificerrmsg'),
                    ],
                ]
            );
            $result->append($error);
            return $result;
        } else {
            return $result;
        }
    }
    public function checkAvailability(RateRequest $request)
    {
        try {
            $quote=$this->checkoutSession->getQuote();
            if (!$this->getConfigFlag('active')) {
                return false;
            }

            if (!$quote->getPickupFromId()) {
                return false;
            }

            if (!$request->getDestCountryId()) {
                return false;
            }

            $grandTotal = $this->cart->getQuote()->getGrandTotal();

            if ($grandTotal < $this->getConfigData('minimum_order_amount') ||
                $grandTotal > $this->getConfigData('maximum_order_amount')) {
                return false;
            }

            $result = $this->rateResultFactory->create();

            $method = $this->rateMethodFactory->create();
            $method->setCarrier($this->_code);
            $method->setCarrierTitle($this->getConfigData('title'));
            $method->setMethod($this->_code);
            $method->setMethodTitle($this->getConfigData('name'));

            $shippingCosts = $this->getConfigData('shipping_cost');
            if ($this->getConfigData('sallowspecific')) {
                $specificCountry = $this->getConfigData('specificcountry');
                $specificCountriesArray=explode(',', $specificCountry);
                if (!in_array($request->getDestCountryId(), $specificCountriesArray)) {
                    return false;
                }
            } else {
                if (!in_array($request->getDestCountryId(), $this->allowedCountryModel->getAllowedCountries())) {
                    return false;
                }
            }

            $shippingCostsArray = $this->helper->getSerializedConfigValue($shippingCosts);
            $regionName=$this->regionCollection
                ->addFieldToFilter(
                    'code',
                    $request->getDestRegionCode()
                )
                ->addFieldToFilter(
                    'country_id',
                    $request->getDestCountryId()
                )
                ->load()->getData();

            if (!count($regionName)) {
                return false;
            }

            $dbsCollectionFactory = $this->dbsCollectionFactory->create();
            $sourceAddressPoint = $dbsCollectionFactory
                ->addFieldToSelect(['longitude', 'latitude'])
                ->addFieldToFilter('id', $quote->getPickupFromId())->load()->getData();
            if ($sourceAddressPoint instanceof \Exception) {
                throw $sourceAddressPoint;
            }
            if ($regionName[0]['default_name']!='') {
                $region=$regionName[0]['default_name'];
            } else {
                $region=$regionName[0]['name'];
            }
            $destinationAddress = [
                'street' => $request->getDestStreet(),
                'country_id' => $request->getDestCountryId(),
                'city' => $request->getDestCity(),
                'postcode' => $request->getDestPostcode(),
                'region' => $region
            ];

            $destinationAddressPoint = $this->getLatLngFromAddress($destinationAddress);
            if ($destinationAddressPoint instanceof \Exception) {
                throw $destinationAddressPoint;
            }
            $distance = $this->getDistanceBetweenPoints(
                $sourceAddressPoint[0]['latitude'],
                $sourceAddressPoint[0]['longitude'],
                $destinationAddressPoint['lat'],
                $destinationAddressPoint['lng']
            );

            if ($distance instanceof \Exception) {
                throw $distance;
            }
            $distance=$distance[$this->getConfigData('distance_unit')];
            $maximumDistanceAmount=$this->getConfigData('maximum_distance_amount');
            if ($maximumDistanceAmount>=$distance && $maximumDistanceAmount!=0) {
                return false;
            }
            $cost = 0;
            $distanceRangeMatch = false;
            foreach ($shippingCostsArray as $shippingCosts) {
                if ($distance >= $shippingCosts['from'] && $distance < $shippingCosts['to']) {
                    if ($shippingCosts['type'] == TypeColumn::TYPE_FIXED) {
                        $cost = (float)$shippingCosts['cost'];
                    } else {
                        $cost = ((float)$shippingCosts['cost'] * $distance);
                    }
                    $distanceRangeMatch = true;
                    break;
                }
            }

            if (!$distanceRangeMatch) {
                return false;
            }

            if ($request->getFreeShipping()) {
                $method->setPrice(0);
                $method->setCost(0);

                $result->append($method);
                return $result;
            }

            $method->setPrice($cost);
            $method->setCost($cost);
            $result->append($method);
            return $result;
        } catch (\Exception $e) {
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/distanceError.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);
            $logger->info($e->getMessage());
            return false;
        }
    }
    public function getLatLngFromAddress($map)
    {
        try {
            $address = implode(',', $map);
            $address = urlencode($address);
            $key=$this->getConfigData('google_map_api');
            $protocol = 'https';
            $url = sprintf(
                "%s://maps.google.com/maps/api/geocode/json?address=%s&key=%s",
                $protocol,
                $address,
                $key
            );
            $geocodeFromAddr = file_get_contents($url);
            $finalResponse = json_decode($geocodeFromAddr, true);

            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/distanceError.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);
            $logger->info(print_r($finalResponse,true));

            return $finalResponse["results"][0]['geometry']['location'];
        } catch (\Exception $e) {
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/distanceError.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);
            $logger->info($e->getMessage());
            return $e;
        }
    }
    public function getDistanceBetweenPoints($lat1, $lon1, $lat2, $lon2)
    {
        $theta = $lon1 - $lon2;
        $miles = (sin(deg2rad($lat1)) * sin(deg2rad($lat2))) + (cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta)));
        $miles = acos($miles);
        $miles = rad2deg($miles);
        $miles = $miles * 60 * 1.1515;
        $feet = $miles * 5280;
        $yards = $feet / 3;
        $kilometers = $miles * 1.609344;
        $meters = $kilometers * 1000;
        return compact('miles', 'feet', 'yards', 'kilometers', 'meters');
    }

    public function getAllowedMethods()
    {
        return [$this->_code => $this->getConfigData('name')];
    }
}
