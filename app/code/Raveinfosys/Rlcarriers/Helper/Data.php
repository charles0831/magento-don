<?php

namespace Raveinfosys\Rlcarriers\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_ORIGIN_CITY = 'shipping/origin/city';
    const XML_PATH_ORIGIN_REGION_ID = 'shipping/origin/region_id';
    const XML_PATH_ORIGIN_COUNTRY_ID = 'shipping/origin/country_id';
    const XML_PATH_ORIGIN_POSTCODE = 'shipping/origin/postcode';
    const XML_PATH_ENABLED = 'carriers/rlcarriers/enabled';
    const XML_PATH_DEBUG = 'carriers/rlcarriers/debug';
    const XML_PATH_SHIPCLASS = 'carriers/rlcarriers/shipclass';

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $_encryptor;

    /**
     * @var \Magento\Directory\Model\Country
     */
    protected $_country;

    /**
     * @var \Magento\Directory\Model\Region
     */
    protected $_region;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Module\ModuleListInterface $moduleList
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Directory\Model\Country $country,
        \Magento\Directory\Model\Region $region
    ) {
        $this->_encryptor = $encryptor;
        $this->_country = $country;
        $this->_region = $region;
        parent::__construct($context);
    }
     
    public function getConfigValue($path)
    {
        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    
    /**
     * Check if enabled
     *
     * @return string|null
     */
    public function isEnabled()
    {
        return $this->getConfigValue(self::XML_PATH_ENABLED);
    }

    public function getDebugStatus()
    {
        return $this->getConfigValue(self::XML_PATH_DEBUG);
    }

    public function getOriginCity()
    {
        return $this->getConfigValue(self::XML_PATH_ORIGIN_CITY);
    }

    public function getOriginRegionId()
    {
        return $this->getConfigValue(self::XML_PATH_ORIGIN_REGION_ID);
    }

    public function getOriginCountry()
    {
        return $this->getConfigValue(self::XML_PATH_ORIGIN_COUNTRY_ID);
    }

    public function getOriginPostcode()
    {
        return $this->getConfigValue(self::XML_PATH_ORIGIN_POSTCODE);
    }

    public function getShipclass()
    {
        return $this->getConfigValue(self::XML_PATH_SHIPCLASS);
    }

    public function getShipClasses()
    {
        return [
            ['value' => '', 'label' => __('Please Select')],
            ['value' => 50, 'label' => __('Class 50')],
            ['value' => 55, 'label' => __('Class 55')],
            ['value' => 60, 'label' => __('Class 60')],
            ['value' => 65, 'label' => __('Class 65')],
            ['value' => 70, 'label' => __('Class 70')],
            ['value' => 77.5, 'label' => __('Class 77.5')],
            ['value' => 85, 'label' => __('Class 85')],
            ['value' => 92.5, 'label' => __('Class 92.5')],
            ['value' => 100, 'label' => __('Class 100')],
            ['value' => 110, 'label' => __('Class 110')],
            ['value' => 125, 'label' => __('Class 125')],
            ['value' => 150, 'label' => __('Class 150')],
            ['value' => 175, 'label' => __('Class 175')],
            ['value' => 200, 'label' => __('Class 200')],
            ['value' => 250, 'label' => __('Class 250')],
            ['value' => 300, 'label' => __('Class 300')],
            ['value' => 400, 'label' => __('Class 400')],
            ['value' => 500, 'label' => __('Class 500')],
        ];
    }

    public function decrypt($value)
    {
        if (!$value) {
            return "";
        }
        return $this->_encryptor->decrypt($value);
    }

    public function getCountryISO3Code($countryID)
    {
        $country = $this->_country->loadByCode($countryID);
        if ($country) {
            return $country->getData('iso3_code');
        }
    }

    public function getRegionCodeById($ID)
    {
        $region = $this->_region->load($ID);
        if ($region) {
            return $region->getCode();
        }
    }
}
