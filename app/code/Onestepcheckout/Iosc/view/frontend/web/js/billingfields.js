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
define([
    'jquery',
    'underscore',
    'ko',
    'uiRegistry',
    'uiComponent',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/view/billing-address',
    'Magento_Checkout/js/checkout-data',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Onestepcheckout_Iosc/js/shared/fields',
    'Magento_Checkout/js/action/create-billing-address',
    'Magento_Checkout/js/action/select-billing-address',
    'Magento_Checkout/js/action/set-billing-address',
    'Magento_Ui/js/model/messageList',
    'mage/translate',
    "Magento_Ui/js/lib/view/utils/dom-observer",
    "Magento_Checkout/js/action/create-shipping-address",
    "Magento_Checkout/js/action/select-shipping-address",
    "Magento_Ui/js/lib/view/utils/async",
    "mage/utils/wrapper",
    "bluebird"
], function (
    jQuery,
    _,
    ko,
    uiRegistry,
    uiComponent,
    customer,
    quote,
    billingAddress,
    checkoutData,
    additionalValidators,
    fieldsObj,
    createBillingAddress,
    selectBillingAddress,
    setBillingAddressAction,
    globalMessageList,
    $t,
    domObserver,
    createShippingAddress,
    selectShippingAddress,
    async,
    wrapper,
    Promise
) {
    "use strict";

    return uiComponent.extend({

        initialize: function () {
            this._super();
            var self = this;
            /*
             * to be executed in sync as promised in order
             */
            self.setUiComponentVariables().bind(self)
            .then(function(result) {
                return self.getFirstVisiblePaymentmethod();
            })
            .then(function(result) {
                return self.getFormobjects(self.addressObjects(), self.formObjects());
            })
            .then(function(addressForm) {
                return self.setFormobjects(addressForm);
            })
            .then(function(addressForm) {
                return self.wrapFormobjects(addressForm);
            })
            .then(function(result) {
                return self.getLastVisiblefield('div[name="' + self.lastVisibleField + '"]', self.formObjects());
            })
            .then(function(result) {
                return self.getDomtarget('#iosc-billing-container');
            })
            .then(function(result) {
                return self.applyDomChanges();
            })
            .then(function(result) {
                return self.applyCssToFields(self.formObjects().dataScopePrefix, '#iosc-billing-container fieldset > div, #iosc-billing-container fieldset > fieldset');
            })
            .then(function(result) {
                return self.setAjaxPrefilters();
            })
            .then(function(result) {
                return additionalValidators.registerValidator(self.getValidator());
            })
            .finally(function() {
                
            })
            .catch( function(err) {
                
            });
        },

        /**
         * set variables to this.
         */
        setUiComponentVariables: function() {
            var self = this;
            return new Promise(function(resolve, reject) {
                
                self.isUseBillingAddress = ko.observable(quote.isVirtual());
                self.isBillingAddressDetailsVisible = ko.observable(false);
                self.isAddressDetailsVisible = ko.observable(false);
                self.formObjects = ko.observable(false);
                self.quote = quote;
                self.addressObjects = ko.observableArray([
                    "checkout.steps.billing-step.payment.afterMethods.billing-address-form"
                    ]);
                resolve(self);
            });
        },

        /**
         * Get First visible payment method name to reference address fields from that method
         */
        getFirstVisiblePaymentmethod: function() {
            var self = this;
            return new Promise(function(resolve, reject) {
                uiRegistry.async({"parent": "checkout.steps.billing-step.payment.payments-list"})(
                    function (list) {
                        self.addressObjects.push(list.name + '-form');
                        resolve(self);
                    }
                );
            });
        },
        /**
         * get form Objects from uiRegistry
         * @param []
         * @param ko.observable()
         */
        getFormobjects: function(addressObjects, formObjects) {
            var self = this;
            var found = false;
            return new Promise(function(resolve, reject) {
                _.each(addressObjects,function(address) {
                    uiRegistry.async(address)(
                        function (addressForm) {
                            if (!formObjects) {
                                
                                addressForm.isAddressDetailsVisible(false);
                                resolve(addressForm);
                            }
                        }
                    );
                });
            });
        },

        /**
         * set formObjects to context
         */
        setFormobjects: function(addressForm) {
            var self = this;
            return new Promise(function(resolve, reject) {
                self.formObjects(addressForm);
                
                resolve(addressForm);
            });
        },

        /**
         * Wrap formObjects methods to suitable state
         */
        wrapFormobjects: function(addressForm) {
            var self = this;
            return new Promise(function(resolve, reject) {
                self.formObjects().isAddressDetailsVisible =
                    wrapper.wrap(self.formObjects().isAddressDetailsVisible,
                        function (originalMethod) {
                            return  false;
                        }
                    );
                self.formObjects().updateAddresses =
                    wrapper.wrap(self.formObjects().updateAddresses,
                        function (originalMethod) {
                            return true;
                        }
                    );
                
                resolve(addressForm);
            });
        },

        /**
         * get the last dom element of uiComponent template from DOM, to say its rendered
         * @param string
         * @param ko.observable()
         */
        getLastVisiblefield: function(lastVisibleField, formObjects) {
            var self = this;
            return new Promise(function(resolve, reject) {
                async.async(
                    {
                        component: formObjects,
                        selector: lastVisibleField
                    },
                    function(node) {
                        
                        resolve(node);
                    }.bind(this)
                );
            });
        },

        /**
         * get the last dom element of uiComponent template from DOM, to say its rendered
         * @param string
         * @param ko.observable()
         */
        getDomtarget: function(domTarget) {
            var self = this;
            return new Promise(function(resolve, reject) {
                async.async(
                    {
                        selector: domTarget
                    },
                    function(node) {
                        
                        resolve(node);
                    }
                );
            });
        },

        /**
         * Mutate DOM
         */
        applyDomChanges: function() {
            var self = this;
            return new Promise(function(resolve, reject) {

                var billingAreaDom, billingContainer, target, addressFields, addressTarget,
                    cloneTarget, elemToCreate;

                billingAreaDom = jQuery('#iosc-billing-container').get(0);

                if (billingAreaDom) {

                    billingContainer = jQuery('#iosc-billing-container');
                    target = jQuery("#checkout-step-shipping");

                    addressFields = jQuery('.checkout-billing-address > fieldset').get(0);
                    addressTarget =  jQuery("#rendermethis");
                    addressTarget.replaceWith(addressFields);

                    if (quote.isVirtual()) {
                        jQuery('#co-payment-form br').hide();

                        cloneTarget = jQuery('#shipping');
                        elemToCreate = document.createElement(cloneTarget.get(0).tagName);
                        target = jQuery(elemToCreate).attr("id", "iosc-billing");
                        target.append(billingContainer);
                        cloneTarget.before(target);
                        uiRegistry.async("checkout.steps.billing-step.payment.customer-email")(
                            function (customerEmail) {
                                domObserver.get('li#payment form.form-login', function (elem) {
                                    var emailElem, billingAddressForm;
                                    emailElem = jQuery(elem).hide();
                                    billingAddressForm = jQuery('#iosc-billing .billing-address-form');
                                    billingAddressForm.before(emailElem);
                                    emailElem.show();
                                });
                            }.bind(self)
                        );
                    } else {
                        target.append(billingContainer);
                    }

                    
                    resolve(true);
                }

            });
        },

        /**
         * Apply OneStepCheckout specific css classes to DOM
         */
        applyCssToFields: function(dataScopePrefix, selector) {
            var self = this;
            return new Promise(function(resolve, reject) {
                fieldsObj.applyCssClassnames(
                    dataScopePrefix,
                    selector
                );
                
                resolve(true);
            });
        },

        /**
         * Set ajax prefilters on needed methods
         */
        setAjaxPrefilters: function() {
            var self = this;
            return new Promise(function(resolve, reject) {
                uiRegistry.async('checkout.iosc.ajax')(
                    function (ajax) {
                        if (customer.isLoggedIn()) {
                            ajax.addMethod('params', 'address', self.paramsHandler.bind(self));
                        }

                        ajax.addMethod('params', 'billingAddress', self.paramsHandler.bind(self));
                        async.async(
                            {
                                selector: "#iosc-billing-container select[name$='_id'], #iosc-billing-container input[name='postcode']"
                            },
                            function(node) {
                                
                                jQuery(node).change(ajax.update.bind(ajax));
                                resolve(node);
                            }
                        );

                    }.bind(self)
                );

            });
        },

        /**
        * show/hide billing address
        */
        showBillingAddress: function () {

            var billingAddress, addressData, newBillingAddress, formObjects;
            var self = this;
            formObjects = self.formObjects();

            if (self.isUseBillingAddress()) {
                formObjects.isAddressSameAsShipping(false);
                formObjects.isAddressDetailsVisible(false);
                self.isBillingAddressDetailsVisible(true);
            } else {
                formObjects.isAddressSameAsShipping(true);
                formObjects.isAddressDetailsVisible(true);
                self.isBillingAddressDetailsVisible(false);
            }

            formObjects.useShippingAddress();

            uiRegistry.async("checkout.iosc.ajax")(
                function (ajax) {
                    ajax.update();
                }
            );

            return true;
        },

        /**
         *
         */
        paramsHandler: function () {

            var formObjects = this.formObjects();
            if (!formObjects.dataScopePrefix) {
                return false;
            }

            var billingAddress, addressData, newBillingAddress;
            if (quote.shippingAddress() == null) {
                quote.shippingAddress(createShippingAddress({}));
            }

            if (quote.billingAddress() == null) {
                quote.billingAddress(createBillingAddress({}));
            }

            if (!this.isUseBillingAddress() && !quote.isVirtual()) {
                if (customer.isLoggedIn()) {
                    if (quote.shippingAddress() == null) {
                        addressData = createShippingAddress({});
                    } else {
                        addressData = quote.shippingAddress();
                    }
                    newBillingAddress = _.clone(addressData);
                } else {
                    addressData = checkoutData.getShippingAddressFromData();
                    if (addressData == null) {
                        addressData = createShippingAddress({});
                    }
                    newBillingAddress = createBillingAddress(addressData);
                }
            } else {
                if (customer.isLoggedIn()) {
                    if (formObjects.selectedAddress() && typeof formObjects.selectedAddress().getCacheKey === "function" ) {
                        newBillingAddress = formObjects.selectedAddress();
                    } else {
                        addressData = formObjects.source.get(formObjects.dataScopePrefix);
                        newBillingAddress = createBillingAddress(addressData);
                    }
                } else {
                    addressData = formObjects.source.get(formObjects.dataScopePrefix);
                    newBillingAddress = createBillingAddress(addressData);
                }
            }

            if (typeof addressData !== "undefined" && addressData !== null && typeof addressData.extension_attributes !== "undefined") {
                newBillingAddress.extension_attributes = addressData.extension_attributes;
            }
            if (customer.isLoggedIn()) {
                var saveInAddressBook = formObjects.saveInAddressBook();
                if (!this.isUseBillingAddress()) {
                    saveInAddressBook = 0;
                } else {

                    if(typeof newBillingAddress.customerAddressId !== "undefined"){
                        saveInAddressBook = 0;
                    }
                }
                saveInAddressBook = (saveInAddressBook == "1") ? 1 : 0;

                newBillingAddress.save_in_address_book  = saveInAddressBook;
                newBillingAddress.saveInAddressBook = saveInAddressBook;
                formObjects.saveInAddressBook(saveInAddressBook)
            }

            selectBillingAddress(newBillingAddress);

            return quote.billingAddress();
        },

        /**
         *
         */
        getValidator: function () {
            return {
                validate: this.validationHandler.bind(this)
            };
        },

        /**
         *
         */
        validationHandler: function () {

            if (!this.isUseBillingAddress() && !quote.isVirtual()) {
                return true;
            }

            var isValid = false;
            var formObjects = this.formObjects();
            if (formObjects.selectedAddress() && typeof formObjects.selectedAddress().getCacheKey === "function") {
                isValid = true;
            } else {
                formObjects.source.set('params.invalid', false);
                formObjects.source.trigger(formObjects.dataScopePrefix + '.data.validate');

                if (formObjects.source.get(formObjects.dataScopePrefix + '.custom_attributes')) {
                    formObjects.source.trigger(formObjects.dataScopePrefix + '.custom_attributes.data.validate');
                }

                if (formObjects.source.get(formObjects.dataScopePrefix + ".extension_attributes")) {
                    formObjects.source.trigger(formObjects.dataScopePrefix + ".extension_attributes.data.validate");
                }

                if (!formObjects.source.get('params.invalid')) {
                    isValid = true;
                }
            }

            if (!isValid && _.isFunction(formObjects.focusInvalid)) {
                formObjects.focusInvalid();
            }

            return isValid;
        }

    });

});

