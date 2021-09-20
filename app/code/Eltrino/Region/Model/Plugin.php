<?php
/**
 * Remove or Change Displayed States and Regions
 *
 * LICENSE
 *
 * This source file is subject to the Eltrino LLC EULA
 * that is bundled with this package in the file LICENSE_EULA.txt.
 * It is also available through the world-wide-web at this URL:
 * http://eltrino.com/license-eula.txt
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@eltrino.com so we can send you a copy immediately.
 *
 * @category    Eltrino
 * @package     Eltrino_Region
 * @copyright   Copyright (c) 2015 Eltrino LLC. (http://eltrino.com)
 * @license     http://eltrino.com/license-eula.txt  Eltrino LLC EULA
 */
namespace Eltrino\Region\Model;

use Eltrino\Region\Helper\DisabledRegionHelper;
use Magento\Checkout\Block\Checkout\AttributeMerger;
use Magento\Customer\Model\Customer;
use Magento\Checkout\Block\Onepage;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Message\ManagerInterface;
use Magento\Checkout\Model\Session as CheckoutSession;

/**
 * Class Plugin
 * @package Eltrino\Region\Model
 */
class Plugin
{
    /**
     * @var DisabledRegionHelper
     */
    protected $disabledRegionHelper;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @param DisabledRegionHelper $disabledRegionHelper
     * @param ManagerInterface $massageManager
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        DisabledRegionHelper $disabledRegionHelper,
        ManagerInterface $massageManager,
        CheckoutSession $checkoutSession
    ) {
        $this->disabledRegionHelper = $disabledRegionHelper;
        $this->messageManager = $massageManager;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Set Regions list for billing and shipping pages
     *
     * @param AttributeMerger $merger
     * @param $elements
     * @param $providerName
     * @param $dataScopePrefix
     * @param array $fields
     * @return array
     */
    public function beforeMerge(AttributeMerger $merger, $elements, $providerName, $dataScopePrefix, $fields = [])
    {
        if (strpos($dataScopePrefix, 'billing') !== false) {
            $billingElement =  $this->disabledRegionHelper->getAvailableRegionOptions();
            if ($billingElement && isset($elements['region_id'])) {
                $elements['region_id']['options'] = $billingElement;
            }

        } else {
            $shippingElement = $this->disabledRegionHelper->getAvailableRegionOptions();
            if ($shippingElement && isset($elements['region_id'])) {
                $elements['region_id']['options'] = $shippingElement;
            }
        }

        return [
            $elements, $providerName, $dataScopePrefix, $fields
        ];
    }

    /**
     * Get checkout Config
     * Add forbidden region list to configprovider.
     * @param Onepage $onpage
     * @param $output
     * @return mixed
     */
    public function afterGetCheckoutConfig(Onepage $onpage, $output)
    {
        if (isset($output['customerData']['addresses'])) {
            $disabledRegions = $this->disabledRegionHelper->getAllDisabledRegions();
            $addresses = $output['customerData']['addresses'];

            foreach ($addresses as $key => $adress) {
                if (isset($adress['region_id']) && in_array($adress['region_id'], $disabledRegions)) {
                    unset($output['customerData']['addresses'][$key]);
                }
            }
        }

        return $output;
    }

    /**
     * Get shipping address
     * @param $interceptor
     * @param $address
     * @return mixed
     */
    public function afterGetShippingAddress($interceptor, $address){
        $quote = $this->checkoutSession->getQuote();
        if ($quote->getIsVirtual()) {
            return false;
        }
        $regioinId = $address->getRegionId();
        $disabledRegions = $this->disabledRegionHelper->getAllDisabledRegions();
        $messages[] = $this->messageManager->createMessage('error', 'regionNotAvaliableError')->setText('<span id="regionNotAvaliableError">Shipping is not available for this region. To proceed, select another region or contact our support team</span>');
        if(in_array($regioinId, $disabledRegions)){
            $this->messageManager->addUniqueMessages($messages);
        }
        return $address;
    }
}
