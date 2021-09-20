<?php
/**
 * Plugin is used to pass customer to multishipping checkout addresses
 * page in case there is no allowed by Eltrino_Region module addresses,
 * instead redirected to new billing address page
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
use Magento\Multishipping\Controller\Checkout\Billing;
use Magento\Framework\Controller\ResultFactory;
use \Magento\Framework\Controller\ResultInterface;
use \Magento\Multishipping\Model\Checkout\Type\Multishipping;
use \Magento\Framework\Exception\LocalizedException;

class BillingPlugin
{
    /**
     * @var DisabledRegionHelper
     */
    protected $disabledRegionHelper;

    /**
     * @var ResultFactory
     */
    protected $resultFactory;

    /**
     * @var Multishipping
     */
    protected $checkout;

    /**
     * BillingPlugin constructor.
     * @param DisabledRegionHelper $disabledRegionHelper
     * @param Multishipping $checkout
     * @param ResultFactory $resultFactory
     */
    public function __construct(
        DisabledRegionHelper $disabledRegionHelper,
        Multishipping $checkout,
        ResultFactory $resultFactory
    ) {
        $this->disabledRegionHelper = $disabledRegionHelper;
        $this->checkout = $checkout;
        $this->resultFactory = $resultFactory;
    }

    /**
     * @param Billing $subject
     * @param callable $proceed
     * @return ResultInterface
     * @throws LocalizedException
     */
    public function aroundExecute(Billing $subject, callable $proceed)
    {
        // handle case when default billing address is forbidden, so we need to redirect
        // customer to select billing address page instead of billing step page
        $billingAddress = $this->checkout->getQuote()->getBillingAddress()->getDataModel();

        if ($this->disabledRegionHelper->isAddressForbidden($billingAddress)) {
            $result = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $result->setPath('multishipping/checkout_address/selectBilling');
            return $result;
        } else {
            return $proceed();
        }
    }
}
