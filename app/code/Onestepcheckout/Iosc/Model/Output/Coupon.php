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
namespace Onestepcheckout\Iosc\Model\Output;

class Coupon implements OutputManagementInterface
{

    public function getOutputKey()
    {
        return 'coupon-code';
    }

    public $scopeConfig = null;

    /**
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Onestepcheckout\Iosc\Helper\Data $helper
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Framework\Escaper $escaper
     * @param \Psr\Log\LoggerInterface $loggerInterface
     * @param \Magento\SalesRule\Model\CouponFactory $couponFactory
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Onestepcheckout\Iosc\Helper\Data $helper,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Framework\Escaper $escaper,
        \Psr\Log\LoggerInterface $loggerInterface,
        \Magento\SalesRule\Model\CouponFactory $couponFactory,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {

        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
        $this->quoteRepository = $quoteRepository;
        $this->escaper = $escaper;
        $this->loggerInterface = $loggerInterface;
        $this->couponFactory = $couponFactory;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * {@inheritDoc}
     * @see \Onestepcheckout\Iosc\Model\Input\InputManagement::processPayload()
     */
    public function processPayload($input)
    {
        $data = [];

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->checkoutSession->getQuote();

        $response = [
            'success' => false,
            'error' => false,
            'message' => false
        ];

        $input = (isset($input[$this->getOutputKey()])) ? $input[$this->getOutputKey()] : false;

        if ($input && $quote->getId()) {
            if (isset($input['remove']) || isset($input['coupon_code'])) {
                $couponCode = $input['remove'] == 1 ? '' : trim($input['coupon_code']);
                $oldCouponCode = $quote->getCouponCode();

                $codeLength = strlen($couponCode);
                if (! $codeLength && empty($oldCouponCode)) {
                    $response['success'] = true;
                    $response['error'] = false;
                    $response['message'] = '';
                }

                if (!$response['success']) {
                    try {
                        $maxLength = \Magento\Checkout\Helper\Cart::COUPON_CODE_MAX_LENGTH;
                        $isCodeLengthValid = $codeLength && $codeLength <= $maxLength;

                        $itemsCount = $quote->getItemsCount();
                        if ($itemsCount) {
                            $quote->getShippingAddress()->setCollectShippingRates(true);
                            $quote->setCouponCode($isCodeLengthValid ? $couponCode : '')->collectTotals();
                            $this->quoteRepository->save($quote);
                        }

                        if ($codeLength) {
                            $response = $this->setCouponCode(
                                $quote,
                                $response,
                                $couponCode,
                                $isCodeLengthValid,
                                $itemsCount
                            );
                        } else {
                            $response['success'] = true;
                            $response['message'] = __('You canceled the coupon code.');
                        }
                    } catch (\Magento\Framework\Exception\LocalizedException $e) {
                        $response['success'] = false;
                        $response['error'] = true;
                        $response['message'] = $e->getMessage();
                    } catch (\Exception $e) {
                        $response['success'] = false;
                        $response['error'] = true;
                        $response['message'] = __($e->getMessage());
                        $this->loggerInterface->critical($e);
                    }
                }
            }
        }
        $data = $response;
        return $data;
    }

    /**
     *
     * @param unknown $quote
     * @param unknown $response
     * @param unknown $couponCode
     * @param unknown $isCodeLengthValid
     * @param unknown $itemsCount
     * @return array
     */
    private function setCouponCode(
        $quote,
        $response,
        $couponCode,
        $isCodeLengthValid,
        $itemsCount
    ) {
        $escapedCode = $this->escaper->escapeHtml($couponCode);
        if (! $itemsCount) {
            if ($isCodeLengthValid) {
                $coupon = $this->couponFactory->create();
                $coupon->load($couponCode, 'code');
                if ($coupon->getId()) {
                    $quote->setCouponCode($couponCode)->save();
                    $response['success'] = true;
                    $response['error'] = false;
                    $response['message'] = __('You used coupon code "%1".', $escapedCode);
                } else {
                    $response['success'] = false;
                    $response['error'] = true;
                    $response['message'] = __('The coupon code "%1" is not valid.', $escapedCode);
                }
            } else {
                $response['success'] = false;
                $response['error'] = true;
                $response['message'] = __('The coupon code "%1" is not valid.', $escapedCode);
            }
        } else {
            if ($isCodeLengthValid && $couponCode == $quote->getCouponCode()) {
                $response['success'] = true;
                $response['message'] = __('You used coupon code "%1".', $escapedCode);
            } else {
                $response['success'] = false;
                $response['error'] = true;
                $response['message'] = __('The coupon code "%1" is not valid.', $escapedCode);
                $this->cart->save();
            }
        }
        return $response;
    }
}
