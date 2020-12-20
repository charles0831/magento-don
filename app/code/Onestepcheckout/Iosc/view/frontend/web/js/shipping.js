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
        "ko",
        "jquery",
        "Magento_Checkout/js/model/shipping-service",
        "Magento_Checkout/js/model/shipping-rate-processor/new-address",
        "Magento_Checkout/js/model/shipping-rate-processor/customer-address",
        "Magento_Checkout/js/model/shipping-rate-registry",
        "Magento_Checkout/js/model/shipping-save-processor",
        "Magento_Checkout/js/model/quote",
        "Magento_Checkout/js/model/payment/additional-validators",
        "Magento_Checkout/js/view/shipping",
        "Magento_Ui/js/lib/view/utils/dom-observer",
        "Magento_Checkout/js/model/checkout-data-resolver",
        "Magento_Ui/js/lib/knockout/template/renderer",
        "Magento_Ui/js/lib/knockout/template/loader",
        "Magento_Checkout/js/model/shipping-rates-validation-rules",
        "Magento_Checkout/js/action/select-shipping-method",
        "mage/utils/wrapper"
    ],
    function (
        uiComponent,
        uiRegistry,
        _,
        ko,
        jQuery,
        shippingService,
        newAddress,
        customerAddress,
        rateRegistry,
        shippingSaveProcessor,
        quote,
        additionalValidators,
        shippingView,
        domObserver,
        checkoutDataResolver,
        renderer,
        loader,
        defaultShippingRatesValidationRules,
        selectShippingMethodAction,
        wrapper
    ) {
        "use strict";

        /**
         * fix of m2 issue where rate checking is done in template level and not on actual values in quote
         */
        renderer.render = wrapper.wrap(renderer.render , function (originalMethod, tmplPath) {
            var loadPromise = loader.loadTemplate(tmplPath);
            return loadPromise.then(renderer.parseTemplate.bind(renderer,tmplPath));
        });

        /**
         * fix for m2 issue where shipping method is selected only in UI level when only 1 rate is available
         */
        renderer.parseTemplate = wrapper.wrap(renderer.parseTemplate , function (originalMethod,tmplPath, html) {
            if (tmplPath === "Magento_Checkout/shipping") {
               html =  html.replace("checked: $parent.rates().length == 1", "checked: $parent.isSelected");
            }
            if (tmplPath === "Magento_Checkout/shipping-address/shipping-method-item") {
                html =  html.replace("element.rates().length == 1 || element.isSelected", "$parent.isSelected");
            }
            return originalMethod(html);
        });

        return uiComponent.extend({
            initialize : function () {
                this._super();

                this.counter = 0;
                this.address = {};
                this.rateCacheKey = false;
                var isSafari = window && window.navigator && (window.navigator.userAgent.toLowerCase().indexOf("safari") >= 0);
                if (isSafari) {
                    checkoutDataResolver.resolveShippingRates = wrapper.wrap(
                        checkoutDataResolver.resolveShippingRates,
                        function (originalMethod, ratesData) {
                            var result = originalMethod(ratesData);
                            jQuery(".table-checkout-shipping-method input[type=radio]").prop("disabled", false);
                            return result;
                        }
                    );

                    ko.bindingHandlers.value.init = this.koValueInit;
                }
                newAddress.getRates = wrapper.wrap(
                    newAddress.getRates,
                    this.voidFunc.bind(this)
                );

                customerAddress.getRates = wrapper.wrap(
                    customerAddress.getRates,
                    this.voidFunc.bind(this)
                );
                shippingService.setShippingRates(this.getMethods());
                if (!window.checkoutConfig.selectedShippingMethod && _.isObject(quote.shippingMethod())) {
                    quote.shippingMethod(null);
                }

                /**
                 * registering dummy validation rules for vat_id field since this update needs rates reload cause totals can change
                 */
                window.checkoutConfig.activeCarriers.push('onestepcheckout_iosc');
                defaultShippingRatesValidationRules.registerRules('onestepcheckout_iosc', { getRules: this.getRules });
                uiRegistry.async("checkout.steps.shipping-step.shippingAddress")(
                    function (shippingView) {

                        shippingView.visible = wrapper.wrap(shippingView.visible, function(originalMethod) { return ko.observable(!quote.isVirtual())});
                        shippingView.selectShippingMethod = wrapper.wrap(shippingView.selectShippingMethod, function (originalMethod, shippingMethod, e) {
                            e.stopImmediatePropagation();
                            var originalReturn = originalMethod(shippingMethod);
                            shippingSaveProcessor.saveShippingInformation();
                            return originalReturn;
                        });
                        shippingView.getPopUp = wrapper.wrap(
                            shippingView.getPopUp,
                            this.getPopUp.bind(this, shippingView.getPopUp, shippingView)
                        );

                    }.bind(this)
                );
                uiRegistry.async("checkout.steps.shipping-step.shippingAddress.before-shipping-method-form.shipping_policy")(
                    function (shippingPolicy) {
                        this.moveShippingPolicy(shippingPolicy);
                    }.bind(this)
                );
                uiRegistry.promise("checkout.iosc.ajax")
                    .then(
                        function (ajax) {
                            ajax.addMethod("params", "shippingMethod", this.paramsHandler.bind(this));
                            ajax.addMethod("success", "shippingMethod", this.successHandler.bind(this));
                        }.bind(this),
                        function () {
                        }
                    );

                this.addTitleNumber();
                if (!quote.isVirtual()) {
                    additionalValidators.registerValidator(this.getValidator());
                }
            },

            moveShippingPolicy: function (shippingPolicy) {
                domObserver.get("#checkout-step-shipping_method", function (elem) {
                    domObserver.get("div.shipping-policy-block", function (policy) {
                        jQuery(elem).after(jQuery(policy));
                    });
                });
            },

            getValidator: function () {
                return {
                    validate: this.validateShippingMethods.bind(this)
                };
            },

            validateShippingMethods: function () {
                var shippingAddressView = uiRegistry.get("checkout.steps.shipping-step.shippingAddress");
                var isValid = shippingAddressView.validateShippingInformation();
                return isValid;
            },

            /**
             *
             */
            successHandler: function (data) {

                if (!data.data) {
                    return;
                }

                if (typeof data.data.shippingMethod !== "undefined" && data.data.shippingMethod.length > 0) {
                    var equal = false;
                    var methods = data.data.shippingMethod;
                    if (this.rateCacheKey) {
                        equal = _.isEqual(rateRegistry.get(this.rateCacheKey),methods);
                    }

                    if (!equal) {
                        rateRegistry.set(this.rateCacheKey, methods);
                        shippingService.setShippingRates(methods);
                    } else if (equal && this._has(data, "data.shippingMethod")) {
                        shippingService.setShippingRates(methods);
                    }
                    if (this._has(data, "data.selectedShippingMethod") ) {
                        var selectMethod, findMethod;
                        if(data.data.selectedShippingMethod === ""){
                            selectMethod = null;
                        } else {
                            findMethod = _.find(
                                methods,
                                function (obj) {
                                    return obj.carrier_code + '_' + obj.method_code === data.data.selectedShippingMethod;
                                }
                            );

                            if(typeof findMethod !== "undefined"){
                                selectMethod = findMethod;
                            }

                        }

                        selectShippingMethodAction(selectMethod);
                        window.checkoutConfig.selectedShippingMethod = selectMethod;
                    }
                } else {
                    shippingService.setShippingRates([]);
                    selectShippingMethodAction(null);
                    window.checkoutConfig.selectedShippingMethod = [];
                }
            },

            _has: function (obj, key) {
                return key.split(".").every(function (x) {
                    if (typeof obj !== "object" || obj === null || !(x in obj)) {
                        return false;
                    }
                    obj = obj[x];
                    return true;
                });
            },

            /**
             *
             */
            paramsHandler: function () {
                var data;
                var shippingMethod = quote.shippingMethod();

                if (shippingMethod) {
                    data = {
                            shipping_method_code: shippingMethod.method_code,
                            shipping_carrier_code: shippingMethod.carrier_code
                    };
                }

                return data;
            },

            /**
             * Get the default rates from config
             */
            getMethods : function () {
                var rates = [];
                if (typeof this.cnf.availableRates !== "undefined") {
                    rates = this.cnf.availableRates;
                }
                return rates;
            },

            /**
             *
             */
            voidFunc : function () {

                var rates = this.getMethods();
                var address = quote.shippingAddress();

                if (!this.rateCacheKey && rates.length > 0 ) {
                    this.rateCacheKey = address.getCacheKey();
                    this.address = address;
                    rateRegistry.set(this.rateCacheKey, rates);
                } else {
                    uiRegistry.async("checkout.iosc.ajax")(
                        function (ajax) {
                            ajax.update();
                        }
                    );
                }

                this.counter++;
                return rates;
            },

            getPopUp: function (originalMethod, shippingView) {
                shippingView.isFormInline = true;
                return {
                    openModal: function (){},
                    closeModal: function () {
                        shippingView.isFormInline = false;
                    }
                };
            },

            addTitleNumber: function () {
                uiRegistry.async("checkout.steps.shipping-step.shippingAddress")(
                    function (paymentStep) {
                        domObserver.get("div.checkout-shipping-method div.step-title", function (elem) {
                            jQuery(elem).prepend(jQuery("<span class='title-number'><span>2</span></span>").get(0));
                        });

                        domObserver.get("li.checkout-shipping-address div.step-title", function (elem) {
                            jQuery(elem).prepend(jQuery("<span class='title-number'><span>1</span></span>").get(0));
                        });

                        if (quote.isVirtual()) {
                            domObserver.get("li#iosc-billing div.step-title", function (elem) {
                                jQuery(elem).prepend(jQuery("<span class='title-number'><span>1</span></span>").get(0));
                            });
                        }

                    }
                );
            },

            /**
             * dumb rule for vat_id updater
             */
            getRules: function () {
                return {
                    'vat_id': {
                        'required': false
                    }
                };
            },

            /* eslint-disable */
            /**
             * rewrite of init method to add autofill event support for safari
             * @param element
             * @param valueAccessor
             * @param allBindings
             */
            koValueInit: function (element, valueAccessor, allBindings) {
                if (element.tagName.toLowerCase() === "input" && (element.type === "checkbox" || element.type === "radio")) {
                    ko.applyBindingAccessorsToNode(element, { "checkedValue": valueAccessor });
                    return;
                }
                var eventsToCatch = ["change"];
                var requestedEventsToCatch = allBindings.get("valueUpdate");
                var propertyChangedFired = false;
                var elementValueBeforeEvent = null;

                var isSafari = window && window.navigator && (window.navigator.userAgent.toLowerCase().indexOf("safari") >= 0);
                if (isSafari) {
                    var isPossibleTextInput = element.tagName.toLowerCase() === "input" &&
                        ["hidden", "checkbox", "radio", "file", "submit", "button"].indexOf(element.type) < 0;

                    var safariAutoCompleteHackNeeded = isPossibleTextInput && element.autocomplete !== "off" && (!element.form || element.form.autocomplete !== "off");
                    if (safariAutoCompleteHackNeeded) {
                        eventsToCatch.unshift("blur");
                    }
                }

                if (requestedEventsToCatch) {
                    if (typeof requestedEventsToCatch === "string") { // Allow both individual event names, and arrays of event names
                        requestedEventsToCatch = [requestedEventsToCatch]; }
                    ko.utils.arrayPushAll(eventsToCatch, requestedEventsToCatch);
                    eventsToCatch = ko.utils.arrayGetDistinctValues(eventsToCatch);
                }

                var valueUpdateHandler = function () {
                    elementValueBeforeEvent = null;
                    propertyChangedFired = false;
                    var modelValue = valueAccessor();
                    var elementValue = ko.selectExtensions.readValue(element);
                    ko.expressionRewriting.writeValueToProperty(modelValue, allBindings, "value", elementValue);
                };
                var ieAutoCompleteHackNeeded = ko.utils.ieVersion && element.tagName.toLowerCase() === "input" && element.type === "text" &&
                    element.autocomplete !== "off" && (!element.form || element.form.autocomplete !== "off");
                if (ieAutoCompleteHackNeeded && ko.utils.arrayIndexOf(eventsToCatch, "propertychange") === -1) {
                    ko.utils.registerEventHandler(element, "propertychange", function () {
                        propertyChangedFired = true ;});
                    ko.utils.registerEventHandler(element, "focus", function () {
                        propertyChangedFired = false; });
                    ko.utils.registerEventHandler(element, "blur", function () {
                        if (propertyChangedFired) {
                            valueUpdateHandler();
                        }
                    });
                }

                ko.utils.arrayForEach(eventsToCatch, function (eventName) {
                    var handler = valueUpdateHandler;
                    if (ko.utils.stringStartsWith(eventName, "after")) {
                        handler = function () {
                            elementValueBeforeEvent = ko.selectExtensions.readValue(element);
                            setTimeout(valueUpdateHandler, 0);
                        };
                        eventName = eventName.substring("after".length);
                    }
                    ko.utils.registerEventHandler(element, eventName, handler);
                });

                var updateFromModel = function () {
                    var newValue = ko.utils.unwrapObservable(valueAccessor());
                    var elementValue = ko.selectExtensions.readValue(element);

                    if (elementValueBeforeEvent !== null && newValue === elementValueBeforeEvent) {
                        setTimeout(updateFromModel, 0);
                        return;
                    }

                    var valueHasChanged = (newValue !== elementValue);

                    if (valueHasChanged) {
                        if (ko.utils.tagNameLower(element) === "select") {
                            var allowUnset = allBindings.get("valueAllowUnset");
                            var applyValueAction = function () {
                                ko.selectExtensions.writeValue(element, newValue, allowUnset);
                            };
                            applyValueAction();

                            if (!allowUnset && newValue !== ko.selectExtensions.readValue(element)) {
                                ko.dependencyDetection.ignore(ko.utils.triggerEvent, null, [element, "change"]);
                            } else {
                                setTimeout(applyValueAction, 0);
                            }
                        } else {
                            ko.selectExtensions.writeValue(element, newValue);
                        }
                    }
                };

                ko.computed(updateFromModel, null, { disposeWhenNodeIsRemoved: element });
            }
            /* eslint-enable */

        });

    }
);
