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
define(
    [
    "uiComponent",
    "uiRegistry",
    "ko",
    "jquery",
    "mage/utils/wrapper",
    "Magento_Checkout/js/model/quote",
    "Magento_Checkout/js/model/shipping-service",
    "Magento_Customer/js/model/customer"
    ],
    function (uiComponent, uiRegistry, ko, jQuery, wrapper, quote, shippingService, customer) {
    "use strict";
    return uiComponent.extend({
        initialize: function () {
            this._super();
            this.billingElems = ko.observable();
            this.shippingElems = ko.observable();
            this.shippingElems.subscribe(
                function (value) {
                    this.controlShippingAddressElems(value);
                }.bind(this), null, 'change'
            );
            this.billingElems.subscribe(
                function (value) {
                    this.controlBillingAddressElems(value);
                }.bind(this), null, 'change'
            );
            uiRegistry.async({"component": "Onestepcheckout_Iosc/js/shippingfields"})(
                function (ioscShippingfields) {
                    uiRegistry.async(
                        "checkout.steps.shipping-step.shippingAddress.before-form.amazon-widget-address"
                    )(
                        function (amazonAddress) {
                            if(amazonAddress.isAmazonAccountLoggedIn()){
                                ioscShippingfields.skipListLengthValidation(true);
                            } else {
                                ioscShippingfields.skipListLengthValidation(false);
                            }
                        }
                    );

                    ioscShippingfields.validationHandler = wrapper.wrap(
                        ioscShippingfields.validationHandler,
                        function (originMethod) {
                            if(ioscShippingfields.skipListLengthValidation()){
                                return true;
                            } else {
                                return originMethod();
                            }
                        }
                    );
                    uiRegistry.async(
                        "checkout.steps.shipping-step.shippingAddress.before-form.amazon-widget-address"
                    )(
                        function (amazonAddress) {
                            amazonAddress.getShippingAddressFromAmazon = wrapper.wrap(
                                amazonAddress.getShippingAddressFromAmazon,
                                function (originalMethod) {
                                    originalMethod();
                                    shippingService.isLoading(false);
                                }
                            );
                            if(!amazonAddress.isAmazonAccountLoggedIn()){
                                this.shippingElems(false);
                                this.billingElems(false);
                            }
                            amazonAddress.isAmazonAccountLoggedIn.subscribe(
                                function (value) {
                                    this.shippingElems(!(value) ? 0:1);
                                    this.billingElems(!(value) ? 0:1);
                                }.bind(this), null, 'change'
                            );
                            uiRegistry.async("checkout.steps.shipping-step.iosc-billing-fields")(
                                function (ioscBillingfields) {
                                    uiRegistry.async('checkout.iosc.ajax')(
                                        function (ajax) {
                                            if(typeof ajax.getMethods('params').address !== "undefined") {
                                                ajax.getMethods('params').address = wrapper.wrap(
                                                        ajax.getMethods('params').address,
                                                        function (originMethod) {
                                                            if(amazonAddress.isAmazonAccountLoggedIn()){
                                                                return quote.billingAddress();
                                                            } else {
                                                                return originMethod();
                                                            }
                                                        }.bind(ioscBillingfields)
                                                    );
                                            }
                                            if(typeof ajax.getMethods('params').billingAddress !== "undefined") {
                                                ajax.getMethods('params').billingAddress = wrapper.wrap(
                                                    ajax.getMethods('params').billingAddress,
                                                    function (originMethod) {
                                                        if(amazonAddress.isAmazonAccountLoggedIn()){
                                                            return quote.billingAddress();
                                                        } else {
                                                            return originMethod();
                                                        }
                                                    }.bind(ioscBillingfields)
                                                );
                                            }
                                        }
                                    );
                                }
                            );

                        }.bind(this)
                    );
                }.bind(this)
            );
            uiRegistry.async(
                "checkout.steps.shipping-step.shippingAddress.before-form.amazon-widget-address.before-widget-address.amazon-checkout-revert"
            ) (
                function (amazonCheckoutRevert) {
                    amazonCheckoutRevert.revertCheckout = wrapper.wrap(
                        amazonCheckoutRevert.revertCheckout,
                        function (originalMethod) {
                            originalMethod();
                        }
                    );
                }.bind(this)
            );
            uiRegistry.async({"component": "Onestepcheckout_Iosc/js/payments"})(
                function (ioscPayments) {
                    uiRegistry.async("checkout.steps.billing-step.payment.payments-list.amazon_payment")(
                        function (amazonPayment) {
                            if(amazonPayment.isAmazonAccountLoggedIn()){
                                ioscPayments.waitWithMove(true);
                            }
                            amazonPayment.initPaymentWidget = wrapper.wrap(
                                amazonPayment.initPaymentWidget,
                                function(originalMethod) {
                                    originalMethod();
                                    setTimeout(
                                        function(){
                                            amazonPayment.isPlaceOrderDisabled(false);
                                            ioscPayments.waitWithMove(false);
                                            amazonPayment.selectPaymentMethod();
                                        }
                                        , 1300
                                    );
                                }
                            );
                        }.bind(this)
                    );
                }.bind(this)
            );
            uiRegistry.async({"component": "Amazon_Payment/js/view/payment/list"})(
                function (paymentList) {
                    paymentList._hidePaymentMethodsOnLoad();
                }.bind(this)
            );
            uiRegistry.async({"component": "Amazon_Payment/js/view/shipping-address/list"})(
                function (addressList) {
                    var subscription = addressList.elems.subscribe(
                        function(elem){
                            addressList.elems.remove(
                                function (item) {
                                    if(
                                       typeof item.address().firstname == "undefined"
                                           || item.address().firstname == ""
                                           || item.address().firstname == "-") {
                                        return true;
                                    }
                                }
                            );
                    }, this, "arrayChange");
                }.bind(this)
            );

        },

        controlShippingAddressElems: function(state) {
            if(state){
                uiRegistry.async(
                    "checkout.steps.shipping-step.shippingAddress.address-list"
                ) (
                    function (shippingAddressList) {
                        shippingAddressList.visible = ko.observable(false);
                    }.bind(this)
                );
                uiRegistry.async(
                    "checkout.steps.shipping-step.shippingAddress"
                ) (
                    function (shippingAddressView) {
                        shippingAddressView.isNewAddressAdded = ko.observable(false);
                        shippingAddressView.isFormInline = false;
                        shippingAddressView.saveInAddressBook = 0;
                    }.bind(this)
                );
            } else {
                uiRegistry.async(
                    "checkout.steps.shipping-step.shippingAddress.address-list"
                ) (
                    function (shippingAddressList) {
                        if(!customer.isLoggedIn() || (customer.isLoggedIn() && window.customerData.addresses.length === 0)){
                            shippingAddressList.visible = ko.observable(false);
                        }
                    }.bind(this)
                );
                uiRegistry.async(
                    "checkout.steps.shipping-step.shippingAddress"
                ) (
                    function (shippingAddressView) {
                        if(!customer.isLoggedIn()){
                            shippingAddressView.isNewAddressAdded = ko.observable(false);
                            shippingAddressView.isFormInline = true;
                        }
                        shippingAddressView.saveInAddressBook = 1;
                    }.bind(this)
                );
            }
        },

        controlBillingAddressElems: function(state) {
            if(state){
                uiRegistry.async(
                    "checkout.steps.shipping-step.iosc-billing-fields"
                ) (
                    function (billingAddressView) {
                        billingAddressView.shippingDomReady = function(val){return false; };
                    }.bind(this)
                );
            } else  {
                uiRegistry.async(
                    "checkout.steps.shipping-step.iosc-billing-fields"
                ) (
                    function (billingAddressView) {
                        billingAddressView.shippingDomReady = ko.observable(false);
                    }.bind(this)
                );
            }
        }
    });
});
