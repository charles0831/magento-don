<?php

namespace Raveinfosys\Rlcarriers\Model\Carrier;

use Raveinfosys\Rlcarriers\Model\Carrier;

class Rlcarriers extends AbstractCarrierModel
{

    /**
     * Carrier's code
     *
     * @var string
     */
    protected $_code = 'rlcarriers';

    /**
     * Fields that should be replaced in debug with '***'
     *
     * @var array
     */
    protected $_debugReplacePrivateDataKeys = ['APIKey'];

    /**
     * Request Quote URL
     */
    protected $_requestQuoteUrl = 'http://api.rlcarriers.com/1.0.1/RateQuoteService.asmx?WSDL';
    protected $_apiKey = null;
    protected $_debugData = [];

    public function collectFreightRate()
    {
        $request = $this->buildRequest();
        $this->_debugData['request'] = $request;
        try {
            $rates = $this->postRequest($request);
        } catch (\Exception $e) {
            return false;
        }
        return $rates;
    }

    public function buildRequest()
    {
        $_originZip = $this->_dataHelper->getOriginPostcode();
        $_defaultShipClass = $this->_dataHelper->getShipclass();

        $this->_apiKey = $this->_dataHelper->decrypt($this->getConfigData('apikey'));

        if (!$this->_apiKey) {
            return false;
        }
        $request["APIKey"] = $this->_apiKey;
        $request["QuoteType"] = "Domestic";
        $request["CODAmount"] = "0";

        $request["Origin"] = [
            "City" => "",
            "StateOrProvince" => "",
            "ZipOrPostalCode" => trim($_originZip),
            "CountryCode" => $this->_dataHelper->getCountryISO3Code($this->_dataHelper->getOriginCountry())
        ];

        $request["Destination"] = [
            "City" => "",
            "StateOrProvince" => "",
            "ZipOrPostalCode" => trim($this->_request->getDestPostcode()),
            "CountryCode" => $this->_dataHelper->getCountryISO3Code($this->_request->getDestCountryId()),
        ];
        $request["DeclaredValue"] = 0;

        $request["OverDimensionPcs"] = 0;

        $items = [];
        $cn = 0;
        foreach ($this->getItems() as $item) {
            $items[$cn]['Class'] = (float) ($this->_dataHelper->getShipclass());
            $items[$cn]['Weight'] = (float) ceil($item->getProduct()->getWeight() * $item->getQty());
            $items[$cn]['Height'] = (float) $item->getProduct()->getFreightWidth();
            $items[$cn]['Width'] = (float) $item->getProduct()->getFreightHeight();
            $items[$cn]['Length'] = (float) $item->getProduct()->getFreightLength();
        }

        $request["Accessorials"] = [];

        $request["Items"] = $items;

        return $request;
    }

    public function postRequest($request)
    {
        try {
            $client = new \Zend\Soap\Client($this->_requestQuoteUrl);
            $response = $client->GetRateQuote(["APIKey" => $this->_apiKey, "request" => $request]);
            $this->_debugData['result'] = $response;
            $_results = $this->parseXmlResponse($response);
        } catch (\Exception $e) {
            $this->_debugData['result'] = $e->getMessage();
            return false;
        }
        $this->debugRlcData($this->_debugData);
        return $_results;
    }

    public function parseXmlResponse($response)
    {
        $_rates = [];
        try {
            $_result = $response->GetRateQuoteResult->Result;
            $_rlcRates = $_result->ServiceLevels->ServiceLevel;
        } catch (\Exception $e) {
            return $_rates;
        }
        $_allowdMethods = $this->getConfigData('allowed_methods_rl');
        if (is_array($_rlcRates)) {
            $_methods = explode(',', $_allowdMethods);
            foreach ($_rlcRates as $_rlcRate) {
                if (!in_array($_rlcRate->Code, $_methods)) {
                    continue;
                }
                $_rate = preg_replace('/[^0-9.]* /', '', str_replace('$', '', $_rlcRate->NetCharge));
                $_rates[] = [
                    'rate'      => $_rate,
                    'method'    => $_rlcRate->Title,
                    'code'      => $_rlcRate->Code,
                    'carrier'   => $this->_code
                ];
            }
        } else {
            $_rate = preg_replace('/[^0-9.]*/', '', str_replace('$', '', $_result->ServiceLevels->ServiceLevel->NetCharge));
            $_rates[] = [
                'rate'      => $_rate,
                'carrier'   => $this->_code,
                'method'    => $_result->ServiceLevels->ServiceLevel->Title,
                'code'      => $_result->ServiceLevels->ServiceLevel->Code
            ];
        }
        return $_rates;
    }
}
