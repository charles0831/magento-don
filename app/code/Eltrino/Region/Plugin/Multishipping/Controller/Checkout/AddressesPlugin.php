<?php
/**
 * Plugin is used to pass customer to multishipping checkout addresses
 * page in case there is no allowed by Eltrino_Region module addresses,
 * instead redirected to new shipping address page
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
 * @copyright   Copyright (c) 2019 Eltrino LLC. (http://eltrino.com)
 * @license     http://eltrino.com/license-eula.txt  Eltrino LLC EULA
 */

namespace Eltrino\Region\Plugin\Multishipping\Controller\Checkout;

use Eltrino\Region\Helper\DisabledRegionHelper;
use Magento\Customer\Model\Session;
use Magento\Framework\Controller\ResultFactory;

class AddressesPlugin
{
    /**
     * @var DisabledRegionHelper
     */
    protected $disabledRegionHelper;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var ResultFactory
     */
    protected $resultFactory;

    /**
     * AddressesPlugin constructor.
     * @param DisabledRegionHelper $disabledRegionHelper
     * @param Session $customerSession
     * @param ResultFactory $resultFactory
     */
    public function __construct(
        DisabledRegionHelper $disabledRegionHelper,
        Session $customerSession,
        ResultFactory $resultFactory
    ) {
        $this->disabledRegionHelper = $disabledRegionHelper;
        $this->customerSession = $customerSession;
        $this->resultFactory = $resultFactory;
    }

    /**
     * Multishipping checkout select address page
     *
     * @return void
     */
    public function aroundExecute($subject, callable $proceed)
    {
        // handle case when no allowed addresses left, so we need to redirect
        // customer to new shipping page instead of select addresses page
        $addresses = $this->customerSession->getCustomer()->getAddresses();
        $forbiddenAddressesQty = 0;
        foreach ($addresses as $address) {
            if ($this->disabledRegionHelper->isAddressForbidden($address->getDataModel())) {
                $forbiddenAddressesQty++;
            }
        }

        if (count($addresses) - $forbiddenAddressesQty === 0) {
            $result = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $result->setPath('multishipping/checkout_address/newShipping');
            return $result;
        } else {
            return $proceed();
        }
    }
}
