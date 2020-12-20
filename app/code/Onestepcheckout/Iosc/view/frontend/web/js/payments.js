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
        "underscore",
        "jquery",
        "Magento_Checkout/js/model/quote",
        "ko",
        "Magento_Ui/js/lib/view/utils/dom-observer",
        "Magento_Checkout/js/view/payment/list",
        "Magento_Checkout/js/model/payment-service",
        "Magento_Checkout/js/model/payment/method-converter",
        "Magento_Checkout/js/model/payment/method-list",
        "Magento_Checkout/js/model/full-screen-loader",
        "mage/utils/wrapper"
    ],
    function (uiComponent, uiRegistry, _, jQuery, quote, ko, domObserver, paymentList, paymentService, methodConverter, methodList, fullScreenLoader, wrapper) {
        "use strict";
        return uiComponent.extend({

            defaults: {
                template: "Onestepcheckout_Iosc/place_order",
                displayArea: "summary"
            },

            initialize: function () {
                this._super();
                this.buttonIsMoved = false;
                this.parentObj = {};
                this.waitWithMove  = ko.observable(false);
                uiRegistry.async("checkout.steps.billing-step.payment")(
                    function (paymentStep) {
                        paymentStep.isVisible = ko.observable(true);
                    }
                );

                uiRegistry.async("vaultGroup")(
                    function (vaultGroup) {
                        var newAlias = 'default';
                        vaultGroup.alias = newAlias;
                        vaultGroup.displayArea = vaultGroup.displayArea.replace('vault', newAlias);
                    }.bind(this)
                );

                uiRegistry.async("checkout.iosc.ajax")(
                    function (ajax) {
                        this.ajax = ajax;
                        ajax.addMethod("success", "paymentMethod", this.successHandler.bind(this));
                        ajax.addMethod("error", "paymentMethod", this.errorHandler.bind(this));
                        quote.paymentMethod.subscribe(this.selectMethod.bind(this), null, "change");
                    }.bind(this)
                );

                uiRegistry.async("checkout.steps.billing-step.payment.payments-list")(
                    function (paymentListView) {
                        paymentListView.getGroupTitle = wrapper.wrap(paymentListView.getGroupTitle, function (originalMethod, group) {
                            return jQuery.mage.__("Payment Methods");
                        });
                    }
                );

                this.selectDefault();
                this.addTitleNumber();
            },

            /**
             * Observes payment method change
             *
             * @param observable
             */
            selectMethod: function (observable) {
                var placeOrderButton, permanentButton, methodScope,
                placeOrderButtonClone, origin, targetOrigin;

                if(observable == null){
                    permanentButton = jQuery(".iosc-place-order-button").first();
                    targetOrigin =  permanentButton.parent();
                    placeOrderButton = jQuery("aside .iosc-place-order-button").first().clone(true);
                    this.restoreButton(placeOrderButton, permanentButton, targetOrigin);
                    return;
                }

                var observed = "#" + observable.method;
                domObserver.get(
                    observed,
                    function (elem) {
                        placeOrderButton = this.getComponentButtonElem(elem);
                        origin = placeOrderButton.parent();
                        permanentButton = jQuery(".iosc-place-order-button").first();
                        targetOrigin =  permanentButton.parent();
                        if (permanentButton.length && placeOrderButton.length && elem.checked) {
                            methodScope = this.getComponentFromButton(placeOrderButton[0]);
                            methodScope.isPlaceOrderActionAllowed(true);
                            this.ajax.addMethod("params", "paymentMethod", methodScope.getData.bind(methodScope));
                            if(!this.waitWithMove()){
                                this.moveAndRestoreButton(placeOrderButton, permanentButton, methodScope, origin, targetOrigin);
                            }
                        }
                        domObserver.off(observed);
                    }.bind(this)
                );
            },

            /**
             * Get reference to uiComponent bound to payment method place order button
             */
            getComponentFromButton: function(buttonElem) {
                return ko.dataFor(buttonElem);
            },

            /**
             * Get reference to payment method place order button
             */
            getComponentButtonElem: function(buttonElem) {
                return jQuery(buttonElem).parents(".payment-method").find("button.action.primary.checkout:not(.disabled)").first();
            },

            /**
             * restore default place order button
             *
             * @param placeOrderButton
             * @param permanentButton
             * @param targetOrigin
             * @returns
             */
            restoreButton: function(placeOrderButton, permanentButton, targetOrigin) {
                if(permanentButton.length > 0){
                    permanentButton.replaceWith(placeOrderButton);
                    return;
                }
                targetOrigin.append(placeOrderButton);
            },

            /**
             * move button to right location and restore on demand
             */
            moveAndRestoreButton: function (placeOrderButton, permanentButton, methodScope, origin, targetOrigin) {

                if (!this.buttonIsMoved) {
                    permanentButton.remove();
                } else {
                    this.parentObj['prevOrign'].append(permanentButton);
                    permanentButton
                    .removeClass("iosc-place-order-button")
                    .removeClass(methodScope.index)
                    .prop("disabled", this.parentObj['prevButtonDisabled']);
                }
                placeOrderButton
                    .addClass("iosc-place-order-button")
                    .addClass(methodScope.index)
                    .prop("disabled", false)
                    .text(jQuery.mage.__("Place Order Now"));
                targetOrigin.append(placeOrderButton);
                this.parentObj['prevOrign'] = origin;
                this.parentObj['prevButtonText'] = placeOrderButton.text();
                this.parentObj['prevButtonDisabled'] = placeOrderButton.prop("disabled");
                this.buttonIsMoved = true;
            },

            /**
             * select default payment method
             */
            selectDefault: function () {
                var defaultIfOne = false;
                if (window.checkoutConfig.paymentMethods.length === 1) {
                    this.cnf.methods = checkoutConfig.paymentMethods[0].method;
                    defaultIfOne = true;
                }

                if (this.cnf.methods !== null) {
                    uiRegistry.async("checkout.steps.billing-step.payment.payments-list." + this.cnf.methods)(
                        function (paymentMethod) {
                            if (defaultIfOne || !quote.paymentMethod() || quote.paymentMethod().method === paymentMethod.index) {
                                paymentMethod.selectPaymentMethod();
                            } else if (quote.paymentMethod() && typeof quote.paymentMethod().method !== "undefined") {
                                uiRegistry.async("checkout.steps.billing-step.payment.payments-list." + quote.paymentMethod().method)(
                                    function (newMethod) {
                                        newMethod.selectPaymentMethod();
                                    }
                                );
                            }
                        }
                    );
                } else {
                    if (quote.paymentMethod() && typeof quote.paymentMethod().method !=='undefined') {
                        this.selectMethod(quote.paymentMethod());
                    }
                }
            },

            /**
             *
             * @param data
             */
            successHandler: function (response) {
                var currentList = methodList();
                if (this._has(response, "data.paymentMethod.payment_methods")) {
                    if (!_.isEqual(methodList(), methodConverter(response.data.paymentMethod.payment_methods))) {
                        paymentService.setPaymentMethods(methodConverter(response.data.paymentMethod.payment_methods));
                    }
                }
                if (this._has(response, "data.paymentMethod.totals")) {
                    quote.setTotals(response.data.paymentMethod.totals);
                }
            },

            _has: function (obj, key) {
                return key.split(".").every(function (x) {
                    if (typeof obj !== "object" || obj === null || !(x in obj)) {
                        return false; }
                    obj = obj[x];
                    return true;
                });
            },

            /**
             *
             * @param data
             */
            errorHandler: function (data) {
            },

            addTitleNumber: function () {
                uiRegistry.async("checkout.steps.billing-step.payment")(
                    function (paymentStep) {
                        domObserver.get("div.payment-methods div.step-title", function (elem) {
                            var number = "3 ";
                            if (quote.isVirtual()) {
                                number = "2 ";
                            }
                            jQuery(elem).prepend(jQuery("<span class='title-number'><span>" + number + "</span></span>").get(0));
                        });
                    }
                );
            }

        });
    }
);
