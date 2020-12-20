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
define([ "uiComponent", "uiRegistry", "ko", "jquery", 'Magento_Checkout/js/model/payment/additional-validators' ], function (uiComponent, uiRegistry, ko, jQuery, additionalValidators) {
    "use strict";
    return uiComponent.extend({
        initialize : function () {
            this._super();
        },

        placeOrder: function () {

            uiRegistry.async("checkout.steps.shipping-step.shippingAddress")(
                function (shippingValidation) {
                    var methodsAvailable = shippingValidation.rates().length;
                    if (methodsAvailable <= 0) {
                        uiRegistry.async("checkout.steps.shipping-step.shippingAddress.before-shipping-method-form.iosc_shipping_validationmessage")(
                            function (shippingValidation) {
                                if (typeof shippingValidation.setValidationMessage === "function") {
                                    shippingValidation.setValidationMessage(jQuery.mage.__("Please specify address info to get rates!"));
                                }
                            }
                        );
                    } else {
                        if (typeof shippingValidation.setValidationMessage !== "undefined") {
                            shippingValidation.setValidationMessage(false);
                        }
                    }
                }
            );

            uiRegistry.async("checkout.steps.billing-step.payment.afterMethods.iosc-payment-validationmessage")(
                function (paymentValidation) {
                    var methodSelected = paymentValidation.validatePaymentMethods();
                    if (!methodSelected) {
                        paymentValidation.setValidationMessage(jQuery.mage.__("Please choose a payment method!"));
                    } else {
                        paymentValidation.setValidationMessage(false);
                    }
                }
            );

            additionalValidators.validate();
        }
    });

});
